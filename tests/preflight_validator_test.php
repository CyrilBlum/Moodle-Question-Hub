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
 * Unit tests for Pre-flight Validator and XXE defense.
 *
 * @package    qbank_questionhub
 * @copyright  2026 Cyril Blum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \qbank_questionhub\preflight_validator
 * @covers \qbank_questionhub\package_importer
 */
final class preflight_validator_test extends \advanced_testcase {
    public function test_empty_payload_rejected(): void {
        $report = preflight_validator::validate('');
        $this->assertFalse($report->is_valid());
        $this->assertEquals('ERR_EMPTY_PAYLOAD', $report->get_errors()[0]['code']);
    }

    public function test_xxe_doctype_blocked(): void {
        $xml = '<?xml version="1.0"?><!DOCTYPE quiz [<!ENTITY xxe SYSTEM "file:///etc/passwd">]><quiz></quiz>';
        $report = preflight_validator::validate($xml);
        $this->assertFalse($report->is_valid());
        $this->assertEquals('ERR_XXE_DOCTYPE_FORBIDDEN', $report->get_errors()[0]['code']);
    }

    public function test_xxe_entity_blocked(): void {
        $xml = '<?xml version="1.0"?><!ENTITY xxe "attack"><quiz></quiz>';
        $report = preflight_validator::validate($xml);
        $this->assertFalse($report->is_valid());
        $this->assertEquals('ERR_XXE_ENTITY_FORBIDDEN', $report->get_errors()[0]['code']);
    }

    public function test_valid_single_choice(): void {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<quiz>
  <question type="multichoice">
    <name><text>Question 1</text></name>
    <questiontext format="html"><text><![CDATA[<p>Capital of Switzerland?</p>]]></text></questiontext>
    <single>true</single>
    <answer fraction="100"><text>Bern</text></answer>
    <answer fraction="0"><text>Zurich</text></answer>
  </question>
</quiz>';
        $report = preflight_validator::validate($xml);
        $this->assertTrue($report->is_valid());
        $this->assertEquals(1, $report->get_summary()['totalQuestions']);
    }

    public function test_single_choice_missing_100(): void {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<quiz>
  <question type="multichoice">
    <name><text>Faulty MC</text></name>
    <questiontext format="html"><text>Prompt</text></questiontext>
    <single>true</single>
    <answer fraction="50"><text>A</text></answer>
    <answer fraction="50"><text>B</text></answer>
  </question>
</quiz>';
        $report = preflight_validator::validate($xml);
        $this->assertFalse($report->is_valid());
        $this->assertEquals('ERR_SINGLE_CHOICE_NO_100', $report->get_errors()[0]['code']);
    }

    public function test_single_choice_multiple_100(): void {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<quiz>
  <question type="multichoice">
    <name><text>Faulty MC</text></name>
    <questiontext format="html"><text>Prompt</text></questiontext>
    <single>true</single>
    <answer fraction="100"><text>A</text></answer>
    <answer fraction="100"><text>B</text></answer>
  </question>
</quiz>';
        $report = preflight_validator::validate($xml);
        $this->assertFalse($report->is_valid());
        $this->assertEquals('ERR_SINGLE_CHOICE_MULTIPLE_100', $report->get_errors()[0]['code']);
    }

    public function test_svg_malicious_script_blocked(): void {
        $malicioussvg = base64_encode('<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<quiz>
  <question type="essay">
    <name><text>Malicious SVG</text></name>
    <questiontext format="html">
      <text><![CDATA[<p>Look at image</p>]]></text>
      <file name="image.svg" encoding="base64">' . $malicioussvg . '</file>
    </questiontext>
  </question>
</quiz>';
        $report = preflight_validator::validate($xml);
        $this->assertFalse($report->is_valid());
        $this->assertEquals('ERR_SVG_MALICIOUS_SCRIPT', $report->get_errors()[0]['code']);
    }

    /**
     * LaTeX and other literal braces are not Cloze tokens and must not be reported.
     */
    public function test_cloze_ignores_latex_braces(): void {
        $text = '\\(\\begin{pmatrix} 1 & 2 \\end{pmatrix}\\) = {1:NUMERICAL:=5} und \\frac{a}{b}';
        $this->assertNull(\qbank_questionhub\preflight_validator::find_cloze_problem($text));
    }

    public function test_cloze_reports_unterminated_and_untyped_tokens(): void {
        $this->assertNotNull(\qbank_questionhub\preflight_validator::find_cloze_problem('x {1:SHORTANSWER:=offen'));
        $this->assertNotNull(\qbank_questionhub\preflight_validator::find_cloze_problem('x {1:=Antwort}'));
        $this->assertNull(\qbank_questionhub\preflight_validator::find_cloze_problem('{1:SHORTANSWER:=a\\}b~=c}'));
    }

    /**
     * Scripts and event handlers are reported, plain text with angle brackets is not.
     */
    public function test_active_content_warned(): void {
        $report = preflight_validator::validate('<?xml version="1.0" encoding="UTF-8"?>
<quiz>
  <question type="shortanswer">
    <name><text>Active</text></name>
    <questiontext format="html">
      <text><![CDATA[<p onclick="alert(1)">Text</p><script>alert(2)</script><p>a &lt; b</p>]]></text>
    </questiontext>
    <answer fraction="100" format="moodle_auto_format"><text>ab</text></answer>
  </question>
</quiz>');
        $this->assertContains('WARN_ACTIVE_CONTENT', array_column($report->get_warnings(), 'code'));
        $this->assertSame(0, preg_match(preflight_validator::ACTIVE_CONTENT_PATTERN, '<p>x < y and one = 1</p>'));
        $this->assertSame(1, preg_match(preflight_validator::ACTIVE_CONTENT_PATTERN, '<a href="javascript:alert(1)">x</a>'));
        $this->assertSame(1, preg_match(preflight_validator::ACTIVE_CONTENT_PATTERN, '<IFRAME src="https://example.org">'));
    }

    /**
     * Stripping cleans HTML texts only and leaves plain answers alone.
     */
    public function test_strip_active_content(): void {
        $this->resetAfterTest();
        $xml = package_importer::strip_active_content('<?xml version="1.0" encoding="UTF-8"?>
<quiz>
  <question type="shortanswer">
    <name><text>Active</text></name>
    <questiontext format="html">
      <text><![CDATA[<p onclick="alert(1)">Text</p><script>alert(2)</script><p>a &lt; b</p>]]></text>
    </questiontext>
    <answer fraction="100" format="moodle_auto_format"><text>a&lt;b</text></answer>
  </question>
</quiz>');
        $doc = simplexml_load_string($xml);
        $text = (string)$doc->question->questiontext->text;
        $this->assertStringNotContainsString('script', $text);
        $this->assertStringNotContainsString('onclick', $text);
        $this->assertStringContainsString('Text', $text);
        $this->assertSame('a<b', (string)$doc->question->answer->text);
    }

    /**
     * A category path without context token is reported with the literal token name.
     */
    public function test_category_missing_context_message(): void {
        $report = preflight_validator::validate('<?xml version="1.0" encoding="UTF-8"?>
<quiz>
  <question type="category">
    <category><text>top/Informatik</text></category>
  </question>
</quiz>');
        $warnings = array_values(array_filter(
            $report->get_warnings(),
            fn($w) => $w['code'] === 'WARN_CATEGORY_MISSING_CONTEXT'
        ));
        $this->assertCount(1, $warnings);
        $this->assertStringContainsString('($course$)', $warnings[0]['message']);
    }

    /**
     * A DOCTYPE in UTF-16 without byte order mark is refused as well.
     */
    public function test_xxe_doctype_utf16_without_bom_blocked(): void {
        $xml = '<?xml version="1.0" encoding="UTF-16"?><!DOCTYPE quiz [<!ENTITY x "y">]><quiz></quiz>';
        foreach (['UTF-16LE', 'UTF-16BE'] as $encoding) {
            $report = preflight_validator::validate(mb_convert_encoding($xml, $encoding, 'UTF-8'));
            $this->assertFalse($report->is_valid());
            $this->assertEquals('ERR_XXE_DOCTYPE_FORBIDDEN', $report->get_errors()[0]['code']);
        }
    }
}
