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

namespace qbank_questionhub\output;

use renderable;
use templatable;
use renderer_base;
use moodle_url;

/**
 * Question bank header action button renderable.
 *
 * @package    qbank_questionhub
 * @copyright  2026 Cyril Wendl
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bank_action_button implements renderable, templatable {
    /** @var moodle_url Target destination URL. */
    protected moodle_url $url;

    /** @var string Button label. */
    protected string $label;

    /** @var string CSS classes for the button. */
    protected string $classes;

    /**
     * Constructor.
     *
     * @param moodle_url $url
     * @param string $label
     * @param string $classes
     */
    public function __construct(moodle_url $url, string $label, string $classes = 'btn-outline-primary') {
        $this->url = $url;
        $this->label = $label;
        $this->classes = $classes;
    }

    /**
     * Export data for Mustache rendering.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        return [
            'url' => $this->url->out(false),
            'label' => $this->label,
            'classes' => $this->classes,
        ];
    }
}
