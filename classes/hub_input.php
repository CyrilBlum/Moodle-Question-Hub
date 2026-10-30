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
 * Strict formats of the Hub identifiers received as request parameters.
 *
 * Package IDs contain dots (ch.gym.inf.krypto) and versions plus signs, so no PARAM_* type fits them: they are read
 * with PARAM_TEXT and checked here.
 *
 * @package    qbank_questionhub
 * @copyright  2026 Cyril Blum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hub_input {
    /** @var string Package ID on the Hub. */
    private const PACKAGE_ID = '/^[A-Za-z0-9][A-Za-z0-9._-]{0,149}$/';

    /** @var string SemVer release. */
    private const VERSION = '/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.+-]+)?$/';

    /** @var string Repository as an HTTPS or SSH URL. */
    private const GITHUB_REPO = '/^[A-Za-z0-9@:\/._-]+$/';

    /** @var string Name of a new repository. */
    private const REPO_NAME = '/^[A-Za-z0-9._-]{1,100}$/';

    /**
     * Checks a package ID. An empty value stands for "no package".
     *
     * @param string $value
     * @return string The trimmed ID.
     */
    public static function package_id(string $value): string {
        return self::check($value, self::PACKAGE_ID, 'error_invalid_package_id');
    }

    /**
     * Checks a version number. An empty value stands for "no version".
     *
     * @param string $value
     * @return string The trimmed version.
     */
    public static function version(string $value): string {
        return self::check($value, self::VERSION, 'error_invalid_version');
    }

    /**
     * Checks a GitHub repository URL. An empty value unlinks the repository.
     *
     * @param string $value
     * @return string The trimmed URL.
     */
    public static function github_repo(string $value): string {
        return self::check($value, self::GITHUB_REPO, 'error_invalid_github_repo');
    }

    /**
     * Checks the name of a repository to create.
     *
     * @param string $value
     * @return string The trimmed name.
     */
    public static function repo_name(string $value): string {
        return self::check($value, self::REPO_NAME, 'error_invalid_github_repo');
    }

    /**
     * Trims a value and rejects it unless it is empty or matches the format.
     *
     * @param string $value
     * @param string $pattern
     * @param string $errorcode
     * @return string
     * @throws moodle_exception
     */
    private static function check(string $value, string $pattern, string $errorcode): string {
        $value = trim($value);
        if ($value !== '' && !preg_match($pattern, $value)) {
            throw new moodle_exception($errorcode, 'qbank_questionhub');
        }
        return $value;
    }
}
