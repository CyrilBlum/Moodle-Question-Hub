<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Restores the Question Hub links and the question tracking of a question bank.
 *
 * A link can be read before its category is restored, so the rows are collected and written once the step has created
 * all categories and questions. Links and tracking are restored for categories and questions this restore created:
 * restoring into a bank that already holds them keeps its own records.
 *
 * @package    qbank_questionhub
 * @copyright  2026 Cyril Blum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_qbank_questionhub_plugin extends restore_qbank_plugin {
    /** @var stdClass[] Links by their ID in the backup. */
    private array $links = [];

    /** @var stdClass[] Published question versions. */
    private array $published = [];

    /** @var stdClass[] Tracked questions. */
    private array $tracked = [];

    /**
     * Returns the paths handled at question level.
     *
     * @return restore_path_element[]
     */
    protected function define_question_plugin_structure() {
        return [
            new restore_path_element('qbank_questionhub_link', $this->get_pathfor('/links/link')),
            new restore_path_element(
                'qbank_questionhub_published',
                $this->get_pathfor('/published_questions/published_question')
            ),
            new restore_path_element(
                'qbank_questionhub_tracked',
                $this->get_pathfor('/tracked_questions/tracked_question')
            ),
        ];
    }

    /**
     * Collects a link.
     *
     * @param array $data
     */
    public function process_qbank_questionhub_link($data) {
        $this->links[(int)$data['id']] = (object)$data;
    }

    /**
     * Collects a published question version and its link.
     *
     * @param array $data
     */
    public function process_qbank_questionhub_published($data) {
        $data = (object)$data;
        $this->links[(int)$data->historyid] = $data;
        $this->published[] = (object)[
            'historyid' => (int)$data->historyid,
            'oldquestionid' => (int)$this->get_old_parentid('question'),
        ];
    }

    /**
     * Collects the tracking of a question.
     *
     * @param array $data
     */
    public function process_qbank_questionhub_tracked($data) {
        $data = (object)$data;
        $data->oldquestionid = (int)$this->get_old_parentid('question');
        $this->tracked[] = $data;
    }

    /**
     * Writes the collected rows.
     */
    public function after_execute_question() {
        global $DB;
        $historyids = [];
        foreach ($this->links as $oldid => $data) {
            $categoryid = (int)$this->get_mappingid('question_category_created', $data->categoryid);
            if (!$categoryid) {
                continue;
            }
            $link = clone $data;
            unset($link->id, $link->historyid);
            $link->categoryid = $categoryid;
            $link->courseid = (int)$this->task->get_courseid();
            // Without users in the backup, the restoring user takes over the link.
            $link->userid = (int)$this->get_mappingid('user', $data->userid, $this->task->get_userid());
            $historyids[$oldid] = (int)$DB->insert_record('qbank_questionhub_history', $link);
        }
        foreach ($this->published as $row) {
            $questionid = (int)$this->get_mappingid('question_created', $row->oldquestionid);
            if ($questionid && isset($historyids[$row->historyid])) {
                $DB->insert_record('qbank_questionhub_published', (object)[
                    'historyid' => $historyids[$row->historyid],
                    'questionbankentryid' => $this->get_entryid($questionid),
                    'questionid' => $questionid,
                ]);
            }
        }
        foreach ($this->tracked as $row) {
            $questionid = (int)$this->get_mappingid('question_created', $row->oldquestionid);
            $categoryid = (int)$this->get_mappingid('question_category_created', $row->targetcategoryid);
            if ($questionid && $categoryid) {
                $DB->insert_record('qbank_questionhub_items', (object)[
                    'packagehash' => $row->packagehash,
                    'targetcategoryid' => $categoryid,
                    'sourcekey' => $row->sourcekey,
                    'questionbankentryid' => $this->get_entryid($questionid),
                    'sourcehash' => $row->sourcehash,
                    'timemodified' => $row->timemodified,
                    'questionid' => (int)$row->questionid === $row->oldquestionid ? $questionid : 0,
                ]);
            }
        }
    }

    /**
     * Question bank entry of a restored question.
     *
     * @param int $questionid
     * @return int
     */
    private function get_entryid(int $questionid): int {
        global $DB;
        return (int)$DB->get_field('question_versions', 'questionbankentryid', ['questionid' => $questionid], MUST_EXIST);
    }
}
