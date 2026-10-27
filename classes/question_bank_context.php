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

namespace qbank_questionhub;

/**
 * Available question-bank contexts for a course.
 *
 * @package    qbank_questionhub
 * @copyright  2026 Cyril Blum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_bank_context {
    /**
     * Return the course bank and populated banks in its course-category ancestry.
     * Moodle 5 requires module contexts for core XML import/export.
     *
     * @param \stdClass $course
     * @return array<int, \context>
     */
    public static function available(\stdClass $course): array {
        global $DB;

        $default = package_exporter::resolve_course_question_context($course);
        $contexts = [$default->id => $default];
        if (class_exists('\\core_question\\local\\bank\\question_bank_helper')) {
            return $contexts;
        }

        $categoryid = (int)$course->category;
        while ($categoryid > 0) {
            $category = $DB->get_record('course_categories', ['id' => $categoryid], 'id,parent,name');
            if (!$category) {
                break;
            }
            $context = \context_coursecat::instance($categoryid);
            if (
                $DB->record_exists_select('question_categories', 'contextid = ? AND parent <> 0', [$context->id]) &&
                    has_capability('qbank/questionhub:view', $context)
            ) {
                $contexts[$context->id] = $context;
            }
            $categoryid = (int)$category->parent;
        }
        return $contexts;
    }

    /**
     * Resolve a selectable question-bank context of a course.
     *
     * @param \stdClass $course
     * @param int $contextid Zero selects the course bank.
     * @return \context
     */
    public static function resolve(\stdClass $course, int $contextid): \context {
        $contexts = self::available($course);
        if ($contextid === 0) {
            return reset($contexts);
        }
        if (!isset($contexts[$contextid])) {
            throw new \moodle_exception('invalidcontext');
        }
        return $contexts[$contextid];
    }
}
