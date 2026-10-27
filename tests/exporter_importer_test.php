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
 * @copyright  2026 Cyril Wendl
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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
}
