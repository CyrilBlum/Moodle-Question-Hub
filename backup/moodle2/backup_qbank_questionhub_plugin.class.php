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
 * Backs up the Question Hub links and the question tracking of a question bank.
 *
 * Core calls qbank plugins for each question only, while a link belongs to a category that may hold no questions
 * of its own. All links of a question bank are therefore written once, with the first question of the bank.
 *
 * @package    qbank_questionhub
 * @copyright  2026 Cyril Blum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_qbank_questionhub_plugin extends backup_qbank_plugin {
    /** @var string[] Fields of a link. */
    private const LINK_FIELDS = ['userid', 'action', 'package_id', 'package_version', 'package_title', 'courseid',
        'categoryid', 'question_count', 'timecreated', 'snapshotted'];

    /**
     * Returns the structure to attach to the question element.
     *
     * @return backup_plugin_element
     */
    protected function define_question_plugin_structure() {
        $plugin = $this->get_plugin_element();
        $wrapper = new backup_nested_element($this->get_recommended_name());
        $plugin->add_child($wrapper);

        $links = new backup_nested_element('links');
        $link = new backup_nested_element('link', ['id'], self::LINK_FIELDS);
        $wrapper->add_child($links);
        $links->add_child($link);

        $published = new backup_nested_element('published_questions');
        // The link of a published version comes along: the bank's first question may not be part of this backup.
        $publishedquestion = new backup_nested_element('published_question', ['id'], array_merge(['historyid'], self::LINK_FIELDS));
        $wrapper->add_child($published);
        $published->add_child($publishedquestion);

        $items = new backup_nested_element('tracked_questions');
        $item = new backup_nested_element('tracked_question', ['id'], ['packagehash', 'targetcategoryid', 'sourcekey',
            'sourcehash', 'timemodified', 'questionid']);
        $wrapper->add_child($items);
        $items->add_child($item);

        $link->set_source_sql(
            "SELECT h.*
               FROM {qbank_questionhub_history} h
               JOIN {question_categories} linked ON linked.id = h.categoryid
               JOIN {question_categories} own ON own.contextid = linked.contextid
               JOIN {question_bank_entries} qbe ON qbe.questioncategoryid = own.id
               JOIN {question_versions} qv ON qv.questionbankentryid = qbe.id
              WHERE qv.questionid = :questionid
                AND qv.version = (SELECT MAX(v.version)
                                    FROM {question_versions} v
                                   WHERE v.questionbankentryid = qbe.id)
                AND qbe.id = (SELECT MIN(e.id)
                                FROM {question_bank_entries} e
                                JOIN {question_categories} c ON c.id = e.questioncategoryid
                               WHERE c.contextid = own.contextid)
           ORDER BY h.id",
            ['questionid' => backup::VAR_PARENTID]
        );
        $publishedquestion->set_source_sql(
            "SELECT p.id, p.historyid, h.userid, h.action, h.package_id, h.package_version, h.package_title, h.courseid,
                    h.categoryid, h.question_count, h.timecreated, h.snapshotted
               FROM {qbank_questionhub_published} p
               JOIN {qbank_questionhub_history} h ON h.id = p.historyid
              WHERE p.questionid = :questionid
           ORDER BY p.id",
            ['questionid' => backup::VAR_PARENTID]
        );
        // A row belongs to the version it names. Older rows name none or a deleted version: those go with the latest one.
        $item->set_source_sql(
            "SELECT i.*
               FROM {qbank_questionhub_items} i
               JOIN {question_versions} qv ON qv.questionbankentryid = i.questionbankentryid
              WHERE qv.questionid = :questionid
                AND (i.questionid = qv.questionid
                     OR (qv.version = (SELECT MAX(v.version)
                                         FROM {question_versions} v
                                        WHERE v.questionbankentryid = i.questionbankentryid)
                         AND NOT EXISTS (SELECT 1
                                           FROM {question_versions} o
                                          WHERE o.questionbankentryid = i.questionbankentryid
                                            AND o.questionid = i.questionid)))
           ORDER BY i.id",
            ['questionid' => backup::VAR_PARENTID]
        );

        $link->annotate_ids('user', 'userid');
        $publishedquestion->annotate_ids('user', 'userid');

        return $plugin;
    }
}
