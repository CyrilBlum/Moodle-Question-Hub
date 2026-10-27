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

use core_question\local\bank\plugin_features_base;
use core_question\local\bank\navigation_node_base;
use core_question\local\bank\view;
use core\context;
use moodle_url;

/**
 * Question Bank subplugin feature entrypoint for Question Hub.
 *
 * Implements dual-version compatible signatures across Moodle 4.5 LTS (PHP 8.1)
 * and Moodle 5.0 Stable (PHP 8.2).
 *
 * @package    qbank_questionhub
 * @copyright  2026 Cyril Blum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class plugin_feature extends plugin_features_base {
    /**
     * Navigation node for Question Bank tertiary action menu.
     *
     * @return navigation_node_base|null
     */
    public function get_navigation_node(): ?navigation_node_base {
        return new navigation();
    }

    /**
     * Question bank columns provided by Question Hub.
     *
     * @param view $qbank
     * @return array|null
     */
    public function get_question_columns(view $qbank): ?array {
        $context = $qbank->get_most_specific_context();
        if (
            !has_capability('qbank/questionhub:view', $context) &&
                !has_capability('moodle/question:viewall', $context) &&
                !is_siteadmin()
        ) {
            return [];
        }
        return [
            new question_sync_column($qbank),
        ];
    }

    /**
     * Question row action menu items.
     *
     * @param view $qbank
     * @return array
     */
    public function get_question_actions($qbank): array {
        return [
            new question_action($qbank),
        ];
    }

    /**
     * Additional controls to display above the question bank table.
     *
     * @param view $qbank
     * @param context $context
     * @param int $categoryid
     * @return array
     */
    public function get_question_bank_controls(view $qbank, context $context, int $categoryid): array {
        if (!has_capability('qbank/questionhub:view', $context)) {
            return [];
        }

        $courseid = $qbank->get_courseid();
        $params = ['courseid' => $courseid, 'cat' => $categoryid];
        if (!empty($qbank->cm)) {
            $params['cmid'] = $qbank->cm->id;
        }

        $browseurl = new moodle_url('/question/bank/questionhub/view.php', $params);
        $exportparams = ['courseid' => $courseid, 'categoryid' => $categoryid];
        if (!empty($qbank->cm)) {
            $exportparams['cmid'] = $qbank->cm->id;
        }
        $exportcaturl = new moodle_url('/question/bank/questionhub/publish.php', $exportparams);

        return [
            200 => new output\bank_action_button($browseurl, get_string('browsehub', 'qbank_questionhub'), 'btn-outline-primary'),
            210 => new output\bank_action_button(
                $exportcaturl,
                get_string('exportcategorytohub', 'qbank_questionhub'),
                'btn-outline-secondary'
            ),
        ];
    }

    /**
     * Bulk actions for questions in the question bank.
     *
     * Reconciled method signature to guarantee 100% compatibility across
     * PHP 8.1 (Moodle 4.5 LTS where parameter is ?view $qbank = null and untyped return)
     * and PHP 8.2 (Moodle 5.0 where parameter is view $qbank and return is : array).
     *
     * @param mixed $qbank Question bank view object or null
     * @return array
     */
    public function get_bulk_actions($qbank = null): array {
        if ($qbank === null) {
            return [];
        }
        return [
            new bulk_publish_action($qbank),
        ];
    }
}
