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
 * Deletes a question category together with its subcategories and questions.
 *
 * Moodle core only deletes empty categories. This removes the whole tree, but refuses as long as
 * a question is in use (quiz slots, attempts) or a random question draws from one of the categories.
 *
 * @package    qbank_questionhub
 * @copyright  2026 Cyril Blum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class category_remover {
    /**
     * Collects what a deletion would remove and whether it is blocked.
     *
     * @param \context $context the question bank context the category must belong to
     * @param int $categoryid the category to delete
     * @return array category, categoryids (parents before children), questionids, entryids, in_use, random_references
     */
    public static function preview(\context $context, int $categoryid): array {
        global $CFG, $DB;
        require_once($CFG->libdir . '/questionlib.php');
        $category = $DB->get_record(
            'question_categories',
            ['id' => $categoryid, 'contextid' => $context->id],
            '*',
            MUST_EXIST
        );
        // Refuses the top category and the last category of the context, like core does.
        (new \core_question\category_manager())->require_can_delete_category($categoryid);

        $categoryids = publication_tracker::category_ids($categoryid);
        [$insql, $params] = $DB->get_in_or_equal($categoryids, SQL_PARAMS_NAMED);
        $questions = $DB->get_records_sql(
            "SELECT q.id, qv.questionbankentryid
               FROM {question} q
               JOIN {question_versions} qv ON qv.questionid = q.id
               JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
              WHERE qbe.questioncategoryid $insql AND q.parent = 0",
            $params
        );
        $questionids = array_map('intval', array_keys($questions));
        $entryids = array_values(array_unique(array_map(static fn($q) => (int)$q->questionbankentryid, $questions)));

        return [
            'category' => $category,
            'categoryids' => $categoryids,
            'questionids' => $questionids,
            'entryids' => $entryids,
            'in_use' => $questionids && questions_in_use($questionids),
            'random_references' => self::count_random_references($context, $categoryids),
        ];
    }

    /**
     * Deletes the category tree with all questions in one transaction.
     *
     * @param \context $context the question bank context the category must belong to
     * @param int $categoryid the category to delete
     * @return \stdClass name, categories and questions (counts) for the confirmation message
     */
    public static function delete(\context $context, int $categoryid): \stdClass {
        global $DB;
        $preview = self::preview($context, $categoryid);
        $name = format_string($preview['category']->name, true, ['context' => $context, 'escape' => false]);
        if ($preview['in_use'] || $preview['random_references'] > 0) {
            throw new \moodle_exception('category_delete_in_use', 'qbank_questionhub', '', $name);
        }

        $transaction = $DB->start_delegated_transaction();
        foreach ($preview['questionids'] as $questionid) {
            question_delete_question($questionid);
        }
        [$insql, $params] = $DB->get_in_or_equal($preview['categoryids']);
        // Function question_delete_question() skips questions it cannot remove; never orphan them.
        if ($DB->record_exists_select('question_bank_entries', "questioncategoryid $insql", $params)) {
            throw new \moodle_exception('category_delete_failed', 'qbank_questionhub', '', $name);
        }
        $DB->delete_records_select('qbank_questionhub_items', "targetcategoryid $insql", $params);
        if ($preview['entryids']) {
            [$entrysql, $entryparams] = $DB->get_in_or_equal($preview['entryids']);
            $DB->delete_records_select('qbank_questionhub_items', "questionbankentryid $entrysql", $entryparams);
        }
        // Children first, so core never moves a child up to a parent that is deleted next.
        $manager = new \core_question\category_manager();
        foreach (array_reverse($preview['categoryids']) as $id) {
            $manager->delete_category($id);
        }
        $transaction->allow_commit();

        return (object)[
            'name' => $name,
            'categories' => count($preview['categoryids']),
            'questions' => count($preview['entryids']),
        ];
    }

    /**
     * Counts random question slots that draw from one of the categories.
     *
     * @param \context $context the question bank context
     * @param int[] $categoryids categories to check
     * @return int
     */
    private static function count_random_references(\context $context, array $categoryids): int {
        global $DB;
        $count = 0;
        $references = $DB->get_records(
            'question_set_references',
            ['questionscontextid' => $context->id],
            '',
            'id, filtercondition'
        );
        foreach ($references as $reference) {
            $filter = json_decode((string)$reference->filtercondition, true) ?: [];
            $ids = $filter['filter']['category']['values'] ?? [];
            if (isset($filter['questioncategoryid'])) {
                $ids[] = $filter['questioncategoryid'];
            }
            if (array_intersect(array_map('intval', (array)$ids), $categoryids)) {
                $count++;
            }
        }
        return $count;
    }
}
