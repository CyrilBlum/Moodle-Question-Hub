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
$string['error_oauth_failed'] = 'Linking your Question Hub account failed: {$a}. Please try again or use a Personal Access Token.';
$string['my_packages'] = 'My published packages';
$string['no_packages'] = 'You have not published any packages yet.';
$string['withdraw_package'] = 'Withdraw package';
$string['withdraw_version'] = 'Withdraw version';
$string['withdraw_package_confirm'] = 'Withdraw this package with all versions from the Hub? Questions already imported into Moodle stay there.';
$string['withdraw_version_confirm'] = 'Withdraw this version? The version number can never be reused.';
$string['withdraw_done'] = 'Withdrawn from Question Hub.';
$string['preview_on'] = 'with preview';
$string['preview_off'] = 'no preview';
$string['publish_category_heading'] = 'Publish a whole category';
$string['publish_category_button'] = 'Publish category';
$string['edit_settings'] = 'Settings';
$string['save_settings'] = 'Save settings';
$string['settings_saved'] = 'Settings saved on the Question Hub.';
$string['version_immutable_note'] = 'Version numbers are immutable: publish a new version for changed content, or withdraw an existing one.';
$string['incl_subcategories'] = 'incl. subcategories';
$string['hub_tab_browse'] = 'Browse';
$string['hub_tab_library'] = 'My library';
$string['hub_tab_my_packages'] = 'My packages';
$string['library_empty'] = 'You have not imported any Question Hub packages into this course yet.';
$string['open_hub_package'] = 'Open in Hub';
$string['category_missing'] = 'Category no longer exists';
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
$string['update_category_button'] = 'Update';
$string['update_package_button'] = 'Update package';
$string['update_package_heading'] = 'Update package on Question Hub';
$string['update_package_intro'] = 'You are updating the package "{$a->title}" (current version: v{$a->version}). {$a->changes} changed or new questions will be published.';
$string['update_package_intro_nochanges'] = 'You are updating the package "{$a->title}" (current version: v{$a->version}). There are currently no local changes.';
$string['changes_count'] = '{$a} changes';
$string['changes_since_last_pub'] = '{$a} questions changed since the last release';
$string['no_changes_since_last_pub'] = 'No changes since the last release';
$string['up_to_date'] = 'Up to date';
$string['published_badge'] = 'Published';
$string['error_update_source'] = 'The category does not match the specified package.';
$string['error_update_version'] = 'The new version number must be higher than the existing version v{$a}.';
$string['publish_update_button'] = 'Publish update to Question Hub';
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

$string['unlink_category_button'] = 'Verknüpfung aufheben';
$string['categories_unlinked'] = 'Die Verknüpfung für «{$a->name}» und {$a->count} Kategorie(n) wurde aufgehoben.';
$string['category_unlinked_status'] = 'Keine aktive Hub-Verknüpfung';

$string['local_changes_status'] = '{$a} lokale Änderungen';
$string['local_up_to_date_status'] = 'Lokal aktuell';
$string['remote_update_status'] = 'Remote v{$a} verfügbar';
$string['remote_up_to_date_status'] = 'Remote aktuell';
