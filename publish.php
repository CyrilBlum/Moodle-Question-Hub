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
 * Question publishing and export interface for Question Hub.
 *
 * @package    qbank_questionhub
 * @copyright  2026 Cyril Wendl
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/question/editlib.php');

require_login();
\core_question\local\bank\helper::require_plugin_enabled('qbank_questionhub');

$courseid = optional_param('courseid', 0, PARAM_INT);
$cmid = optional_param('cmid', 0, PARAM_INT);
$categoryid = optional_param('categoryid', 0, PARAM_INT);
$questionid = optional_param('questionid', 0, PARAM_INT);

// Resolve course and cmid bidirectionally across Moodle 4.5 and 5.0.
if ($courseid <= 0 && $cmid > 0) {
    list($module, $cm) = get_module_from_cmid($cmid);
    $courseid = (int)$cm->course;
}

if ($courseid > 0) {
    $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
} else {
    $course = $COURSE;
}

if ($cmid <= 0 && class_exists('\core_question\local\bank\question_bank_helper')) {
    $qbank = \core_question\local\bank\question_bank_helper::get_default_open_instance_system_type($course, true);
    if ($qbank) {
        $cmid = (int)$qbank->id;
    }
}

$context = \qbank_questionhub\package_exporter::resolve_course_question_context($course);
require_capability('qbank/questionhub:publish', $context);

$pageparams = ['courseid' => $course->id];
if ($cmid > 0) {
    $pageparams['cmid'] = $cmid;
}
if ($categoryid > 0) {
    $pageparams['categoryid'] = $categoryid;
}
if ($questionid > 0) {
    $pageparams['questionid'] = $questionid;
}

$thispageurl = new moodle_url('/question/bank/questionhub/publish.php', $pageparams);
$PAGE->set_url($thispageurl);
$PAGE->set_title(get_string('publishtohub', 'qbank_questionhub'));
$PAGE->set_heading($course->fullname);
$PAGE->set_context($context);

// Resolve selected category or question
if ($questionid > 0) {
    $sql = "SELECT qbe.questioncategoryid
              FROM {question_bank_entries} qbe
              JOIN {question_versions} qv ON qv.questionbankentryid = qbe.id
             WHERE qv.questionid = ?";
    $resolvedcatid = (int)$DB->get_field_sql($sql, [$questionid]);
    if ($resolvedcatid > 0) {
        $categoryid = $resolvedcatid;
    } else {
        $question = $DB->get_record('question', ['id' => $questionid], '*', MUST_EXIST);
        $categoryid = (int)($question->category ?? 0);
    }
} else if ($categoryid === 0) {
    $defaultcat = question_get_default_category($context->id, true);
    $categoryid = $defaultcat ? (int)$defaultcat->id : 0;
}

// Handle Form Submissions (Download XML or Direct Publish)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
    $action = optional_param('action', 'publish_hub', PARAM_ALPHAEXT);

    $packageid = required_param('package_id', PARAM_RAW_TRIMMED);
    $title = required_param('package_title', PARAM_TEXT);
    $summary = required_param('package_summary', PARAM_TEXT);
    $subject = required_param('package_subject', PARAM_TEXT);
    $level = required_param('package_level', PARAM_ALPHA);
    $canton = required_param('package_canton', PARAM_ALPHA);
    $gradesraw = optional_param_array('package_grades', ['GYM2', 'GYM3'], PARAM_ALPHANUM);
    $version = required_param('package_version', PARAM_RAW_TRIMMED);
    $license = required_param('package_license', PARAM_TEXT);
    // Checkbox: absent when unchecked. Default in the form is checked.
    $previewenabled = (bool)optional_param('package_preview', 0, PARAM_BOOL);

    try {
        $exporter = new \qbank_questionhub\package_exporter((int)$course->id);

        if ($questionid > 0) {
            $xmlcontent = $exporter->export_questions_xml([$questionid]);
        } else {
            $xmlcontent = $exporter->export_category_xml($categoryid);
        }

        // Action 1: Direct File Download
        if ($action === 'download_xml') {
            $filename = clean_filename("package-{$packageid}-{$version}.xml");
            header('Content-Type: application/xml; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($xmlcontent));
            echo $xmlcontent;
            exit;
        }

        // Action 2: Direct Publish to Marketplace
        $allowedgrades = ['GYM1', 'GYM2', 'GYM3', 'GYM4'];
        $grades = array_values(array_intersect($allowedgrades, $gradesraw));
        $metadata = [
            'id' => $packageid,
            'title' => $title,
            'summary' => $summary,
            'version' => $version,
            'language' => 'de-CH',
            'license' => $license,
            'preview_enabled' => $previewenabled,
            'taxonomy' => [
                'framework' => 'CH-EDK-RLP',
                'canton' => $canton,
                'level' => 'GYM_SEK_II',
                'grades' => array_values($grades),
                'subject_category' => $level,
                'subject' => $subject,
                'tags' => [],
            ],
        ];

        $client = new \qbank_questionhub\api_client();
        $published = $client->publish($xmlcontent, $metadata);

        // Audit log in history table
        try {
            $history = new stdClass();
            $history->userid = (int)$USER->id;
            $history->action = 'publish';
            $history->package_id = $packageid;
            $history->package_version = $version;
            $history->package_title = $title;
            $history->courseid = (int)$course->id;
            $history->categoryid = (int)$categoryid;
            $history->question_count = $published['stats']['question_count'] ?? 1;
            $history->timecreated = time();
            $DB->insert_record('qbank_questionhub_history', $history);
        } catch (\Throwable $t) {}

        \core\notification::success(get_string('export_success', 'qbank_questionhub', $version));

        $returnparams = ['courseid' => $course->id];
        if ($cmid > 0) {
            $returnparams['cmid'] = $cmid;
        }
        redirect(new moodle_url('/question/bank/questionhub/view.php', $returnparams));
    } catch (\Exception $e) {
        \core\notification::error($e->getMessage());
    }
}

// Fetch Taxonomy for dropdown choices
$client = new \qbank_questionhub\api_client();
$taxonomy = [];
try {
    $taxonomy = $client->get_taxonomy();
} catch (\Exception $e) {
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
            ['code' => 'AG'], ['code' => 'BE'], ['code' => 'BS'], ['code' => 'LU'],
            ['code' => 'SG'], ['code' => 'ZH'],
        ],
    ];
}

$subjectsdata = [];
foreach ($taxonomy['subjects'] ?? [] as $sub) {
    $subjectsdata[] = [
        'name_de' => $sub['name_de'],
        'selected' => ($sub['name_de'] === 'Informatik'),
    ];
}

$cantonsdata = [];
$cantonlist = $taxonomy['cantons'] ?? [
    ['code' => 'AG'], ['code' => 'BE'], ['code' => 'BS'], ['code' => 'LU'],
    ['code' => 'SG'], ['code' => 'ZH'],
];
foreach ($cantonlist as $c) {
    $cantonsdata[] = [
        'code' => $c['code'],
        'selected' => ($c['code'] === 'ZH'),
    ];
}

// Prepare category choices
$allcategories = $DB->get_records('question_categories', ['contextid' => $context->id], 'sortorder, name');
$childmap = [];
foreach ($allcategories as $cat) {
    $childmap[(int)$cat->parent][] = $cat;
}
$categoriesdata = [];
$defaulttitle = 'Fragensammlung';
$defaultslug = 'ch.gym.inf.collection.' . time();

$walkcats = function (int $parentid, int $depth) use (&$walkcats, $childmap, $DB, $categoryid, &$categoriesdata,
        &$defaulttitle, &$defaultslug) {
    foreach ($childmap[$parentid] ?? [] as $cat) {
        $istop = ($cat->name === 'top' && $parentid === 0);
        if (!$istop) {
            $qcount = $DB->count_records('question_bank_entries', ['questioncategoryid' => $cat->id]);
            $isselected = ((int)$cat->id === (int)$categoryid);
            $plainname = format_string($cat->name, true, ['escape' => false]);
            if ($isselected) {
                $defaulttitle = $plainname;
                $clean = preg_replace('/[^a-z0-9]/', '', strtolower($cat->name));
                $defaultslug = 'ch.gym.inf.' . ($clean ?: 'questions');
            }
            $categoriesdata[] = [
                'id' => $cat->id,
                'name' => str_repeat("\u{00A0}\u{00A0}\u{00A0}", max(0, $depth - 1)) . ($depth > 1 ? "\u{2514} " : '') . $plainname,
                'questioncount' => $qcount,
                'selected' => $isselected,
            ];
        }
        $walkcats((int)$cat->id, $istop ? 1 : $depth + 1);
    }
};
$walkcats(0, 0);

$cancelparams = ['courseid' => $course->id];
if ($cmid > 0) {
    $cancelparams['cmid'] = $cmid;
}
$cancelurl = new moodle_url('/question/bank/questionhub/view.php', $cancelparams);

$templatedata = [
    'action_url' => $thispageurl->out(false),
    'cancel_url' => $cancelurl->out(false),
    'sesskey' => sesskey(),
    'courseid' => $course->id,
    'cmid' => $cmid,
    'categoryid' => $categoryid,
    'questionid' => $questionid,
    'default_package_id' => $defaultslug,
    'default_title' => $defaulttitle,
    'default_summary' => 'Fragensammlung für Schweizer Gymnasien.',
    'categories' => $categoriesdata,
    'subjects' => $subjectsdata,
    'cantons' => $cantonsdata,
];

echo $OUTPUT->header();

$renderer = $PAGE->get_renderer('core_question', 'bank');
$qbankaction = new \core_question\output\qbank_action_menu($thispageurl);
echo $renderer->render($qbankaction);

echo $OUTPUT->render_from_template('qbank_questionhub/publish_view', $templatedata);

echo $OUTPUT->footer();
