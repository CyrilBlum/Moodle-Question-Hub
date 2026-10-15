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
 * Questions with the same name and type but different content in one category: compare and delete selected ones.
 *
 * @package    qbank_questionhub
 * @copyright  2026 Cyril Wendl
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/question/editlib.php');

require_login();
\core_question\local\bank\helper::require_plugin_enabled('qbank_questionhub');

$courseid = required_param('courseid', PARAM_INT);
$cmid = optional_param('cmid', 0, PARAM_INT);
$bankcontextid = optional_param('bankcontextid', 0, PARAM_INT);
$categoryid = optional_param('categoryid', 0, PARAM_INT);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
if ($cmid > 0 && !$DB->record_exists('course_modules', ['id' => $cmid, 'course' => $course->id])) {
    $cmid = 0;
}
$context = \qbank_questionhub\question_bank_context::resolve($course, $bankcontextid);
require_capability('qbank/questionhub:import', $context);

$pageparams = ['courseid' => $course->id, 'bankcontextid' => $context->id];
if ($cmid > 0) {
    $pageparams['cmid'] = $cmid;
}
$returnurl = new moodle_url('/question/bank/questionhub/view.php', $pageparams);
if ($categoryid > 0) {
    $pageparams['categoryid'] = $categoryid;
}
$thispageurl = new moodle_url('/question/bank/questionhub/variants.php', $pageparams);

$PAGE->set_url($thispageurl);
if ((int)$course->id !== (int)SITEID) {
    $PAGE->set_course($course);
}
if ($cmid > 0) {
    [$module, $cm] = get_module_from_cmid($cmid);
    $PAGE->set_cm($cm, $course);
}
$PAGE->set_context($context);
$PAGE->set_title(get_string('variants_heading', 'qbank_questionhub'));
$PAGE->set_heading($course->fullname);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
    $entryids = optional_param_array('delete', [], PARAM_INT);
    $deleted = \qbank_questionhub\duplicate_manager::delete_variants($context, $entryids);
    \core\notification::success(get_string('variants_deleted', 'qbank_questionhub', $deleted));
    redirect($thispageurl);
}

$exporter = new \qbank_questionhub\package_exporter((int)$course->id, (int)$context->id);
$format = get_string('strftimedatetime', 'langconfig');
$groups = [];
foreach (\qbank_questionhub\duplicate_manager::find_variants($context, $categoryid) as $index => $group) {
    $first = $group['entries'][0];
    $firstlines = \qbank_questionhub\release_diff::question_lines($exporter, $first['questionid']);
    $files = [];
    $entries = [];
    foreach ($group['entries'] as $position => $entry) {
        $entries[] = $entry + [
            'number' => $position + 1,
            'created' => userdate($entry['timecreated'], $format),
            'modified' => userdate($entry['timemodified'], $format),
            'has_tracked' => $entry['tracked'] !== '',
            'can_delete' => !$entry['inuse'],
        ];
        if ($position > 0) {
            $files[] = \qbank_questionhub\release_diff::compare($group['name'], $firstlines,
                \qbank_questionhub\release_diff::question_lines($exporter, $entry['questionid'])) +
                ['label' => get_string('variants_diff_label', 'qbank_questionhub', $position + 1)];
        }
    }
    $files = \qbank_questionhub\release_diff::decorate($files);
    foreach ($files as &$file) {
        $file['open'] = false;
    }
    unset($file);
    $groups[] = [
        'name' => $group['name'],
        'categoryname' => format_string($group['categoryname'], true, ['context' => $context, 'escape' => false]),
        'qtype' => $group['qtype'],
        'entries' => $entries,
        'diff' => ['files' => $files],
    ];
}

$templatedata = [
    'action_url' => $thispageurl->out(false),
    'return_url' => $returnurl->out(false),
    'sesskey' => sesskey(),
    'groups' => $groups,
    'has_groups' => (bool)$groups,
    'group_count' => count($groups),
];

echo $OUTPUT->header();
$renderer = $PAGE->get_renderer('core_question', 'bank');
echo $renderer->render(new \core_question\output\qbank_action_menu($returnurl));
echo $OUTPUT->render_from_template('qbank_questionhub/variants_view', $templatedata);
echo $OUTPUT->footer();
