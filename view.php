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
 * Question Hub marketplace browser view page.
 *
 * @package    qbank_questionhub
 * @copyright  2026 Cyril Wendl
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/question/editlib.php');

require_login();
\core_question\local\bank\helper::require_plugin_enabled('qbank_questionhub');

// Resolve cmid / courseid fallback before question_edit_setup().
// In Moodle 5.0, question_edit_setup() unconditionally requires cmid.
// When navigating with courseid only (e.g. from tertiary menu or direct link),
// resolve the default mod_qbank cmid for that course.
if (empty($_GET['cmid']) && empty($_POST['cmid'])) {
    $courseid = optional_param('courseid', 0, PARAM_INT);
    if ($courseid <= 0 && !empty($COURSE->id)) {
        $courseid = (int)$COURSE->id;
        $_GET['courseid'] = $courseid;
    }
    if ($courseid > 0 && class_exists('\core_question\local\bank\question_bank_helper')) {
        $course = $DB->get_record('course', ['id' => $courseid]);
        if ($course) {
            $qbank = \core_question\local\bank\question_bank_helper::get_default_open_instance_system_type($course, true);
            if ($qbank) {
                $_GET['cmid'] = (int)$qbank->id;
            }
        }
    }
}

list($thispageurl, $contexts, $cmid, $cm, $module, $pagevars) =
        question_edit_setup('questions', '/question/bank/questionhub/view.php');

$coursecontext = $contexts->lowest();
require_capability('qbank/questionhub:view', $coursecontext);

$PAGE->set_url($thispageurl);
$PAGE->set_title(get_string('pluginname', 'qbank_questionhub'));
$PAGE->set_heading($COURSE->fullname);

// Handle POST actions (Save PAT, Import Package)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
    $action = optional_param('action', '', PARAM_ALPHAEXT);

    if ($action === 'save_pat') {
        $pattoken = required_param('pat_token', PARAM_RAW_TRIMMED);
        if (!empty($pattoken)) {
            \qbank_questionhub\auth_manager::save_user_token(
                (int)$USER->id,
                $pattoken,
                ['full_name' => fullname($USER), 'email' => $USER->email]
            );
            \core\notification::success(get_string('token_saved', 'qbank_questionhub'));
        }
        redirect($thispageurl);
    } else if ($action === 'import_package') {
        require_capability('qbank/questionhub:import', $coursecontext);
        $packageid = required_param('packageid', PARAM_RAW_TRIMMED);
        $version = required_param('version', PARAM_RAW_TRIMMED);
        $title = optional_param('title', 'Imported Package', PARAM_TEXT);
        $targetcatid = optional_param('targetcategoryid', 0, PARAM_INT);
        $preservehierarchy = (bool)optional_param('preservehierarchy', 1, PARAM_BOOL);

        try {
            $client = new \qbank_questionhub\api_client();
            $xmlcontent = $client->download_release($packageid, $version);

            $importer = new \qbank_questionhub\package_importer(
                (int)$COURSE->id,
                $targetcatid > 0 ? $targetcatid : null
            );

            $result = $importer->import_package(
                $xmlcontent,
                $preservehierarchy,
                ['id' => $packageid, 'version' => $version, 'title' => $title]
            );

            \core\notification::success(get_string('import_success', 'qbank_questionhub', $result->questioncount));
        } catch (\Exception $e) {
            \core\notification::error($e->getMessage());
        }

        redirect($thispageurl);
    }
}

// Prepare filter parameters
$query = optional_param('q', '', PARAM_TEXT);
$subject = optional_param('subject', '', PARAM_TEXT);
$level = optional_param('level', '', PARAM_ALPHA);
$canton = optional_param('canton', '', PARAM_ALPHA);
$page = optional_param('page', 1, PARAM_INT);
if ($page < 1) {
    $page = 1;
}

$filters = [
    'page' => $page,
    'limit' => 12,
];
if ($query !== '') {
    $filters['q'] = $query;
}
if ($subject !== '') {
    $filters['subject'] = $subject;
}
if ($level !== '') {
    $filters['level'] = $level;
}
if ($canton !== '') {
    $filters['canton'] = $canton;
}

// Fetch taxonomy and packages from marketplace
$client = new \qbank_questionhub\api_client();
$taxonomy = [];
$packageresult = ['packages' => [], 'pagination' => ['page' => 1, 'limit' => 12, 'total' => 0, 'total_pages' => 1]];

try {
    $taxonomy = $client->get_taxonomy();
} catch (\Exception $e) {
    // Fallback default taxonomy if offline
    $taxonomy = [
        'subjects' => [
            ['id' => 'informatik', 'name_de' => 'Informatik'],
            ['id' => 'mathematik', 'name_de' => 'Mathematik'],
            ['id' => 'physik', 'name_de' => 'Physik'],
            ['id' => 'biologie', 'name_de' => 'Biologie'],
            ['id' => 'chemie', 'name_de' => 'Chemie'],
            ['id' => 'deutsch', 'name_de' => 'Deutsch'],
            ['id' => 'franzoesisch', 'name_de' => 'Französisch'],
            ['id' => 'englisch', 'name_de' => 'Englisch'],
            ['id' => 'geschichte', 'name_de' => 'Geschichte'],
            ['id' => 'geografie', 'name_de' => 'Geografie'],
        ],
        'cantons' => [
            ['code' => 'AG'], ['code' => 'BE'], ['code' => 'BS'], ['code' => 'BL'],
            ['code' => 'LU'], ['code' => 'SG'], ['code' => 'SO'], ['code' => 'TG'],
            ['code' => 'ZH'],
        ],
    ];
}

try {
    $packageresult = $client->get_packages($filters);
} catch (\Exception $e) {
    \core\notification::warning(get_string('error_network', 'qbank_questionhub', $e->getMessage()));
}

// Format subjects for filter dropdown
$subjectsdata = [];
foreach ($taxonomy['subjects'] ?? [] as $sub) {
    $subjectsdata[] = [
        'id' => $sub['id'] ?? $sub['name_de'],
        'name_de' => $sub['name_de'],
        'selected' => ($subject === ($sub['id'] ?? '') || $subject === ($sub['name_de'] ?? '')),
    ];
}

// Format cantons for filter dropdown
$cantonsdata = [];
$cantonlist = $taxonomy['cantons'] ?? [
    ['code' => 'AG'], ['code' => 'BE'], ['code' => 'BS'], ['code' => 'LU'],
    ['code' => 'SG'], ['code' => 'ZH'],
];
foreach ($cantonlist as $c) {
    $cantonsdata[] = [
        'code' => $c['code'],
        'selected' => ($canton === $c['code']),
    ];
}

// Format package cards data
$packagesdata = [];
foreach ($packageresult['packages'] ?? [] as $pkg) {
    $author = '';
    if (!empty($pkg['author'])) {
        $parts = [];
        if (!empty($pkg['author']['name'])) {
            $parts[] = $pkg['author']['name'];
        }
        if (!empty($pkg['author']['institution'])) {
            $parts[] = $pkg['author']['institution'];
        }
        $author = implode(' · ', $parts);
    }

    $packagesdata[] = [
        'id' => $pkg['id'],
        'title' => $pkg['title'],
        'summary' => $pkg['summary'] ?? '',
        'latest_version' => $pkg['latest_version'] ?? '1.0.0',
        'license' => $pkg['license'] ?? 'CC-BY-4.0',
        'subject_badge' => $pkg['taxonomy']['subject'] ?? 'Allgemein',
        'canton_badge' => $pkg['taxonomy']['canton'] ?? '',
        'level_badge' => $pkg['taxonomy']['subject_category'] ?? ($pkg['taxonomy']['level'] ?? ''),
        'stats' => $pkg['stats'] ?? ['question_count' => 0],
        'author_info' => $author,
    ];
}

// Get user account linking status
$userrecord = \qbank_questionhub\auth_manager::get_user_token_record((int)$USER->id);
$hasaccount = !empty($userrecord) && !empty($userrecord->access_token);
$accountname = $hasaccount ? ($userrecord->hub_name ?: ($userrecord->hub_email ?: 'Verbunden')) : '';

// Course categories for import target dropdown
$categories = $DB->get_records('question_categories', ['contextid' => $coursecontext->id], 'parent, sortorder, name');
$coursecategories = [];
foreach ($categories as $cat) {
    $coursecategories[] = [
        'id' => $cat->id,
        'name' => format_string($cat->name),
        'selected' => ($cat->name !== 'top'),
    ];
}

// Build URLs
$reseturl = new moodle_url('/question/bank/questionhub/view.php', ['courseid' => $COURSE->id]);
if (!empty($cmid)) {
    $reseturl->param('cmid', $cmid);
}

$connecturl = \qbank_questionhub\auth_manager::init_oauth_flow($thispageurl->out(false));
$disconnecturl = new moodle_url('/question/bank/questionhub/disconnect.php', [
    'sesskey' => sesskey(),
    'returnurl' => $thispageurl->out_as_local_url(false),
]);

$huburl = rtrim(get_config('qbank_questionhub', 'huburl') ?: 'https://hub.cblum.ch', '/');

// Pagination URLs
$pagination = $packageresult['pagination'] ?? ['page' => 1, 'total_pages' => 1];
$prevurl = clone $thispageurl;
$prevurl->param('page', max(1, $page - 1));
$nexturl = clone $thispageurl;
$nexturl->param('page', min($pagination['total_pages'] ?? 1, $page + 1));

$templatedata = [
    'has_account' => $hasaccount,
    'account_name' => $accountname,
    'connect_url' => $connecturl,
    'disconnect_url' => $disconnecturl->out(false),
    'browser_url' => (new moodle_url('/question/bank/questionhub/view.php'))->out(false),
    'action_url' => $thispageurl->out(false),
    'reset_url' => $reseturl->out(false),
    'hub_url' => $huburl,
    'sesskey' => sesskey(),
    'courseid' => $COURSE->id,
    'cmid' => $cmid,
    'categoryid' => optional_param('cat', 0, PARAM_INT),
    'query' => $query,
    'level_gf_selected' => ($level === 'GF'),
    'level_sf_selected' => ($level === 'SF'),
    'level_ef_selected' => ($level === 'EF'),
    'level_ff_selected' => ($level === 'FF'),
    'subjects' => $subjectsdata,
    'cantons' => $cantonsdata,
    'packages' => $packagesdata,
    'course_categories' => $coursecategories,
    'pagination' => $pagination,
    'has_prev' => ($page > 1),
    'has_next' => ($page < ($pagination['total_pages'] ?? 1)),
    'prev_url' => $prevurl->out(false),
    'next_url' => $nexturl->out(false),
];

echo $OUTPUT->header();

// Render tertiary question bank navigation dropdown
$renderer = $PAGE->get_renderer('core_question', 'bank');
$qbankaction = new \core_question\output\qbank_action_menu($thispageurl);
echo $renderer->render($qbankaction);

echo $OUTPUT->render_from_template('qbank_questionhub/hub_browser', $templatedata);

echo $OUTPUT->footer();
