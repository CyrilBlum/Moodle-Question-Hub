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

use stdClass;
use context;
use moodle_exception;

/**
 * Question package importer ingesting Moodle XML packages into course question banks.
 *
 * Integrates pre-flight diagnostics, stage management in temp storage, Base64 embedded
 * media extraction, and transactional commit via core qformat_xml.
 *
 * @package    qbank_questionhub
 * @copyright  2026 Cyril Wendl
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class package_importer {

    /** @var stdClass Target course record. */
    protected stdClass $course;

    /** @var context Active course question context. */
    protected context $context;

    /** @var stdClass Destination question category. */
    protected stdClass $targetcategory;

    /**
     * Constructor.
     *
     * @param int $courseid Target course ID.
     * @param int|null $targetcategoryid Destination question category ID or null for top category.
     */
    public function __construct(int $courseid, ?int $targetcategoryid = null) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/question/editlib.php');

        $this->course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
        $this->context = package_exporter::resolve_course_question_context($this->course);

        if ($targetcategoryid) {
            $this->targetcategory = $DB->get_record(
                'question_categories',
                ['id' => $targetcategoryid],
                '*',
                MUST_EXIST
            );
        } else {
            $defaultcat = question_get_default_category($this->context->id, true);
            $this->targetcategory = $defaultcat ?: question_get_top_category($this->context->id, true);
        }
    }

    /**
     * Executes in-memory preflight validation without modifying the database.
     *
     * @param string $xmlcontent Raw XML package payload.
     * @return diagnostic_report
     */
    public function run_diagnostics(string $xmlcontent): diagnostic_report {
        return preflight_validator::validate($xmlcontent);
    }

    /**
     * Imports validated Moodle XML package into course question bank.
     *
     * @param string $xmlcontent Raw XML package string.
     * @param bool $preservehierarchy Preserve nested category structure from XML package.
     * @param array $packagemetadata Optional metadata for history logging (id, version, title).
     * @return import_result
     * @throws moodle_exception On validation or import failure.
     */
    public function import_package(string $xmlcontent, bool $preservehierarchy = true, array $packagemetadata = []): import_result {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot . '/question/editlib.php');
        require_once($CFG->dirroot . '/question/format/xml/format.php');

        // 1. Mandatory Pre-Flight Validation Gate.
        $report = $this->run_diagnostics($xmlcontent);
        if (!$report->is_valid()) {
            $errorlist = [];
            foreach ($report->get_errors() as $err) {
                $errorlist[] = "[{$err['code']}] {$err['message']}";
            }
            throw new moodle_exception(
                'error_import_failed',
                'qbank_questionhub',
                '',
                implode("\n", $errorlist)
            );
        }

        // 2. Prepare XML content (rewrite categories if forcing destination category).
        $processedxml = $xmlcontent;
        if (!$preservehierarchy && $this->targetcategory) {
            $catpath = '$course$/top/' . $this->targetcategory->name;
            $processedxml = preg_replace(
                '/<category>\s*<text>[^<]*<\/text>\s*<\/category>/i',
                '<category><text>' . htmlspecialchars($catpath, ENT_XML1) . '</text></category>',
                $processedxml
            );
        }

        // 3. Stage XML into safe temporary file on dataroot.
        $tempdir = make_temp_directory('questionhub');
        $tempfile = tempnam($tempdir, 'qh_imp_');
        file_put_contents($tempfile, $processedxml);

        // 4. Fallback for CLI scripts if unauthenticated
        if (empty($USER->id) && defined('CLI_SCRIPT') && CLI_SCRIPT) {
            \core\session\manager::set_user(get_admin());
        }

        // 5. Configure and execute qformat_xml within an isolated transaction.
        $qformat = new \qformat_xml();
        $contexts = new \core_question\local\bank\question_edit_contexts($this->context);
        $qformat->setCourse($this->course);
        $qformat->setContexts($contexts->all());
        $qformat->setCategory($this->targetcategory);
        $qformat->setCatfromfile($preservehierarchy);
        $qformat->setContextfromfile(true);
        $qformat->setMatchgrades('nearest');
        $qformat->setStoponerror(true);
        $qformat->setFilename($tempfile);
        $qformat->setRealfilename('package.xml');
        $qformat->displayprogress = false;

        $transaction = $DB->start_delegated_transaction();
        ob_start();
        $importok = false;
        try {
            if ($qformat->importpreprocess()) {
                $importok = $qformat->importprocess();
                if ($importok) {
                    $qformat->importpostprocess();
                }
            }
            if (!$importok) {
                $output = ob_get_clean();
                throw new moodle_exception('error_import_failed', 'qbank_questionhub', '', $output ?: '');
            }

            // 6. Determine newly imported question IDs directly from format instance.
            // Completely eliminates cross-process race conditions and full-table scans.
            $newids = array_map('intval', $qformat->questionids ?? []);

            // 7. Audit log history record within the transaction.
            $history = new stdClass();
            $history->userid = !empty($USER->id) ? (int)$USER->id : 0;
            $history->action = 'import';
            $history->package_id = $packagemetadata['id'] ?? '';
            $history->package_version = $packagemetadata['version'] ?? '';
            $history->package_title = $packagemetadata['title'] ?? 'Imported Package';
            $history->courseid = (int)$this->course->id;
            $history->categoryid = (int)$this->targetcategory->id;
            $history->question_count = count($newids);
            $history->timecreated = time();
            $DB->insert_record('qbank_questionhub_history', $history);

            $transaction->allow_commit();
            ob_end_clean();
        } catch (\Throwable $t) {
            $output = ob_get_clean();
            if (!$transaction->is_disposed()) {
                try {
                    $transaction->rollback($t);
                } catch (\Throwable $ignore) {
                    // Moodle's rollback_delegated_transaction re-throws $t.
                }
            }
            if ($t instanceof moodle_exception) {
                throw $t;
            }
            throw new moodle_exception('error_import_failed', 'qbank_questionhub', '', $t->getMessage() . "\n" . ($output ?: ''));
        } finally {
            // 8. Cleanup temporary staged file.
            @unlink($tempfile);
        }

        return new import_result(true, $newids, $report->get_warnings());
    }
}
