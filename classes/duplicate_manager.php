<?php
namespace qbank_questionhub;

/** Finds unused, single-version duplicate question-bank entries. */
class duplicate_manager {
    /** Returns the selected category and all of its descendants. */
    private static function scoped_category_ids(\context $context, int $categoryid): array {
        if ($categoryid === 0) {
            return [];
        }
        global $DB;
        $categories = $DB->get_records('question_categories', ['contextid' => $context->id], '', 'id, parent');
        if (!isset($categories[$categoryid])) {
            throw new \moodle_exception('invalidcategoryid', 'error');
        }
        $children = [];
        foreach ($categories as $category) {
            $children[(int)$category->parent][] = (int)$category->id;
        }
        $ids = [];
        $pending = [$categoryid];
        while ($pending) {
            $id = array_pop($pending);
            $ids[$id] = true;
            foreach ($children[$id] ?? [] as $childid) {
                $pending[] = $childid;
            }
        }
        return $ids;
    }

    public static function find_removable(\context $context, int $categoryid = 0): array {
        global $CFG, $DB;
        require_once($CFG->libdir . '/questionlib.php');
        $scopeids = self::scoped_category_ids($context, $categoryid);
        $sql = "SELECT q.id questionid, q.qtype, q.name, q.questiontext, q.generalfeedback,
                       q.defaultmark, q.penalty, q.timecreated, q.timemodified, qv.version questionversion,
                       qbe.id entryid, qc.id categoryid, qc.name categoryname,
                       h.package_id packageid, h.package_version packageversion, h.package_title packagetitle,
                       h.action packageaction, h.timecreated packagetime
                  FROM {question} q
                  JOIN {question_versions} qv ON qv.questionid = q.id
                  JOIN (SELECT questionbankentryid, MAX(version) version FROM {question_versions} GROUP BY questionbankentryid) latest
                    ON latest.questionbankentryid = qv.questionbankentryid AND latest.version = qv.version
                  JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
                  JOIN {question_categories} qc ON qc.id = qbe.questioncategoryid
             LEFT JOIN (SELECT categoryid, MAX(id) id FROM {qbank_questionhub_history}
                         WHERE package_id IS NOT NULL AND package_id <> '' GROUP BY categoryid) lasthistory
                    ON lasthistory.categoryid = qc.id
             LEFT JOIN {qbank_questionhub_history} h ON h.id = lasthistory.id
                 WHERE qc.contextid = :contextid AND q.parent = 0";
        $rows = array_values($DB->get_records_sql($sql, ['contextid' => $context->id]));
        $groups = [];
        foreach ($rows as $row) {
            $answers = [];
            foreach ($DB->get_records('question_answers', ['question' => $row->questionid], 'id ASC',
                    'answer, fraction, feedback, feedbackformat') as $answer) {
                $answers[] = [(string)$answer->answer, (string)$answer->fraction,
                    (string)$answer->feedback, (int)$answer->feedbackformat];
            }
            // Moodle serialises Cloze question text differently on import. Its name and
            // answer set provide a stable fingerprint for reviewable duplicate candidates.
            $questiontext = $row->qtype === 'multianswer' ? '' : $row->questiontext;
            $key = hash('sha256', json_encode([$row->qtype, $row->name, $questiontext,
                $row->generalfeedback, $row->defaultmark, $row->penalty, $answers], JSON_UNESCAPED_UNICODE));
            $groups[$key][] = $row;
        }
        $result = [];
        foreach ($groups as $matches) {
            if (count($matches) < 2) { continue; }
            usort($matches, static fn($a, $b) => [(int)$a->timecreated, (int)$a->entryid] <=> [(int)$b->timecreated, (int)$b->entryid]);
            $keep = array_shift($matches);
            $remove = [];
            foreach ($matches as $row) {
                if ($scopeids && empty($scopeids[(int)$row->categoryid])) {
                    continue;
                }
                if ($DB->count_records('question_versions', ['questionbankentryid' => $row->entryid]) === 1 &&
                        !questions_in_use([(int)$row->questionid])) {
                    $remove[] = $row;
                }
            }
            if ($remove) { $result[] = ['keep' => $keep, 'remove' => $remove]; }
        }
        return $result;
    }

    public static function delete_removable(\context $context, int $categoryid = 0): int {
        global $CFG, $DB;
        require_once($CFG->libdir . '/questionlib.php');
        $count = 0;
        foreach (self::find_removable($context, $categoryid) as $group) {
            foreach ($group['remove'] as $row) {
                question_require_capability_on((int)$row->questionid, 'edit');
                $DB->delete_records('qbank_questionhub_items', ['questionbankentryid' => (int)$row->entryid]);
                question_delete_question((int)$row->questionid);
                $count++;
            }
        }
        return $count;
    }
}
