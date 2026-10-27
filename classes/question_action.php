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

use core_question\local\bank\question_action_base;

/**
 * Question bank action menu item for exporting/publishing a question to Question Hub.
 *
 * @package    qbank_questionhub
 * @copyright  2026 Cyril Wendl
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_action extends question_action_base {
    /** @var string Cached action label. */
    protected string $strpublishtohub;

    public function init(): void {
        parent::init();
        $this->strpublishtohub = get_string('publishtohub', 'qbank_questionhub');
    }

    public function get_menu_position(): int {
        return 610; // Placed right next to core exporttoxml at position 600.
    }

    protected function get_url_icon_and_label(\stdClass $question): array {
        if (!\question_bank::is_qtype_installed($question->qtype)) {
            return [null, null, null];
        }

        if (!question_has_capability_on($question, 'view')) {
            return [null, null, null];
        }

        $params = [
            'questionid' => $question->id,
            'courseid' => $this->qbank->get_courseid(),
        ];

        if (!empty($this->qbank->cm)) {
            $params['cmid'] = $this->qbank->cm->id;
        }

        $url = new \moodle_url('/question/bank/questionhub/publish.php', $params);

        return [$url, 't/upload', $this->strpublishtohub];
    }
}
