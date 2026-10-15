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
 * Review of local changes in a category linked to a Hub package: accept or reject each changed question.
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
$categoryid = required_param('categoryid', PARAM_INT);
$cmid = optional_param('cmid', 0, PARAM_INT);
$bankcontextid = optional_param('bankcontextid', 0, PARAM_INT);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
if ($cmid > 0 && !$DB->record_exists('course_modules', ['id' => $cmid, 'course' => $course->id])) {
    $cmid = 0;
}
$category = $DB->get_record('question_categories', ['id' => $categoryid], '*', MUST_EXIST);
$context = \qbank_questionhub\question_bank_context::resolve($course, $bankcontextid ?: (int)$category->contextid);
if ((int)$category->contextid !== (int)$context->id) {
    throw new moodle_exception('invalidcategory', 'question');
}
require_capability('qbank/questionhub:import', $context);

$pageparams = ['courseid' => $course->id, 'categoryid' => $categoryid, 'bankcontextid' => $context->id];
if ($cmid > 0) {
    $pageparams['cmid'] = $cmid;
}
$thispageurl = new moodle_url('/question/bank/questionhub/review.php', $pageparams);
$returnparams = array_diff_key($pageparams, ['categoryid' => true]);
$returnurl = new moodle_url('/question/bank/questionhub/view.php', $returnparams);

$PAGE->set_url($thispageurl);
if ((int)$course->id !== (int)SITEID) {
    $PAGE->set_course($course);
}
if ($cmid > 0) {
    [$module, $cm] = get_module_from_cmid($cmid);
    $PAGE->set_cm($cm, $course);
}
$PAGE->set_context($context);
$PAGE->set_title(get_string('review_heading', 'qbank_questionhub',
    format_string($category->name, true, ['context' => $context, 'escape' => false])));
$PAGE->set_heading($course->fullname);

$history = \qbank_questionhub\local_changes::link((int)$course->id, $categoryid);
if (!$history) {
    throw new moodle_exception('error_review_not_linked', 'qbank_questionhub', $returnurl);
}
$issource = \qbank_questionhub\local_changes::is_source($history);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
    $action = required_param('action', PARAM_ALPHAEXT);
    $entryids = $action === 'accept_all' ? array_map('intval', optional_param_array('entryids', [], PARAM_INT)) :
        [required_param('entryid', PARAM_INT)];
    $done = 0;
    try {
        foreach ($entryids as $entryid) {
            if ($action === 'reject') {
                $result = \qbank_questionhub\local_changes::reject($history, $entryid, $context, $course);
                \core\notification::success(get_string('review_' . $result, 'qbank_questionhub'));
            } else if ($action === 'accept' || $action === 'accept_all') {
                \qbank_questionhub\local_changes::accept($history, $entryid);
                $done++;
            }
        }
        if ($done) {
            \core\notification::success(get_string('review_accepted', 'qbank_questionhub', $done));
        }
    } catch (moodle_exception $e) {
        \core\notification::error($e->getMessage());
    }
    redirect($thispageurl);
}

$exporter = new \qbank_questionhub\package_exporter((int)$course->id, (int)$context->id);
$diff = \qbank_questionhub\local_changes::files($history, $exporter);
$items = [];
$files = \qbank_questionhub\release_diff::label_sides($diff['files'] ?? [],
    get_string('diff_side_synced', 'qbank_questionhub', $history->package_version),
    get_string('diff_side_current', 'qbank_questionhub'));
foreach ($files as $file) {
    $items[] = [
        'entryid' => $file['entryid'],
        'name' => $file['name'],
        // A removed question cannot be restored here: its versions are gone with it.
        'can_accept' => !$issource && $file['status'] !== 'removed',
        'can_reject' => $file['status'] !== 'removed',
        'reject_confirm' => get_string($file['status'] === 'added' ? 'review_reject_added_confirm' :
            'review_reject_confirm', 'qbank_questionhub', $file['name']),
        'diff' => ['files' => [$file]],
    ];
}

$templatedata = [
    'action_url' => $thispageurl->out(false),
    'return_url' => $returnurl->out(false),
    'sesskey' => sesskey(),
    'category_name' => format_string($category->name, true, ['context' => $context, 'escape' => false]),
    'package_id' => $history->package_id,
    'package_version' => $history->package_version,
    'is_source' => $issource,
    'is_copy' => !$issource,
    'unknown' => $diff === null,
    'no_changes' => $diff !== null && !$items,
    'same_content' => $diff['same_content'] ?? 0,
    'items' => $items,
    'has_items' => (bool)$items,
    'item_count' => count($items),
    'has_many' => count($items) > 1,
    'can_accept_all' => !$issource && count(array_filter($items, static fn($item) => $item['can_accept'])) > 1,
    'accept_all_ids' => array_values(array_map(static fn($item) => ['id' => $item['entryid']],
        array_filter($items, static fn($item) => $item['can_accept']))),
    'push_url' => '',
];
if ($issource) {
    $pushparams = $returnparams + ['categoryid' => $categoryid, 'updatepackageid' => $history->package_id];
    $templatedata['push_url'] = (new moodle_url('/question/bank/questionhub/publish.php', $pushparams))->out(false);
}

echo $OUTPUT->header();
$renderer = $PAGE->get_renderer('core_question', 'bank');
echo $renderer->render(new \core_question\output\qbank_action_menu($returnurl));
echo $OUTPUT->render_from_template('qbank_questionhub/review_view', $templatedata);
echo $OUTPUT->footer();
