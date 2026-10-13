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

use JsonSerializable;

/**
 * Structured diagnostic report produced by pre-flight validation.
 *
 * @package    qbank_questionhub
 * @copyright  2026 Cyril Wendl
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class diagnostic_report implements JsonSerializable {

    /** @var bool Whether package is valid (0 blocking errors). */
    protected bool $valid = true;

    /** @var array Summary statistics. */
    protected array $summary = [
        'totalQuestions' => 0,
        'totalCategories' => 0,
        'questionTypeBreakdown' => [],
        'mediaFileCount' => 0,
        'totalMediaBytes' => 0,
        'errorCount' => 0,
        'warningCount' => 0,
    ];

    /** @var array List of blocking errors. */
    protected array $errors = [];

    /** @var array List of non-blocking warnings. */
    protected array $warnings = [];

    /**
     * Adds a blocking error to the report.
     *
     * @param string $code
     * @param string $message
     * @param int|null $questionindex
     * @param string|null $questionname
     * @param array|null $details
     */
    public function add_error(string $code, string $message, ?int $questionindex = null, ?string $questionname = null, ?array $details = null): void {
        $this->valid = false;
        $this->summary['errorCount']++;
        $this->errors[] = [
            'code' => $code,
            'severity' => 'error',
            'message' => $message,
            'questionIndex' => $questionindex,
            'questionName' => $questionname,
            'details' => $details,
        ];
    }

    /**
     * Adds a non-blocking warning to the report.
     *
     * @param string $code
     * @param string $message
     * @param int|null $questionindex
     * @param string|null $questionname
     * @param array|null $details
     */
    public function add_warning(string $code, string $message, ?int $questionindex = null, ?string $questionname = null, ?array $details = null): void {
        $this->summary['warningCount']++;
        $this->warnings[] = [
            'code' => $code,
            'severity' => 'warning',
            'message' => $message,
            'questionIndex' => $questionindex,
            'questionName' => $questionname,
            'details' => $details,
        ];
    }

    public function is_valid(): bool {
        return $this->valid;
    }

    public function get_errors(): array {
        return $this->errors;
    }

    public function get_warnings(): array {
        return $this->warnings;
    }

    public function get_summary(): array {
        return $this->summary;
    }

    public function increment_category_count(): void {
        $this->summary['totalCategories']++;
    }

    public function increment_question_count(string $qtype): void {
        $this->summary['totalQuestions']++;
        if (!isset($this->summary['questionTypeBreakdown'][$qtype])) {
            $this->summary['questionTypeBreakdown'][$qtype] = 0;
        }
        $this->summary['questionTypeBreakdown'][$qtype]++;
    }

    public function add_media_file_stat(int $bytes): void {
        $this->summary['mediaFileCount']++;
        $this->summary['totalMediaBytes'] += $bytes;
    }

    public function jsonSerialize(): array {
        return [
            'valid' => $this->valid,
            'summary' => $this->summary,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
        ];
    }
}
