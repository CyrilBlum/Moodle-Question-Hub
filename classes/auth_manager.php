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

use stdClass;
use moodle_url;

/**
 * Authentication and token management service for Question Hub accounts.
 *
 * Implements two-tier token resolution (User OAuth PKCE > Institutional PAT fallback).
 *
 * @package    qbank_questionhub
 * @copyright  2026 Cyril Wendl
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class auth_manager {

    /**
     * Resolves the active access token for API requests.
     *
     * @param int|null $userid Specific user ID or null for currently logged-in user.
     * @return string|null Active bearer token or null if unauthenticated.
     */
    public static function resolve_active_token(?int $userid = null): ?string {
        $usertoken = self::resolve_user_token($userid);
        if ($usertoken !== null) {
            return $usertoken;
        }

        // Fallback to institutional Personal Access Token from plugin settings.
        $institutiontoken = get_config('qbank_questionhub', 'institutiontoken');
        if (!empty($institutiontoken)) {
            return trim($institutiontoken);
        }

        return null;
    }

    /** Resolves only the current Moodle user's Hub token, without an institutional fallback. */
    public static function resolve_user_token(?int $userid = null): ?string {
        global $USER, $DB;

        $targetuserid = $userid !== null ? $userid : (!empty($USER->id) ? (int)$USER->id : 0);
        if ($targetuserid <= 0) {
            return null;
        }
        $record = $DB->get_record('qbank_questionhub_tokens', ['userid' => $targetuserid]);
        if ($record && !empty($record->access_token) && ($record->expires_at == 0 || $record->expires_at > time())) {
            return $record->access_token;
        }
        return null;
    }

    /**
     * Retrieves token record for a given user.
     *
     * @param int $userid
     * @return stdClass|null
     */
    public static function get_user_token_record(int $userid): ?stdClass {
        global $DB;
        $record = $DB->get_record('qbank_questionhub_tokens', ['userid' => $userid]);
        return $record ?: null;
    }

    /**
     * Stores or updates a user token record.
     *
     * @param int $userid
     * @param string $accesstoken
     * @param array $userdata Optional marketplace user metadata.
     * @param int $expiresin Token lifetime in seconds (default: 30 days).
     */
    public static function save_user_token(int $userid, string $accesstoken, array $userdata = [], int $expiresin = 2592000): void {
        global $DB;

        $now = time();
        $record = $DB->get_record('qbank_questionhub_tokens', ['userid' => $userid]);

        $data = new stdClass();
        $data->userid = $userid;
        $data->access_token = $accesstoken;
        $data->token_type = 'Bearer';
        $data->expires_at = $expiresin > 0 ? ($now + $expiresin) : 0;
        $data->hub_user_id = $userdata['id'] ?? ($userdata['uuid'] ?? '');
        $data->hub_email = $userdata['email'] ?? '';
        $data->hub_name = $userdata['full_name'] ?? ($userdata['name'] ?? '');
        $data->timemodified = $now;

        if ($record) {
            $data->id = $record->id;
            $DB->update_record('qbank_questionhub_tokens', $data);
        } else {
            $data->timecreated = $now;
            $DB->insert_record('qbank_questionhub_tokens', $data);
        }
    }

    /**
     * Removes token record for a user (disconnect account).
     *
     * @param int $userid
     */
    public static function delete_user_token(int $userid): void {
        global $DB;
        $DB->delete_records('qbank_questionhub_tokens', ['userid' => $userid]);
    }

    /**
     * Initializes OAuth 2.0 PKCE flow and returns marketplace authorization URL.
     *
     * @param string $returnurl URL to return to after authorization.
     * @return string Full authorization redirect URL.
     */
    public static function init_oauth_flow(string $returnurl): string {
        global $SESSION, $CFG;

        $huburl = rtrim(get_config('qbank_questionhub', 'huburl') ?: 'https://hub.cblum.ch', '/');
        $clientid = get_config('qbank_questionhub', 'clientid') ?: 'moodle-plugin-client';
        $redirecturi = (new moodle_url('/question/bank/questionhub/oauth_callback.php'))->out(false);

        // Generate 64-char random code verifier.
        $verifierbytes = random_bytes(32);
        $codeverifier = rtrim(strtr(base64_encode($verifierbytes), '+/', '-_'), '=');

        // Generate S256 code challenge.
        $challengehash = hash('sha256', $codeverifier, true);
        $codechallenge = rtrim(strtr(base64_encode($challengehash), '+/', '-_'), '=');

        // Generate random state.
        $state = bin2hex(random_bytes(16));

        // Save PKCE parameters to user session.
        $SESSION->qbank_questionhub_oauth = [
            'code_verifier' => $codeverifier,
            'state' => $state,
            'returnurl' => $returnurl,
        ];

        $params = [
            'client_id' => $clientid,
            'redirect_uri' => $redirecturi,
            'response_type' => 'code',
            'code_challenge' => $codechallenge,
            'code_challenge_method' => 'S256',
            'state' => $state,
        ];

        // Explicit '&': Moodle sets arg_separator.output to '&amp;', which the template would escape a second time.
        return $huburl . '/oauth/authorize?' . http_build_query($params, '', '&');
    }
}
