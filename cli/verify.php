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
 * CLI verification suite for qbank_questionhub.
 *
 * @package    qbank_questionhub
 * @copyright  2026 Cyril Blum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

\core\session\manager::set_user(get_admin());

cli_writeln("=== qbank_questionhub Verification Suite ===");
$passed = 0;
$failed = 0;

/**
 * Print and count the result of one check.
 *
 * @param string $name
 * @param bool $condition
 * @param string $detail
 */
function assert_test(string $name, bool $condition, string $detail = '') {
    global $passed, $failed;
    if ($condition) {
        $passed++;
        cli_writeln(" [PASS] {$name}");
    } else {
        $failed++;
        cli_writeln(" [FAIL] {$name} - {$detail}");
    }
}

// 1. Test Marketplace Health Check via api_client
try {
    $client = new \qbank_questionhub\api_client();
    $health = $client->health();
    assert_test('API Client: Marketplace Health Check', ($health['status'] ?? '') === 'ok', json_encode($health));
} catch (\Throwable $t) {
    assert_test('API Client: Marketplace Health Check', false, $t->getMessage());
}

// 2. Test Taxonomy Retrieval
try {
    $taxonomy = $client->get_taxonomy();
    $subjectcount = count($taxonomy['subjects'] ?? []);
    assert_test('API Client: Taxonomy Retrieval', $subjectcount >= 10, "Found {$subjectcount} subjects");
} catch (\Throwable $t) {
    assert_test('API Client: Taxonomy Retrieval', false, $t->getMessage());
}

// 2b. Test Capability String Format & Authorization Resolution
try {
    $course = $DB->get_record('course', ['id' => 2]);
    if ($course) {
        $context = \qbank_questionhub\package_exporter::resolve_course_question_context($course);
        \core\session\manager::set_user(get_admin());

        $canview = has_capability('qbank/questionhub:view', $context);
        $canpublish = has_capability('qbank/questionhub:publish', $context);
        $canimport = has_capability('qbank/questionhub:import', $context);
        $badview = has_capability('qbank_questionhub:view', $context);

        assert_test(
            'Capabilities: Canonical Slash Format Registered & Resolvable',
            $canview && $canpublish && $canimport && !$badview,
            "view={$canview}, publish={$canpublish}, import={$canimport}, underscore_format={$badview}"
        );
    }
} catch (\Throwable $t) {
    assert_test('Capabilities: Canonical Slash Format Registered & Resolvable', false, $t->getMessage());
}

// 3. Test Pre-flight Validator: Valid Quiz XML
$validxml = '<?xml version="1.0" encoding="UTF-8"?>
<quiz>
  <question type="category">
    <category><text>$course$/top/TestCategory</text></category>
  </question>
  <question type="multichoice">
    <name><text>MC 1</text></name>
    <questiontext format="html"><text><![CDATA[<p>Was ist 2+2?</p>]]></text></questiontext>
    <single>true</single>
    <answer fraction="100"><text>4</text></answer>
    <answer fraction="0"><text>5</text></answer>
  </question>
</quiz>';
$report = \qbank_questionhub\preflight_validator::validate($validxml);
assert_test('Preflight Validator: Valid Quiz XML', $report->is_valid() && count($report->get_errors()) === 0);

// 4. Test Pre-flight Validator: XXE Injection Block
$xxexml = '<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE quiz [
  <!ENTITY xxe SYSTEM "file:///etc/passwd">
]>
<quiz>
  <question type="essay">
    <name><text>Attack</text></name>
    <questiontext><text>&xxe;</text></questiontext>
  </question>
</quiz>';
$reportxxe = \qbank_questionhub\preflight_validator::validate($xxexml);
assert_test('Preflight Validator: XXE Injection Defense', !$reportxxe->is_valid(), 'Expected blocking error');

// 4b. Test Pre-flight Validator: Multi-byte UTF-16 DOCTYPE Defense
$utf16lexxe = "\xFF\xFE" . mb_convert_encoding($xxexml, 'UTF-16LE', 'UTF-8');
$reportutf16 = \qbank_questionhub\preflight_validator::validate($utf16lexxe);
$utf16errors = array_column($reportutf16->get_errors(), 'code');
assert_test(
    'Preflight Validator: Multi-byte UTF-16 DOCTYPE Defense',
    !$reportutf16->is_valid() && in_array('ERR_XXE_DOCTYPE_FORBIDDEN', $utf16errors, true),
    'Expected ERR_XXE_DOCTYPE_FORBIDDEN in ' . json_encode($utf16errors)
);

// 4c. Test Pre-flight Validator: Embedded Media Path Traversal Defense
$traversalxml = '<?xml version="1.0" encoding="UTF-8"?>
<quiz>
  <question type="multichoice">
    <name><text>Traversal Test</text></name>
    <questiontext format="html">
      <text><![CDATA[<p>Photo</p>]]></text>
      <file name="../../etc/passwd" encoding="base64">YQ==</file>
    </questiontext>
    <single>true</single>
    <answer fraction="100"><text>A</text></answer>
    <answer fraction="0"><text>B</text></answer>
  </question>
</quiz>';
$reporttraversal = \qbank_questionhub\preflight_validator::validate($traversalxml);
$traversalerrors = array_column($reporttraversal->get_errors(), 'code');
assert_test(
    'Preflight Validator: Embedded Media Path Traversal Defense',
    !$reporttraversal->is_valid() && in_array('ERR_FILE_PATH_TRAVERSAL', $traversalerrors, true),
    'Expected ERR_FILE_PATH_TRAVERSAL in ' . json_encode($traversalerrors)
);

// 5. Test Pre-flight Validator: Single-Choice Fraction Violation
$badmcxml = '<?xml version="1.0" encoding="UTF-8"?>
<quiz>
  <question type="multichoice">
    <name><text>Bad MC</text></name>
    <questiontext><text>Pick one</text></questiontext>
    <single>true</single>
    <answer fraction="50"><text>Choice A</text></answer>
    <answer fraction="50"><text>Choice B</text></answer>
  </question>
</quiz>';
$reportbadmc = \qbank_questionhub\preflight_validator::validate($badmcxml);
assert_test('Preflight Validator: Single-Choice Fraction Violation Detected', !$reportbadmc->is_valid());

// 6. Test Exporter and Importer on Course DEMO101 (ID 2)
$course = $DB->get_record('course', ['id' => 2]);
if ($course) {
    try {
        $exporter = new \qbank_questionhub\package_exporter(2);
        $context = \qbank_questionhub\package_exporter::resolve_course_question_context($course);
        $cats = $DB->get_records('question_categories', ['contextid' => $context->id], 'id ASC');
        $testcat = null;
        foreach ($cats as $c) {
            $count = $DB->count_records('question_bank_entries', ['questioncategoryid' => $c->id]);
            if ($c->name !== 'top' && $count > 0) {
                $testcat = $c;
                break;
            }
        }
        if ($testcat) {
            $exportedxml = $exporter->export_category_xml($testcat->id);
            assert_test(
                'Package Exporter: Category Export to XML',
                str_contains($exportedxml, '<quiz>') && str_contains($exportedxml, '$course$')
            );

            // Test importing into new category.
            $importer = new \qbank_questionhub\package_importer(2);
            $importresult = $importer->import_package($exportedxml, true, ['title' => 'Self-Verification']);
            assert_test(
                'Package Importer: Import Package Roundtrip',
                $importresult->success && $importresult->questioncount > 0,
                "Imported {$importresult->questioncount} questions"
            );
        } else {
            cli_writeln(" [SKIP] No existing questions in course 2 to test exporter; will test importing directly.");
            $importer = new \qbank_questionhub\package_importer(2);
            $importresult = $importer->import_package($validxml, true, ['title' => 'Sample Question']);
            assert_test(
                'Package Importer: Import Valid Package',
                $importresult->success && $importresult->questioncount > 0,
                "Imported {$importresult->questioncount} questions"
            );
        }

        // 6b. Test Single-Question Export (No Category Leakage)
        $sampleq = $DB->get_records('question', null, 'id ASC', '*', 0, 1);
        $firstq = reset($sampleq);
        if ($firstq) {
            $singlexml = $exporter->export_questions_xml([(int)$firstq->id]);
            $hasquiz = str_contains($singlexml, '<quiz>');
            $hascourse = str_contains($singlexml, '$course$');
            preg_match_all('/<question type="(?!category)[^"]+"/', $singlexml, $singlematches);
            $singlecount = count($singlematches[0]);
            assert_test(
                'Package Exporter: Single-Question Export (No Leakage)',
                $hasquiz && $hascourse && ($singlecount === 1),
                "Exported {$singlecount} question(s), length: " . strlen($singlexml)
            );
        } else {
            cli_writeln(" [SKIP] No questions found in database for single-question export test");
        }
    } catch (\Throwable $t) {
        assert_test('Export/Import Roundtrip', false, $t->getMessage());
    }
} else {
    cli_writeln(" [SKIP] Course ID 2 not found");
}

cli_writeln("=== Verification Complete: {$passed} passed, {$failed} failed ===");
if ($failed > 0) {
    exit(1);
}
exit(0);
