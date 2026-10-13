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
 * Result data container from package import execution.
 *
 * @package    qbank_questionhub
 * @copyright  2026 Cyril Wendl
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class import_result {

    /** @var bool Whether import succeeded. */
    public bool $success;

    /** @var array Array of imported question IDs. */
    public array $questionids;

    /** @var int Total number of imported questions. */
    public int $questioncount;

    /** @var array Non-blocking warnings encountered. */
    public array $warnings;

    /**
     * Constructor.
     *
     * @param bool $success
     * @param array $questionids
     * @param array $warnings
     */
    public function __construct(bool $success, array $questionids = [], array $warnings = []) {
        $this->success = $success;
        $this->questionids = $questionids;
        $this->questioncount = count($questionids);
        $this->warnings = $warnings;
    }
}
