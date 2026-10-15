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
if (!empty($_GET['cmid']) && !empty($_GET['courseid'])) {
    $cmcheck = $DB->get_record('course_modules', ['id' => (int)$_GET['cmid']], 'id,course');
    if ($cmcheck && (int)$cmcheck->course !== (int)$_GET['courseid']) {
        unset($_GET['cmid']);
    }
}

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
$bankcontextid = optional_param('bankcontextid', 0, PARAM_INT);
$bankcontexts = \qbank_questionhub\question_bank_context::available($COURSE);
$bankcontext = \qbank_questionhub\question_bank_context::resolve($COURSE, $bankcontextid);
$thispageurl->param('bankcontextid', $bankcontext->id);

$PAGE->set_url($thispageurl);
$PAGE->set_title(get_string('pluginname', 'qbank_questionhub'));
$PAGE->set_heading($COURSE->fullname);

// Handle POST actions (Save PAT, Import Package)
$importpreview = null;
$duplicatepreview = [];
$duplicatecategoryid = 0;
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
    } else if ($action === 'edit_package') {
        try {
            $client = new \qbank_questionhub\api_client(null, null, 30, true);
            $grades = optional_param_array('grades', [], PARAM_ALPHANUM);
            $tags = array_values(array_filter(array_map('trim', explode(',', optional_param('tags', '', PARAM_TEXT)))));
            $patch = [
                'title' => required_param('title', PARAM_TEXT),
                'summary' => required_param('summary', PARAM_TEXT),
                'license' => required_param('license', PARAM_TEXT),
                'subject_category' => required_param('subject_category', PARAM_ALPHA),
                'grades' => array_values(array_intersect(['GYM1', 'GYM2', 'GYM3', 'GYM4'], $grades)),
                'tags' => $tags,
                'preview_enabled' => (bool)optional_param('preview_enabled', 0, PARAM_BOOL),
                'visibility' => optional_param('visibility', 'public', PARAM_ALPHA),
            ];
            // Only a changed link is sent: the Hub checks it against the saving user's GitHub connection.
            $githubrepo = optional_param('github_repo', '', PARAM_RAW_TRIMMED);
            if ($githubrepo !== optional_param('github_repo_orig', '', PARAM_RAW_TRIMMED)) {
                $patch['github_repo'] = $githubrepo;
            }
            $updated = $client->update_package(required_param('packageid', PARAM_RAW_TRIMMED), $patch);
            \core\notification::success(get_string('settings_saved', 'qbank_questionhub'));
            \qbank_questionhub\api_client::notify_github_result($updated['github_sync'] ?? null);
        } catch (\Exception $e) {
            \core\notification::error($e->getMessage());
        }
        redirect($thispageurl);
    } else if ($action === 'create_github_repo') {
        // Create a private repository in the connected GitHub account and link it; the Hub commits the current version.
        try {
            $client = new \qbank_questionhub\api_client(null, null, 30, true);
            $created = $client->create_github_repo(required_param('repo_name', PARAM_RAW_TRIMMED));
            \core\notification::success(get_string('github_repo_created_named', 'qbank_questionhub', s($created['html_url'] ?? $created['repo'])));
            $updated = $client->update_package(required_param('packageid', PARAM_RAW_TRIMMED), ['github_repo' => $created['repo']]);
            \qbank_questionhub\api_client::notify_github_result($updated['github_sync'] ?? null);
        } catch (\Exception $e) {
            \core\notification::error($e->getMessage());
        }
        redirect($thispageurl);
    } else if ($action === 'withdraw_package' || $action === 'withdraw_release') {
        try {
            $client = new \qbank_questionhub\api_client(null, null, 30, true);
            $wpackage = required_param('packageid', PARAM_RAW_TRIMMED);
            if ($action === 'withdraw_package') {
                $client->withdraw_package($wpackage);
                // The Hub deletes the package; local categories must stop showing it as published.
                \qbank_questionhub\publication_tracker::unlink_package($wpackage, (int)$USER->id);
            } else {
                $client->withdraw_release($wpackage, required_param('version', PARAM_RAW_TRIMMED));
            }
            \core\notification::success(get_string('withdraw_done', 'qbank_questionhub'));
        } catch (\Exception $e) {
            \core\notification::error($e->getMessage());
        }
        redirect($thispageurl);
    } else if ($action === 'unlink_category') {
        require_capability('moodle/question:managecategory', $bankcontext);
        $categoryid = required_param('categoryid', PARAM_INT);
        $category = $DB->get_record('question_categories', ['id' => $categoryid, 'contextid' => $bankcontext->id], '*', MUST_EXIST);
        if (!\qbank_questionhub\publication_tracker::latest((int)$COURSE->id, $categoryid)) {
            // A subcategory only inherits its parent's link; it stays part of that package either way.
            $inherited = \qbank_questionhub\publication_tracker::find_local_publication_for_category(
                $categoryid, (int)$COURSE->id);
            if ($inherited && (int)$inherited->categoryid !== $categoryid) {
                \core\notification::error(get_string('error_unlink_inherited', 'qbank_questionhub'));
                redirect($thispageurl);
            }
        }
        $unlinked = 0;
        foreach (\qbank_questionhub\publication_tracker::category_ids($categoryid) as $scopedcategoryid) {
            // Resolve inherited parent links as well, so disconnecting a parent makes every
            // category in its tree independently publishable again.
            $link = \qbank_questionhub\publication_tracker::find_publication_for_category(
                $scopedcategoryid, (int)$COURSE->id);
            if (!$link || empty($link->package_id) ||
                    \qbank_questionhub\publication_tracker::is_unlinked((int)$COURSE->id, $scopedcategoryid)) {
                continue;
            }
            $DB->insert_record('qbank_questionhub_history', (object)[
                'userid' => (int)$USER->id, 'action' => 'unlink', 'package_id' => $link->package_id,
                'package_version' => $link->package_version, 'package_title' => $link->package_title,
                'courseid' => (int)$COURSE->id, 'categoryid' => $scopedcategoryid, 'question_count' => 0,
                'timecreated' => time(), 'snapshotted' => 0,
            ]);
            $unlinked++;
        }
        if ($unlinked) {
            \core\notification::success(get_string('categories_unlinked', 'qbank_questionhub', (object)[
                'name' => $category->name, 'count' => $unlinked,
            ]));
        }
        redirect($thispageurl);
    } else if ($action === 'delete_category') {
        require_capability('moodle/question:managecategory', $bankcontext);
        require_capability('moodle/question:editall', $bankcontext);
        $categoryid = required_param('categoryid', PARAM_INT);
        try {
            $deleted = \qbank_questionhub\category_remover::delete($bankcontext, $categoryid);
            \core\notification::success(get_string('category_deleted', 'qbank_questionhub', $deleted));
        } catch (\moodle_exception $e) {
            \core\notification::error($e->getMessage());
        }
        redirect($thispageurl);
    } else if ($action === 'import_package') {
        require_capability('qbank/questionhub:import', $bankcontext);
        $packageid = required_param('packageid', PARAM_RAW_TRIMMED);
        $version = required_param('version', PARAM_RAW_TRIMMED);
        $title = optional_param('title', 'Imported Package', PARAM_TEXT);
        $targetcatid = optional_param('targetcategoryid', 0, PARAM_INT);
        $categorymode = optional_param('categorymode', 'keep', PARAM_ALPHA);
        $updatechanged = (bool)optional_param('updatechanged', 0, PARAM_BOOL);
        $dryrun = (bool)optional_param('dryrun', 0, PARAM_BOOL);
        $resolutions = optional_param_array('resolution', [], PARAM_ALPHA);
        if (!in_array($categorymode, ['keep', 'rename'], true)) {
            $categorymode = 'keep';
        }

        try {
            $client = new \qbank_questionhub\api_client(null, null, 30, true);
            $xmlcontent = $client->download_release($packageid, $version);

            $importer = new \qbank_questionhub\package_importer(
                (int)$COURSE->id,
                $targetcatid > 0 ? $targetcatid : null,
                (int)$bankcontext->id
            );

            $metadata = ['id' => $packageid, 'version' => $version, 'title' => $title];
            $items = null;
            if (!$dryrun && $updatechanged && !$resolutions) {
                // A one-click pull must not decide conflicts: show them in the preview instead.
                $items = $importer->preview_package($xmlcontent, $metadata, $categorymode);
                if (array_filter($items, static fn($item) => $item['status'] === 'conflict')) {
                    $dryrun = true;
                    \core\notification::warning(get_string('import_conflicts_found', 'qbank_questionhub'));
                }
            }
            if ($dryrun) {
                $items = $items ?? $importer->preview_package($xmlcontent, $metadata, $categorymode);
                if (!$updatechanged) {
                    foreach ($items as &$item) {
                        $item['status'] = 'new';
                    }
                    unset($item);
                }
                $importpreview = [
                    'title' => $title, 'packageid' => $packageid, 'version' => $version,
                    'targetcategoryid' => $targetcatid, 'categorymode' => $categorymode,
                    'updatechanged' => $updatechanged,
                    'new' => array_values(array_filter($items, static function($item) {
                        return $item['status'] === 'new';
                    })),
                    'update' => array_values(array_filter($items, static function($item) {
                        return $item['status'] === 'update';
                    })),
                    'unchanged' => array_values(array_filter($items, static function($item) {
                        return $item['status'] === 'unchanged';
                    })),
                    'conflict' => array_values(array_filter($items, static function($item) {
                        return $item['status'] === 'conflict';
                    })),
                ];
                // Line diffs (local question -> Hub version) for questions the import would change or that conflict.
                $blocks = \qbank_questionhub\release_diff::package_blocks($xmlcontent, count($items));
                if ($blocks !== null) {
                    $diffexporter = new \qbank_questionhub\package_exporter((int)$COURSE->id, (int)$bankcontext->id);
                    $label = get_string('diff_label_local_hub', 'qbank_questionhub', $version);
                    $difffor = static function(array $item) use ($blocks, $diffexporter, $label): array {
                        $file = \qbank_questionhub\release_diff::compare($item['name'],
                            \qbank_questionhub\release_diff::entry_lines($diffexporter, (int)$item['entryid']),
                            \qbank_questionhub\release_diff::xml_lines($blocks[$item['index']]));
                        return $file + ['label' => $label];
                    };
                    foreach ($importpreview['conflict'] as &$conflictitem) {
                        if ($conflictitem['entryid']) {
                            $files = \qbank_questionhub\release_diff::decorate([$difffor($conflictitem)]);
                            $files[0]['open'] = false;
                            $conflictitem['diff'] = ['files' => $files];
                        }
                    }
                    unset($conflictitem);
                    $updatefiles = [];
                    foreach ($importpreview['update'] as $updateitem) {
                        if ($updateitem['entryid']) {
                            $updatefiles[] = $difffor($updateitem);
                        }
                    }
                    $importpreview['update_diff'] = $updatefiles ? [
                        'files' => \qbank_questionhub\release_diff::decorate($updatefiles),
                        'has_toolbar' => count($updatefiles) > 1,
                    ] : null;
                }
                $importpreview['conflictcount'] = count($importpreview['conflict']);
                $importpreview['newcount'] = count($importpreview['new']);
                $importpreview['updatecount'] = count($importpreview['update']);
                $importpreview['unchangedcount'] = count($importpreview['unchanged']);
            } else {
                $result = $importer->import_package($xmlcontent, true, $metadata, $categorymode, $updatechanged,
                    $resolutions);
                \core\notification::success(get_string('import_result_summary', 'qbank_questionhub', (object)[
                    'added' => $result->addedcount, 'updated' => $result->updatedcount,
                    'unchanged' => $result->unchangedcount,
                ]));
            }
        } catch (\Exception $e) {
            \core\notification::error($e->getMessage());
        }
        if (!$dryrun) {
            redirect($thispageurl);
        }
    } else if ($action === 'review_duplicates') {
        require_capability('qbank/questionhub:import', $bankcontext);
        $duplicatecategoryid = optional_param('duplicatecategoryid', 0, PARAM_INT);
        $duplicatepreview = \qbank_questionhub\duplicate_manager::find_removable($bankcontext, $duplicatecategoryid);
    } else if ($action === 'delete_duplicates') {
        require_capability('qbank/questionhub:import', $bankcontext);
        $duplicatecategoryid = optional_param('duplicatecategoryid', 0, PARAM_INT);
        $deleted = \qbank_questionhub\duplicate_manager::delete_removable($bankcontext, $duplicatecategoryid);
        \core\notification::success(get_string('duplicates_deleted', 'qbank_questionhub', $deleted));
        redirect($thispageurl);
    }
}

// Prepare filter parameters
$section = optional_param('section', 'browse', PARAM_ALPHANUMEXT);
if (!in_array($section, ['browse', 'library', 'my_packages'], true)) {
    $section = 'browse';
}
$showbrowse = ($section === 'browse');
$showlibrary = ($section === 'library');
$showmypackages = ($section === 'my_packages');
$duplicategroups = \qbank_questionhub\duplicate_manager::find_removable($bankcontext);
$duplicatecount = array_sum(array_map(static fn($group) => count($group['remove']), $duplicategroups));

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
$client = new \qbank_questionhub\api_client(null, null, 30, true);
$taxonomy = [];
$packageresult = ['packages' => [], 'pagination' => ['page' => 1, 'limit' => 12, 'total' => 0, 'total_pages' => 1]];

try {
    if ($showbrowse) {
        $taxonomy = $client->get_taxonomy();
    }
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

if ($showbrowse) {
    try {
        $packageresult = $client->get_packages($filters);
    } catch (\Exception $e) {
        \core\notification::warning(get_string('error_network', 'qbank_questionhub', $e->getMessage()));
    }
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
// The Hub API returns the list under "data" (older mocks used "packages").
foreach ($packageresult['data'] ?? $packageresult['packages'] ?? [] as $pkg) {
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
        'title' => html_entity_decode($pkg['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        'summary' => $pkg['summary'] ?? '',
        'latest_version' => $pkg['latest_version'] ?? '',
        'license' => $pkg['license'] ?? '',
        'subject_badge' => $pkg['taxonomy']['subject'] ?? 'Allgemein',
        'canton_badge' => $pkg['taxonomy']['canton'] ?? '',
        'level_badge' => $pkg['taxonomy']['subject_category'] ?? ($pkg['taxonomy']['level'] ?? ''),
        'stats' => $pkg['stats'] ?? ['question_count' => 0],
        'author_info' => $author,
        'preview_enabled' => ($pkg['visibility'] ?? 'public') !== 'private' && !empty($pkg['preview_enabled']),
        'is_private' => ($pkg['visibility'] ?? 'public') === 'private',
        'can_access' => !empty($pkg['can_access']),
        'package_root' => (function(array $paths): string {
            $roots = [];
            foreach ($paths as $path) {
                $segments = array_values(array_filter(explode('/', trim((string)$path, '/')), static function($segment) {
                    return $segment !== '';
                }));
                if (!empty($segments) && preg_match('/^\$[a-z]+\$$/i', $segments[0])) {
                    array_shift($segments);
                }
                if (!empty($segments) && $segments[0] === 'top') {
                    array_shift($segments);
                }
                if (!empty($segments)) {
                    $roots[$segments[0]] = true;
                }
            }
            return count($roots) === 1 ? (string)array_key_first($roots) : '';
        })($pkg['categories'] ?? []),
        'request_access_url' => rtrim(get_config('qbank_questionhub', 'huburl') ?: 'https://hub.cblum.ch', '/')
                . '/packages/' . rawurlencode($pkg['id']),
    ];
}

// Get user account linking status
$userrecord = \qbank_questionhub\auth_manager::get_user_token_record((int)$USER->id);
$hasaccount = !empty($userrecord) && !empty($userrecord->access_token);
$accountname = $hasaccount ? ($userrecord->hub_name ?: ($userrecord->hub_email ?: 'Verbunden')) : '';
$huburl = rtrim(get_config('qbank_questionhub', 'huburl') ?: 'https://hub.cblum.ch', '/');

// Course categories in this question bank context.
$categories = $DB->get_records('question_categories', ['contextid' => $bankcontext->id], 'sortorder, name');

// Packages the linked account has published (for withdrawal) and categories to publish.
$mypackages = [];
$mypackagesbyid = [];
if ($hasaccount) {
    try {
        $mypackagesraw = $client->get_my_packages();
        foreach ($mypackagesraw as $mp) {
            $mypackagesbyid[$mp['id']] = $mp;
            // Older Hubs list only created packages (no my_role): those are admin.
            $ispackageadmin = ($mp['my_role'] ?? 'admin') === 'admin';
            $versions = [];
            foreach ($mp['versions'] ?? [] as $v) {
                $versions[] = ['version' => $v, 'can_withdraw' => count($mp['versions']) > 1];
            }

            // Find local publication history for this package to calculate changes and offer update.
            $history = \qbank_questionhub\publication_tracker::latest_active_publication_by_package($mp['id'], (int)$COURSE->id);
            if (!$history) {
                $history = \qbank_questionhub\publication_tracker::latest_active_publication_by_package($mp['id'], 0);
            }
            $updateurl = '';

            $catid = $history ? (int)$history->categoryid : 0;
            if ($catid === 0) {
                // Try matching course category by title or slug in current course.
                $cleantitle = preg_replace('/[^a-z0-9]/', '', mb_strtolower(html_entity_decode($mp['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8')));
                foreach ($categories as $catcandidate) {
                    if ((int)$catcandidate->parent === 0 && $catcandidate->name === 'top') {
                        continue;
                    }
                    $cname = preg_replace('/[^a-z0-9]/', '', mb_strtolower($catcandidate->name));
                    if ($cname !== '' && ($cname === $cleantitle || str_ends_with($mp['id'], $cname))) {
                        $catid = (int)$catcandidate->id;
                        break;
                    }
                }
            }

            if ($catid > 0) {
                $category = $DB->get_record('question_categories', ['id' => $catid]);
                if ($category) {
                    $targetcourseid = ($history && (int)$history->courseid > 0) ? (int)$history->courseid : (int)$COURSE->id;
                    $updateparams = [
                        'courseid' => $targetcourseid,
                        'categoryid' => $catid,
                        'updatepackageid' => $mp['id'],
                    ];
                    if (!empty($cmid) && $targetcourseid === (int)$COURSE->id) {
                        $updateparams['cmid'] = $cmid;
                    }
                    $updateurl = (new moodle_url('/question/bank/questionhub/publish.php', $updateparams))->out(false);
                }
            }
            if (!$updateurl) {
                $updateparams = [
                    'courseid' => (int)$COURSE->id,
                    'updatepackageid' => $mp['id'],
                ];
                if (!empty($cmid)) {
                    $updateparams['cmid'] = $cmid;
                }
                $updateurl = (new moodle_url('/question/bank/questionhub/publish.php', $updateparams))->out(false);
            }

            if ($showmypackages) {
                $mypackages[] = [
                    'id' => $mp['id'],
                    'domid' => md5($mp['id']),
                    'title' => html_entity_decode($mp['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                    'latest_version' => $mp['latest_version'],
                    'downloads' => (int)($mp['downloads_count'] ?? 0),
                    'preview_on' => !empty($mp['preview_enabled']),
                    'is_private' => ($mp['visibility'] ?? 'public') === 'private',
                    'is_public' => ($mp['visibility'] ?? 'public') !== 'private',
                    'visibility' => $mp['visibility'] ?? 'public',
                    'summary' => $mp['summary'] ?? '',
                    'tags' => implode(', ', $mp['tags'] ?? []),
                    'github_repo' => (string)($mp['github_repo'] ?? ''),
                    'gh_package_id' => $mp['id'],
                    'gh_domid' => 'mp' . md5($mp['id']),
                    'lic_by' => ($mp['license'] ?? '') === 'CC-BY-4.0',
                    'lic_bysa' => ($mp['license'] ?? '') === 'CC-BY-SA-4.0',
                    'lic_cc0' => ($mp['license'] ?? '') === 'CC0-1.0',
                    'g1' => in_array('GYM1', $mp['grades'] ?? [], true),
                    'g2' => in_array('GYM2', $mp['grades'] ?? [], true),
                    'g3' => in_array('GYM3', $mp['grades'] ?? [], true),
                    'g4' => in_array('GYM4', $mp['grades'] ?? [], true),
                    'level_gf' => ($mp['subject_category'] ?? '') === 'GF',
                    'level_sf' => ($mp['subject_category'] ?? '') === 'SF',
                    'level_ef' => ($mp['subject_category'] ?? '') === 'EF',
                    'level_ff' => ($mp['subject_category'] ?? '') === 'FF',
                    'versions' => $versions,
                    'update_url' => $updateurl,
                    'is_package_admin' => $ispackageadmin,
                    'shared_role' => empty($mp['is_creator']) && isset($mp['my_role'])
                        ? get_string('package_role_' . ($ispackageadmin ? 'admin' : 'editor'), 'qbank_questionhub') : '',
                    // Settings forms of the category list are not on this tab; the package has its own.
                    'hide_settings_toggle' => true,
                ];
            }
        }
    } catch (\Exception $e) {
        // The list is a convenience; the page works without it.
        debugging($e->getMessage(), DEBUG_DEVELOPER);
    }
}

// Course categories as a tree (import target dropdown and "publish a whole category" list).
$categories = $DB->get_records('question_categories', ['contextid' => $bankcontext->id], 'sortorder, name');
$rootcategory = null;
foreach ($categories as $category) {
    if ((int)$category->parent === 0 && $category->name === 'top') {
        $rootcategory = $category;
        break;
    }
}
$children = [];
foreach ($categories as $cat) {
    $children[(int)$cat->parent][] = $cat;
}
$owncounts = [];
foreach ($categories as $cat) {
    $owncounts[(int)$cat->id] = (int)$DB->get_field_sql(
        'SELECT COUNT(1)
           FROM {question_bank_entries} qbe
           JOIN {question_versions} qv ON qv.questionbankentryid = qbe.id
           JOIN (SELECT questionbankentryid, MAX(version) AS maxversion
                   FROM {question_versions} GROUP BY questionbankentryid) latest
             ON latest.questionbankentryid = qv.questionbankentryid AND latest.maxversion = qv.version
           JOIN {question} q ON q.id = qv.questionid
          WHERE qbe.questioncategoryid = :categoryid AND q.parent = 0',
        ['categoryid' => $cat->id]
    );
}
$totalcount = function (int $id) use (&$totalcount, $children, $owncounts): int {
    $sum = $owncounts[$id] ?? 0;
    foreach ($children[$id] ?? [] as $child) {
        $sum += $totalcount((int)$child->id);
    }
    return $sum;
};
$duplicatecandidatesbycategory = [];
foreach ($duplicategroups as $duplicategroup) {
    foreach ($duplicategroup['remove'] as $duplicate) {
        $categoryid = (int)$duplicate->categoryid;
        $duplicatecandidatesbycategory[$categoryid] = ($duplicatecandidatesbycategory[$categoryid] ?? 0) + 1;
    }
}
$duplicatecountforcategory = function (int $id) use (&$duplicatecountforcategory, $children, $duplicatecandidatesbycategory): int {
    $count = $duplicatecandidatesbycategory[$id] ?? 0;
    foreach ($children[$id] ?? [] as $child) {
        $count += $duplicatecountforcategory((int)$child->id);
    }
    return $count;
};

$coursecategories = [];
$publishcategories = [];
$remotepackages = [];
$remotepackageerrors = [];
$remotepackagesmissing = [];
$getremotepackage = static function(string $packageid) use (&$remotepackages, &$remotepackageerrors,
        &$remotepackagesmissing, $client): ?array {
    if ($packageid === '') {
        return null;
    }
    if (array_key_exists($packageid, $remotepackages)) {
        return $remotepackages[$packageid];
    }
    if (isset($remotepackageerrors[$packageid])) {
        return null;
    }
    try {
        return $remotepackages[$packageid] = $client->get_package($packageid);
    } catch (\Throwable $e) {
        $remotepackageerrors[$packageid] = true;
        if ($e instanceof \moodle_exception && $e->errorcode === 'error_package_withdrawn') {
            $remotepackagesmissing[$packageid] = 'withdrawn';
        } else if ($e instanceof \moodle_exception && $e->errorcode === 'error_notfound') {
            // Unknown ID: deleted before the Hub kept tombstones, or published to another Hub.
            $remotepackagesmissing[$packageid] = 'notfound';
        }
        return null;
    }
};
$canimport = has_capability('qbank/questionhub:import', $bankcontext);
$canmanagecategories = has_capability('moodle/question:managecategory', $bankcontext);
$candeletecategories = $canmanagecategories && has_capability('moodle/question:editall', $bankcontext);
$categorymanager = new \core_question\category_manager();
$subcategorycount = function (int $id) use (&$subcategorycount, $children): int {
    $count = 0;
    foreach ($children[$id] ?? [] as $child) {
        $count += 1 + $subcategorycount((int)$child->id);
    }
    return $count;
};
$walk = function (int $parentid, int $depth) use (&$walk, $children, $owncounts, $totalcount, $duplicatecountforcategory, &$coursecategories,
        &$publishcategories, $COURSE, $cmid, $bankcontext, $mypackagesbyid, $getremotepackage, $canimport, $huburl, &$remotepackagesmissing,
        $canmanagecategories, $candeletecategories, $categorymanager, $subcategorycount) {
    foreach ($children[$parentid] ?? [] as $cat) {
        $istop = ($cat->name === 'top' && $parentid === 0);
        $name = $cat->name;
        $level = $istop ? 0 : max(1, $depth);
        if (!$istop) {
            $isfirst = empty($coursecategories);
            $coursecategories[] = [
                'id' => $cat->id,
                'name' => str_repeat("\u{00A0}\u{00A0}\u{00A0}", max(0, $level - 1)) . ($level > 1 ? "\u{2514} " : '') . $name,
                'display_name' => str_repeat("\u{00A0}\u{00A0}\u{00A0}", max(0, $level - 1)) . ($level > 1 ? "\u{2514} " : '') . $name . ' (' . get_string('questions_count', 'qbank_questionhub', $owncounts[(int)$cat->id] ?? 0) . ')',
                'question_count' => $owncounts[(int)$cat->id] ?? 0,
                'is_root' => false,
                'can_rename' => ($owncounts[(int)$cat->id] ?? 0) === 0 && empty($children[(int)$cat->id]),
                'selected' => $isfirst,
            ];
            $publishparams = ['courseid' => $COURSE->id, 'categoryid' => $cat->id,
                'bankcontextid' => $bankcontext->id];
            // Core requires both category and context IDs. Moodle 5.0 banks are mod_qbank modules and need
            // their cmid; edit.php redirects any request with courseid to the bank overview there.
            $questionbankparams = ['cat' => $cat->id . ',' . $bankcontext->id];
            if ($bankcontext->contextlevel == CONTEXT_MODULE) {
                $questionbankparams['cmid'] = $bankcontext->instanceid;
            } else {
                $questionbankparams['courseid'] = $COURSE->id;
            }
            if (!empty($cmid)) {
                $publishparams['cmid'] = $cmid;
            }
            $own = $owncounts[(int)$cat->id] ?? 0;
            $total = $totalcount((int)$cat->id);
            $categoryduplicatecount = $duplicatecountforcategory((int)$cat->id);

            $directlink = \qbank_questionhub\publication_tracker::latest((int)$COURSE->id, (int)$cat->id);
            $latestpub = \qbank_questionhub\publication_tracker::find_local_publication_for_category((int)$cat->id, (int)$COURSE->id);
            $isunlinked = \qbank_questionhub\publication_tracker::is_unlinked((int)$COURSE->id, (int)$cat->id);
            $matchedpackage = null;
            if ($latestpub && !empty($latestpub->package_id)) {
                $matchedpackage = $mypackagesbyid[$latestpub->package_id] ?? null;
            }
            if (!$matchedpackage && !$isunlinked) {
                // Try matching by category name or slug to an owned package in My Packages.
                $cleanname = preg_replace('/[^a-z0-9]/', '', mb_strtolower($name));
                foreach ($mypackagesbyid as $mp) {
                    $mptitle = trim(mb_strtolower(html_entity_decode($mp['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8')));
                    $cleantitle = preg_replace('/[^a-z0-9]/', '', $mptitle);
                    if ($cleanname !== '' && ($cleanname === $cleantitle || str_ends_with($mp['id'], $cleanname) || $mptitle === trim(mb_strtolower($name)))) {
                        $matchedpackage = $mp;
                        break;
                    }
                }
            }

            $ispublished = ($directlink !== null) || ($latestpub !== null) || ($matchedpackage !== null);
            $islinked = $directlink !== null || $latestpub !== null;
            $ispackagesource = false;
            $changescount = 0;
            $haschanges = false;
            $changesknown = false;
            $updateurl = '';
            $pkgid = '';
            $pkgversion = '';
            $pkgdetails = null;
            $localstatus = '';
            $haslocalchanges = false;
            $localchangesknown = false;
            $remoteversion = '';
            $hasremoteupdate = false;
            $remotechecked = false;
            $pulltargetid = 0;
            $pulltitle = '';
            $missingonhub = false;
            $linkcategoryid = 0;
            $linkaction = '';
            $linktitle = '';

            if ($ispublished) {
                $linkhistory = $directlink ?: $latestpub;
                $linkcategoryid = $linkhistory ? (int)$linkhistory->categoryid : 0;
                $linkaction = $linkhistory ? (string)$linkhistory->action : '';
                $linktitle = $linkhistory ? html_entity_decode((string)$linkhistory->package_title,
                    ENT_QUOTES | ENT_HTML5, 'UTF-8') : '';
                $pkgid = $linkhistory ? $linkhistory->package_id : $matchedpackage['id'];
                $pkgversion = $linkhistory ? $linkhistory->package_version : $matchedpackage['latest_version'];
                $mp = $matchedpackage ?: ($mypackagesbyid[$pkgid] ?? null);
                // Push belongs to the category holding the link, and only for accounts that may publish
                // versions of the package on the Hub (creator, editor or admin); settings only for admins.
                $mprole = $mp ? (string)($mp['my_role'] ?? 'admin') : '';
                $ispackagesource = $linkhistory && (int)$linkhistory->categoryid === (int)$cat->id &&
                    in_array($mprole, ['admin', 'editor'], true);
                $statushistory = $linkhistory;
                if ($statushistory) {
                    $linkchanges = \qbank_questionhub\publication_tracker::changes_in_scope((int)$cat->id, $statushistory);
                    if ($linkchanges !== null) {
                        $localchangesknown = true;
                        $haslocalchanges = $linkchanges['count'] > 0;
                        $localstatus = $haslocalchanges ? (string)$linkchanges['count'] : '0';
                    }
                    $remote = $getremotepackage($pkgid);
                    if ($remote) {
                        $remotechecked = true;
                        $remoteversion = (string)($remote['latest_version'] ?? '');
                        $hasremoteupdate = $remoteversion !== '' && $pkgversion !== '' &&
                            version_compare($remoteversion, $pkgversion, '>');
                    }
                    $missingonhub = $remotepackagesmissing[$pkgid] ?? false;
                    // Pull into the linked category (import target or own package source), so the
                    // existing questions are updated. A pull into the source keeps it the source
                    // (publication_tracker::is_own_link()).
                    if ($hasremoteupdate && $canimport) {
                        $pulltargetid = (int)$statushistory->categoryid;
                        $pulltitle = html_entity_decode((string)($remote['title'] ?? $statushistory->package_title),
                            ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    }
                }

                if ($ispackagesource) {
                    $changes = \qbank_questionhub\publication_tracker::changes((int)$cat->id, $linkhistory);
                    if ($changes !== null) {
                        $changescount = $changes['count'];
                        $haschanges = ($changescount > 0);
                        $changesknown = true;
                    } else {
                        $changesknown = false;
                        $haschanges = true;
                    }
                    $updateparams = array_merge($publishparams, ['updatepackageid' => $pkgid]);
                    $updateurl = (new moodle_url('/question/bank/questionhub/publish.php', $updateparams))->out(false);

                    $pkgdetails = $mprole !== 'admin' ? null : [
                    'packageid' => $pkgid,
                    'domid' => md5($pkgid . '_' . $cat->id),
                    'title' => $mp ? html_entity_decode($mp['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8') : ($linkhistory->package_title ?: $name),
                    'summary' => $mp['summary'] ?? '',
                    'tags' => implode(', ', $mp['tags'] ?? []),
                    'github_repo' => (string)($mp['github_repo'] ?? ''),
                    'gh_package_id' => $pkgid,
                    'gh_domid' => 'cat' . md5($pkgid . '_' . $cat->id),
                    'is_private' => ($mp['visibility'] ?? 'public') === 'private',
                    'is_public' => ($mp['visibility'] ?? 'public') !== 'private',
                    'lic_by' => ($mp['license'] ?? 'CC-BY-4.0') === 'CC-BY-4.0',
                    'lic_bysa' => ($mp['license'] ?? '') === 'CC-BY-SA-4.0',
                    'lic_cc0' => ($mp['license'] ?? '') === 'CC0-1.0',
                    'preview_on' => !empty($mp['preview_enabled']),
                    'g1' => in_array('GYM1', $mp['grades'] ?? [], true),
                    'g2' => in_array('GYM2', $mp['grades'] ?? ['GYM2', 'GYM3'], true),
                    'g3' => in_array('GYM3', $mp['grades'] ?? ['GYM2', 'GYM3'], true),
                    'g4' => in_array('GYM4', $mp['grades'] ?? [], true),
                    'level_gf' => ($mp['subject_category'] ?? '') === 'GF',
                    'level_sf' => ($mp['subject_category'] ?? '') === 'SF',
                    'level_ef' => ($mp['subject_category'] ?? '') === 'EF',
                    'level_ff' => ($mp['subject_category'] ?? '') === 'FF',
                    ];
                }
            }

            // Local changes not yet on the Hub; for the package source, removed questions count too.
            $aheadknown = $ispackagesource ? $changesknown : $localchangesknown;
            $aheadcount = $ispackagesource ? $changescount : ($haslocalchanges ? (int)$localstatus : 0);

            $publishcategories[] = [
                'id' => $cat->id,
                'name' => $name,
                'question_bank_url' => (new moodle_url('/question/edit.php', $questionbankparams))->out(false),
                'url' => (new moodle_url('/question/bank/questionhub/publish.php', $publishparams))->out(false),
                'indent' => max(0, $level - 1) * 1.5,
                'is_child' => $level > 1,
                'is_parent' => !empty($children[(int)$cat->id]),
                'own' => $own,
                'total' => $total,
                'has_sub_total' => $total !== $own,
                'has_duplicates' => $categoryduplicatecount > 0,
                'duplicate_count' => $categoryduplicatecount,
                'is_published' => $ispublished,
                'is_linked' => $islinked,
                'is_unlinked' => $isunlinked,
                'has_ahead' => $islinked && $aheadknown && $aheadcount > 0,
                'ahead_count' => $aheadcount,
                'remote_version' => $remoteversion,
                'has_remote_update' => $islinked && $hasremoteupdate,
                'is_in_sync' => $islinked && $aheadknown && $aheadcount === 0 && $remotechecked && !$hasremoteupdate,
                'can_pull' => $pulltargetid > 0,
                'pull_target_id' => $pulltargetid,
                'pull_title' => $pulltitle,
                // A withdrawn package can no longer be updated; offer a new publication instead.
                'is_package_source' => $ispackagesource && !$missingonhub,
                'is_missing_on_hub' => $islinked && $missingonhub !== false,
                'is_withdrawn_on_hub' => $islinked && $missingonhub === 'withdrawn',
                'can_republish' => $islinked && $missingonhub !== false && $linkaction === 'publish',
                'link_category_id' => $linkcategoryid,
                // Subcategory of a package category: Hub actions belong to the category holding the link.
                'is_inherited_link' => $islinked && $linkcategoryid > 0 && $linkcategoryid !== (int)$cat->id,
                'link_action' => $linkaction,
                'link_title' => $linktitle,
                'package_id' => $pkgid,
                'package_version' => $pkgversion,
                'hub_package_url' => $pkgid !== '' ? $huburl . '/packages/' . rawurlencode($pkgid) : '',
                'has_changes' => $haschanges,
                'update_url' => $updateurl,
                'review_url' => (new moodle_url('/question/bank/questionhub/review.php', $publishparams))->out(false),
                'pkg_settings' => $pkgdetails,
                'can_edit_category' => $canmanagecategories,
                'can_delete_category' => $candeletecategories &&
                    !$categorymanager->is_only_child_of_top_category_in_context((int)$cat->id),
                'category_parent' => (int)$cat->parent,
                'category_sortorder' => (int)$cat->sortorder,
                'category_delete_confirm' => get_string('category_delete_confirm', 'qbank_questionhub', (object)[
                    'name' => $name, 'subcategories' => $subcategorycount((int)$cat->id), 'questions' => $total,
                ]),
            ];
        }
        $walk((int)$cat->id, $istop ? 1 : $depth + 1);
    }
};
$walk(0, 0);

// Browse cards of packages already linked to a category here show that category's sync status and
// actions instead of "Import" (a second import would create a separate copy). Subcategories inherit
// the link, so prefer the row of the category that holds it.
$syncrowsbypackage = [];
foreach ($publishcategories as $row) {
    if (!$row['is_linked'] || $row['package_id'] === '') {
        continue;
    }
    $current = $syncrowsbypackage[$row['package_id']] ?? null;
    if ($current === null || ((int)$current['link_category_id'] !== (int)$current['id']
            && (int)$row['link_category_id'] === (int)$row['id'])) {
        $syncrowsbypackage[$row['package_id']] = $row;
    }
}
foreach ($packagesdata as &$packagecard) {
    $packagecard['sync'] = $syncrowsbypackage[$packagecard['id']] ?? null;
}
unset($packagecard);
// My packages uses the same sync row, so status and push/pull follow the same rules everywhere.
foreach ($mypackages as &$mypackage) {
    $mypackage['sync'] = $syncrowsbypackage[$mypackage['id']] ?? null;
}
unset($mypackage);

// The library lists the Hub packages synced with a local category in this course, imported ones and
// own publications alike, i.e. the Browse packages that show sync status instead of "Import". Plain
// file imports (CLI, no package ID) and removed links are not part of it.
$library = [];
if ($showlibrary) {
    foreach ($syncrowsbypackage as $packageid => $row) {
        $remote = $getremotepackage($packageid);
        $title = html_entity_decode((string)($remote['title'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // Older releases on the Hub can be imported again into the linked category (preview first).
        $restoreversions = [];
        if ($canimport && (int)$row['link_category_id'] === (int)$row['id'] && $row['package_version'] !== '') {
            foreach ($remote['versions'] ?? [] as $v) {
                if (version_compare((string)$v, $row['package_version'], '<')) {
                    $restoreversions[] = ['version' => (string)$v];
                }
            }
            usort($restoreversions, static fn(array $a, array $b): int => version_compare($b['version'], $a['version']));
        }
        $library[] = $row + ['title' => $title !== '' ? $title : ($row['link_title'] ?: $packageid),
            'hide_settings_toggle' => true, 'restore_versions' => $restoreversions, 'can_restore' => !empty($restoreversions)];
    }
    usort($library, static fn(array $a, array $b): int => strcasecmp($a['title'], $b['title']));
}
if ($rootcategory) {
    array_unshift($coursecategories, [
        'id' => $rootcategory->id,
        'name' => get_string('questionbankroot', 'qbank_questionhub'),
        'display_name' => get_string('questionbankroot', 'qbank_questionhub') . ' (' . get_string('questions_count', 'qbank_questionhub', $owncounts[(int)$rootcategory->id] ?? 0) . ')',
        'question_count' => $owncounts[(int)$rootcategory->id] ?? 0,
        'is_root' => true,
        'can_rename' => false,
        'selected' => false,
    ]);
}

// Build URLs
$reseturl = new moodle_url('/question/bank/questionhub/view.php', ['courseid' => $COURSE->id]);
$reseturl->param('bankcontextid', $bankcontext->id);
if (!empty($cmid)) {
    $reseturl->param('cmid', $cmid);
}
$browseurl = clone $reseturl;
$browseurl->param('section', 'browse');
$libraryurl = clone $reseturl;
$libraryurl->param('section', 'library');
$mypackagesurl = clone $reseturl;
$mypackagesurl->param('section', 'my_packages');

$connecturl = \qbank_questionhub\auth_manager::init_oauth_flow($thispageurl->out(false));
$disconnecturl = new moodle_url('/question/bank/questionhub/disconnect.php', [
    'sesskey' => sesskey(),
    'returnurl' => $thispageurl->out_as_local_url(false),
]);

// Pagination URLs
$pagination = $packageresult['pagination'] ?? ['page' => 1, 'total_pages' => 1];
$prevurl = clone $thispageurl;
$prevurl->param('page', max(1, $page - 1));
$nexturl = clone $thispageurl;
$nexturl->param('page', min($pagination['total_pages'] ?? 1, $page + 1));

$templatedata = [
    'bank_contexts' => array_values(array_map(static function($context) use ($COURSE, $cmid, $bankcontext) {
        $url = new moodle_url('/question/bank/questionhub/view.php', [
            'courseid' => $COURSE->id, 'bankcontextid' => $context->id,
            'section' => optional_param('section', 'browse', PARAM_ALPHANUMEXT),
        ]);
        if ($cmid) {
            $url->param('cmid', $cmid);
        }
        return [
            'name' => get_string($context->contextlevel === CONTEXT_COURSECAT ?
                'question_bank_course_category' : 'question_bank_course',
                'qbank_questionhub', $context->get_context_name(false)),
            'url' => $url->out(false),
            'selected' => (int)$context->id === (int)$bankcontext->id,
        ];
    }, $bankcontexts)),
    'has_multiple_bank_contexts' => count($bankcontexts) > 1,
    'bankcontextid' => $bankcontext->id,
    'has_account' => $hasaccount,
    'account_name' => $accountname,
    'connect_url' => $connecturl,
    'disconnect_url' => $disconnecturl->out(false),
    'browser_url' => (new moodle_url('/question/bank/questionhub/view.php'))->out(false),
    'action_url' => $thispageurl->out(false),
    'reset_url' => $reseturl->out(false),
    'browse_tab_url' => $browseurl->out(false),
    'library_tab_url' => $libraryurl->out(false),
    'my_packages_tab_url' => $mypackagesurl->out(false),
    'section_browse' => $showbrowse,
    'section_library' => $showlibrary,
    'section_my_packages' => $showmypackages,
    'show_browse' => $showbrowse,
    'show_library' => $showlibrary,
    'show_my_packages' => $showmypackages,
    'library' => $library,
    'has_library' => !empty($library),
    'hub_url' => $huburl,
    // Package settings (browse and my packages) offer the GitHub link; asks the Hub only there.
] + ($hasaccount && ($showbrowse || $showmypackages) ? $client->github_template_data($huburl) : []) + [
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
    'publish_categories' => $publishcategories,
    'category_naming_note' => get_string('category_naming_note', 'qbank_questionhub'),
    'my_packages' => $mypackages,
    'has_my_packages' => !empty($mypackages),
    'pagination' => $pagination,
    'has_prev' => ($page > 1),
    'has_next' => ($page < ($pagination['total_pages'] ?? 1)),
    'import_preview' => $importpreview,
    'has_duplicates' => $duplicatecount > 0,
    'duplicate_count' => $duplicatecount,
    'duplicate_categories' => (function() use ($duplicatepreview, $bankcontext): array {
        $categories = [];
        $formatrow = static function($row, bool $keep, string $previewid) use ($bankcontext): array {
            return [
                'name' => $row->name,
                'question_id' => $row->questionid,
                'entry_id' => $row->entryid,
                'question_version' => $row->questionversion,
                'created_at' => userdate($row->timecreated, get_string('strftimedatetime', 'langconfig')),
                'modified_at' => userdate($row->timemodified, get_string('strftimedatetime', 'langconfig')),
                'package_id' => $row->packageid ?: '—',
                'package_version' => $row->packageversion ?: '—',
                'package_title' => $row->packagetitle ?: '—',
                'package_action' => $row->packageaction ?: '—',
                'package_at' => $row->packagetime ? userdate($row->packagetime, get_string('strftimedatetime', 'langconfig')) : '—',
                'is_keep' => $keep,
                'is_delete' => !$keep,
                'preview_id' => $previewid,
                'preview_html' => format_text($row->questiontext, FORMAT_HTML, ['context' => $bankcontext]),
            ];
        };
        foreach ($duplicatepreview as $groupindex => $group) {
            $categoryid = (int)$group['keep']->categoryid;
            if (!isset($categories[$categoryid])) {
                $categories[$categoryid] = [
                    'id' => 'qh-duplicate-category-' . $categoryid,
                    'name' => $group['keep']->categoryname,
                    'groups' => [],
                ];
            }
            $rows = [$formatrow($group['keep'], true, 'qh-duplicate-preview-' . $groupindex . '-keep')];
            foreach ($group['remove'] as $rowindex => $row) {
                $rows[] = $formatrow($row, false, 'qh-duplicate-preview-' . $groupindex . '-' . $rowindex);
            }
            $categories[$categoryid]['groups'][] = ['name' => $group['keep']->name, 'rows' => $rows];
        }
        $categories = array_values($categories);
        foreach ($categories as $index => &$category) {
            $category['expanded'] = $index === 0;
        }
        return $categories;
    })(),
    'has_duplicate_preview' => !empty($duplicatepreview),
    'duplicate_preview_count' => array_sum(array_map(static fn($group) => count($group['remove']), $duplicatepreview)),
    'duplicate_category_id' => $duplicatecategoryid,
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
