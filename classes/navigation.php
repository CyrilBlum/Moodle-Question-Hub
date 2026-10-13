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
 * Question Bank tertiary navigation node linking to Question Hub.
 *
 * @package    qbank_questionhub
 * @copyright  2026 Cyril Wendl
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class navigation extends \core_question\local\bank\navigation_node_base {

    public function get_navigation_title(): string {
        return get_string('pluginname', 'qbank_questionhub');
    }

    public function get_navigation_key(): string {
        return 'questionhub';
    }

    public function get_navigation_url(): \moodle_url {
        return new \moodle_url('/question/bank/questionhub/view.php');
    }

    public function get_navigation_capabilities(): ?array {
        return ['qbank/questionhub:view'];
    }
}
