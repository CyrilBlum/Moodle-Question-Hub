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
 * Tests for the formats of Hub identifiers in requests.
 *
 * @package    qbank_questionhub
 * @copyright  2026 Cyril Blum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \qbank_questionhub\hub_input
 */
final class hub_input_test extends \advanced_testcase {
    public function test_valid_values_pass_unchanged(): void {
        $this->assertSame('ch.gym.inf.krypto', hub_input::package_id(' ch.gym.inf.krypto '));
        $this->assertSame('ch.gym_x-1', hub_input::package_id('ch.gym_x-1'));
        $this->assertSame('', hub_input::package_id(''));
        $this->assertSame('1.2.3', hub_input::version('1.2.3'));
        $this->assertSame('1.2.3-beta.1+build', hub_input::version('1.2.3-beta.1+build'));
        $this->assertSame('git@github.com:konto/repo.git', hub_input::github_repo('git@github.com:konto/repo.git'));
        $this->assertSame('https://github.com/konto/repo', hub_input::github_repo('https://github.com/konto/repo'));
        $this->assertSame('my.repo-1', hub_input::repo_name('my.repo-1'));
    }

    public function test_package_id_rejects_path_characters(): void {
        $this->expectException(\moodle_exception::class);
        hub_input::package_id('../../account');
    }

    public function test_version_rejects_other_formats(): void {
        $this->expectException(\moodle_exception::class);
        hub_input::version('1.2');
    }

    public function test_repository_rejects_spaces(): void {
        $this->expectException(\moodle_exception::class);
        hub_input::github_repo('github.com/a b');
    }

    public function test_repository_name_rejects_slashes(): void {
        $this->expectException(\moodle_exception::class);
        hub_input::repo_name('a/b');
    }
}
