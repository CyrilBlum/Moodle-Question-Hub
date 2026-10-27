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

namespace qbank_questionhub\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\writer;

/**
 * Privacy Subsystem for qbank_questionhub implementing user data management.
 *
 * @package    qbank_questionhub
 * @copyright  2026 Cyril Blum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Describe the types of data stored by this plugin.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'qbank_questionhub_tokens',
            [
                'userid' => 'privacy:metadata:tokens:userid',
                'access_token' => 'privacy:metadata:tokens:access_token',
                'hub_user_id' => 'privacy:metadata:tokens:hub_user_id',
                'hub_email' => 'privacy:metadata:tokens:hub_email',
                'hub_name' => 'privacy:metadata:tokens:hub_name',
                'timecreated' => 'privacy:metadata:tokens:timecreated',
            ],
            'privacy:metadata:tokens:tableexplanation'
        );

        $collection->add_database_table(
            'qbank_questionhub_history',
            [
                'userid' => 'privacy:metadata:history:userid',
                'action' => 'privacy:metadata:history:action',
                'package_id' => 'privacy:metadata:history:package_id',
                'package_version' => 'privacy:metadata:history:package_version',
                'timecreated' => 'privacy:metadata:history:timecreated',
            ],
            'privacy:metadata:history:tableexplanation'
        );

        $collection->link_external_location(
            'questionhub_marketplace',
            [
                'email' => 'privacy:metadata:marketplace:email',
            ],
            'privacy:metadata:marketplace:explanation'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $context = \context_user::instance($userid, IGNORE_MISSING);
        if ($context) {
            $contextlist->add_from_sql(
                "SELECT c.id FROM {context} c WHERE c.id = :contextid",
                ['contextid' => $context->id]
            );
        }
        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if ($context->contextlevel == CONTEXT_USER) {
            $userlist->add_user($context->instanceid);
        }
    }

    /**
     * Export all user data for the specified context.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;
        $user = $contextlist->get_user();
        $token = $DB->get_record('qbank_questionhub_tokens', ['userid' => $user->id]);
        if ($token) {
            $data = (object)[
                'hub_user_id' => $token->hub_user_id,
                'hub_email' => $token->hub_email,
                'hub_name' => $token->hub_name,
                'timecreated' => \core_privacy\local\request\transform::datetime($token->timecreated),
            ];
            $context = \context_user::instance($user->id);
            writer::with_context($context)->export_data(
                [get_string('pluginname', 'qbank_questionhub')],
                $data
            );
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;
        if ($context->contextlevel == CONTEXT_USER) {
            $DB->delete_records('qbank_questionhub_tokens', ['userid' => $context->instanceid]);
            $DB->delete_records('qbank_questionhub_history', ['userid' => $context->instanceid]);
        }
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;
        $user = $contextlist->get_user();
        $DB->delete_records('qbank_questionhub_tokens', ['userid' => $user->id]);
        $DB->delete_records('qbank_questionhub_history', ['userid' => $user->id]);
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;
        $userids = $userlist->get_userids();
        if (!empty($userids)) {
            [$insql, $params] = $DB->get_in_or_equal($userids);
            $DB->delete_records_select('qbank_questionhub_tokens', "userid $insql", $params);
            $DB->delete_records_select('qbank_questionhub_history', "userid $insql", $params);
        }
    }
}
