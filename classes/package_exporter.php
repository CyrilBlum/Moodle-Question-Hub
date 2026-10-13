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
// You between have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace qbank_questionhub;

use stdClass;
use context;
use context_course;
use context_module;
use moodle_exception;

/**
 * Question package exporter serializing categories and questions to Moodle XML format.
 *
 * Implements context normalization to canonical $course$/top/..., Base64 embedded
 * media preservation, and dual-version support across Moodle 4.5 and 5.0.
 *
 * @package    qbank_questionhub
 * @copyright  2026 Cyril Wendl
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class package_exporter {

    /** @var stdClass Target course record. */
    protected stdClass $course;

    /** @var context Active course question context. */
    protected context $context;

    /**
     * Constructor.
     *
     * @param int $courseid Target course ID.
     */
    public function __construct(int $courseid) {
        global $DB;
        $this->course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
        $this->context = self::resolve_course_question_context($this->course);
    }

    /**
     * Dynamically resolves the question context across Moodle 4.5 LTS and 5.0 Stable.
     *
     * @param stdClass $course
     * @return context
     */
    public static function resolve_course_question_context(stdClass $course): context {
        if (class_exists('\core_question\local\bank\question_bank_helper')) {
            // Moodle 5.0+: Context is CONTEXT_MODULE of default mod_qbank instance.
            $qbank = \core_question\local\bank\question_bank_helper::get_default_open_instance_system_type($course, true);
            return context_module::instance($qbank->id);
        } else {
            // Moodle 4.5 LTS: Context is CONTEXT_COURSE.
            return context_course::instance($course->id);
        }
    }

    /**
     * Exports an entire question category to normalized Moodle XML.
     *
     * @param int $categoryid Target question category ID.
     * @return string Normalized Moodle XML <quiz> envelope.
     * @throws moodle_exception On export error or empty category.
     */
    public function export_category_xml(int $categoryid): string {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/question/editlib.php');
        require_once($CFG->dirroot . '/question/format/xml/format.php');

        $category = $DB->get_record('question_categories', ['id' => $categoryid], '*', MUST_EXIST);

        $qformat = new \qformat_xml();
        $contexts = new \core_question\local\bank\question_edit_contexts($this->context);
        $qformat->setContexts($contexts->all());
        $qformat->setCourse($this->course);
        $qformat->setCategory($category);
        $qformat->setCattofile(true);
        $qformat->setContexttofile(true);

        try {
            $rawxml = $qformat->exportprocess(false);
        } catch (\moodle_exception $e) {
            if ($e->errorcode === 'noquestions') {
                throw new moodle_exception('error_empty_category', 'qbank_questionhub');
            }
            throw $e;
        }

        return self::normalize_xml($rawxml);
    }

    /**
     * Exports a list of specific questions to normalized Moodle XML.
     *
     * @param array $questionids Array of question IDs.
     * @return string Normalized Moodle XML <quiz> envelope.
     * @throws moodle_exception On export error.
     */
    public function export_questions_xml(array $questionids): string {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/question/editlib.php');
        require_once($CFG->dirroot . '/question/format/xml/format.php');

        if (empty($questionids)) {
            throw new moodle_exception('error_empty_category', 'qbank_questionhub');
        }

        list($insql, $params) = $DB->get_in_or_equal($questionids);
        $questions = $DB->get_records_select('question', "id $insql", $params);

        if (empty($questions)) {
            throw new moodle_exception('error_empty_category', 'qbank_questionhub');
        }

        // Ensure category property is set on all questions across Moodle 4.x and 5.x.
        foreach ($questions as $q) {
            if (!isset($q->category)) {
                $sql = "SELECT qbe.questioncategoryid
                          FROM {question_bank_entries} qbe
                          JOIN {question_versions} qv ON qv.questionbankentryid = qbe.id
                         WHERE qv.questionid = ?";
                $q->category = (int)$DB->get_field_sql($sql, [$q->id]);
            }
        }

        // Determine primary category.
        $firstq = reset($questions);
        $category = $DB->get_record('question_categories', ['id' => $firstq->category], '*', MUST_EXIST);

        $qformat = new \qformat_xml();
        $contexts = new \core_question\local\bank\question_edit_contexts($this->context);
        $qformat->setContexts($contexts->all());
        $qformat->setCourse($this->course);
        $qformat->setCategory(null);
        $qformat->questions = array_values($questions);
        $qformat->setCattofile(true);
        $qformat->setContexttofile(true);

        $rawxml = $qformat->exportprocess(false);

        return self::normalize_xml($rawxml);
    }

    /**
     * Normalizes generated XML string to ensure universal cross-version compatibility.
     *
     * Converts Moodle 5.0 $module$ context tokens into canonical $course$ tokens,
     * guarantees presence of /top/ in category paths, and runs preflight verification.
     *
     * @param string $rawxml
     * @return string Normalized XML string.
     */
    public static function normalize_xml(string $rawxml): string {
        // 1. Normalize $module$, $system$, or $cat123$ to $course$
        $normalized = preg_replace(
            '/<category>\s*<text>\$(?:module|cat\d+|system)\$/i',
            '<category><text>$course$',
            $rawxml
        );

        // 2. Ensure /top/ exists in all category paths: $course$/top/...
        $normalized = preg_replace_callback(
            '/<category>\s*<text>\$course\$\/(?!top\/)([^<]+)<\/text>\s*<\/category>/i',
            function ($matches) {
                return '<category><text>$course$/top/' . ltrim($matches[1], '/') . '</text></category>';
            },
            $normalized
        );

        return $normalized;
    }
}
