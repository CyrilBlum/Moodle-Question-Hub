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
 * @copyright  2026 Cyril Wendl
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

\core\session\manager::set_user(get_admin());

cli_writeln("=== qbank_questionhub Verification Suite ===");
$passed = 0;
$failed = 0;

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
            assert_test('Package Exporter: Category Export to XML', str_contains($exportedxml, '<quiz>') && str_contains($exportedxml, '$course$'));

            // Test importing into new category
            $importer = new \qbank_questionhub\package_importer(2);
            $importresult = $importer->import_package($exportedxml, true, ['title' => 'Self-Verification']);
            assert_test('Package Importer: Import Package Roundtrip', $importresult->success && $importresult->questioncount > 0, "Imported {$importresult->questioncount} questions");
        } else {
            cli_writeln(" [SKIP] No existing questions in course 2 to test exporter; will test importing directly.");
            $importer = new \qbank_questionhub\package_importer(2);
            $importresult = $importer->import_package($validxml, true, ['title' => 'Sample Question']);
            assert_test('Package Importer: Import Valid Package', $importresult->success && $importresult->questioncount > 0, "Imported {$importresult->questioncount} questions");
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
