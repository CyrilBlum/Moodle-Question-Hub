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
 * OAuth 2.0 PKCE redirect callback receiver for Question Hub.
 *
 * @package    qbank_questionhub
 * @copyright  2026 Cyril Wendl
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');

require_login();
$context = \context_system::instance();

$code = optional_param('code', '', PARAM_RAW);
$state = optional_param('state', '', PARAM_RAW);
$hubError = optional_param('error', '', PARAM_TEXT);

$oauthstate = $SESSION->qbank_questionhub_oauth ?? null;
unset($SESSION->qbank_questionhub_oauth);

$defaultreturn = new moodle_url('/question/bank/questionhub/view.php');
$returnurl = !empty($oauthstate['returnurl']) ? new moodle_url($oauthstate['returnurl']) : $defaultreturn;

if (!\qbank_questionhub\auth_manager::linking_allowed()) {
    \core\notification::error(get_string('error_linking_disabled', 'qbank_questionhub'));
    redirect($returnurl);
}

if ($hubError !== '' || $code === '') {
    \core\notification::error(get_string('error_oauth_failed', 'qbank_questionhub', s($hubError ?: 'no authorization code')));
    redirect($returnurl);
}

if (empty($oauthstate) || empty($oauthstate['code_verifier']) || $oauthstate['state'] !== $state) {
    \core\notification::error(get_string('error_unauthorized', 'qbank_questionhub'));
    redirect($returnurl);
}

try {
    $redirecturi = (new moodle_url('/question/bank/questionhub/oauth_callback.php'))->out(false);
    $clientid = get_config('qbank_questionhub', 'clientid') ?: 'moodle-plugin-client';

    // The token request must not carry the institutional token.
    $client = new \qbank_questionhub\api_client('');
    $tokenresponse = $client->exchange_oauth_code($code, $oauthstate['code_verifier'], $redirecturi, $clientid);

    if (empty($tokenresponse['access_token'])) {
        throw new moodle_exception('error_unauthorized', 'qbank_questionhub');
    }

    $userdata = $tokenresponse['user'] ?? [];
    $expiresin = (int)($tokenresponse['expires_in'] ?? 2592000);

    \qbank_questionhub\auth_manager::save_user_token(
        (int)$USER->id,
        $tokenresponse['access_token'],
        $userdata,
        $expiresin
    );

    \core\notification::success(get_string('token_saved', 'qbank_questionhub'));
} catch (\Exception $e) {
    \core\notification::error(s($e->getMessage()));
}

redirect($returnurl);
