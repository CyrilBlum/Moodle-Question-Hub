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
$string['questionhub:import'] = 'Import Question Hub packages into course question banks';
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
$string['package_author'] = 'Author';
$string['questions_count'] = '{$a} questions';
$string['preview_package'] = 'Preview';
$string['import_package'] = 'Import to Course';
$string['target_category'] = 'Target Question Category';
$string['import_preserve_hierarchy'] = 'Preserve category hierarchy from package';
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
