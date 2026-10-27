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
 * Tests for the OAuth flow start.
 *
 * @package    qbank_questionhub
 * @copyright  2026 Cyril Blum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \qbank_questionhub\auth_manager
 */
final class auth_manager_test extends \advanced_testcase {
    /**
     * Moodle sets arg_separator.output to '&amp;'; the authorize URL must still use plain '&',
     * otherwise the template escapes it again and the Hub receives "amp;redirect_uri".
     */
    public function test_authorize_url_uses_plain_ampersands(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $url = auth_manager::init_oauth_flow('https://example.org/return');
        $this->assertStringNotContainsString('&amp;', $url);
        parse_str((string)parse_url($url, PHP_URL_QUERY), $params);
        foreach (['client_id', 'redirect_uri', 'response_type', 'code_challenge', 'code_challenge_method', 'state'] as $key) {
            $this->assertArrayHasKey($key, $params);
        }
        $this->assertSame('code', $params['response_type']);
    }

    /**
     * Switching linking off disables tokens that were linked before.
     */
    public function test_linking_disabled_ignores_user_token(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        auth_manager::save_user_token(2, 'mqh_pat_test');
        $this->assertSame('mqh_pat_test', auth_manager::resolve_user_token(2));
        set_config('allowteacherlinking', 0, 'qbank_questionhub');
        $this->assertFalse(auth_manager::linking_allowed());
        $this->assertNull(auth_manager::resolve_user_token(2));
    }
}
