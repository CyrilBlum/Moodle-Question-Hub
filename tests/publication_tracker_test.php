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
 * Unit tests for publication tracker and version bumping.
 *
 * @package    qbank_questionhub
 * @copyright  2026 Cyril Wendl
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class publication_tracker_test extends \advanced_testcase {

    public function test_next_version_patch_bump(): void {
        $this->assertEquals('1.0.1', publication_tracker::next_version('1.0.0'));
        $this->assertEquals('1.2.4', publication_tracker::next_version('1.2.3'));
        $this->assertEquals('2.0.10', publication_tracker::next_version('2.0.9'));
    }

    public function test_next_version_fallback(): void {
        $this->assertEquals('1.0.1', publication_tracker::next_version(''));
        $this->assertEquals('beta.1', publication_tracker::next_version('beta'));
        $this->assertEquals('1.0.1', publication_tracker::next_version('1.0'));
    }
}
