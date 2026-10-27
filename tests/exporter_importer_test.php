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
 * Integration test verifying category export and import roundtrip.
 *
 * @package    qbank_questionhub
 * @copyright  2026 Cyril Blum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \qbank_questionhub\package_exporter
 */
final class exporter_importer_test extends \advanced_testcase {
    public function test_xml_normalization_course_token(): void {
        $input = '<quiz><question type="category"><category><text>$module$/top/Informatik</text></category></question></quiz>';
        $normalized = package_exporter::normalize_xml($input);
        $this->assertStringContainsString('$course$/top/Informatik', $normalized);
        $this->assertStringNotContainsString('$module$', $normalized);
    }

    public function test_xml_normalization_adds_top(): void {
        $input = '<quiz><question type="category"><category><text>$course$/Informatik</text></category></question></quiz>';
        $normalized = package_exporter::normalize_xml($input);
        $this->assertStringContainsString('$course$/top/Informatik', $normalized);
    }

    public function test_xml_normalization_cdata_from_any_moodle_version(): void {
        foreach (['$module$', '$cat1$', '$course$', '$system$'] as $prefix) {
            $input = "<quiz><question type=\"category\">\n    <category>\n      <text><![CDATA[{$prefix}/top/" .
                "Computer & Kodierungen/Hardware]]></text>\n    </category>\n  </question></quiz>";
            $normalized = package_exporter::normalize_xml($input);
            $this->assertStringContainsString(
                '<text><![CDATA[$course$/top/Computer & Kodierungen/Hardware]]></text>',
                $normalized,
                $prefix
            );
        }
    }

    public function test_same_question_content(): void {
        $question = static fn(string $name, string $grade, string $answer, string $extra = '') =>
            "<quiz><question type=\"multichoice\"><name><text>{$name}</text></name>" .
            "<questiontext format=\"html\"><text><![CDATA[<p>Was gilt?</p>]]></text></questiontext>" .
            "<defaultgrade>{$grade}</defaultgrade>{$extra}" .
            "<answer fraction=\"100\"><text>{$answer}</text></answer><answer fraction=\"0\"><text>B</text></answer>" .
            "</question></quiz>";
        $hub = $question('K1: Begriff', '1.0000000', 'Information und Automatik');
        // Same content from another Moodle: other name, number format and an element only one version writes.
        $this->assertTrue(package_importer::same_question_content(
            $hub,
            $question('K1 - Begriff', '1', 'Information und Automatik', '<showstandardinstruction>0</showstandardinstruction>')
        ));
        // An older local wording is a different question content.
        $this->assertFalse(package_importer::same_question_content(
            $hub,
            $question('K1: Begriff', '1', 'Internet und Mathematik')
        ));
        $this->assertFalse(package_importer::same_question_content($hub, '<quiz></quiz>'));
    }

    public function test_xml_normalization_keeps_single_top(): void {
        $this->assertSame('$course$/top', package_exporter::normalize_category_path('$cat12$/top'));
        $this->assertSame('$course$/top', package_exporter::normalize_category_path('$module$'));
        $this->assertSame('$course$/top/A', package_exporter::normalize_category_path('$course$/A'));
        $this->assertSame('$course$/top/topics', package_exporter::normalize_category_path('$course$/topics'));
        $this->assertSame('Informatik/K1', package_exporter::normalize_category_path('Informatik/K1'));
    }
}
