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

use core_question\local\bank\column_base;
use core_question\local\bank\view;

/**
 * Question bank column indicating whether a question is synchronized with the Question Hub release.
 *
 * @package    qbank_questionhub
 * @copyright  2026 Cyril Blum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_sync_column extends column_base {
    /** @var array Cached map of historyid => snapshot array. */
    protected array $snapshots = [];

    /** @var array Cached map of categoryid => history record (or false if none). */
    protected array $cathistory = [];

    /**
     * Internal column name.
     *
     * @return string
     */
    public function get_name(): string {
        return 'syncstatus';
    }

    /**
     * Column header title.
     *
     * @return string
     */
    public function get_title(): string {
        return get_string('sync_column_title', 'qbank_questionhub');
    }

    /**
     * Database fields needed for synchronization state evaluation.
     *
     * @return array
     */
    public function get_required_fields(): array {
        return [
            'qbe.id as questionbankentryid',
            'qbe.questioncategoryid',
            'qc.id as categoryid',
        ];
    }

    /**
     * Resolves question category ID across Moodle versions.
     *
     * @param object $question
     * @return int
     */
    protected function resolve_category_id(object $question): int {
        return (int)($question->categoryid ?? $question->questioncategoryid ?? $question->questioncategory
            ?? $question->category ?? 0);
    }

    /**
     * Extra CSS classes for the column cell.
     *
     * @return array
     */
    public function get_extra_classes(): array {
        return ['text-center', 'pe-2'];
    }

    /**
     * Bulk-preloads publication snapshots for all categories represented in the question table.
     *
     * @param array $questions List of question records.
     */
    public function load_additional_data(array $questions): void {
        parent::load_additional_data($questions);

        $courseid = (int)$this->qbank->get_courseid();

        $catids = [];
        foreach ($questions as $q) {
            $catid = $this->resolve_category_id($q);
            if ($catid > 0) {
                $catids[$catid] = true;
            }
        }

        foreach (array_keys($catids) as $catid) {
            if (!array_key_exists($catid, $this->cathistory)) {
                $history = publication_tracker::find_publication_for_category($catid, $courseid);
                $this->cathistory[$catid] = $history ?: false;
                if ($history && !array_key_exists((int)$history->id, $this->snapshots)) {
                    $this->snapshots[(int)$history->id] = publication_tracker::snapshot((int)$history->id);
                }
            }
        }
    }

    /**
     * Renders badge indicating if question is new, changed, or synced with server.
     *
     * @param object $question
     * @param string $rowclasses
     */
    protected function display_content($question, $rowclasses): void {
        $catid = $this->resolve_category_id($question);
        $history = $this->cathistory[$catid] ?? false;

        if (!$history) {
            return;
        }

        global $OUTPUT;
        $snapshot = $this->snapshots[(int)$history->id] ?? null;
        $entryid = (int)($question->questionbankentryid ?? 0);
        $classes = 'badge-warning bg-warning text-dark';
        if ($snapshot === null) {
            $state = 'unknown';
            $classes = 'badge-light bg-light text-muted border';
        } else if (!isset($snapshot[$entryid])) {
            // New question added since last release: not yet pushed.
            $state = 'new';
        } else if ($snapshot[$entryid] !== (int)$question->id) {
            // Question modified since last release: not yet pushed.
            $state = 'changed';
        } else {
            // Up to date with release on server.
            $state = 'synced';
            $classes = 'badge-success bg-success text-white';
        }
        echo $OUTPUT->render_from_template('qbank_questionhub/sync_badge', [
            'label' => get_string('badge_' . $state, 'qbank_questionhub'),
            'help' => get_string('badge_' . $state . '_help', 'qbank_questionhub'),
            'classes' => $classes,
        ]);
    }
}
