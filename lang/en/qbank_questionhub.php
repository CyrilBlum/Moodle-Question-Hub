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
 * English strings for qbank_questionhub.
 *
 * @package    qbank_questionhub
 * @copyright  2026 Cyril Wendl
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Question Hub';
$string['questionhub'] = 'Question Hub';
$string['browsehub'] = 'Browse Question Hub';
$string['exportcategorytohub'] = 'Export Category to Hub';
$string['publishtohub'] = 'Export to Question Hub';
$string['publishselected'] = 'Publish selected to Question Hub';

// Settings
$string['huburl'] = 'Hub Base URL';
$string['huburl_desc'] = 'The base URL of the Moodle Question Hub marketplace (e.g. https://hub.cblum.ch).';
$string['clientid'] = 'OAuth 2.0 Client ID';
$string['clientid_desc'] = 'Client ID configured for OAuth 2.0 PKCE authentication with the marketplace.';
$string['institutiontoken'] = 'Institutional Access Token';
$string['institutiontoken_desc'] = 'Optional fallback Personal Access Token (PAT) for system-level queries or unlinked teachers.';
$string['allowteacherlinking'] = 'Allow Teacher Account Linking';
$string['allowteacherlinking_desc'] = 'If enabled, individual teachers can link their Question Hub marketplace accounts via OAuth 2.0 PKCE.';

// Capabilities
$string['questionhub:view'] = 'View and search Question Hub collections';
$string['questionhub:import'] = 'Import Question Hub packages into question banks';
$string['questionhub:export'] = 'Export questions or categories to Question Hub packages';
$string['questionhub:publish'] = 'Publish question packages directly to Question Hub marketplace';
$string['questionhub:manage'] = 'Manage Question Hub administrative configuration';

// Browser UI
$string['search_placeholder'] = 'Search collections by keyword, topic, or question type...';
$string['filter_subject'] = 'Subject';
$string['filter_level'] = 'Course Level';
$string['filter_canton'] = 'Canton';
$string['filter_grade'] = 'Grade';
$string['all_subjects'] = 'All Subjects';
$string['all_levels'] = 'All Levels';
$string['all_cantons'] = 'All Cantons';
$string['all_grades'] = 'All Grades';
$string['search_button'] = 'Search';
$string['reset_filters'] = 'Reset';
$string['no_packages_found'] = 'No packages found matching your criteria.';
$string['package_version'] = 'Version';
$string['package_license'] = 'License';
$string['package_visibility_label'] = 'Visibility';
$string['package_visibility_public'] = 'Public';
$string['package_visibility_private'] = 'Private – access after approval';
$string['private_package_badge'] = 'Private';
$string['private_package_summary'] = 'Private question package. Only limited information is shown.';
$string['request_access'] = 'Request access';
$string['package_author'] = 'Author';
$string['questions_count'] = '{$a} questions';
$string['preview_package'] = 'Preview';
$string['import_package'] = 'Import to question bank';
$string['target_category'] = 'Target category';
$string['import_confirm_title'] = 'Import Question Hub Package';
$string['import_button'] = 'Import Questions';
$string['import_success'] = 'Successfully imported {$a} questions into your question bank!';
$string['export_success'] = 'Package published successfully with version {$a}!';

// Account Linking
$string['account_linked_as'] = 'Linked to Question Hub as: {$a}';
$string['account_not_linked'] = 'Your account is not linked to Question Hub.';
$string['link_account_button'] = 'Connect Question Hub Account';
$string['disconnect_account_button'] = 'Disconnect';
$string['disconnect_success'] = 'Your Question Hub account has been disconnected.';
$string['pat_token_label'] = 'Or enter Personal Access Token (PAT):';
$string['save_token'] = 'Save Token';
$string['token_saved'] = 'Personal Access Token saved successfully.';

// Publishing UI
$string['publish_title'] = 'Publish to Question Hub';
$string['package_id'] = 'Package Identifier (Reverse Domain, e.g. ch.gym.inf.trees)';
$string['package_title_label'] = 'Package Title';
$string['package_summary_label'] = 'Summary / Description';
$string['package_subject_label'] = 'Swiss Gymnasium Subject';
$string['package_level_label'] = 'Course Level';
$string['question_bank_context'] = 'Question bank';
$string['question_bank_course'] = 'Course: {$a}';
$string['question_bank_course_category'] = 'Course category: {$a}';
$string['package_canton_label'] = 'Target Canton';
$string['package_grades_label'] = 'Target Levels (Ctrl/Cmd-click for several)';
$string['package_version_label'] = 'Release Version (SemVer, e.g. 1.0.0)';
$string['package_license_label'] = 'License';
$string['publish_button'] = 'Publish to Question Hub';
$string['download_xml_button'] = 'Download Moodle XML File';

// Preflight & Errors
$string['preflight_validation'] = 'Pre-flight Package Diagnostics';
$string['preflight_passed'] = 'Package passed all pre-flight compatibility checks.';
$string['preflight_warnings_found'] = 'Pre-flight warnings detected (import is still allowed):';
$string['preflight_errors_found'] = 'Pre-flight validation failed with blocking errors:';
$string['error_xxe_detected'] = 'Security error: XML External Entity (XXE) or DOCTYPE declaration detected. The package was rejected.';
$string['error_invalid_xml'] = 'Invalid XML syntax in question package.';
$string['error_network'] = 'Network error communicating with Question Hub marketplace: {$a}';
$string['error_unauthorized'] = 'Authentication failed. Please check your token or link your account.';
$string['error_forbidden'] = 'Permission denied on Question Hub marketplace.';
$string['error_notfound'] = 'Requested resource not found on Question Hub marketplace.';
$string['error_import_failed'] = 'Import failed during Moodle core question creation: {$a}';
$string['error_empty_category'] = 'Selected category contains no exportable questions.';

// Privacy
$string['privacy:metadata'] = 'The Question Hub plugin stores user access tokens and import/export audit logs.';
$string['privacy:metadata:tokens:tableexplanation'] = 'Stores user OAuth tokens and linked marketplace profile.';
$string['privacy:metadata:tokens:userid'] = 'Moodle user ID.';
$string['privacy:metadata:tokens:access_token'] = 'Marketplace access token.';
$string['privacy:metadata:tokens:hub_user_id'] = 'Question Hub marketplace user UUID.';
$string['privacy:metadata:tokens:hub_email'] = 'Question Hub email address.';
$string['privacy:metadata:tokens:hub_name'] = 'Question Hub display name.';
$string['privacy:metadata:tokens:timecreated'] = 'Timestamp when token was created.';
$string['privacy:metadata:history:tableexplanation'] = 'Audit log of question package import and publish actions.';
$string['privacy:metadata:history:userid'] = 'Moodle user ID.';
$string['privacy:metadata:history:action'] = 'Action performed (import or publish).';
$string['privacy:metadata:history:package_id'] = 'Question Hub package identifier.';
$string['privacy:metadata:history:package_version'] = 'Question Hub package release version.';
$string['privacy:metadata:history:timecreated'] = 'Timestamp of action.';
$string['privacy:metadata:marketplace:explanation'] = 'Question Hub connects to the external Question Hub marketplace to discover, download, and publish question packages.';
$string['privacy:metadata:marketplace:email'] = 'User email sent for account linking.';
$string['package_preview_label'] = 'Show a public preview of the questions on the Hub';
$string['package_preview_help'] = 'Model answers and feedback are never shown. Without a preview, visitors only see the description.';
$string['github_repo_label'] = 'GitHub repository (optional)';
$string['github_repo_help'] = 'Optional, not needed for the Hub. Every new version is committed to this repository as the file PACKAGE-ID.xml, with the release notes as commit message, and tagged with v and the version number. Requires a GitHub connection in your Hub account (Dashboard, section GitHub-Verbindung) with write access to the repository. A repository that does not exist yet is created as a private repository (the token then needs access to all repositories and Administration: Read and write). Linking an existing package commits its current version at once. Leave the field empty to unlink.';
$string['github_commit_done'] = 'Committed to GitHub: {$a}';
$string['github_connect_hint'] = 'To set up versioning on GitHub, connect your Hub account to GitHub with a token at {$a} (section GitHub-Verbindung).';
$string['github_new_repo_label'] = 'New GitHub repository';
$string['github_new_repo_help'] = 'Created as a private repository in your GitHub account {$a}. The current version is committed right away, every later version too.';
$string['github_create_link_button'] = 'Create and link repository';
$string['github_repo_created_named'] = 'Private GitHub repository created: {$a}';
$string['github_mode_none'] = 'No GitHub';
$string['github_mode_new'] = 'Create a new private repository in {$a}:';
$string['github_mode_existing'] = 'Use an existing repository:';
$string['github_push_linked'] = 'This version is also committed to GitHub ({$a}), with the release notes as commit message.';
$string['github_push_linked_unknown'] = 'This version is also committed to the GitHub repository linked to the package, with the release notes as commit message.';
$string['github_push_unlinked'] = 'This package is not linked to GitHub. Package admins can link or create a repository in the package settings.';
$string['error_hub_rejected'] = 'Question Hub refused the request: {$a}';
$string['github_repo_created'] = 'The private GitHub repository was created.';
$string['github_commit_failed'] = 'The data is saved on the Hub, but the GitHub commit failed: {$a}';
$string['error_oauth_failed'] = 'Linking your Question Hub account failed: {$a}. Please try again or use a Personal Access Token.';
$string['no_packages'] = 'You have not published any packages yet.';
$string['withdraw_package'] = 'Withdraw package';
$string['withdraw_version'] = 'Withdraw version';
$string['withdraw_package_confirm'] = 'Withdraw this package with all versions from the Hub? Questions already imported into Moodle stay there.';
$string['withdraw_version_confirm'] = 'Withdraw this version? The version number can never be reused.';
$string['withdraw_versions_heading'] = 'Withdraw versions';
$string['withdraw_last_version_hint'] = 'Last version: withdraw the whole package instead.';
$string['withdraw_done'] = 'Withdrawn from Question Hub.';
$string['preview_on'] = 'with preview';
$string['preview_off'] = 'no preview';
$string['publish_category_heading'] = 'Categories and Hub sync';
$string['publish_category_button'] = 'Publish category';
$string['edit_settings'] = 'Settings';
$string['save_settings'] = 'Save settings';
$string['settings_saved'] = 'Settings saved on the Question Hub.';
$string['version_immutable_note'] = 'Version numbers are immutable: publish a new version for changed content, or withdraw an existing one.';
$string['incl_subcategories'] = 'incl. subcategories';
$string['hub_tab_browse'] = 'Browse';
$string['hub_tab_library'] = 'My library';
$string['hub_tab_my_packages'] = 'My packages';
$string['library_empty'] = 'No Question Hub package is currently synced with a category in this course.';
$string['my_packages'] = 'My packages (created or edited)';
$string['package_role_admin'] = 'Admin';
$string['package_role_editor'] = 'Editor';
$string['library_restore'] = 'Restore an older version';
$string['library_restore_help'] = 'Imports an older release of this package into the linked category again. You see a preview first; questions changed since then are reset to that version, questions added later stay. The category then offers to pull the latest version again.';
$string['library_restore_version'] = 'Version to restore';
$string['library_restore_button'] = 'Preview restore';
$string['open_hub_package'] = 'Open in Hub';
$string['questionbankroot'] = 'Question bank root';
$string['target_category_help'] = 'The number shows how many questions are directly in this category. Questions in subcategories are not included. Renaming is available only for an empty category with no subcategories.';
$string['category_placement'] = 'Target category name and contents';
$string['rename_empty_target'] = 'Rename the empty target category to';
$string['rename_empty_target_help'] = 'The selected category will take the name of the package’s top folder. This is available only when the category has no questions or subcategories.';
$string['keep_target_name'] = 'Keep the target category name';
$string['keep_target_name_description'] = 'Questions go into this category. Subcategories from the package are added below it.';
$string['keep_target_name_help'] = 'The selected category keeps its name. Questions from the package’s top folder go here, and its subcategories are added below.';
$string['import_update_changed'] = 'Add new questions and update changed ones';
$string['import_update_changed_help'] = 'New questions are added. Changed questions from an earlier import get a new version, and unchanged questions are skipped. Questions without an ID are matched by name and category, so a renamed question appears new. Clear this box to add every question as a separate copy.';
$string['import_preview_button'] = 'Preview changes';
$string['import_preview_heading'] = 'Import preview';
$string['import_preview_explanation'] = 'No questions or categories were changed. The import will be checked again when you confirm.';
$string['import_preview_new'] = 'new';
$string['import_preview_updated'] = 'to update';
$string['import_preview_unchanged'] = 'unchanged';
$string['import_preview_new_heading'] = 'Questions to add';
$string['import_preview_updated_heading'] = 'Questions to update';
$string['import_preview_uncertain'] = 'Previous content could not be compared';
$string['import_preview_unchanged_heading'] = 'Questions to skip';
$string['import_preview_confirm'] = 'Import these changes';
$string['import_preview_cancel'] = 'Back to packages';
$string['import_result_summary'] = 'Import complete: {$a->added} added, {$a->updated} updated, {$a->unchanged} unchanged.';

// Synchronization and update status
$string['sync_column_title'] = 'Hub';
$string['badge_changed'] = 'Modified';
$string['badge_changed_help'] = 'This question was modified since the last release on the Hub and has not been pushed yet.';
$string['badge_new'] = 'New';
$string['badge_new_help'] = 'This question was created after the last release and has not been pushed to the Hub yet.';
$string['badge_synced'] = 'Synchronised';
$string['badge_synced_help'] = 'Matches the latest published release on Question Hub.';
$string['badge_unknown'] = 'Unknown';
$string['badge_unknown_help'] = 'The package was published before version snapshotting was enabled.';
$string['update_package_button'] = 'Update package';
$string['published_badge'] = 'Published';
$string['error_update_source'] = 'The category does not match the specified package.';
$string['error_update_version'] = 'The new version number must be higher than the existing version v{$a}.';
$string['publication_tracking_failed'] = 'Failed to record published question snapshot.';
$string['duplicates_found'] = '{$a} removable duplicate questions found.';
$string['review_duplicates'] = 'Review duplicates';
$string['duplicate_review_heading'] = 'Confirm duplicate cleanup';
$string['duplicate_review_intro'] = '{$a} duplicate questions will be deleted; the oldest identical question is kept.';
$string['duplicate_delete_confirm'] = 'Delete {$a} duplicate questions? This cannot be undone.';
$string['delete_duplicates'] = 'Delete {$a} duplicate questions';
$string['duplicates_deleted'] = '{$a} duplicate questions deleted.';
$string['duplicate_keep'] = 'Question kept';
$string['duplicate_remove'] = 'Questions to delete';
$string['duplicate_category'] = 'Category';
$string['duplicate_question_id'] = 'Question ID';
$string['duplicate_entry_id'] = 'Bank entry ID';
$string['duplicate_question_version'] = 'Question version';
$string['duplicate_created'] = 'Created';
$string['duplicate_modified'] = 'Last modified';
$string['duplicate_package'] = 'Last Question Hub package for this category';
$string['duplicate_package_version'] = 'Package version';
$string['duplicate_package_activity'] = 'Package activity';
$string['duplicate_suggested_action'] = 'Suggested action';
$string['duplicate_question'] = 'Question';
$string['duplicate_dates'] = 'Created / modified';
$string['duplicate_preview'] = 'Preview';
$string['duplicate_question_preview'] = 'Question preview';
$string['duplicate_matching_questions'] = 'Matching questions';
$string['duplicate_keep_badge'] = 'Keep';
$string['duplicate_delete_badge'] = 'Delete';
$string['review_duplicates_count'] = 'Review {$a} duplicates';
$string['open_category_questions'] = 'Open this category in the question bank';

$string['unlink_category_button'] = 'Unlink from Hub';
$string['categories_unlinked'] = 'Die Verknüpfung für «{$a->name}» und {$a->count} Kategorie(n) wurde aufgehoben.';
$string['category_unlinked_status'] = 'Link removed';
$string['category_unlinked_status_help'] = 'This category was linked to a Hub package; the link was removed. The questions are unchanged and the package on the Hub is not affected. You can publish the category as a new package or link it again by importing the package again.';

$string['local_changes_status'] = '{$a} local changes';
$string['remote_update_status'] = 'v{$a} on the Hub';
$string['sync_in_sync'] = 'In sync';
$string['linked_to_category'] = 'Linked to';
$string['sync_source_label'] = 'published from here';
$string['sync_ahead_help'] = 'Questions changed in this category since the linked Hub version';
$string['sync_behind_help'] = 'The Hub has a newer version than the one linked to this category';
$string['push_button'] = 'Push';
$string['push_button_help'] = 'Publish the local changes as a new version of {$a}';
$string['pull_button'] = 'Pull v{$a}';
$string['pull_button_help'] = 'Import v{$a} into this category: add new questions and update changed ones';
$string['pull_preview_button'] = 'Pull (preview)';
$string['pull_preview_help'] = 'Show what a pull would add or update, without changing the question bank';
$string['error_package_withdrawn'] = 'The package was withdrawn from the Hub. {$a}';
$string['withdrawn_on_hub'] = 'Withdrawn on the Hub';
$string['missing_on_hub'] = 'Not on the Hub';
$string['missing_on_hub_help'] = 'The Hub no longer offers this package. The questions in this category stay unchanged; publish the category again or remove the link.';
$string['republish_button'] = 'Publish again';
$string['republish_button_help'] = 'Publish this category as a package again (new version number required)';
$string['category_delete_button'] = 'Delete category';
$string['category_delete_confirm'] = 'Permanently delete category "{$a->name}" with {$a->subcategories} subcategories and {$a->questions} questions? A package on the Hub is kept. This cannot be undone.';
$string['category_deleted'] = '"{$a->name}" was deleted ({$a->categories} categories, {$a->questions} questions).';
$string['category_delete_in_use'] = '"{$a}" was not deleted: its questions are used in quizzes or random questions draw from it. Remove these uses first.';
$string['category_delete_failed'] = '"{$a}" was not deleted: not all questions could be removed. Nothing was changed.';
$string['category_naming_note'] = '<strong>Caution with categories linked to the Question Hub</strong>
<ul class="mb-0">
<li>On updates, questions are recognised by the path of their subcategory and the question name. Do not rename or move subcategories of a linked package: existing questions would otherwise be imported as new with the next version (duplicates).</li>
<li>You may rename the top linked category; its name is not part of the path.</li>
<li>If a rename is needed: change the source category first, publish a new version and make the same change in every imported copy before updating it.</li>
<li>Use short, unique and stable names without additions such as initials, "old", "new" or version numbers (the Hub manages versions).</li>
<li>Avoid categories with the same name on the same level.</li>
<li>To split off a subcategory, move it out of the package category and publish it separately. The next version of the package lists its questions as removed; Moodles that imported the package keep them.</li>
</ul>';
$string['import_conflicts_found'] = 'Some questions were changed both on the Hub and here. Decide for each question which version applies.';
$string['import_preview_conflicts'] = 'conflicts';
$string['import_preview_conflicts_heading'] = 'Conflicts: changed on the Hub and locally';
$string['import_preview_conflicts_help'] = 'These questions differ locally and on the Hub, e.g. a question with the same name and type but a different text in a category linked for the first time. The local version is kept by default. If you take the Hub version, your version stays in the question\'s version history and can be restored.';
$string['conflict_keep'] = 'Keep local version';
$string['conflict_hub'] = 'Take Hub version';
$string['conflict_both'] = 'Keep both (Hub version as copy "… (Hub v{$a})")';
$string['error_conflicts_unresolved'] = 'A decision is missing for questions with conflicts. Open the preview and choose a version for each question.';
$string['pull_before_push'] = 'A newer version exists on the Hub: pull it first, then publish.';
$string['error_release_notes_required'] = 'Please describe the changes in this version.';
$string['push_heading'] = 'New version of "{$a}"';
$string['push_current_version'] = 'current version on the Hub: v{$a}';
$string['push_source_category'] = 'source category: {$a}';
$string['push_files_heading'] = 'Changed questions';
$string['push_files_summary'] = '{$a} question(s) changed';
$string['push_changes_unknown'] = 'The changes since the last version cannot be determined (no snapshot). You can still publish a new version.';
$string['push_no_changes'] = 'No questions changed since the last version. You can still publish a new version, e.g. after an undone change.';
$string['push_status_added'] = 'Added';
$string['push_status_modified'] = 'Modified';
$string['push_status_removed'] = 'Removed';
$string['push_diff_too_large'] = 'This question is too large for a line-by-line view.';
$string['release_notes_label'] = 'Release note';
$string['release_notes_placeholder'] = 'What changed? e.g. "Fixed a typo in question 3, added two regression questions"';
$string['push_version_help'] = 'Must be higher than v{$a}. Small fixes: raise the last number; new questions: the middle one.';
$string['push_settings_note'] = 'Change title, summary, licence and other settings in the category list (gear icon).';
$string['push_submit'] = 'Publish version';
$string['push_new_version_link'] = 'Publish a new version';
$string['push_same_content'] = '{$a} more question(s) have a new version number but unchanged content (e.g. an undone edit); they are not listed.';
$string['inherited_link_status'] = 'Part of "{$a}"';
$string['inherited_link_status_help'] = 'This subcategory belongs to the package of its parent category. Publishing, pulling and unlinking happen there. To split it off, move the subcategory out of the package category and publish it separately.';
$string['error_unlink_inherited'] = 'This subcategory belongs to the package of its parent category. Unlink it there or move the subcategory out of the package category.';

// Diff view, import preview diffs and review of local changes.
$string['diff_expand_all'] = 'Expand all';
$string['diff_collapse_all'] = 'Collapse all';
$string['diff_show_unchanged'] = 'Show {$a} unchanged lines';
$string['import_preview_show_update_diffs'] = 'Show the changes of the updated questions';
$string['review_button'] = 'Review changes';
$string['review_button_help'] = 'Review the local changes since the last sync and accept or reject each one';
$string['review_heading'] = 'Local changes: {$a}';
$string['review_synced_version'] = 'compared with the synced version {$a}';
$string['review_intro_copy'] = 'These questions were changed in this Moodle since the last pull. <strong>Accept</strong> keeps your version: it no longer counts as a local change, and if the Hub changes the question later, the pull shows a conflict instead of overwriting it. <strong>Reject</strong> restores the Hub version as a new question version (your version stays in the version history) or deletes a question that was added here.';
$string['review_intro_source'] = 'These changes are not yet on the Hub. Changes you keep are published with the next push. <strong>Reject</strong> restores the published version as a new question version (your version stays in the version history) or deletes a question that was added here.';
$string['review_no_changes'] = 'There are no local changes since the last sync.';
$string['review_list_heading'] = 'Changed questions ({$a})';
$string['review_accept_all'] = 'Accept all';
$string['review_accept'] = 'Accept';
$string['review_accept_help'] = 'Keep the local version of this question';
$string['review_reject'] = 'Reject';
$string['review_reject_help'] = 'Restore the synced version of this question';
$string['review_reject_confirm'] = 'Restore the synced version of "{$a}"? Your version stays in the version history.';
$string['review_reject_added_confirm'] = 'Delete the question "{$a}" that was added here?';
$string['review_back'] = 'Back to Question Hub';
$string['review_to_push'] = 'Push the remaining changes';
$string['review_restored'] = 'The synced version was restored as a new question version.';
$string['review_deleted'] = 'The question added here was deleted.';
$string['review_accepted'] = '{$a} local change(s) accepted.';
$string['error_review_entry'] = 'This question is no longer part of the linked category, or its synced version no longer exists.';
$string['error_review_accept_source'] = 'Changes in the package source are accepted by pushing them.';
$string['error_review_in_use'] = 'The question is used in a quiz and cannot be deleted.';
$string['error_review_not_linked'] = 'This category is not linked to a Hub package.';

// Questions with the same name and type but different content.
$string['variants_found'] = '{$a} question name(s) occur more than once in the same category with different content (e.g. a local question next to the imported Hub version).';
$string['variants_review'] = 'Compare and clean up';
$string['variants_heading'] = 'Same name, different content';
$string['variants_intro'] = 'Each group holds questions of the same category with the same name and type but different content. Compare them, choose the question to <strong>keep</strong> and apply: the other questions of the group are deleted with all their versions. Questions used in a quiz are never deleted. Groups set to "Keep all" stay unchanged. A question used in a quiz can instead <strong>take over the Hub version</strong>: its content becomes a new version of the used question (quizzes keep using it, earlier attempts keep their version), and the separate Hub question is deleted.';
$string['variants_none'] = 'There are no questions with the same name and different content.';
$string['variants_groups'] = 'Groups ({$a})';
$string['variants_created'] = 'Created';
$string['variants_modified'] = 'changed {$a}';
$string['variants_versions'] = 'Version (count)';
$string['variants_origin'] = 'Origin';
$string['variants_local'] = 'local';
$string['variants_inuse'] = 'in use';
$string['variants_inuse_help'] = 'Used in a quiz: cannot be deleted here';
$string['variants_keep'] = 'Keep';
$string['variants_keep_this'] = 'keep';
$string['variants_keep_all'] = 'Keep all questions of this group (no change)';
$string['variants_keep_hub'] = 'Use the Hub version where there is exactly one (as a new version of a used local question)';
$string['variants_reset'] = 'Reset (keep all)';
$string['variants_will_delete'] = 'will be deleted';
$string['variants_stays_inuse'] = 'stays (in use)';
$string['variants_number'] = 'Question {$a}';
$string['variants_side'] = 'Question {$a->number} · {$a->origin} · created {$a->created} · ID {$a->id}';
$string['variants_apply'] = 'Apply';
$string['variants_apply_confirm'] = 'Delete {count} question(s) with all their versions?';
$string['variants_result'] = '{$a->merged} used question(s) updated to the Hub version, {$a->deleted} question(s) deleted, {$a->inuse} kept because a quiz uses them.';
$string['diff_side_local'] = 'local version in this Moodle';
$string['diff_side_hub'] = 'Hub version {$a}';
$string['diff_side_synced'] = 'synced version (package version {$a})';
$string['diff_side_published'] = 'published version {$a}';
$string['diff_side_current'] = 'current version in this Moodle';
$string['diff_removed_unknown'] = 'Deleted question (entry {$a})';
$string['diff_removed_no_content'] = 'The content of this deleted question is available neither in this Moodle nor in the published version on the Hub.';
$string['variants_merge_this'] = 'take over the Hub version (new version)';
$string['variants_merge_help'] = 'The content of the Hub question becomes a new version of this used question; the separate Hub question is deleted.';
$string['variants_will_merge'] = 'gets the Hub version as a new version';
$string['variants_will_be_merged'] = 'content taken over, question deleted';
