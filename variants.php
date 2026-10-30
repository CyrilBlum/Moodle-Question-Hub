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
 * @copyright  2026 Cyril Blum
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

if (data_submitted()) {
    require_sesskey();
    // One choice per group: the entry ID to keep, "m" + entry ID to let a used local question take over the
    // Hub question, or 0 to keep all questions of the group.
    $choices = optional_param_array('keep', [], PARAM_ALPHANUM);
    $result = \qbank_questionhub\duplicate_manager::apply_variants($context, $course, $choices);
    \core\notification::success(get_string('variants_result', 'qbank_questionhub', (object)$result));
    redirect($thispageurl);
}

$exporter = new \qbank_questionhub\package_exporter((int)$course->id, (int)$context->id);
$format = get_string('strftimedatetime', 'langconfig');
// Question bank pages need the mod_qbank cmid on Moodle 5.0 and the course ID on 4.5 (course context).
$bankparams = (int)$context->contextlevel === CONTEXT_MODULE ? ['cmid' => $context->instanceid] :
    ['courseid' => $course->id];
$returnlocal = $thispageurl->out_as_local_url(false);
$groups = [];
foreach (\qbank_questionhub\duplicate_manager::find_variants($context, $categoryid) as $index => $group) {
    $first = $group['entries'][0];
    $firstlines = \qbank_questionhub\release_diff::question_lines($exporter, $first['questionid']);
    $files = [];
    $entries = [];
    $tracked = array_filter($group['entries'], static fn($entry) => $entry['tracked'] !== '');
    $mergesource = \qbank_questionhub\duplicate_manager::merge_source($group);
    // How a question is named in the diff legend: number, origin, creation date and ID.
    $side = static fn(array $entry, int $position) => get_string('variants_side', 'qbank_questionhub', (object)[
        'number' => $position + 1,
        'origin' => $entry['tracked'] !== '' ? $entry['tracked'] : get_string('variants_local', 'qbank_questionhub'),
        'created' => userdate($entry['timecreated'], $format),
        'id' => $entry['questionid'],
    ]);
    foreach ($group['entries'] as $position => $entry) {
        $entries[] = $entry + [
            'number' => $position + 1,
            'groupindex' => $index,
            // Suggestion of the helper button: the only question that came from a Hub package.
            'is_hub_choice' => count($tracked) === 1 && $entry['tracked'] !== '',
            // A used local question can take over the Hub question as a new version.
            'can_merge' => $mergesource && $entry['inuse'] && $entry['tracked'] === '',
            'is_merge_source' => $mergesource && $mergesource['entryid'] === $entry['entryid'],
            'preview_url' => (new moodle_url(
                '/question/bank/previewquestion/preview.php',
                ['id' => $entry['questionid']] + $bankparams
            ))->out(false),
            'edit_url' => (new moodle_url(
                '/question/bank/editquestion/question.php',
                ['id' => $entry['questionid'], 'returnurl' => $returnlocal] + $bankparams
            ))->out(false),
            'created' => userdate($entry['timecreated'], $format),
            'modified' => userdate($entry['timemodified'], $format),
            'has_tracked' => $entry['tracked'] !== '',
        ];
        if ($position > 0) {
            $files[] = \qbank_questionhub\release_diff::label_sides([
                \qbank_questionhub\release_diff::compare(
                    $group['name'],
                    $firstlines,
                    \qbank_questionhub\release_diff::question_lines($exporter, $entry['questionid'])
                ),
            ], $side($first, 0), $side($entry, $position))[0];
        }
    }
    $files = \qbank_questionhub\release_diff::decorate($files);
    foreach ($files as &$file) {
        $file['open'] = false;
    }
    unset($file);
    $groups[] = [
        'groupindex' => $index,
        'name' => $group['name'],
        'categoryname' => format_string($group['categoryname'], true, ['context' => $context, 'escape' => false]),
        'category_url' => (new moodle_url(
            '/question/edit.php',
            ['cat' => $group['categoryid'] . ',' . $context->id] + $bankparams
        ))->out(false),
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
$PAGE->requires->js_call_amd('qbank_questionhub/diffs', 'init');
$PAGE->requires->js_call_amd('qbank_questionhub/variants', 'init');
echo $OUTPUT->render_from_template('qbank_questionhub/variants_view', $templatedata);
echo $OUTPUT->footer();
