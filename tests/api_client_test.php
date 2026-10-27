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
 * Unit and integration tests for API client.
 *
 * @package    qbank_questionhub
 * @copyright  2026 Cyril Blum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \qbank_questionhub\api_client
 */
final class api_client_test extends \advanced_testcase {
    public function test_health_check(): void {
        $client = new api_client();
        $health = $client->health();
        $this->assertIsArray($health);
        $this->assertEquals('ok', $health['status']);
    }

    public function test_get_taxonomy(): void {
        $client = new api_client();
        $taxonomy = $client->get_taxonomy();
        $this->assertIsArray($taxonomy);
        $this->assertArrayHasKey('subjects', $taxonomy);
        $this->assertGreaterThan(5, count($taxonomy['subjects']));
    }

    public function test_get_packages(): void {
        $client = new api_client();
        $result = $client->get_packages(['limit' => 5]);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('pagination', $result);
    }
}
