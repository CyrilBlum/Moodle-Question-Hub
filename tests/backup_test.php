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

namespace qbank_questionhub;

use backup;
use backup_controller;
use restore_controller;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

/**
 * Tests for the backup and restore of links and question tracking.
 *
 * @package    qbank_questionhub
 * @copyright  2026 Cyril Blum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \backup_qbank_questionhub_plugin
 * @covers \restore_qbank_questionhub_plugin
 */
final class backup_test extends \advanced_testcase {
    /**
     * A question bank context of a course (mod_qbank on Moodle 5.0, the course on 4.5).
     *
     * @param \stdClass $course
     * @return \context
     */
    private function bank_context(\stdClass $course): \context {
        if (class_exists('\core_question\local\bank\question_bank_helper')) {
            $bank = \core_question\local\bank\question_bank_helper::get_default_open_instance_system_type($course, true);
            return \context_module::instance($bank->id);
        }
        return \context_course::instance($course->id);
    }

    /**
     * Backs up a course and restores it into a new course.
     *
     * @param int $courseid
     * @param bool $users Whether the backup holds users.
     * @return int ID of the new course.
     */
    private function backup_and_restore(int $courseid, bool $users = true): int {
        global $CFG, $USER;
        $CFG->backup_file_logger_level = backup::LOG_NONE;
        $bc = new backup_controller(
            backup::TYPE_1COURSE,
            $courseid,
            backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO,
            backup::MODE_IMPORT,
            $USER->id
        );
        $bc->get_plan()->get_setting('users')->set_status(\backup_setting::NOT_LOCKED);
        $bc->get_plan()->get_setting('users')->set_value($users);
        $backupid = $bc->get_backupid();
        $bc->execute_plan();
        $bc->destroy();

        $newcourseid = \restore_dbops::create_new_course('Restored', 'RESTORED' . $courseid, 1);
        $rc = new restore_controller(
            $backupid,
            $newcourseid,
            backup::INTERACTIVE_NO,
            backup::MODE_IMPORT,
            $USER->id,
            backup::TARGET_NEW_COURSE
        );
        $this->assertTrue($rc->execute_precheck());
        $rc->execute_plan();
        $rc->destroy();
        return (int)$newcourseid;
    }

    /**
     * Links, snapshots and tracking of a bank arrive in the restored course with the new IDs, including the link of a
     * parent category that holds no questions of its own.
     */
    public function test_links_and_tracking_are_restored(): void {
        global $DB, $USER;
        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $questions = $generator->get_plugin_generator('core_question');
        $course = $generator->create_course();
        $context = $this->bank_context($course);
        $parent = $questions->create_question_category(['contextid' => $context->id, 'name' => 'Package']);
        $child = $questions->create_question_category(['contextid' => $context->id, 'name' => 'Chapter',
            'parent' => $parent->id]);
        $first = $questions->create_question('truefalse', null, ['category' => $child->id, 'name' => 'First']);
        $second = $questions->create_question('truefalse', null, ['category' => $child->id, 'name' => 'Second']);

        $entryid = fn($question) => (int)$DB->get_field(
            'question_versions',
            'questionbankentryid',
            ['questionid' => $question->id]
        );
        $link = [
            'userid' => $USER->id, 'action' => 'publish', 'package_id' => 'ch.gym.inf.test', 'package_version' => '1.2.3',
            'package_title' => 'Test', 'courseid' => $course->id, 'categoryid' => $parent->id, 'question_count' => 2,
            'timecreated' => 1000, 'snapshotted' => 1,
        ];
        $historyid = $DB->insert_record('qbank_questionhub_history', (object)$link);
        $DB->insert_record('qbank_questionhub_history', (object)(['action' => 'unlink', 'timecreated' => 2000,
            'categoryid' => $child->id] + $link));
        foreach ([$first, $second] as $question) {
            $DB->insert_record('qbank_questionhub_published', (object)[
                'historyid' => $historyid, 'questionbankentryid' => $entryid($question), 'questionid' => $question->id,
            ]);
        }
        $hash = hash('sha256', 'ch.gym.inf.test');
        $DB->insert_record('qbank_questionhub_items', (object)[
            'packagehash' => $hash, 'targetcategoryid' => $parent->id, 'sourcekey' => 'first',
            'questionbankentryid' => $entryid($first), 'sourcehash' => 'a', 'timemodified' => 3000,
            'questionid' => $first->id,
        ]);
        // An older row without a version.
        $DB->insert_record('qbank_questionhub_items', (object)[
            'packagehash' => $hash, 'targetcategoryid' => $parent->id, 'sourcekey' => 'second',
            'questionbankentryid' => $entryid($second), 'sourcehash' => 'b', 'timemodified' => 3000, 'questionid' => 0,
        ]);

        $newcourseid = $this->backup_and_restore((int)$course->id);

        $links = $DB->get_records('qbank_questionhub_history', ['courseid' => $newcourseid], 'timecreated');
        $this->assertCount(2, $links);
        [$newlink, $unlink] = array_values($links);
        $this->assertSame('publish', $newlink->action);
        $this->assertSame('ch.gym.inf.test', $newlink->package_id);
        $this->assertSame('1.2.3', $newlink->package_version);
        $this->assertEquals(1000, $newlink->timecreated);
        $this->assertEquals($USER->id, $newlink->userid);
        $this->assertSame('unlink', $unlink->action);

        $newparent = $DB->get_record('question_categories', ['id' => $newlink->categoryid], '*', MUST_EXIST);
        $this->assertSame('Package', $newparent->name);
        $this->assertNotEquals($parent->id, $newparent->id);
        $newchild = $DB->get_record('question_categories', ['id' => $unlink->categoryid], '*', MUST_EXIST);
        $this->assertSame('Chapter', $newchild->name);
        $this->assertEquals($newparent->id, $newchild->parent);

        $published = $DB->get_records('qbank_questionhub_published', ['historyid' => $newlink->id]);
        $this->assertCount(2, $published);
        $names = [];
        foreach ($published as $row) {
            $this->assertNotEquals($first->id, $row->questionid);
            $this->assertNotEquals($second->id, $row->questionid);
            $this->assertEquals($row->questionbankentryid, $DB->get_field(
                'question_versions',
                'questionbankentryid',
                ['questionid' => $row->questionid]
            ));
            $names[] = $DB->get_field('question', 'name', ['id' => $row->questionid]);
        }
        sort($names);
        $this->assertSame(['First', 'Second'], $names);

        $items = $DB->get_records('qbank_questionhub_items', ['targetcategoryid' => $newparent->id], 'sourcekey');
        $this->assertCount(2, $items);
        [$tracked, $legacy] = array_values($items);
        $this->assertSame('first', $tracked->sourcekey);
        $this->assertSame('First', $DB->get_field('question', 'name', ['id' => $tracked->questionid]));
        $this->assertSame('second', $legacy->sourcekey);
        $this->assertEquals(0, $legacy->questionid);
        $this->assertSame($hash, $legacy->packagehash);

        // The tracker sees the restored category as linked to the package.
        $restored = publication_tracker::latest($newcourseid, (int)$newparent->id);
        $this->assertSame('ch.gym.inf.test', $restored->package_id);
        // The original course keeps its records.
        $this->assertSame(2, $DB->count_records('qbank_questionhub_history', ['courseid' => $course->id]));
    }

    /**
     * Without users in the backup, the restoring user takes over the links.
     */
    public function test_links_without_users_belong_to_the_restoring_user(): void {
        global $DB, $USER;
        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $questions = $generator->get_plugin_generator('core_question');
        $course = $generator->create_course();
        $teacher = $generator->create_user();
        $category = $questions->create_question_category(['contextid' => $this->bank_context($course)->id]);
        $questions->create_question('truefalse', null, ['category' => $category->id]);
        $DB->insert_record('qbank_questionhub_history', (object)[
            'userid' => $teacher->id, 'action' => 'import', 'package_id' => 'ch.gym.inf.test', 'package_version' => '1.0.0',
            'package_title' => 'Test', 'courseid' => $course->id, 'categoryid' => $category->id, 'question_count' => 1,
            'timecreated' => 1000, 'snapshotted' => 0,
        ]);

        $newcourseid = $this->backup_and_restore((int)$course->id, false);

        $link = $DB->get_record('qbank_questionhub_history', ['courseid' => $newcourseid], '*', MUST_EXIST);
        $this->assertEquals($USER->id, $link->userid);
    }
}
