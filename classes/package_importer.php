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
        // Versions this category held at its last sync (pull or publish); a newer local version is a local edit.
        $baseline = null;
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
            $basekey = self::source_key($relative, $idnumber, $type, $name);
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
                $match = $this->find_existing_question($categoryid, $node, $idnumber, $name, $type, $matchedentryids);
                if ($match) {
                    $entryid = $match['id'];
                    $matchedentryids[$entryid] = true;
                    if (!$history || $sameversion) {
                        // Without package history there is no baseline that tells a local edit from a Hub
                        // change. An identical question is adopted; a different one is a conflict the
                        // teacher decides (keep the local question, take the Hub version or keep both).
                        $status = $match['differs'] ? 'conflict' : 'unchanged';
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
                            $status = $previoushashes[$sourcekey]['hash'] === $sourcehash ? 'unchanged' : 'update';
                        } else {
                            $status = 'update';
                            $uncertain = true;
                        }
                        $uncertain = $uncertain || $comparisonunavailable;
                        // A question matched by name has no recorded Hub version (e.g. kept at the first link).
                        // Unless it still holds the text of the previous Hub release, it is a local version
                        // that an update would overwrite: the teacher decides.
                        $previoustext = $previoushashes[$sourcekey]['text'] ?? null;
                        if ($status === 'update' && $match['differs'] && $match['text'] !== $previoustext) {
                            $status = 'conflict';
                        }
                    }
                }
            }
            $baselinequestionid = 0;
            if ($status === 'update' && $entryid) {
                $baselinequestionid = (int)($tracking->questionid ?? 0);
                if (!$baselinequestionid) {
                    if ($baseline === null) {
                        $synced = publication_tracker::latest((int)$this->course->id, (int)$this->targetcategory->id);
                        $baseline = ($synced ? publication_tracker::snapshot((int)$synced->id) : null) ?? [];
                    }
                    $baselinequestionid = (int)($baseline[$entryid] ?? 0);
                }
                if ($baselinequestionid && $this->latest_questionid($entryid) !== $baselinequestionid) {
                    // Changed on the Hub and edited here: never overwrite silently.
                    $status = 'conflict';
                }
            }
            $items[] = [
                'name' => $name, 'type' => $type, 'category' => implode(' / ', $relative),
                'status' => $status, 'sourcekey' => $sourcekey, 'sourcehash' => $sourcehash,
                'entryid' => $entryid, 'trackingid' => $tracking->id ?? 0, 'uncertain' => $uncertain,
                'baselinequestionid' => $baselinequestionid, 'index' => count($items),
                'loosehash' => self::loose_hash($node), 'path' => $relative,
            ];
        }
        $this->match_renames($items, $packagehash, $history, $previoushashes);
        return $items;
    }

    /**
     * Makes a freshly imported question the newest version of an existing question bank entry
     * (the entry the import created for it is removed).
     */
    public static function attach_as_version(int $questionid, int $entryid, \context $context): void {
        global $DB;
        $newversion = $DB->get_record('question_versions', ['questionid' => $questionid], '*', MUST_EXIST);
        $newentryid = (int)$newversion->questionbankentryid;
        $oldentry = $DB->get_record('question_bank_entries', ['id' => $entryid], '*', MUST_EXIST);
        $newversion->questionbankentryid = $entryid;
        // Moodle 4.5 has no question_bank_entries.nextversion (5.0 does); never reuse a number.
        $newversion->version = max((int)($oldentry->nextversion ?? 0), 1 + (int)$DB->get_field_sql(
            'SELECT MAX(version) FROM {question_versions} WHERE questionbankentryid = ?', [$entryid]));
        $DB->update_record('question_versions', $newversion);
        if (property_exists($oldentry, 'nextversion')) {
            $oldentry->nextversion = $newversion->version + 1;
            $DB->update_record('question_bank_entries', $oldentry);
        }
        $DB->delete_records('question_bank_entries', ['id' => $newentryid]);
        $updatedquestion = $DB->get_record('question', ['id' => $questionid], '*', MUST_EXIST);
        $updatedquestion->category = $oldentry->questioncategoryid;
        \core\event\question_updated::create_from_question_instance($updatedquestion, $context)->trigger();
    }

    /**
     * Runs every HTML text of a package through Moodle's HTML cleaner (HTML Purifier), which removes scripts,
     * event handlers and frames. Moodle displays question text uncleaned, so this is the site's choice
     * (setting stripactivecontent) for packages from authors it does not trust.
     *
     * @param string $xml Moodle XML package
     * @return string the package with cleaned HTML texts
     */
    public static function strip_active_content(string $xml): string {
        $doc = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        // Embedded media can exceed libxml's default text node limit; the size is capped by the preflight check.
        $loaded = $doc->loadXML($xml, LIBXML_NONET | LIBXML_PARSEHUGE);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if (!$loaded) {
            throw new moodle_exception('error_import_failed', 'qbank_questionhub', '', 'Invalid package XML.');
        }
        $xpath = new \DOMXPath($doc);
        foreach ($xpath->query('//*[@format="html"]/text') as $node) {
            $html = $node->textContent;
            if (!preg_match(preflight_validator::ACTIVE_CONTENT_PATTERN, $html)) {
                continue;
            }
            while ($node->firstChild) {
                $node->removeChild($node->firstChild);
            }
            $node->appendChild($doc->createTextNode(purify_html($html)));
        }
        return $doc->saveXML();
    }

    /**
     * Imports a Moodle XML export of one question into a category and returns the new question ID.
     * Category entries in the XML are ignored.
     */
    public static function import_question_xml(string $xml, \stdClass $category, \context $context,
            \stdClass $course): int {
        global $CFG;
        require_once($CFG->dirroot . '/question/editlib.php');
        require_once($CFG->dirroot . '/question/format/xml/format.php');
        $tempfile = tempnam(make_temp_directory('questionhub'), 'qh_rev_');
        file_put_contents($tempfile, $xml);
        try {
            $qformat = new qformat_xml_importer();
            $contexts = new \core_question\local\bank\question_edit_contexts($context);
            $qformat->setCourse($course);
            $qformat->setContexts($contexts->all());
            $qformat->setCategory($category);
            $qformat->setCatfromfile(false);
            $qformat->setContextfromfile(false);
            $qformat->setMatchgrades('nearest');
            $qformat->setStoponerror(true);
            $qformat->setFilename($tempfile);
            $qformat->setRealfilename('question.xml');
            $qformat->displayprogress = false;
            ob_start();
            $ok = $qformat->importpreprocess() && $qformat->importprocess();
            if ($ok) {
                $qformat->importpostprocess();
            }
            $output = ob_get_clean();
            $ids = array_map('intval', $qformat->questionids ?? []);
            if (!$ok || count($ids) !== 1) {
                throw new moodle_exception('error_import_failed', 'qbank_questionhub', '', $output ?: '');
            }
            return $ids[0];
        } finally {
            @unlink($tempfile);
        }
    }

    /**
     * Key of a package question for repeat imports: its category path below the package root and its
     * identity (idnumber, otherwise Moodle XML type and name). A further question with the same key
     * gets a numbered key (see preview_package()).
     */
    public static function source_key(array $relative, string $idnumber, string $type, string $name): string {
        $identity = $idnumber !== '' ? ['idnumber', $idnumber] : ['name', $type, $name];
        return hash('sha256', json_encode([$relative, $identity], JSON_UNESCAPED_UNICODE));
    }

    /**
     * Pairs package questions that look new with tracked questions missing from the package because they were
     * renamed or moved into another subcategory on the Hub: the content apart from the name is identical to the
     * tracked question in the previous release. Only unambiguous pairs are used. The tracked local question is
     * then updated (or left unchanged) instead of importing a copy next to it.
     *
     * @param array[] $items preview items, changed in place
     * @param array|null $previoushashes fingerprints of the previous release if already downloaded
     */
    private function match_renames(array &$items, string $packagehash, ?\stdClass $history,
            ?array $previoushashes): void {
        global $DB;
        $candidates = [];
        foreach ($items as $index => $item) {
            if ($item['status'] === 'new' && !$item['trackingid'] && !$item['entryid']) {
                $candidates[$item['loosehash']][] = $index;
            }
        }
        if (!$candidates || !$history) {
            return;
        }
        $keys = array_flip(array_column($items, 'sourcekey'));
        $orphans = [];
        $rows = $DB->get_records('qbank_questionhub_items', [
            'packagehash' => $packagehash, 'targetcategoryid' => $this->targetcategory->id,
        ]);
        foreach ($rows as $row) {
            if (!isset($keys[$row->sourcekey]) &&
                    $DB->record_exists('question_bank_entries', ['id' => $row->questionbankentryid])) {
                $orphans[$row->sourcekey] = $row;
            }
        }
        if (!$orphans) {
            return;
        }
        if ($previoushashes === null) {
            try {
                $client = new api_client(null, null, 30, true);
                $previoushashes = $this->release_fingerprints(
                    $client->download_release((string)$history->package_id, (string)$history->package_version));
            } catch (\Throwable $e) {
                // Without the previous release a rename cannot be told from a new question.
                return;
            }
        }
        $byloose = [];
        foreach (array_keys($orphans) as $key) {
            if (isset($previoushashes[$key]['loose'])) {
                $byloose[$previoushashes[$key]['loose']][] = $key;
            }
        }
        foreach ($byloose as $loose => $orphankeys) {
            if (count($orphankeys) !== 1 || count($candidates[$loose] ?? []) !== 1) {
                continue;
            }
            $row = $orphans[$orphankeys[0]];
            $previous = $previoushashes[$orphankeys[0]];
            $item = &$items[$candidates[$loose][0]];
            $item['trackingid'] = (int)$row->id;
            $item['entryid'] = (int)$row->questionbankentryid;
            $item['baselinequestionid'] = (int)($row->questionid ?? 0);
            // Only moved: the question itself (name included) is the one last pulled.
            $item['status'] = $row->sourcehash === $item['sourcehash'] ? 'unchanged' : 'update';
            if ($item['status'] === 'update' && $item['baselinequestionid'] &&
                    $this->latest_questionid($item['entryid']) !== $item['baselinequestionid']) {
                // Renamed on the Hub and edited here: never overwrite silently.
                $item['status'] = 'conflict';
            }
            if ($previous['name'] !== $item['name']) {
                $item['renamed_from'] = $previous['name'];
            }
            if ($previous['path'] !== $item['path']) {
                $item['moved_label'] = get_string('diff_moved', 'qbank_questionhub', (object)[
                    'from' => release_diff::path_label($previous['path']),
                    'to' => release_diff::path_label($item['path']),
                ]);
            }
            unset($item);
        }
    }

    /** Hash of a package question without its name: equal for a question that was only renamed. */
    private static function loose_hash(\SimpleXMLElement $node): string {
        $doc = new \DOMDocument();
        $copy = $doc->importNode(dom_import_simplexml($node), true);
        $doc->appendChild($copy);
        foreach (iterator_to_array($copy->childNodes) as $child) {
            if ($child instanceof \DOMElement && $child->tagName === 'name') {
                $copy->removeChild($child);
            }
        }
        return hash('sha256', $copy->C14N());
    }

    /** Question ID of the latest version of a question bank entry. */
    private function latest_questionid(int $entryid): int {
        global $DB;
        return (int)$DB->get_field_sql(
            'SELECT questionid FROM {question_versions} WHERE questionbankentryid = ? ORDER BY version DESC',
            [$entryid], IGNORE_MULTIPLE);
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
     * An idnumber is authoritative. Without one, the only local question with the same name and type
     * in the category is the same question, even if its text was edited since (on either side); the
     * caller decides how to treat a different text. Several local questions with the same name are
     * told apart by an identical question text, otherwise there is no match.
     *
     * @param int[] $exclude entry IDs (as keys) already matched to other questions of the package
     * @return array|null id (question bank entry), differs (the question text is not identical) and text
     *         (the local question text)
     */
    private function find_existing_question(int $categoryid, \SimpleXMLElement $node, string $idnumber,
            string $name, string $type, array $exclude = []): ?array {
        global $DB;
        $params = ['categoryid' => $categoryid];
        $requiretextmatch = $idnumber === '';
        if (!$requiretextmatch) {
            $where = 'qbe.idnumber = :identity';
            $params['identity'] = $idnumber;
        } else {
            $where = 'q.name = :name AND q.qtype = :qtype';
            $params['name'] = $name;
            // Moodle XML names these types differently from the qtype plugins Moodle stores.
            $params['qtype'] = ['cloze' => 'multianswer', 'matching' => 'match'][$type] ?? $type;
        }
        $sql = "SELECT qbe.id, q.questiontext FROM {question_bank_entries} qbe
                  JOIN {question_versions} qv ON qv.questionbankentryid = qbe.id
                  JOIN {question} q ON q.id = qv.questionid
                 WHERE qbe.questioncategoryid = :categoryid AND $where
                   AND q.parent = 0
                   AND qv.version = (SELECT MAX(v.version) FROM {question_versions} v
                                        WHERE v.questionbankentryid = qbe.id)";
        $matches = array_diff_key($DB->get_records_sql($sql, $params), $exclude);
        $questiontext = (string)($node->questiontext->text ?? '');
        if ($requiretextmatch && count($matches) > 1) {
            $matches = array_filter($matches, static fn($match) => $match->questiontext === $questiontext);
        }
        if (count($matches) !== 1) {
            return null;
        }
        $match = reset($matches);
        // Cloze questions keep their content in type-specific tables and may leave questiontext empty;
        // Moodle also serialises their text differently on import, so the text does not tell them apart.
        $differs = $requiretextmatch && $type !== 'cloze' && $match->questiontext !== '' &&
            $match->questiontext !== $questiontext;
        return ['id' => (int)$match->id, 'differs' => $differs, 'text' => (string)$match->questiontext];
    }

    /**
     * Fingerprints (hash) and question texts of an earlier release, keyed independently of its root category
     * name.
     */
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
            $basekey = self::source_key($relative, $idnumber, $type, $name);
            $seen[$basekey] = ($seen[$basekey] ?? 0) + 1;
            $key = $seen[$basekey] === 1 ? $basekey :
                hash('sha256', $basekey . ':' . $seen[$basekey]);
            $hashes[$key] = [
                'hash' => hash('sha256', dom_import_simplexml($node)->C14N()),
                'text' => (string)($node->questiontext->text ?? ''),
                'loose' => self::loose_hash($node), 'name' => $name, 'path' => $relative,
            ];
        }
        return $hashes;
    }

    /**
     * Imports validated Moodle XML package into course question bank.
     *
     * @param string $xmlcontent Raw XML package string.
     * @param bool $preservehierarchy Preserve nested category structure from XML package.
     * @param array $packagemetadata Optional metadata for history logging (id, version, title).
     * @param array $resolutions Conflict decisions by source key: keep (local version), hub (Hub version
     *        as new version), both (Hub version as a separate copy). Unresolved conflicts abort the import.
     * @return import_result
     * @throws moodle_exception On validation or import failure.
     */
    public function import_package(string $xmlcontent, bool $preservehierarchy = true, array $packagemetadata = [],
            ?string $categorymode = null, bool $updatechanged = false, array $resolutions = []): import_result {
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
        foreach ($plan as &$item) {
            if ($item['status'] !== 'conflict') {
                continue;
            }
            $resolution = $resolutions[$item['sourcekey']] ?? '';
            if (!in_array($resolution, ['keep', 'hub', 'both'], true)) {
                throw new moodle_exception('error_conflicts_unresolved', 'qbank_questionhub');
            }
            $item['status'] = ['keep' => 'kept', 'hub' => 'update', 'both' => 'copy'][$resolution];
        }
        unset($item);
        // A release without content changes still has to be recorded, or the category keeps offering it.
        $needsimport = $categorymode === null || (bool)array_filter($plan, static function($item) {
            return !in_array($item['status'], ['unchanged', 'kept'], true);
        });
        $copysuffix = ' (Hub v' . ($packagemetadata['version'] ?? '') . ')';

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

        if (get_config('qbank_questionhub', 'stripactivecontent')) {
            $processedxml = self::strip_active_content($processedxml);
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
            $qformat->set_question_filter(static function(array $questions) use ($plan, $copysuffix): array {
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
                    if ($plan[$index]['status'] === 'copy') {
                        $question->name .= $copysuffix;
                    }
                    if (!in_array($plan[$index]['status'], ['unchanged', 'kept'], true)) {
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
            if ($needsimport) {
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
            }

            // 6. Determine newly imported question IDs directly from format instance.
            // Completely eliminates cross-process race conditions and full-table scans.
            $newids = $needsimport ? array_map('intval', $qformat->questionids ?? []) : [];

            $addedcount = count($newids);
            $updatedcount = 0;
            $unchangedcount = 0;
            if ($categorymode !== null) {
                $packagehash = hash('sha256', (string)$packagemetadata['id']);
                $nextid = 0;
                // Local question bank entries that hold a package question after this import.
                $packageentries = [];
                foreach ($plan as $item) {
                    // Version last written from the Hub; a kept local edit keeps the old one, so the
                    // question stays a conflict until the teacher takes a Hub version.
                    $hubquestionid = null;
                    if ($item['status'] === 'unchanged' || $item['status'] === 'kept') {
                        $unchangedcount++;
                        $entryid = $item['entryid'];
                        $packageentries[(int)$entryid] = true;
                        if ($item['status'] === 'kept') {
                            $hubquestionid = (int)$item['baselinequestionid'];
                            if (!$hubquestionid && !$item['trackingid']) {
                                // A local question kept at the first link has no Hub version to compare
                                // with. Leaving it untracked makes the next pull match it again by name,
                                // so a differing Hub version stays a conflict instead of overwriting it.
                                continue;
                            }
                        } else if (!$item['trackingid'] && $entryid) {
                            $hubquestionid = $this->latest_questionid((int)$entryid);
                        }
                    } else {
                        if (!isset($newids[$nextid])) {
                            throw new \coding_exception('Imported question count does not match the preview.');
                        }
                        $questionid = $newids[$nextid++];
                        $newversion = $DB->get_record('question_versions', ['questionid' => $questionid], '*', MUST_EXIST);
                        $newentryid = (int)$newversion->questionbankentryid;
                        $entryid = $newentryid;
                        $hubquestionid = $questionid;
                        if ($item['status'] === 'copy') {
                            // The separate copy stays untracked; the tracked local question is kept.
                            $entryid = $item['entryid'];
                            $hubquestionid = (int)$item['baselinequestionid'];
                        } else if ($item['status'] === 'update') {
                            $entryid = $item['entryid'];
                            // Category the package places the question in (its temporary entry is removed below).
                            $packagecategoryid = (int)$DB->get_field('question_bank_entries', 'questioncategoryid',
                                ['id' => $newentryid]);
                            self::attach_as_version($questionid, (int)$entryid, $this->context);
                            if (!empty($item['moved_label']) && $packagecategoryid) {
                                // Moved into another subcategory on the Hub: follow it here.
                                $DB->set_field('question_bank_entries', 'questioncategoryid', $packagecategoryid,
                                    ['id' => $entryid]);
                            }
                            $updatedcount++;
                            $addedcount--;
                        }
                    }
                    // A separate copy ("keep both") is a local addition: the tracked local question holds the
                    // package question.
                    $packageentries[(int)$entryid] = true;
                    $tracking = (object)[
                        'questionbankentryid' => $entryid, 'sourcehash' => $item['sourcehash'], 'timemodified' => time(),
                    ];
                    if ($hubquestionid) {
                        $tracking->questionid = $hubquestionid;
                    }
                    if ($item['trackingid']) {
                        $tracking->id = $item['trackingid'];
                        // A question renamed or moved on the Hub keeps its tracking row under the new key.
                        $tracking->sourcekey = $item['sourcekey'];
                        $DB->update_record('qbank_questionhub_items', $tracking);
                    } else if ($entryid) {
                        $tracking->packagehash = $packagehash;
                        $tracking->targetcategoryid = $this->targetcategory->id;
                        $tracking->sourcekey = $item['sourcekey'];
                        $DB->insert_record('qbank_questionhub_items', $tracking);
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
                if ($categorymode !== null) {
                    // The sync state holds the package questions only: other questions in the category were
                    // added here and count as local changes (badge "new"), not as synchronised.
                    $importedquestions = array_intersect_key($importedquestions, $packageentries);
                }
                // Also an empty state is recorded: an unsaved one is backfilled with every question later.
                if (!empty($importedquestions) || $categorymode !== null) {
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
