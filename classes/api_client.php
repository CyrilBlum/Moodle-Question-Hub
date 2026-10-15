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

use moodle_exception;

/**
 * REST API client communicating with the centralized Question Hub marketplace.
 *
 * Uses Moodle's native \curl wrapper to respect enterprise proxy configurations,
 * system CA bundles, and timeout policies.
 *
 * @package    qbank_questionhub
 * @copyright  2026 Cyril Wendl
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class api_client {

    /** @var string Base URL of Question Hub marketplace. */
    protected string $baseurl;

    /** @var string|null Active Bearer authentication token. */
    protected ?string $token;

    /** @var int Timeout in seconds. */
    protected int $timeout;

    /**
     * Constructor.
     *
     * @param string|null $token Explicit Bearer token or null to resolve automatically.
     * @param string|null $baseurl Explicit base URL or null to load from plugin settings.
     * @param int $timeout HTTP timeout in seconds.
     * @param bool $userauthonly Use a user-linked token only, never the institutional fallback.
     */
    public function __construct(?string $token = null, ?string $baseurl = null, int $timeout = 30, bool $userauthonly = false) {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $configuredurl = get_config('qbank_questionhub', 'huburl');
        $this->baseurl = rtrim($baseurl ?: ($configuredurl ?: 'https://hub.cblum.ch'), '/');
        $this->token = $token !== null ? $token : ($userauthonly
            ? auth_manager::resolve_user_token()
            : auth_manager::resolve_active_token());
        $this->timeout = $timeout;
    }

    /**
     * Initializes a fresh \curl client instance with standard options.
     *
     * @return \curl
     */
    protected function get_curl(): \curl {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $curl = new \curl([
            'CONNECTTIMEOUT' => 10,
            'TIMEOUT' => $this->timeout,
        ]);
        return $curl;
    }

    /**
     * Builds default request headers.
     *
     * @param bool $jsoncontent Whether request body is JSON.
     * @return array
     */
    protected function get_headers(bool $jsoncontent = false): array {
        $headers = [
            'Accept: application/json',
            'User-Agent: Moodle-qbank_questionhub/1.0.0',
        ];

        if ($jsoncontent) {
            $headers[] = 'Content-Type: application/json; charset=utf-8';
        }

        if (!empty($this->token)) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }

        return $headers;
    }

    /**
     * Checks health and connectivity of the marketplace service.
     *
     * @return array Health status payload.
     * @throws moodle_exception On connection error.
     */
    public function health(): array {
        return $this->get('/api/v1/health', [], false);
    }

    /**
     * Fetches the canonical Swiss Gymnasium curriculum taxonomy.
     *
     * @return array Taxonomy catalog with subjects, levels, topics, cantons.
     * @throws moodle_exception On error.
     */
    public function get_taxonomy(): array {
        return $this->get('/api/v1/taxonomy', [], false);
    }

    /**
     * Searches and filters question packages.
     *
     * @param array $filters Query parameters (q, subject, level, canton, qtype, page, limit).
     * @return array Package search results with pagination.
     * @throws moodle_exception On error.
     */
    public function get_packages(array $filters = []): array {
        return $this->get('/api/v1/packages', $filters, false);
    }

    /**
     * Fetches full metadata and release history for a specific package.
     *
     * @param string $id Package identifier (e.g. ch.gym.inf.trees).
     * @return array Package record.
     * @throws moodle_exception On error.
     */
    public function get_package(string $id): array {
        return $this->get('/api/v1/packages/' . rawurlencode($id), [], false);
    }

    /**
     * Fetches sanitized question previews without exposing solutions or answer keys.
     *
     * @param string $id Package identifier.
     * @param string|null $version Specific release version or null for latest.
     * @return array Sanitized preview questions and categories.
     * @throws moodle_exception On error.
     */
    public function get_preview(string $id, ?string $version = null): array {
        $params = [];
        if (!empty($version)) {
            $params['version'] = $version;
        }
        return $this->get('/api/v1/packages/' . rawurlencode($id) . '/preview', $params, false);
    }

    /**
     * Downloads raw Moodle XML question package.
     *
     * @param string $id Package identifier.
     * @param string $version Semantic version to download.
     * @return string Raw Moodle XML string.
     * @throws moodle_exception On error.
     */
    public function download_release(string $id, string $version): string {
        $url = $this->baseurl . '/api/v1/packages/' . rawurlencode($id) . '/releases/' . rawurlencode($version) . '/download';
        $curl = $this->get_curl();

        $headers = [
            'Accept: application/xml, text/xml',
            'User-Agent: Moodle-qbank_questionhub/1.0.0',
        ];
        if (!empty($this->token)) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }
        $curl->setHeader($headers);

        $response = $curl->get($url);
        $info = $curl->get_info();
        $httpcode = (int)($info['http_code'] ?? 0);

        if ($curl->get_errno() !== 0) {
            throw new moodle_exception('error_network', 'qbank_questionhub', '', $curl->error);
        }

        if ($httpcode >= 400) {
            $this->handle_http_error($httpcode, $response);
        }

        return $response;
    }

    /**
     * Sends XML and metadata to marketplace preflight validation endpoint.
     *
     * @param string $xml Raw Moodle XML string.
     * @param array $metadata Optional package metadata.
     * @return array Structured DiagnosticReport.
     * @throws moodle_exception On error.
     */
    public function preflight(string $xml, array $metadata = []): array {
        $payload = [
            'xml' => $xml,
        ];
        if (!empty($metadata)) {
            $payload['metadata'] = $metadata;
        }
        return $this->post('/api/v1/packages/preflight', $payload, true);
    }

    /**
     * Publishes a new question package release to the marketplace.
     *
     * @param string $xml Raw Moodle XML string.
     * @param array $metadata Complete package manifest metadata.
     * @return array Published release record including SHA-256 and version.
     * @throws moodle_exception On error.
     */
    public function publish(string $xml, array $metadata): array {
        $payload = [
            'xml' => $xml,
            'metadata' => $metadata,
        ];
        // Whole categories with embedded media can be large; allow time for upload and validation.
        $this->timeout = max($this->timeout, 180);
        return $this->post('/api/v1/packages', $payload, true);
    }

    /**
     * Lists the packages owned by the linked Hub account.
     *
     * @return array List of { id, title, latest_version, versions, preview_enabled, downloads_count }.
     */
    public function get_my_packages(): array {
        $result = $this->get('/api/v1/account/packages', [], true);
        return $result['data'] ?? [];
    }

    /**
     * Edits package settings on the Hub (title, summary, license, grades, tags, preview).
     * The Hub is the single source of truth, so the web dashboard shows the change immediately.
     *
     * @param string $id Package id.
     * @param array $patch Fields to change.
     * @return array The updated package; `github_sync` holds the first GitHub commit of a newly linked repository.
     * @throws moodle_exception On error.
     */
    public function update_package(string $id, array $patch): array {
        if (empty($this->token)) {
            throw new moodle_exception('error_unauthorized', 'qbank_questionhub');
        }
        $curl = $this->get_curl();
        $curl->setHeader($this->get_headers(true));
        $response = $curl->patch($this->baseurl . '/api/v1/packages/' . rawurlencode($id), json_encode($patch));
        $httpcode = (int)($curl->get_info()['http_code'] ?? 0);
        if ($curl->get_errno() !== 0) {
            throw new moodle_exception('error_network', 'qbank_questionhub', '', $curl->error);
        }
        if ($httpcode >= 400) {
            $this->handle_http_error($httpcode, (string)$response);
        }
        $decoded = json_decode((string)$response, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Shows the outcome of the Hub's GitHub commit (publish response `github`, settings response `github_sync`).
     * Nothing is shown when the package has no linked repository (null).
     *
     * @param array|null $result { status: ok, commit_url, warning, repo_created? } or { status: error, message }.
     */
    public static function notify_github_result(?array $result): void {
        if (empty($result['status'])) {
            return;
        }
        if ($result['status'] === 'ok') {
            if (!empty($result['repo_created'])) {
                \core\notification::info(get_string('github_repo_created', 'qbank_questionhub'));
            }
            \core\notification::info(get_string('github_commit_done', 'qbank_questionhub', s($result['commit_url'] ?? '')));
            if (!empty($result['warning'])) {
                \core\notification::warning(s($result['warning']));
            }
        } else {
            \core\notification::warning(get_string('github_commit_failed', 'qbank_questionhub', s($result['message'] ?? '')));
        }
    }

    /**
     * Withdraws a whole package (all versions) from the Hub.
     *
     * @param string $id Package id.
     */
    public function withdraw_package(string $id): void {
        $this->delete('/api/v1/packages/' . rawurlencode($id));
    }

    /**
     * Withdraws a single release of a package.
     *
     * @param string $id Package id.
     * @param string $version SemVer version.
     */
    public function withdraw_release(string $id, string $version): void {
        $this->delete('/api/v1/packages/' . rawurlencode($id) . '/releases/' . rawurlencode($version));
    }

    /**
     * Exchanges OAuth 2.0 PKCE authorization code for an access token.
     *
     * @param string $code Authorization code.
     * @param string $codeverifier PKCE code verifier.
     * @param string $redirecturi Registered redirect URI.
     * @param string $clientid Client ID.
     * @return array Token response { access_token, token_type, expires_in, user }.
     * @throws moodle_exception On error.
     */
    public function exchange_oauth_code(string $code, string $codeverifier, string $redirecturi, string $clientid = 'moodle-plugin-client'): array {
        $payload = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $clientid,
            'redirect_uri' => $redirecturi,
            'code_verifier' => $codeverifier,
        ];
        return $this->post('/oauth/token', $payload, false);
    }

    /**
     * Generic GET request handler.
     *
     * @param string $endpoint API endpoint path.
     * @param array $params Query string parameters.
     * @param bool $requireauth Whether authentication is mandatory.
     * @return array Decoded JSON response.
     * @throws moodle_exception On error.
     */
    protected function get(string $endpoint, array $params = [], bool $requireauth = false): array {
        if ($requireauth && empty($this->token)) {
            throw new moodle_exception('error_unauthorized', 'qbank_questionhub');
        }

        $url = $this->baseurl . $endpoint;
        if (!empty($params)) {
            // Explicit '&': Moodle sets arg_separator.output to '&amp;', which would garble a URL sent to the Hub.
            $url .= '?' . http_build_query($params, '', '&');
        }

        $curl = $this->get_curl();
        $curl->setHeader($this->get_headers(false));

        $response = $curl->get($url);
        $info = $curl->get_info();
        $httpcode = (int)($info['http_code'] ?? 0);

        if ($curl->get_errno() !== 0) {
            throw new moodle_exception('error_network', 'qbank_questionhub', '', $curl->error);
        }

        if ($httpcode >= 400) {
            $this->handle_http_error($httpcode, $response);
        }

        $decoded = json_decode($response, true);
        if ($decoded === null && !empty($response)) {
            throw new moodle_exception('error_network', 'qbank_questionhub', '', 'Invalid JSON response from Hub: ' . substr($response, 0, 200));
        }

        return $decoded ?? [];
    }

    /**
     * Generic POST request handler.
     *
     * @param string $endpoint API endpoint path.
     * @param array $data JSON payload data.
     * @param bool $requireauth Whether authentication is mandatory.
     * @return array Decoded JSON response.
     * @throws moodle_exception On error.
     */
    protected function post(string $endpoint, array $data, bool $requireauth = false): array {
        if ($requireauth && empty($this->token)) {
            throw new moodle_exception('error_unauthorized', 'qbank_questionhub');
        }

        $url = $this->baseurl . $endpoint;
        $curl = $this->get_curl();
        $curl->setHeader($this->get_headers(true));

        $jsonpayload = json_encode($data);
        $response = $curl->post($url, $jsonpayload);
        $info = $curl->get_info();
        $httpcode = (int)($info['http_code'] ?? 0);

        if ($curl->get_errno() !== 0) {
            throw new moodle_exception('error_network', 'qbank_questionhub', '', $curl->error);
        }

        if ($httpcode >= 400) {
            $this->handle_http_error($httpcode, $response);
        }

        $decoded = json_decode($response, true);
        if ($decoded === null && !empty($response)) {
            throw new moodle_exception('error_network', 'qbank_questionhub', '', 'Invalid JSON response from Hub: ' . substr($response, 0, 200));
        }

        return $decoded ?? [];
    }

    /**
     * Generic DELETE request handler (authenticated).
     *
     * @param string $endpoint API endpoint path.
     * @throws moodle_exception On error.
     */
    protected function delete(string $endpoint): void {
        if (empty($this->token)) {
            throw new moodle_exception('error_unauthorized', 'qbank_questionhub');
        }
        $curl = $this->get_curl();
        $curl->setHeader($this->get_headers(false));
        $response = $curl->delete($this->baseurl . $endpoint);
        $httpcode = (int)($curl->get_info()['http_code'] ?? 0);

        if ($curl->get_errno() !== 0) {
            throw new moodle_exception('error_network', 'qbank_questionhub', '', $curl->error);
        }
        if ($httpcode >= 400) {
            $this->handle_http_error($httpcode, (string)$response);
        }
    }

    /**
     * Parses HTTP error responses into meaningful Moodle exceptions.
     *
     * @param int $httpcode HTTP status code.
     * @param string $body Raw response body.
     * @throws moodle_exception
     */
    protected function handle_http_error(int $httpcode, string $body): void {
        $message = '';
        $decoded = json_decode($body, true);
        if (is_array($decoded)) {
            $message = $decoded['message'] ?? ($decoded['error_description'] ?? ($decoded['error'] ?? ''));
            if (!empty($decoded['diagnostics'])) {
                $message .= ' ' . json_encode($decoded['diagnostics']);
            }
        }

        switch ($httpcode) {
            case 401:
                throw new moodle_exception('error_unauthorized', 'qbank_questionhub', '', $message);
            case 403:
                throw new moodle_exception('error_forbidden', 'qbank_questionhub', '', $message);
            case 404:
                throw new moodle_exception('error_notfound', 'qbank_questionhub', '', $message);
            case 410:
                throw new moodle_exception('error_package_withdrawn', 'qbank_questionhub', '', $message);
            default:
                $detail = !empty($message) ? $message : ("HTTP {$httpcode}: " . substr($body, 0, 300));
                throw new moodle_exception('error_network', 'qbank_questionhub', '', $detail);
        }
    }
}
