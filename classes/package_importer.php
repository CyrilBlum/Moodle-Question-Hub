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
     * @param int $bankcontextid Selected question-bank context ID.
     */
    public function __construct(int $courseid, ?int $targetcategoryid = null, int $bankcontextid = 0) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/question/editlib.php');

        $this->course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
        $this->context = question_bank_context::resolve($this->course, $bankcontextid);

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
     * Converts an XML category path into the category names below Moodle's special "top" category.
     *
     * @param string $path
     * @return string[]
     */
    private function category_path_segments(string $path): array {
        $segments = array_values(array_filter(explode('/', trim($path, '/')), static function($segment) {
            return $segment !== '';
        }));
        if (!empty($segments) && preg_match('/^\$[a-z]+\$$/i', $segments[0])) {
            array_shift($segments);
        }
        if (!empty($segments) && $segments[0] === 'top') {
            array_shift($segments);
        }
        return $segments;
    }

    /**
     * Gets the full import path for the selected target category.
     *
     * @return string
     */
    private function get_target_category_path(): string {
        global $DB;
        $category = $this->targetcategory;
        $names = [];
        while ($category) {
            array_unshift($names, $category->name);
            if ((int)$category->parent === 0) {
                break;
            }
            $category = $DB->get_record('question_categories', ['id' => $category->parent], '*', MUST_EXIST);
        }
        if (empty($names) || $names[0] !== 'top') {
            throw new \moodle_exception('error_import_failed', 'qbank_questionhub', '', 'The selected category is not inside this question bank.');
        }
        return '$course$/' . implode('/', $names);
    }

    /**
     * Remaps package category markers below the chosen target and optionally renames an empty target.
     *
     * @param array $questions
     * @param string $mode
     */
    private function remap_package_categories(array $questions, string $mode): void {
        global $DB;

        if (!in_array($mode, ['keep', 'rename'], true)) {
            throw new \moodle_exception('error_import_failed', 'qbank_questionhub', '', 'Invalid category placement option.');
        }
        if ((int)$this->targetcategory->contextid !== (int)$this->context->id) {
            throw new \moodle_exception('error_import_failed', 'qbank_questionhub', '', 'The selected category is not in this course question bank.');
        }

        $roots = [];
        foreach ($questions as $question) {
            if (!is_object($question) || ($question->qtype ?? '') !== 'category') {
                continue;
            }
            $segments = $this->category_path_segments((string)($question->category ?? ''));
            if (!empty($segments)) {
                $roots[$segments[0]] = true;
            }
        }
        $packageroot = count($roots) === 1 ? (string)array_key_first($roots) : null;

        if ($mode === 'rename') {
            if ($packageroot === null || $packageroot === '') {
                throw new \moodle_exception('error_import_failed', 'qbank_questionhub', '', 'This package has no single top-level category to use as the new name.');
            }
            if ((int)$this->targetcategory->parent === 0 && $this->targetcategory->name === 'top') {
                throw new \moodle_exception('error_import_failed', 'qbank_questionhub', '', 'The question bank root cannot be renamed.');
            }
            if ($DB->count_records('question_bank_entries', ['questioncategoryid' => $this->targetcategory->id]) > 0 ||
                    $DB->record_exists('question_categories', ['parent' => $this->targetcategory->id])) {
                throw new \moodle_exception('error_import_failed', 'qbank_questionhub', '', 'Choose an empty category before replacing its name.');
            }
            $context = \context::instance_by_id($this->targetcategory->contextid);
            require_capability('moodle/question:managecategory', $context);
            $sibling = $DB->get_record('question_categories', [
                'contextid' => $this->targetcategory->contextid,
                'parent' => $this->targetcategory->parent,
                'name' => $packageroot,
            ]);
            if ($sibling && (int)$sibling->id !== (int)$this->targetcategory->id) {
                throw new \moodle_exception('error_import_failed', 'qbank_questionhub', '', 'A category with the package name already exists beside the selected category.');
            }
            if ($this->targetcategory->name !== $packageroot) {
                $this->targetcategory->name = $packageroot;
                $DB->update_record('question_categories', $this->targetcategory);
                $event = \core\event\question_category_updated::create_from_question_category_instance($this->targetcategory);
                $event->trigger();
            }
        }

        $targetpath = $this->get_target_category_path();
        foreach ($questions as $question) {
            if (!is_object($question) || ($question->qtype ?? '') !== 'category') {
                continue;
            }
            $segments = $this->category_path_segments((string)($question->category ?? ''));
            if ($packageroot !== null && !empty($segments) && $segments[0] === $packageroot) {
                array_shift($segments);
            }
            $question->category = $segments ? $targetpath . '/' . implode('/', $segments) : $targetpath;
        }
    }

    /**
     * Classify package questions without changing the question bank.
     *
     * @param string $xmlcontent
     * @param array $packagemetadata
     * @param string $categorymode
     * @return array
     */
    public function preview_package(string $xmlcontent, array $packagemetadata, string $categorymode = 'keep'): array {
        global $DB;

        $report = $this->run_diagnostics($xmlcontent);
        if (!$report->is_valid()) {
            $errors = array_map(static function($error) {
                return '[' . $error['code'] . '] ' . $error['message'];
            }, $report->get_errors());
            throw new moodle_exception('error_import_failed', 'qbank_questionhub', '', implode("\n", $errors));
        }
        if (!in_array($categorymode, ['keep', 'rename'], true) ||
                (int)$this->targetcategory->contextid !== (int)$this->context->id) {
            throw new moodle_exception('error_import_failed', 'qbank_questionhub', '', 'Invalid target category or placement.');
        }

        $xml = simplexml_load_string($xmlcontent, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOBLANKS);
        if ($xml === false) {
            throw new moodle_exception('error_import_failed', 'qbank_questionhub', '', 'Invalid package XML.');
        }
        $roots = [];
        foreach ($xml->question as $node) {
            if ((string)$node['type'] === 'category') {
                $segments = $this->category_path_segments((string)$node->category->text);
                if ($segments) {
                    $roots[$segments[0]] = true;
                }
            }
        }
        $packageroot = count($roots) === 1 ? (string)array_key_first($roots) : null;
        if ($categorymode === 'rename') {
            if ($packageroot === null || $packageroot === '' ||
                    ((int)$this->targetcategory->parent === 0 && $this->targetcategory->name === 'top') ||
                    $DB->count_records('question_bank_entries', ['questioncategoryid' => $this->targetcategory->id]) > 0 ||
                    $DB->record_exists('question_categories', ['parent' => $this->targetcategory->id])) {
                throw new moodle_exception('error_import_failed', 'qbank_questionhub', '', 'Choose an empty category for renaming.');
            }
            require_capability('moodle/question:managecategory', $this->context);
            if ($DB->record_exists('question_categories', [
                    'contextid' => $this->context->id, 'parent' => $this->targetcategory->parent,
                    'name' => $packageroot]) && $packageroot !== $this->targetcategory->name) {
                throw new moodle_exception('error_import_failed', 'qbank_questionhub', '', 'A category with this name already exists.');
            }
        }

        $packageid = (string)($packagemetadata['id'] ?? '');
        if ($packageid === '') {
            throw new moodle_exception('error_import_failed', 'qbank_questionhub', '', 'Package ID is required.');
        }
        $packagehash = hash('sha256', $packageid);
        $previousimports = $DB->get_records('qbank_questionhub_history', [
            'action' => 'import', 'package_id' => $packageid,
            'courseid' => $this->course->id, 'categoryid' => $this->targetcategory->id,
        ], 'timecreated DESC, id DESC', '*', 0, 1);
        $history = $previousimports ? reset($previousimports) : null;
        $sameversion = $history && $history->package_version === ($packagemetadata['version'] ?? '');
        $previoushashes = null;
        $comparisonunavailable = false;
        $currentpath = [];
        $seen = [];
        $matchedentryids = [];
        $items = [];
        foreach ($xml->question as $node) {
            $type = (string)$node['type'];
            if ($type === 'category') {
                $currentpath = $this->category_path_segments((string)$node->category->text);
                continue;
            }
            $relative = $currentpath;
            if ($packageroot !== null && $relative && $relative[0] === $packageroot) {
                array_shift($relative);
            }
            $name = trim((string)$node->name->text);
            $idnumber = trim((string)$node->idnumber);
            $identity = $idnumber !== '' ? ['idnumber', $idnumber] : ['name', $type, $name];
            $basekey = hash('sha256', json_encode([$relative, $identity], JSON_UNESCAPED_UNICODE));
            $seen[$basekey] = ($seen[$basekey] ?? 0) + 1;
            $sourcekey = $seen[$basekey] === 1 ? $basekey :
                hash('sha256', $basekey . ':' . $seen[$basekey]);
            $sourcehash = hash('sha256', dom_import_simplexml($node)->C14N());
            $categoryid = $this->find_existing_category($relative);
            $tracking = $DB->get_record('qbank_questionhub_items', [
                'packagehash' => $packagehash, 'targetcategoryid' => $this->targetcategory->id,
                'sourcekey' => $sourcekey,
            ]);
            $entryid = 0;
            $status = 'new';
            $uncertain = false;
            if ($tracking) {
                $entry = $DB->get_record('question_bank_entries', ['id' => $tracking->questionbankentryid]);
                if ($entry) {
                    // A teacher may move a question after importing it. Preserve that choice and
                    // continue to update the tracked entry instead of aborting the complete import.
                    $entryid = (int)$entry->id;
                    $status = $tracking->sourcehash === $sourcehash ? 'unchanged' : 'update';
                }
                // If the entry was deleted, retain the tracking ID in the plan. A new copy is
                // imported and replaces this stale tracking record transactionally below.
            } else if ($categoryid) {
                // Match an existing local question before importing a second copy. This also adopts
                // questions that were imported before Question Hub tracking existed.
                $entryid = $this->find_existing_question($categoryid, $node, $idnumber, $name, $type);
                if ($entryid && isset($matchedentryids[$entryid])) {
                    $entryid = 0;
                }
                if ($entryid) {
                    $matchedentryids[$entryid] = true;
                    if (!$history || $sameversion) {
                        // Without package history there is no reliable baseline for an automatic
                        // content update. Keep the matching local question and establish tracking.
                        $status = 'unchanged';
                    } else {
                        if ($previoushashes === null) {
                            try {
                                $client = new api_client(null, null, 30, true);
                                $previousxml = $client->download_release($packageid, $history->package_version);
                                $previoushashes = $this->release_fingerprints($previousxml);
                            } catch (\Throwable $e) {
                                $previoushashes = [];
                                $comparisonunavailable = true;
                            }
                        }
                        if (isset($previoushashes[$sourcekey])) {
                            $status = $previoushashes[$sourcekey] === $sourcehash ? 'unchanged' : 'update';
                        } else {
                            $status = 'update';
                            $uncertain = true;
                        }
                        $uncertain = $uncertain || $comparisonunavailable;
                    }
                }
            }
            $items[] = [
                'name' => $name, 'type' => $type, 'category' => implode(' / ', $relative),
                'status' => $status, 'sourcekey' => $sourcekey, 'sourcehash' => $sourcehash,
                'entryid' => $entryid, 'trackingid' => $tracking->id ?? 0, 'uncertain' => $uncertain,
            ];
        }
        return $items;
    }

    /** Find an existing descendant without creating categories. */
    private function find_existing_category(array $relative): int {
        global $DB;
        $parent = (int)$this->targetcategory->id;
        foreach ($relative as $name) {
            $child = $DB->get_record('question_categories', [
                'contextid' => $this->context->id, 'parent' => $parent, 'name' => $name,
            ]);
            if (!$child) {
                return 0;
            }
            $parent = (int)$child->id;
        }
        return $parent;
    }

    /**
     * Finds one unambiguous existing local question.
     *
     * An idnumber is authoritative. Questions without one must also have the same type,
     * name and question text. Some question types, such as Cloze, keep their content in
     * type-specific tables and leave question.questiontext empty; a unique local name/type
     * match is the only reliable identity available for those questions.
     */
    private function find_existing_question(int $categoryid, \SimpleXMLElement $node, string $idnumber,
            string $name, string $type): int {
        global $DB;
        $params = ['categoryid' => $categoryid];
        $requiretextmatch = $idnumber === '';
        if (!$requiretextmatch) {
            $where = 'qbe.idnumber = :identity';
            $params['identity'] = $idnumber;
        } else {
            $where = 'q.name = :name AND q.qtype = :qtype';
            $params['name'] = $name;
            // Moodle XML calls this type "cloze"; Moodle stores it as "multianswer".
            $params['qtype'] = $type === 'cloze' ? 'multianswer' : $type;
        }
        $sql = "SELECT qbe.id, q.questiontext FROM {question_bank_entries} qbe
                  JOIN {question_versions} qv ON qv.questionbankentryid = qbe.id
                  JOIN {question} q ON q.id = qv.questionid
                 WHERE qbe.questioncategoryid = :categoryid AND $where
                   AND q.parent = 0
                   AND qv.version = (SELECT MAX(v.version) FROM {question_versions} v
                                        WHERE v.questionbankentryid = qbe.id)";
        $matches = $DB->get_records_sql($sql, $params, 0, 2);
        if (count($matches) !== 1) {
            return 0;
        }
        $match = reset($matches);
        if (!$requiretextmatch) {
            return (int)$match->id;
        }
        $questiontext = (string)($node->questiontext->text ?? '');
        if ($type === 'cloze' || $match->questiontext === '' || $match->questiontext === $questiontext) {
            return (int)$match->id;
        }
        return 0;
    }

    /** Fingerprints of questions in an earlier release, keyed independently of its root category name. */
    private function release_fingerprints(string $xmlcontent): array {
        $xml = simplexml_load_string($xmlcontent, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOBLANKS);
        if ($xml === false) {
            throw new \moodle_exception('error_import_failed', 'qbank_questionhub', '', 'Invalid previous release XML.');
        }
        $roots = [];
        foreach ($xml->question as $node) {
            if ((string)$node['type'] === 'category') {
                $segments = $this->category_path_segments((string)$node->category->text);
                if ($segments) {
                    $roots[$segments[0]] = true;
                }
            }
        }
        $root = count($roots) === 1 ? (string)array_key_first($roots) : null;
        $path = [];
        $seen = [];
        $hashes = [];
        foreach ($xml->question as $node) {
            $type = (string)$node['type'];
            if ($type === 'category') {
                $path = $this->category_path_segments((string)$node->category->text);
                continue;
            }
            $relative = $path;
            if ($root !== null && $relative && $relative[0] === $root) {
                array_shift($relative);
            }
            $idnumber = trim((string)$node->idnumber);
            $name = trim((string)$node->name->text);
            $identity = $idnumber !== '' ? ['idnumber', $idnumber] : ['name', $type, $name];
            $basekey = hash('sha256', json_encode([$relative, $identity], JSON_UNESCAPED_UNICODE));
            $seen[$basekey] = ($seen[$basekey] ?? 0) + 1;
            $key = $seen[$basekey] === 1 ? $basekey :
                hash('sha256', $basekey . ':' . $seen[$basekey]);
            $hashes[$key] = hash('sha256', dom_import_simplexml($node)->C14N());
        }
        return $hashes;
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
    public function import_package(string $xmlcontent, bool $preservehierarchy = true, array $packagemetadata = [],
            ?string $categorymode = null, bool $updatechanged = false): import_result {
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

        $plan = $categorymode === null ? [] : $this->preview_package($xmlcontent, $packagemetadata, $categorymode);
        if (!$updatechanged) {
            foreach ($plan as &$item) {
                $item['status'] = 'new';
                $item['entryid'] = 0;
            }
            unset($item);
        }
        if ($categorymode !== null && !array_filter($plan, static function($item) {
                return $item['status'] !== 'unchanged';
            })) {
            $result = new import_result(true, [], $report->get_warnings());
            $result->unchangedcount = count($plan);
            return $result;
        }

        // 2. Retain the old CLI flattening behaviour when no new category placement mode is supplied.
        $processedxml = $xmlcontent;
        if ($categorymode === null && !$preservehierarchy && $this->targetcategory) {
            $istopcategory = (int)$this->targetcategory->parent === 0 && $this->targetcategory->name === 'top';
            $catpath = $istopcategory ? '$course$/top' : '$course$/top/' . $this->targetcategory->name;
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
        $qformat = $categorymode === null ? new \qformat_xml() : new qformat_xml_importer();
        if ($categorymode !== null) {
            $qformat->set_category_transformer(function(array $questions) use ($categorymode): void {
                $this->remap_package_categories($questions, $categorymode);
            });
            $qformat->set_question_filter(static function(array $questions) use ($plan): array {
                $index = 0;
                $filtered = [];
                foreach ($questions as $question) {
                    if (($question->qtype ?? '') === 'category') {
                        $filtered[] = $question;
                        continue;
                    }
                    if (!isset($plan[$index])) {
                        throw new \coding_exception('Package question count changed during import.');
                    }
                    if ($plan[$index]['status'] !== 'unchanged') {
                        $filtered[] = $question;
                    }
                    $index++;
                }
                if ($index !== count($plan)) {
                    throw new \coding_exception('Package question count changed during import.');
                }
                return $filtered;
            });
        }
        $contexts = new \core_question\local\bank\question_edit_contexts($this->context);
        $qformat->setCourse($this->course);
        $qformat->setContexts($contexts->all());
        $qformat->setCategory($this->targetcategory);
        $qformat->setCatfromfile($categorymode !== null ? true : $preservehierarchy);
        $qformat->setContextfromfile($this->context->contextlevel !== CONTEXT_COURSECAT);
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

            $addedcount = count($newids);
            $updatedcount = 0;
            $unchangedcount = 0;
            if ($categorymode !== null) {
                $packagehash = hash('sha256', (string)$packagemetadata['id']);
                $nextid = 0;
                foreach ($plan as $item) {
                    if ($item['status'] === 'unchanged') {
                        $unchangedcount++;
                        $entryid = $item['entryid'];
                    } else {
                        if (!isset($newids[$nextid])) {
                            throw new \coding_exception('Imported question count does not match the preview.');
                        }
                        $questionid = $newids[$nextid++];
                        $newversion = $DB->get_record('question_versions', ['questionid' => $questionid], '*', MUST_EXIST);
                        $newentryid = (int)$newversion->questionbankentryid;
                        $entryid = $newentryid;
                        if ($item['status'] === 'update') {
                            $entryid = $item['entryid'];
                            $oldentry = $DB->get_record('question_bank_entries', ['id' => $entryid], '*', MUST_EXIST);
                            $newversion->questionbankentryid = $entryid;
                            $newversion->version = (int)$oldentry->nextversion;
                            $DB->update_record('question_versions', $newversion);
                            $oldentry->nextversion = $newversion->version + 1;
                            $DB->update_record('question_bank_entries', $oldentry);
                            $DB->delete_records('question_bank_entries', ['id' => $newentryid]);
                            $updatedquestion = $DB->get_record('question', ['id' => $questionid], '*', MUST_EXIST);
                            $updatedquestion->category = $oldentry->questioncategoryid;
                            \core\event\question_updated::create_from_question_instance(
                                $updatedquestion, $this->context)->trigger();
                            $updatedcount++;
                            $addedcount--;
                        }
                    }
                    if ($item['trackingid']) {
                        $DB->update_record('qbank_questionhub_items', (object)[
                            'id' => $item['trackingid'], 'questionbankentryid' => $entryid,
                            'sourcehash' => $item['sourcehash'], 'timemodified' => time(),
                        ]);
                    } else if ($entryid) {
                        $DB->insert_record('qbank_questionhub_items', (object)[
                            'packagehash' => $packagehash, 'targetcategoryid' => $this->targetcategory->id,
                            'sourcekey' => $item['sourcekey'], 'questionbankentryid' => $entryid,
                            'sourcehash' => $item['sourcehash'], 'timemodified' => time(),
                        ]);
                    }
                }
                if ($nextid !== count($newids)) {
                    throw new \coding_exception('Imported question count does not match the preview.');
                }
            }

            // 7. Audit log history record within the transaction.
            $history = new stdClass();
            $history->userid = !empty($USER->id) ? (int)$USER->id : 0;
            $history->action = 'import';
            $history->package_id = $packagemetadata['id'] ?? '';
            $history->package_version = $packagemetadata['version'] ?? '';
            $history->package_title = $packagemetadata['title'] ?? 'Imported Package';
            $history->courseid = (int)$this->course->id;
            $history->categoryid = (int)$this->targetcategory->id;
            $history->question_count = $addedcount + $updatedcount;
            $history->timecreated = time();
            $history->snapshotted = 0;
            $historyid = $DB->insert_record('qbank_questionhub_history', $history);

            try {
                $importedquestions = publication_tracker::current((int)$this->targetcategory->id);
                if (!empty($importedquestions)) {
                    publication_tracker::save((int)$historyid, $importedquestions);
                }
            } catch (\Throwable $ignore) {
                // Non-fatal if snapshot fails during import.
            }

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

        $result = new import_result(true, $newids, $report->get_warnings());
        $result->addedcount = $addedcount;
        $result->updatedcount = $updatedcount;
        $result->unchangedcount = $unchangedcount;
        return $result;
    }
}
