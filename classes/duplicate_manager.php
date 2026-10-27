<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace qbank_questionhub;

/**
 * Finds unused, single-version duplicate question-bank entries.
 *
 * @package    qbank_questionhub
 * @copyright  2026 Cyril Blum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class duplicate_manager {
    /**
     * Returns the selected category and all of its descendants.
     */
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

    /**
     * Groups of identical questions in a context, with the copies that can be removed.
     *
     * @param \context $context
     * @param int $categoryid Limit to this category and its descendants (0 = whole context)
     * @return array
     */
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
                  JOIN (SELECT questionbankentryid, MAX(version) version
                          FROM {question_versions} GROUP BY questionbankentryid) latest
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
            foreach (
                $DB->get_records(
                    'question_answers',
                    ['question' => $row->questionid],
                    'id ASC',
                    'answer, fraction, feedback, feedbackformat'
                ) as $answer
            ) {
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
            if (count($matches) < 2) {
                continue;
            }
            usort(
                $matches,
                static fn($a, $b) => [(int)$a->timecreated, (int)$a->entryid] <=> [(int)$b->timecreated, (int)$b->entryid]
            );
            $keep = array_shift($matches);
            $remove = [];
            foreach ($matches as $row) {
                if ($scopeids && empty($scopeids[(int)$row->categoryid])) {
                    continue;
                }
                if (
                    $DB->count_records('question_versions', ['questionbankentryid' => $row->entryid]) === 1 &&
                        !questions_in_use([(int)$row->questionid])
                ) {
                    $remove[] = $row;
                }
            }
            if ($remove) {
                $result[] = ['keep' => $keep, 'remove' => $remove];
            }
        }
        return $result;
    }

    /**
     * Groups of questions in the same category with the same name and type whose content differs, e.g. a
     * local question and the Hub version imported next to it. Unlike exact duplicates, the teacher picks
     * which one to keep.
     *
     * @return array[] groups: categoryid, categoryname, name, qtype, entries (entryid, questionid of the latest
     *         version, version, versions, timecreated, timemodified, tracked by a Hub package, in use)
     */
    public static function find_variants(\context $context, int $categoryid = 0): array {
        global $CFG, $DB;
        require_once($CFG->libdir . '/questionlib.php');
        $scopeids = self::scoped_category_ids($context, $categoryid);
        $sql = "SELECT qbe.id entryid, q.id questionid, q.name, q.qtype, q.questiontext, q.timecreated, q.timemodified,
                       qv.version, qc.id categoryid, qc.name categoryname
                  FROM {question_bank_entries} qbe
                  JOIN {question_categories} qc ON qc.id = qbe.questioncategoryid
                  JOIN {question_versions} qv ON qv.questionbankentryid = qbe.id
                  JOIN {question} q ON q.id = qv.questionid
                 WHERE qc.contextid = :contextid AND q.parent = 0
                   AND qv.version = (SELECT MAX(v.version) FROM {question_versions} v
                                      WHERE v.questionbankentryid = qbe.id)";
        $groups = [];
        foreach ($DB->get_records_sql($sql, ['contextid' => $context->id]) as $row) {
            if ($scopeids && empty($scopeids[(int)$row->categoryid])) {
                continue;
            }
            $groups[$row->categoryid . '|' . $row->qtype . '|' . $row->name][] = $row;
        }
        $result = [];
        foreach ($groups as $rows) {
            if (count($rows) < 2 || count(array_unique(array_column($rows, 'questiontext'))) < 2) {
                // A single question, or exact copies (those are handled by find_removable()).
                continue;
            }
            usort(
                $rows,
                static fn($a, $b) => [(int)$a->timecreated, (int)$a->entryid] <=> [(int)$b->timecreated, (int)$b->entryid]
            );
            $entries = [];
            foreach ($rows as $row) {
                $versions = $DB->get_fieldset_select('question_versions', 'questionid', 'questionbankentryid = ?', [$row->entryid]);
                $tracking = $DB->get_record_sql(
                    "SELECT h.package_title, h.package_version
                       FROM {qbank_questionhub_items} i
                       JOIN {qbank_questionhub_history} h ON h.categoryid = i.targetcategoryid AND h.action = 'import'
                      WHERE i.questionbankentryid = ?
                   ORDER BY h.timecreated DESC, h.id DESC",
                    [$row->entryid],
                    IGNORE_MULTIPLE
                );
                $entries[] = [
                    'entryid' => (int)$row->entryid,
                    'questionid' => (int)$row->questionid,
                    'version' => (int)$row->version,
                    'versions' => count($versions),
                    'timecreated' => (int)$row->timecreated,
                    'timemodified' => (int)$row->timemodified,
                    'tracked' => $tracking ? $tracking->package_title . ' v' . $tracking->package_version : '',
                    'inuse' => questions_in_use(array_map('intval', $versions)),
                ];
            }
            $first = reset($rows);
            $result[] = [
                'categoryid' => (int)$first->categoryid, 'categoryname' => $first->categoryname,
                'name' => $first->name, 'qtype' => $first->qtype, 'entries' => $entries,
            ];
        }
        usort($result, static fn($a, $b) => [$a['categoryname'], $a['name']] <=> [$b['categoryname'], $b['name']]);
        return $result;
    }

    /**
     * The Hub question a used local question of a group can take over: the only question of the group that
     * came from a Hub package, provided no quiz uses it.
     */
    public static function merge_source(array $group): ?array {
        $tracked = array_values(array_filter($group['entries'], static fn($entry) => $entry['tracked'] !== ''));
        return count($tracked) === 1 && !$tracked[0]['inuse'] ? $tracked[0] : null;
    }

    /**
     * Applies the choice of each variant group: keep one question and delete the others (all versions), or
     * ("m" + entry ID) let a used local question take over the Hub question. Groups without a valid choice are
     * left alone; questions used in a quiz are never deleted.
     *
     * Taking over: the content of the Hub question becomes a new version of the local question (quizzes keep
     * using it, earlier attempts keep their version), its Hub tracking and sync state move to the local
     * question, and the separate Hub question is deleted.
     *
     * @param string[] $choices per group: entry ID to keep, or "m" + entry ID to take over the Hub question
     * @return array merged, deleted and inuse (questions kept because a quiz uses them)
     */
    public static function apply_variants(\context $context, \stdClass $course, array $choices): array {
        global $CFG, $DB;
        require_once($CFG->libdir . '/questionlib.php');
        $keep = [];
        $merge = [];
        foreach ($choices as $choice) {
            if (preg_match('/^(m?)(\d+)$/', (string)$choice, $match) && (int)$match[2] > 0) {
                $keep[(int)$match[2]] = true;
                if ($match[1] === 'm') {
                    $merge[(int)$match[2]] = true;
                }
            }
        }
        $result = ['merged' => 0, 'deleted' => 0, 'inuse' => 0];
        foreach (self::find_variants($context) as $group) {
            $chosen = array_values(array_filter($group['entries'], static fn($entry) => isset($keep[$entry['entryid']])));
            if (count($chosen) !== 1) {
                continue;
            }
            $target = $chosen[0];
            $source = null;
            if (isset($merge[$target['entryid']])) {
                $source = self::merge_source($group);
                if (!$source || $source['entryid'] === $target['entryid']) {
                    continue;
                }
                self::take_over($context, $course, $source, $target);
                $result['merged']++;
            }
            foreach ($group['entries'] as $entry) {
                if ($entry['entryid'] === $target['entryid'] || ($source && $entry['entryid'] === $source['entryid'])) {
                    continue;
                }
                if ($entry['inuse']) {
                    $result['inuse']++;
                    continue;
                }
                self::delete_entry($entry);
                $result['deleted']++;
            }
        }
        return $result;
    }

    /**
     * The Hub question's content becomes a new version of the target; tracking and sync state move along.
     */
    private static function take_over(\context $context, \stdClass $course, array $source, array $target): void {
        global $DB;
        question_require_capability_on($target['questionid'], 'edit');
        question_require_capability_on($source['questionid'], 'edit');
        $exporter = new package_exporter((int)$course->id, (int)$context->id);
        $xml = $exporter->export_questions_xml([$source['questionid']]);
        $categoryid = (int)$DB->get_field(
            'question_bank_entries',
            'questioncategoryid',
            ['id' => $target['entryid']],
            MUST_EXIST
        );
        $category = $DB->get_record('question_categories', ['id' => $categoryid], '*', MUST_EXIST);

        $transaction = $DB->start_delegated_transaction();
        $newid = package_importer::import_question_xml($xml, $category, $context, $course);
        package_importer::attach_as_version($newid, $target['entryid'], $context);
        // The next pull compares with this version: it holds the Hub content.
        $DB->execute('UPDATE {qbank_questionhub_items} SET questionbankentryid = ?, questionid = ?
                       WHERE questionbankentryid = ?', [$target['entryid'], $newid, $source['entryid']]);
        foreach ($DB->get_records('qbank_questionhub_published', ['questionbankentryid' => $source['entryid']]) as $row) {
            $existing = $DB->get_record(
                'qbank_questionhub_published',
                ['historyid' => $row->historyid, 'questionbankentryid' => $target['entryid']]
            );
            if ($existing) {
                $DB->set_field('qbank_questionhub_published', 'questionid', $newid, ['id' => $existing->id]);
                $DB->delete_records('qbank_questionhub_published', ['id' => $row->id]);
            } else {
                $DB->update_record('qbank_questionhub_published', (object)[
                    'id' => $row->id, 'questionbankentryid' => $target['entryid'], 'questionid' => $newid,
                ]);
            }
        }
        self::delete_entry($source);
        $transaction->allow_commit();
    }

    /**
     * Deletes a question bank entry with all versions and its Hub tracking.
     */
    private static function delete_entry(array $entry): void {
        global $DB;
        question_require_capability_on($entry['questionid'], 'edit');
        $DB->delete_records('qbank_questionhub_items', ['questionbankentryid' => $entry['entryid']]);
        foreach (
            $DB->get_fieldset_select(
                'question_versions',
                'questionid',
                'questionbankentryid = ?',
                [$entry['entryid']]
            ) as $questionid
        ) {
            question_delete_question((int)$questionid);
        }
    }

    /**
     * Delete the removable copies found by find_removable().
     *
     * @param \context $context
     * @param int $categoryid Limit to this category and its descendants (0 = whole context)
     * @return int Number of deleted questions
     */
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
