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

namespace qbank_questionhub;

/**
 * Bulk action for publishing multiple selected questions to Question Hub.
 *
 * @package    qbank_questionhub
 * @copyright  2026 Cyril Blum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bulk_publish_action extends \core_question\local\bank\bulk_action_base {
    /**
     * Title of the bulk action.
     *
     * @return string
     */
    public function get_bulk_action_title(): string {
        return get_string('publishselected', 'qbank_questionhub');
    }

    /**
     * Unique key of the bulk action.
     *
     * @return string
     */
    public function get_key(): string {
        return 'publishselected';
    }

    /**
     * URL of the publish page for the selected questions.
     *
     * @return \moodle_url
     */
    public function get_bulk_action_url(): \moodle_url {
        $params = [];
        if (!empty($this->qbank)) {
            $params['courseid'] = $this->qbank->get_courseid();
            if (!empty($this->qbank->cm)) {
                $params['cmid'] = $this->qbank->cm->id;
            }
        }
        return new \moodle_url('/question/bank/questionhub/publish.php', $params);
    }

    /**
     * Capabilities needed for the bulk action.
     *
     * @return array|null
     */
    public function get_bulk_action_capabilities(): ?array {
        return ['qbank/questionhub:publish'];
    }
}
