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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Tests the saved Hub URL, the institutional token and the admin's own linked account.
 *
 * @package    qbank_questionhub
 * @copyright  2026 Cyril Blum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');

require_login();
$context = context_system::instance();
require_capability('moodle/site:config', $context);

$settingsurl = new moodle_url('/admin/settings.php', ['section' => 'qbank_questionhub']);
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/question/bank/questionhub/testconnection.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('testconnection', 'qbank_questionhub'));
$PAGE->set_heading(get_string('pluginname', 'qbank_questionhub'));

$huburl = rtrim(get_config('qbank_questionhub', 'huburl') ?: 'https://hub.cblum.ch', '/');
$results = [];

try {
    (new \qbank_questionhub\api_client('', null, 15))->health();
    $results[] = [get_string('testconnection_hub_ok', 'qbank_questionhub', s($huburl)), 'success'];
    $reachable = true;
} catch (\Exception $e) {
    $error = (object)['url' => s($huburl), 'error' => $e->getMessage()];
    $results[] = [get_string('testconnection_hub_failed', 'qbank_questionhub', $error), 'error'];
    $reachable = false;
}

if ($reachable) {
    $describe = static function (array $me): string {
        return s(trim(($me['full_name'] ?? '') . ' <' . ($me['email'] ?? '') . '>'));
    };

    $institutiontoken = trim((string)get_config('qbank_questionhub', 'institutiontoken'));
    if ($institutiontoken === '') {
        $results[] = [get_string('testconnection_token_none', 'qbank_questionhub'), 'info'];
    } else {
        try {
            $me = (new \qbank_questionhub\api_client($institutiontoken, null, 15))->get_me();
            $results[] = [get_string('testconnection_token_ok', 'qbank_questionhub', $describe($me)), 'success'];
        } catch (\Exception $e) {
            $results[] = [get_string('testconnection_token_failed', 'qbank_questionhub', $e->getMessage()), 'error'];
        }
    }

    $usertoken = \qbank_questionhub\auth_manager::resolve_user_token((int)$USER->id);
    if ($usertoken !== null) {
        try {
            $me = (new \qbank_questionhub\api_client($usertoken, null, 15))->get_me();
            $results[] = [get_string('testconnection_user_ok', 'qbank_questionhub', $describe($me)), 'success'];
        } catch (\Exception $e) {
            $results[] = [get_string('testconnection_user_failed', 'qbank_questionhub', $e->getMessage()), 'error'];
        }
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('testconnection', 'qbank_questionhub'));
foreach ($results as [$message, $type]) {
    echo $OUTPUT->notification($message, $type, false);
}
echo $OUTPUT->continue_button($settingsurl);
echo $OUTPUT->footer();
