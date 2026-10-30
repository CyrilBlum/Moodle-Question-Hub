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
 * @copyright  2026 Cyril Blum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/question/editlib.php');

require_login();
\core_question\local\bank\helper::require_plugin_enabled('qbank_questionhub');

$courseid = optional_param('courseid', 0, PARAM_INT);
$cmid = optional_param('cmid', 0, PARAM_INT);
$categoryid = optional_param('categoryid', 0, PARAM_INT);
$bankcontextid = optional_param('bankcontextid', 0, PARAM_INT);
$questionid = optional_param('questionid', 0, PARAM_INT);
$updatepackageid = \qbank_questionhub\hub_input::package_id(optional_param('updatepackageid', '', PARAM_TEXT));

// Resolve course and cmid bidirectionally across Moodle 4.5 and 5.0.
if ($courseid <= 0 && $cmid > 0) {
    [$module, $cm] = get_module_from_cmid($cmid);
    $courseid = (int)$cm->course;
}

if ($courseid > 0) {
    $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
} else {
    $course = $COURSE;
}

// Validate that cmid belongs to this course to prevent coding_exception in $PAGE->set_cm.
if ($cmid > 0) {
    $cmrecord = $DB->get_record('course_modules', ['id' => $cmid], 'id,course');
    if (!$cmrecord || (int)$cmrecord->course !== (int)$course->id) {
        $cmid = 0;
    }
}

if ($cmid <= 0 && class_exists('\core_question\local\bank\question_bank_helper')) {
    $qbank = \core_question\local\bank\question_bank_helper::get_default_open_instance_system_type($course, true);
    if ($qbank) {
        $cmid = (int)$qbank->id;
    }
}

$availablecontexts = \qbank_questionhub\question_bank_context::available($course);
if ($bankcontextid === 0 && $categoryid === 0 && $questionid > 0) {
    $categoryid = (int)$DB->get_field_sql(
        'SELECT qbe.questioncategoryid FROM {question_bank_entries} qbe ' .
        'JOIN {question_versions} qv ON qv.questionbankentryid = qbe.id WHERE qv.questionid = ?',
        [$questionid]
    );
}
if ($bankcontextid === 0 && $categoryid > 0) {
    $sourcecategory = $DB->get_record('question_categories', ['id' => $categoryid], 'id,contextid');
    if ($sourcecategory && isset($availablecontexts[$sourcecategory->contextid])) {
        $bankcontextid = (int)$sourcecategory->contextid;
    }
}
$context = \qbank_questionhub\question_bank_context::resolve($course, $bankcontextid);
require_capability('qbank/questionhub:publish', $context);

$pageparams = ['courseid' => $course->id, 'bankcontextid' => $context->id];
if ($cmid > 0) {
    $pageparams['cmid'] = $cmid;
}
if ($categoryid > 0) {
    $pageparams['categoryid'] = $categoryid;
}
if ($questionid > 0) {
    $pageparams['questionid'] = $questionid;
}
if ($updatepackageid !== '') {
    $pageparams['updatepackageid'] = $updatepackageid;
}

$thispageurl = new moodle_url('/question/bank/questionhub/publish.php', $pageparams);
$PAGE->set_url($thispageurl);
if ((int)$course->id !== (int)SITEID) {
    $PAGE->set_course($course);
}
if ($cmid > 0) {
    [$module, $cm] = get_module_from_cmid($cmid);
    if ((int)$cm->course === (int)$course->id) {
        $PAGE->set_cm($cm, $course);
    }
}
$PAGE->set_title(get_string('publishtohub', 'qbank_questionhub'));
$PAGE->set_heading($course->fullname);
$PAGE->set_context($context);

// Resolve selected category or question.
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
if ($categoryid > 0) {
    $selectedcategory = $DB->get_record('question_categories', ['id' => $categoryid], 'id,contextid', MUST_EXIST);
    if ((int)$selectedcategory->contextid !== (int)$context->id) {
        throw new moodle_exception('invalidcategory', 'question');
    }
}

$ownedpackage = null;
if ($updatepackageid !== '') {
    if ($questionid > 0) {
        throw new moodle_exception('error_update_source', 'qbank_questionhub');
    }
    // Verify user owns this package on the Hub.
    $accountclient = new \qbank_questionhub\api_client(null, null, 30, true);
    foreach ($accountclient->get_my_packages() as $candidate) {
        if ($candidate['id'] === $updatepackageid) {
            $ownedpackage = $candidate;
            break;
        }
    }
    if (!$ownedpackage) {
        throw new moodle_exception('error_update_source', 'qbank_questionhub');
    }

    if ($categoryid === 0) {
        $hist = \qbank_questionhub\publication_tracker::latest_active_publication_by_package($updatepackageid, (int)$course->id);
        if ($hist && (int)$hist->categoryid > 0) {
            $categoryid = (int)$hist->categoryid;
        } else {
            // Try matching course category by title or slug.
            $cleantitle = preg_replace('/[^a-z0-9]/', '', mb_strtolower($ownedpackage['title']));
            $allcats = $DB->get_records('question_categories', ['contextid' => $context->id]);
            foreach ($allcats as $c) {
                if ((int)$c->parent === 0 && $c->name === 'top') {
                    continue;
                }
                $cname = preg_replace('/[^a-z0-9]/', '', mb_strtolower($c->name));
                if ($cname !== '' && ($cname === $cleantitle || str_ends_with($updatepackageid, $cname))) {
                    $categoryid = (int)$c->id;
                    break;
                }
            }
        }
    }

    $latestpublication = null;
    if ($categoryid > 0) {
        $latestpublication = \qbank_questionhub\publication_tracker::latest((int)$course->id, $categoryid);
        // An editor's category may hold an import of the package: its snapshot is the base of the update.
        if (
            $latestpublication && !($latestpublication->action === 'publish' ||
                ($latestpublication->action === 'import' && $latestpublication->package_id === $updatepackageid))
        ) {
            $latestpublication = null;
        }
    }
    if (!$latestpublication) {
        $latestpublication = \qbank_questionhub\publication_tracker::latest_active_publication_by_package(
            $updatepackageid,
            (int)$course->id
        );
    }
}

// Handle Form Submissions (Download XML or Direct Publish).
$inputerror = null;
$postedversion = null;
$postednotes = '';
$issubmitted = (bool)data_submitted();
if ($issubmitted) {
    require_sesskey();
    $action = optional_param('action', 'publish_hub', PARAM_ALPHAEXT);

    if ($ownedpackage) {
        // An update only adds a release; the package settings stay as on the Hub (edited in the category list).
        $packageid = $updatepackageid;
        $version = trim(required_param('package_version', PARAM_TEXT));
        $releasenotes = trim(required_param('release_notes', PARAM_TEXT));
        $title = html_entity_decode($ownedpackage['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $summary = (string)($ownedpackage['summary'] ?? '');
        $subject = (string)($ownedpackage['subject'] ?? 'Informatik');
        $level = (string)($ownedpackage['subject_category'] ?? 'GF');
        $canton = (string)($ownedpackage['canton'] ?? 'ZH');
        $gradesraw = $ownedpackage['grades'] ?? ['GYM2', 'GYM3'];
        $license = (string)($ownedpackage['license'] ?? 'CC-BY-4.0');
        $visibility = ($ownedpackage['visibility'] ?? 'public') === 'private' ? 'private' : 'public';
        $previewenabled = !empty($ownedpackage['preview_enabled']);
        $tags = $ownedpackage['tags'] ?? [];
        // The GitHub link stays as set in the package settings.
        $githubrepo = '';
        $githubnewname = '';
        // Downloading the XML needs neither a release note nor a new version.
        if ($action === 'publish_hub') {
            if ($releasenotes === '') {
                $inputerror = new moodle_exception('error_release_notes_required', 'qbank_questionhub');
            } else if (version_compare($version, $ownedpackage['latest_version'], '<=')) {
                $inputerror = new moodle_exception(
                    'error_update_version',
                    'qbank_questionhub',
                    '',
                    $ownedpackage['latest_version']
                );
            }
        }
        // Shown again in the form after an error.
        $postedversion = $version;
        $postednotes = $releasenotes;
    } else {
        $packageid = trim(required_param('package_id', PARAM_TEXT));
        $title = required_param('package_title', PARAM_TEXT);
        $summary = required_param('package_summary', PARAM_TEXT);
        $subject = required_param('package_subject', PARAM_TEXT);
        $level = required_param('package_level', PARAM_ALPHA);
        $canton = required_param('package_canton', PARAM_ALPHA);
        $gradesraw = optional_param_array('package_grades', ['GYM2', 'GYM3'], PARAM_ALPHANUM);
        $version = trim(required_param('package_version', PARAM_TEXT));
        $license = required_param('package_license', PARAM_TEXT);
        $visibility = optional_param('package_visibility', 'public', PARAM_ALPHA);
        if (!in_array($visibility, ['public', 'private'], true)) {
            $visibility = 'public';
        }
        // Checkbox: absent when unchecked. Default in the form is checked.
        $previewenabled = (bool)optional_param('package_preview', 0, PARAM_BOOL);
        $releasenotes = '';
        $tags = [];
        // GitHub (optional): none, a new private repository created before publishing, or an existing one.
        $githubmode = optional_param('github_mode', 'none', PARAM_ALPHA);
        $githubrepo = $githubmode === 'existing' ? trim(optional_param('package_github_repo', '', PARAM_TEXT)) : '';
        $githubnewname = $githubmode === 'new' ? trim(optional_param('package_github_new', '', PARAM_TEXT)) : '';
    }

    try {
        if ($inputerror) {
            throw $inputerror;
        }
        // Typed into the form, so a wrong format is an error message there and not a failed page.
        \qbank_questionhub\hub_input::package_id($packageid);
        \qbank_questionhub\hub_input::version($version);
        \qbank_questionhub\hub_input::github_repo($githubrepo);
        \qbank_questionhub\hub_input::repo_name($githubnewname);
        $exporter = new \qbank_questionhub\package_exporter((int)$course->id, (int)$context->id);
        $publishedquestions = $questionid > 0 ? [] : \qbank_questionhub\publication_tracker::current($categoryid);

        if ($questionid > 0) {
            $xmlcontent = $exporter->export_questions_xml([$questionid]);
        } else {
            $xmlcontent = $exporter->export_category_xml($categoryid);
        }

        // Action 1: Direct File Download.
        if ($action === 'download_xml') {
            $filename = clean_filename("package-{$packageid}-{$version}.xml");
            header('Content-Type: application/xml; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($xmlcontent));
            echo $xmlcontent;
            exit;
        }

        // Action 2: Direct Publish to Marketplace.
        // Checked here because the Hub (and nginx in front of it) refuses larger packages only after the whole upload.
        if (strlen($xmlcontent) > \qbank_questionhub\preflight_validator::MAX_PACKAGE_BYTES) {
            $largest = [];
            foreach (\qbank_questionhub\preflight_validator::largest_questions($xmlcontent) as $q) {
                $largest[] = '«' . s($q['name']) . '» (' . display_size($q['bytes']) . ')';
            }
            throw new moodle_exception('error_package_too_large', 'qbank_questionhub', '', (object)[
                'size' => display_size(strlen($xmlcontent)),
                'limit' => display_size(\qbank_questionhub\preflight_validator::MAX_PACKAGE_BYTES),
                'largest' => implode(', ', $largest),
            ]);
        }

        $allowedgrades = ['GYM1', 'GYM2', 'GYM3', 'GYM4'];
        $grades = array_values(array_intersect($allowedgrades, $gradesraw));
        $metadata = [
            'id' => $packageid,
            'title' => $title,
            'summary' => $summary,
            'version' => $version,
            'language' => 'de-CH',
            'license' => $license,
            'visibility' => $visibility,
            'preview_enabled' => $previewenabled,
            'taxonomy' => [
                'framework' => 'CH-EDK-RLP',
                'canton' => $canton,
                'level' => 'GYM_SEK_II',
                'grades' => array_values($grades),
                'subject_category' => $level,
                'subject' => $subject,
                'tags' => array_values($tags),
            ],
        ];
        if ($releasenotes !== '') {
            $metadata['release_notes'] = $releasenotes;
        }
        if ($githubrepo !== '') {
            $metadata['github_repo'] = $githubrepo;
        }
        if ($ownedpackage) {
            // The Hub refuses the update if a newer release exists that this category has not pulled yet.
            $synced = \qbank_questionhub\publication_tracker::latest((int)$course->id, $categoryid);
            if ($synced && $synced->package_id === $packageid) {
                $metadata['base_version'] = $synced->package_version;
            }
        }

        $client = new \qbank_questionhub\api_client(null, null, 30, true);
        if ($githubnewname !== '') {
            // Created first: if that fails, nothing is published. The Hub commits the release into it.
            $createdrepo = $client->create_github_repo($githubnewname);
            $metadata['github_repo'] = $createdrepo['repo'];
            \core\notification::success(get_string(
                'github_repo_created_named',
                'qbank_questionhub',
                s($createdrepo['html_url'] ?? $createdrepo['repo'])
            ));
        }
        $published = $client->publish($xmlcontent, $metadata);

        // Snapshot the versions represented by this category export after Hub acceptance.
        // Audit log in history table.
        try {
            $history = new stdClass();
            $history->userid = (int)$USER->id;
            $history->action = 'publish';
            $history->package_id = $packageid;
            $history->package_version = $version;
            $history->package_title = $title;
            $history->courseid = (int)$course->id;
            $history->categoryid = $questionid > 0 ? 0 : (int)$categoryid;
            $history->question_count = $published['stats']['question_count'] ?? 1;
            $history->timecreated = time();
            $historyid = $DB->insert_record('qbank_questionhub_history', $history);
            if ($questionid === 0) {
                \qbank_questionhub\publication_tracker::save((int)$historyid, $publishedquestions);
            }
        } catch (\Throwable $t) {
            debugging($t->getMessage(), DEBUG_DEVELOPER);
            \core\notification::warning(get_string('publication_tracking_failed', 'qbank_questionhub'));
        }

        \core\notification::success(get_string('export_success', 'qbank_questionhub', $version));
        \qbank_questionhub\api_client::notify_github_result($published['github'] ?? null);

        $returnparams = ['courseid' => $course->id, 'bankcontextid' => $context->id];
        if ($cmid > 0) {
            $returnparams['cmid'] = $cmid;
        }
        redirect(new moodle_url('/question/bank/questionhub/view.php', $returnparams));
    } catch (\Exception $e) {
        \core\notification::error($e->getMessage());
    }
}

// Fetch Taxonomy for dropdown choices.
$client = new \qbank_questionhub\api_client();
$taxonomy = [];
try {
    $taxonomy = $client->get_taxonomy();
} catch (\Exception $e) {
    $taxonomy = \qbank_questionhub\api_client::fallback_taxonomy();
}

$subjectsdata = [];
foreach ($taxonomy['subjects'] ?? [] as $sub) {
    $subjectsdata[] = [
        'name_de' => $sub['name_de'],
        'selected' => ($sub['name_de'] === ($ownedpackage['subject'] ?? 'Informatik')),
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
        'selected' => ($c['code'] === ($ownedpackage['canton'] ?? 'ZH')),
    ];
}

// Prepare category choices.
$allcategories = $DB->get_records('question_categories', ['contextid' => $context->id], 'sortorder, name');
$childmap = [];
foreach ($allcategories as $cat) {
    $childmap[(int)$cat->parent][] = $cat;
}
$categoriesdata = [];
$defaulttitle = $ownedpackage ? html_entity_decode($ownedpackage['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8') : 'Fragensammlung';
$defaultslug = $ownedpackage ? $ownedpackage['id'] : ('ch.gym.inf.collection.' . time());
$defaultsummary = $ownedpackage ? ($ownedpackage['summary'] ?? '') : get_string('default_package_summary', 'qbank_questionhub');
$defaultversion = $ownedpackage ? \qbank_questionhub\publication_tracker::next_version($ownedpackage['latest_version']) : '1.0.0';

$walkcats = function (
    int $parentid,
    int $depth
) use (
    &$walkcats,
    $childmap,
    $DB,
    $categoryid,
    &$categoriesdata,
    &$defaulttitle,
    &$defaultslug,
    $ownedpackage
) {
    foreach ($childmap[$parentid] ?? [] as $cat) {
        $istop = ($cat->name === 'top' && $parentid === 0);
        if (!$istop) {
            $qcount = $DB->count_records('question_bank_entries', ['questioncategoryid' => $cat->id]);
            $isselected = ((int)$cat->id === (int)$categoryid);
            $plainname = format_string($cat->name, true, ['escape' => false]);
            if ($isselected && !$ownedpackage) {
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

$cancelparams = ['courseid' => $course->id, 'bankcontextid' => $context->id];
if ($cmid > 0) {
    $cancelparams['cmid'] = $cmid;
}
$cancelurl = new moodle_url('/question/bank/questionhub/view.php', $cancelparams);

$templatedata = [
    'action_url' => $thispageurl->out(false),
    'cancel_url' => $cancelurl->out(false),
    'sesskey' => sesskey(),
    'courseid' => $course->id,
    'bankcontextid' => $context->id,
    'cmid' => $cmid,
    'categoryid' => $categoryid,
    'questionid' => $questionid,
    'is_update' => !empty($ownedpackage),
    'update_package_id' => $updatepackageid,
    'owned_version' => $ownedpackage['latest_version'] ?? '',
    'default_package_id' => $defaultslug,
    'default_title' => $defaulttitle,
    'default_summary' => $defaultsummary,
    'default_version' => $defaultversion,
    'categories' => $categoriesdata,
    'subjects' => $subjectsdata,
    'cantons' => $cantonsdata,
    'level_gf' => ($ownedpackage['subject_category'] ?? 'GF') === 'GF',
    'level_sf' => ($ownedpackage['subject_category'] ?? 'GF') === 'SF',
    'level_ef' => ($ownedpackage['subject_category'] ?? 'GF') === 'EF',
    'level_ff' => ($ownedpackage['subject_category'] ?? 'GF') === 'FF',
    'preview_checked' => $ownedpackage ? !empty($ownedpackage['preview_enabled']) : true,
    'default_github_repo' => $issubmitted ? trim(optional_param('package_github_repo', '', PARAM_TEXT)) : '',
    'default_github_new' => $issubmitted ? trim(optional_param('package_github_new', $defaultslug, PARAM_TEXT)) : $defaultslug,
    'github_mode_new_checked' => optional_param('github_mode', 'none', PARAM_ALPHA) === 'new',
    'github_mode_existing_checked' => optional_param('github_mode', 'none', PARAM_ALPHA) === 'existing',
    'github_mode_none_checked' => !in_array(optional_param('github_mode', 'none', PARAM_ALPHA), ['new', 'existing'], true),
    // New versions of a linked package also go to GitHub; editors only know that it is linked.
    'github_push_note' => !$ownedpackage ? '' : (!empty($ownedpackage['github_repo'])
        ? get_string('github_push_linked', 'qbank_questionhub', $ownedpackage['github_repo'])
        : (!empty($ownedpackage['github_linked'])
            ? get_string('github_push_linked_unknown', 'qbank_questionhub')
            : get_string('github_push_unlinked', 'qbank_questionhub'))),
    'github_push_linked' => !empty($ownedpackage['github_repo']) || !empty($ownedpackage['github_linked']),
] + ($ownedpackage ? [] : (new \qbank_questionhub\api_client(null, null, 30, true))->github_template_data(
    rtrim(get_config('qbank_questionhub', 'huburl') ?: 'https://hub.cblum.ch', '/')
)) + [
    'is_private' => $ownedpackage ? (($ownedpackage['visibility'] ?? 'public') === 'private') : false,
    'is_public' => $ownedpackage ? (($ownedpackage['visibility'] ?? 'public') !== 'private') : true,
    ];

    echo $OUTPUT->header();

    $renderer = $PAGE->get_renderer('core_question', 'bank');
    $qbankaction = new \core_question\output\qbank_action_menu($thispageurl);
    echo $renderer->render($qbankaction);

if ($ownedpackage) {
    // Push view: what changed since the last release, plus version and release note only.
    $diff = null;
    if ($categoryid > 0 && $latestpublication) {
        $diff = \qbank_questionhub\release_diff::since(
            $categoryid,
            $latestpublication,
            new \qbank_questionhub\package_exporter((int)$course->id, (int)$context->id)
        );
    }
    $files = $diff === null ? null : \qbank_questionhub\release_diff::add_links(
        \qbank_questionhub\release_diff::label_sides(
            $diff['files'],
            get_string('diff_side_published', 'qbank_questionhub', $latestpublication->package_version),
            get_string('diff_side_current', 'qbank_questionhub')
        ),
        $context,
        (int)$course->id,
        $thispageurl->out_as_local_url(false)
    );
    $category = $DB->get_record('question_categories', ['id' => $categoryid], 'id,name', IGNORE_MISSING);
    $templatedata += [
        'package_title' => $defaulttitle,
        'category_name' => $category ? format_string($category->name, true, ['context' => $context, 'escape' => false]) : '',
        'base_version' => $latestpublication->package_version ?? '',
        'files' => $files ?? [],
        'has_toolbar' => count($files ?? []) > 1,
        'files_count' => $files === null ? 0 : count($files),
        'files_unknown' => $files === null,
        'no_changes' => $files !== null && !$files,
        'same_content' => $diff['same_content'] ?? 0,
        'additions' => array_sum(array_column($files ?? [], 'additions')),
            'deletions' => array_sum(array_column($files ?? [], 'deletions')),
            'version_value' => $postedversion ?? $defaultversion,
            'release_notes' => $postednotes,
        ];
        $PAGE->requires->js_call_amd('qbank_questionhub/diffs', 'init');
        echo $OUTPUT->render_from_template('qbank_questionhub/push_view', $templatedata);
} else {
    echo $OUTPUT->render_from_template('qbank_questionhub/publish_view', $templatedata);
}

    echo $OUTPUT->footer();
