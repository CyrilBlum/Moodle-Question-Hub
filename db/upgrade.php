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
 * Upgrade steps for qbank_questionhub.
 *
 * @package    qbank_questionhub
 * @copyright  2026 Cyril Wendl
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Executes plugin database upgrades.
 *
 * @param int $oldversion Old installed plugin version.
 * @return bool True on success.
 */
function xmldb_qbank_questionhub_upgrade(int $oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026101400) {
        $table = new xmldb_table('qbank_questionhub_items');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('packagehash', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL);
        $table->add_field('targetcategoryid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('sourcekey', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL);
        $table->add_field('questionbankentryid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('sourcehash', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('source_uniq', XMLDB_INDEX_UNIQUE, ['packagehash', 'targetcategoryid', 'sourcekey']);
        $table->add_index('entry_ix', XMLDB_INDEX_NOTUNIQUE, ['questionbankentryid']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        upgrade_plugin_savepoint(true, 2026101400, 'qbank', 'questionhub');
    }
    if ($oldversion < 2026101401) {
        $history = new xmldb_table('qbank_questionhub_history');
        $field = new xmldb_field('snapshotted', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'timecreated');
        if (!$dbman->field_exists($history, $field)) {
            $dbman->add_field($history, $field);
        }
        $table = new xmldb_table('qbank_questionhub_published');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('historyid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('questionbankentryid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('questionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('history_entry_uniq', XMLDB_INDEX_UNIQUE, ['historyid', 'questionbankentryid']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        upgrade_plugin_savepoint(true, 2026101401, 'qbank', 'questionhub');
    }
    if ($oldversion < 2026101514) {
        $table = new xmldb_table('qbank_questionhub_items');
        $field = new xmldb_field('questionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'timemodified');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2026101514, 'qbank', 'questionhub');
    }
    if ($oldversion < 2026101525) {
        // Import snapshots used to hold every question of the category, so questions added locally (e.g.
        // local copies next to the imported Hub versions) showed as synchronised. Restrict the current import
        // snapshot of each linked category to the questions the package tracks. Categories without tracking
        // rows (imports from before tracking existed) are left unchanged.
        $links = $DB->get_records_sql(
            "SELECT h.id, h.package_id, h.categoryid
               FROM {qbank_questionhub_history} h
              WHERE h.action = 'import' AND h.snapshotted = 1
                AND h.id = (SELECT MAX(h2.id) FROM {qbank_questionhub_history} h2
                             WHERE h2.categoryid = h.categoryid AND h2.action IN ('publish', 'import', 'unlink'))"
        );
        foreach ($links as $link) {
            if ((string)$link->package_id === '') {
                continue;
            }
            $tracked = $DB->get_fieldset_select(
                'qbank_questionhub_items',
                'questionbankentryid',
                'packagehash = ? AND targetcategoryid = ?',
                [hash('sha256', $link->package_id), $link->categoryid]
            );
            if (!$tracked) {
                continue;
            }
            $tracked = array_flip(array_map('intval', $tracked));
            foreach (
                $DB->get_records(
                    'qbank_questionhub_published',
                    ['historyid' => $link->id],
                    '',
                    'id, questionbankentryid'
                ) as $row
            ) {
                if (!isset($tracked[(int)$row->questionbankentryid])) {
                    $DB->delete_records('qbank_questionhub_published', ['id' => $row->id]);
                }
            }
        }
        upgrade_plugin_savepoint(true, 2026101525, 'qbank', 'questionhub');
    }
    return true;
}
