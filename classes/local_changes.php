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

use moodle_exception;
use stdClass;

/**
 * Review of local changes in a category linked to a Hub package: accept or reject each changed question.
 *
 * The baseline is the snapshot of the category's last sync (publish or pull). Rejecting restores the synced
 * version as a new question version (like a revert commit) or removes a question added locally. Accepting
 * keeps a local edit in an imported copy: it no longer counts as a local change, but the Hub version stays
 * the comparison basis of the next pull, so a later Hub change of the question is shown as a conflict.
 * In the package source, local changes are accepted by pushing them.
 *
 * @package    qbank_questionhub
 * @copyright  2026 Cyril Wendl
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_changes {

    /** The link (publish or import record) held by this category itself, or null. */
    public static function link(int $courseid, int $categoryid): ?stdClass {
        $history = publication_tracker::latest($courseid, $categoryid);
        return $history && (int)$history->categoryid === $categoryid ? $history : null;
    }

    /** Whether the category is the source of its package (local changes are published by a push). */
    public static function is_source(stdClass $history): bool {
        return publication_tracker::is_own_link($history);
    }

    /**
     * Changed questions with diffs (synced version -> local version).
     *
     * @return array|null see release_diff::since(); null without a snapshot
     */
    public static function files(stdClass $history, package_exporter $exporter): ?array {
        return release_diff::since((int)$history->categoryid, $history, $exporter, !self::is_source($history));
    }

    /** Synced question version of an entry, 0 if the entry was added locally. */
    private static function synced_questionid(stdClass $history, int $entryid): int {
        return (int)(publication_tracker::snapshot((int)$history->id)[$entryid] ?? 0);
    }

    /** Latest question version of an entry within the linked category tree. */
    private static function current_questionid(stdClass $history, int $entryid): int {
        $current = publication_tracker::current((int)$history->categoryid);
        if (!isset($current[$entryid])) {
            throw new moodle_exception('error_review_entry', 'qbank_questionhub');
        }
        return $current[$entryid];
    }

    /** Points the snapshot of the sync at another question version of an entry. */
    private static function set_synced(stdClass $history, int $entryid, int $questionid): void {
        global $DB;
        $row = $DB->get_record('qbank_questionhub_published', [
            'historyid' => $history->id, 'questionbankentryid' => $entryid,
        ]);
        if ($row) {
            $DB->set_field('qbank_questionhub_published', 'questionid', $questionid, ['id' => $row->id]);
        } else {
            $DB->insert_record('qbank_questionhub_published', (object)[
                'historyid' => $history->id, 'questionbankentryid' => $entryid, 'questionid' => $questionid,
            ]);
        }
    }

    /**
     * Keeps a local change of an imported copy.
     *
     * @throws moodle_exception for the package source (its changes are accepted by pushing them)
     */
    public static function accept(stdClass $history, int $entryid): void {
        global $DB;
        if (self::is_source($history)) {
            throw new moodle_exception('error_review_accept_source', 'qbank_questionhub');
        }
        $questionid = self::current_questionid($history, $entryid);
        question_require_capability_on($questionid, 'edit');
        $syncedid = self::synced_questionid($history, $entryid);
        $transaction = $DB->start_delegated_transaction();
        if ($syncedid) {
            // The next pull compares with the Hub version recorded in the tracking row. Rows written before
            // that field existed, and questions adopted by name without one, fall back to the snapshot,
            // which now holds the local version: record the Hub version explicitly.
            $tracked = $DB->get_records('qbank_questionhub_items', ['questionbankentryid' => $entryid]);
            if ($tracked) {
                foreach ($tracked as $row) {
                    if (!(int)$row->questionid) {
                        $DB->set_field('qbank_questionhub_items', 'questionid', $syncedid, ['id' => $row->id]);
                    }
                }
            } else {
                self::track($history, $entryid, $syncedid);
            }
        }
        self::set_synced($history, $entryid, $questionid);
        $transaction->allow_commit();
    }

    /**
     * Adds a tracking row for a question that was matched by name without one, so that the next pull
     * treats the given version as the Hub version. The source hash is unknown and left empty: the next
     * pull then sees a Hub change and, as the local version differs, reports a conflict.
     */
    private static function track(stdClass $history, int $entryid, int $hubquestionid): void {
        global $DB;
        $question = $DB->get_record('question', ['id' => $hubquestionid], 'id, name, qtype', IGNORE_MISSING);
        $entry = $DB->get_record('question_bank_entries', ['id' => $entryid], 'id, questioncategoryid, idnumber');
        if (!$question || !$entry) {
            return;
        }
        // Category path from the linked category down to the question, as in the package.
        $relative = [];
        $categoryid = (int)$entry->questioncategoryid;
        while ($categoryid !== (int)$history->categoryid) {
            $category = $DB->get_record('question_categories', ['id' => $categoryid], 'id, parent, name', IGNORE_MISSING);
            if (!$category || !(int)$category->parent) {
                return;
            }
            array_unshift($relative, $category->name);
            $categoryid = (int)$category->parent;
        }
        $type = ['multianswer' => 'cloze', 'match' => 'matching'][$question->qtype] ?? $question->qtype;
        $sourcekey = package_importer::source_key($relative, trim((string)($entry->idnumber ?? '')), $type,
            trim($question->name));
        $packagehash = hash('sha256', (string)$history->package_id);
        if ($DB->record_exists('qbank_questionhub_items', [
                'packagehash' => $packagehash, 'targetcategoryid' => $history->categoryid, 'sourcekey' => $sourcekey])) {
            return;
        }
        $DB->insert_record('qbank_questionhub_items', (object)[
            'packagehash' => $packagehash, 'targetcategoryid' => $history->categoryid, 'sourcekey' => $sourcekey,
            'questionbankentryid' => $entryid, 'sourcehash' => '', 'timemodified' => time(),
            'questionid' => $hubquestionid,
        ]);
    }

    /**
     * Discards a local change: restores the synced version as a new version, or deletes a question that was
     * added locally.
     *
     * @return string restored or deleted
     */
    public static function reject(stdClass $history, int $entryid, \context $context, stdClass $course): string {
        global $CFG, $DB;
        require_once($CFG->libdir . '/questionlib.php');
        $questionid = self::current_questionid($history, $entryid);
        question_require_capability_on($questionid, 'edit');
        $syncedid = self::synced_questionid($history, $entryid);

        if (!$syncedid) {
            $versions = $DB->get_fieldset_select('question_versions', 'questionid', 'questionbankentryid = ?', [$entryid]);
            if (questions_in_use($versions)) {
                throw new moodle_exception('error_review_in_use', 'qbank_questionhub');
            }
            foreach ($versions as $versionid) {
                question_delete_question((int)$versionid);
            }
            return 'deleted';
        }
        if (!$DB->record_exists('question', ['id' => $syncedid])) {
            throw new moodle_exception('error_review_entry', 'qbank_questionhub');
        }

        $exporter = new package_exporter((int)$course->id, (int)$context->id);
        $xml = $exporter->export_questions_xml([$syncedid]);
        $categoryid = (int)$DB->get_field('question_bank_entries', 'questioncategoryid', ['id' => $entryid], MUST_EXIST);
        $category = $DB->get_record('question_categories', ['id' => $categoryid], '*', MUST_EXIST);

        $transaction = $DB->start_delegated_transaction();
        $restoredid = package_importer::import_question_xml($xml, $category, $context, $course);
        package_importer::attach_as_version($restoredid, $entryid, $context);
        // The restored version has the synced content: it is the new baseline of the sync, and the version
        // a later pull compares with.
        self::set_synced($history, $entryid, $restoredid);
        $DB->set_field('qbank_questionhub_items', 'questionid', $restoredid,
            ['questionbankentryid' => $entryid, 'questionid' => $syncedid]);
        $transaction->allow_commit();
        return 'restored';
    }
}
