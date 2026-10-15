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

use stdClass;

/** Tracks the question versions included in a category's last Hub release. */
class publication_tracker {

    /** Latest publish or import record for a local category, or null. */
    public static function latest(int $courseid, int $categoryid): ?stdClass {
        global $DB;
        if ($categoryid <= 0) {
            return null;
        }
        $sql = "SELECT *
                  FROM {qbank_questionhub_history}
                 WHERE categoryid = :categoryid
                   AND action IN ('publish', 'import', 'unlink')
                   AND package_id IS NOT NULL
                   AND " . $DB->sql_isnotempty('qbank_questionhub_history', 'package_id', false, true);
        $params = ['categoryid' => $categoryid];
        if ($courseid > 0) {
            $sql .= " AND (courseid = :courseid OR courseid = 0)";
            $params['courseid'] = $courseid;
        }
        $sql .= " ORDER BY timecreated DESC, id DESC";
        $records = $DB->get_records_sql($sql, $params, 0, 1);
        if ($records) {
            $latest = reset($records);
            return $latest->action === 'unlink' ? null : $latest;
        }
        if ($courseid > 0) {
            return self::latest(0, $categoryid);
        }
        return null;
    }

    /** Whether the latest category link was explicitly removed. */
    public static function is_unlinked(int $courseid, int $categoryid): bool {
        global $DB;
        $params = ['categoryid' => $categoryid];
        $sql = "SELECT action FROM {qbank_questionhub_history}
                  WHERE categoryid = :categoryid AND action IN ('publish', 'import', 'unlink')";
        if ($courseid > 0) {
            $sql .= ' AND (courseid = :courseid OR courseid = 0)';
            $params['courseid'] = $courseid;
        }
        $sql .= ' ORDER BY timecreated DESC, id DESC';
        $record = $DB->get_record_sql($sql, $params, IGNORE_MISSING);
        return $record && $record->action === 'unlink';
    }

    /** Latest record for a package ID, or null. */
    public static function latest_by_package(string $packageid, int $courseid = 0): ?stdClass {
        global $DB;
        if ($packageid === '') {
            return null;
        }
        $sql = "SELECT *
                  FROM {qbank_questionhub_history}
                 WHERE package_id = :packageid
                   AND action IN ('publish', 'import')";
        $params = ['packageid' => $packageid];
        if ($courseid > 0) {
            $sql .= " AND (courseid = :courseid OR courseid = 0)";
            $params['courseid'] = $courseid;
        }
        $sql .= " ORDER BY timecreated DESC, id DESC";
        $records = $DB->get_records_sql($sql, $params, 0, 1);
        if ($records) {
            return reset($records);
        }
        if ($courseid > 0) {
            return self::latest_by_package($packageid, 0);
        }
        return null;
    }

    /** Latest local publication for a package, excluding later imports of it. */
    public static function latest_publication_by_package(string $packageid, int $courseid = 0): ?stdClass {
        global $DB;
        if ($packageid === '') {
            return null;
        }
        $sql = "SELECT *
                  FROM {qbank_questionhub_history}
                 WHERE package_id = :packageid
                   AND action = 'publish'";
        $params = ['packageid' => $packageid];
        if ($courseid > 0) {
            $sql .= " AND (courseid = :courseid OR courseid = 0)";
            $params['courseid'] = $courseid;
        }
        $sql .= " ORDER BY timecreated DESC, id DESC";
        $records = $DB->get_records_sql($sql, $params, 0, 1);
        if ($records) {
            return reset($records);
        }
        if ($courseid > 0) {
            return self::latest_publication_by_package($packageid, 0);
        }
        return null;
    }

    /**
     * Latest publication whose category has not subsequently become an import target.
     *
     * An imported copy can itself be published. Once a newer import lands in that
     * category, it is no longer the source that should offer package updates.
     */
    public static function latest_active_publication_by_package(string $packageid, int $courseid = 0): ?stdClass {
        global $DB;
        if ($packageid === '') {
            return null;
        }
        $sql = "SELECT *
                  FROM {qbank_questionhub_history}
                 WHERE package_id = :packageid
                   AND action = 'publish'";
        $params = ['packageid' => $packageid];
        if ($courseid > 0) {
            $sql .= " AND (courseid = :courseid OR courseid = 0)";
            $params['courseid'] = $courseid;
        }
        $sql .= ' ORDER BY timecreated DESC, id DESC';
        foreach ($DB->get_records_sql($sql, $params) as $publication) {
            $latest = self::latest($courseid, (int)$publication->categoryid);
            if ($latest && (int)$latest->id === (int)$publication->id) {
                return $publication;
            }
        }
        if ($courseid > 0) {
            return self::latest_active_publication_by_package($packageid, 0);
        }
        return null;
    }

    /** Category IDs including descendants in the same question bank context. */
    public static function category_ids(int $categoryid): array {
        global $DB;
        if ($categoryid <= 0) {
            return [];
        }
        $root = $DB->get_record('question_categories', ['id' => $categoryid], 'id,contextid');
        if (!$root) {
            return [];
        }
        $categories = $DB->get_records('question_categories', ['contextid' => $root->contextid], '', 'id,parent');
        $children = [];
        foreach ($categories as $category) {
            $children[(int)$category->parent][] = (int)$category->id;
        }
        $result = [$categoryid];
        for ($i = 0; $i < count($result); $i++) {
            foreach ($children[$result[$i]] ?? [] as $childid) {
                $result[] = $childid;
            }
        }
        return $result;
    }

    /** Find publication history for a category by checking itself, parent categories, or package title match. */
    public static function find_publication_for_category(int $categoryid, int $courseid = 0): ?stdClass {
        global $DB;
        $currentid = $categoryid;
        while ($currentid > 0) {
            $cat = $DB->get_record('question_categories', ['id' => $currentid], 'id,parent,name');
            if (!$cat || ($cat->name === 'top' && (int)$cat->parent === 0)) {
                break;
            }
            if (self::is_unlinked($courseid, $currentid)) {
                return null;
            }
            $history = self::latest($courseid, $currentid);
            if ($history) {
                return $history;
            }
            // Check if category name matches an existing package in history.
            $clean = preg_replace('/[^a-z0-9]/', '', mb_strtolower($cat->name));
            if ($clean !== '') {
                $histbyname = $DB->get_records_select(
                    'qbank_questionhub_history',
                    "action IN ('publish', 'import') AND (" .
                    "LOWER(package_title) = :name OR package_id LIKE :slug)",
                    ['name' => mb_strtolower(trim($cat->name)), 'slug' => '%' . $clean],
                    'timecreated DESC, id DESC',
                    '*',
                    0,
                    1
                );
                if ($histbyname) {
                    return reset($histbyname);
                }
            }
            $currentid = (int)$cat->parent;
        }
        return null;
    }

    /** Find a local publication for a category; imports must not make a category publishable as an update. */
    public static function find_local_publication_for_category(int $categoryid, int $courseid = 0): ?stdClass {
        global $DB;
        $currentid = $categoryid;
        while ($currentid > 0) {
            $cat = $DB->get_record('question_categories', ['id' => $currentid], 'id,parent,name');
            if (!$cat || ($cat->name === 'top' && (int)$cat->parent === 0)) {
                break;
            }
            if (self::is_unlinked($courseid, $currentid)) {
                return null;
            }
            $history = self::latest($courseid, $currentid);
            if ($history && $history->action === 'publish') {
                return $history;
            }
            $clean = preg_replace('/[^a-z0-9]/', '', mb_strtolower($cat->name));
            if ($clean !== '') {
                $historybyname = $DB->get_records_select(
                    'qbank_questionhub_history',
                    "action = 'publish' AND (LOWER(package_title) = :name OR package_id LIKE :slug)",
                    ['name' => mb_strtolower(trim($cat->name)), 'slug' => '%' . $clean],
                    'timecreated DESC, id DESC',
                    '*'
                );
                foreach ($historybyname as $publication) {
                    $latest = self::latest($courseid, (int)$publication->categoryid);
                    if ($latest && (int)$latest->id === (int)$publication->id) {
                        return $publication;
                    }
                }
            }
            $currentid = (int)$cat->parent;
        }
        return null;
    }

    /** Current latest question IDs by question bank entry within a category tree. */
    public static function current(int $categoryid): array {
        global $DB;
        $catids = self::category_ids($categoryid);
        if (empty($catids)) {
            return [];
        }
        list($insql, $params) = $DB->get_in_or_equal($catids, SQL_PARAMS_NAMED, 'pubcat');
        $sql = "SELECT qbe.id, latest.questionid
                  FROM {question_bank_entries} qbe
                  JOIN (SELECT questionbankentryid, MAX(version) AS maxversion
                          FROM {question_versions}
                      GROUP BY questionbankentryid) newest ON newest.questionbankentryid = qbe.id
                  JOIN {question_versions} latest ON latest.questionbankentryid = newest.questionbankentryid
                       AND latest.version = newest.maxversion
                 WHERE qbe.questioncategoryid $insql";
        $result = [];
        foreach ($DB->get_records_sql($sql, $params) as $entry) {
            $result[(int)$entry->id] = (int)$entry->questionid;
        }
        return $result;
    }

    /** Snapshot from the most recent local publication. Automatically backfills if missing. */
    public static function snapshot(int $historyid): ?array {
        global $DB;
        $history = $DB->get_record('qbank_questionhub_history', ['id' => $historyid]);
        if (!$history) {
            return null;
        }
        if (empty($history->snapshotted)) {
            self::backfill_snapshot($history);
        }
        $result = [];
        foreach ($DB->get_records('qbank_questionhub_published', ['historyid' => $historyid]) as $row) {
            $result[(int)$row->questionbankentryid] = (int)$row->questionid;
        }
        return !empty($result) ? $result : null;
    }

    /** Backfill snapshot for historical records created before tracking was enabled. */
    public static function backfill_snapshot(stdClass $history): void {
        global $DB;
        $catid = (int)$history->categoryid;
        if ($catid <= 0) {
            $DB->set_field('qbank_questionhub_history', 'snapshotted', 1, ['id' => $history->id]);
            return;
        }
        $catids = self::category_ids($catid);
        if (empty($catids)) {
            $DB->set_field('qbank_questionhub_history', 'snapshotted', 1, ['id' => $history->id]);
            return;
        }
        list($insql, $params) = $DB->get_in_or_equal($catids, SQL_PARAMS_NAMED, 'bcat');
        $sql = "SELECT qv.id AS qvid, qbe.id AS qbeid, qv.questionid, qv.version, q.timemodified
                  FROM {question_bank_entries} qbe
                  JOIN {question_versions} qv ON qv.questionbankentryid = qbe.id
                  JOIN {question} q ON q.id = qv.questionid
                 WHERE qbe.questioncategoryid $insql
              ORDER BY qbe.id ASC, qv.version ASC";
        $rows = $DB->get_records_sql($sql, $params);
        $entryversions = [];
        foreach ($rows as $row) {
            $entryversions[(int)$row->qbeid][] = $row;
        }

        $questions = [];
        $cutoff = (int)$history->timecreated;
        foreach ($entryversions as $entryid => $versions) {
            $chosen = null;
            foreach ($versions as $v) {
                if ((int)$v->timemodified <= $cutoff) {
                    $chosen = (int)$v->questionid;
                }
            }
            if ($chosen === null && !empty($versions)) {
                $chosen = (int)$versions[0]->questionid;
            }
            if ($chosen !== null) {
                $questions[$entryid] = $chosen;
            }
        }

        if (!empty($questions)) {
            self::save((int)$history->id, $questions);
        } else {
            $DB->set_field('qbank_questionhub_history', 'snapshotted', 1, ['id' => $history->id]);
        }
    }

    /** Changes for one category tree, using a package-level snapshot where necessary. */
    public static function changes_in_scope(int $categoryid, stdClass $history): ?array {
        $previous = self::snapshot((int)$history->id);
        if ($previous === null) {
            return null;
        }
        $current = self::current($categoryid);
        // A parent release snapshots all descendants. Only entries currently in this
        // category tree belong to its row; entries removed from another branch must not
        // make this child look outdated.
        $previous = array_intersect_key($previous, $current);
        $changed = [];
        foreach ($current as $entryid => $questionid) {
            if (!isset($previous[$entryid]) || $previous[$entryid] !== $questionid) {
                $changed[$entryid] = true;
            }
        }
        return ['count' => count($changed), 'entries' => $changed, 'removed' => []];
    }

    /** Number of added, edited, or removed entries since the latest release. */
    public static function changes(int $categoryid, stdClass $history): ?array {
        $previous = self::snapshot((int)$history->id);
        if ($previous === null) {
            return null;
        }
        $current = self::current($categoryid);
        $changed = [];
        foreach ($current as $entryid => $questionid) {
            if (!isset($previous[$entryid]) || $previous[$entryid] !== $questionid) {
                $changed[$entryid] = true;
            }
        }
        $removed = array_diff_key($previous, $current);
        return [
            'count' => count($changed) + count($removed),
            'entries' => $changed,
            'removed' => $removed,
        ];
    }

    /** Persist a snapshot only after the Hub has accepted the release. */
    public static function save(int $historyid, array $questions): void {
        global $DB;
        $transaction = $DB->start_delegated_transaction();
        foreach ($questions as $entryid => $questionid) {
            $DB->insert_record('qbank_questionhub_published', (object)[
                'historyid' => $historyid,
                'questionbankentryid' => $entryid,
                'questionid' => $questionid,
            ]);
        }
        $DB->set_field('qbank_questionhub_history', 'snapshotted', 1, ['id' => $historyid]);
        $transaction->allow_commit();
    }

    /** Suggest next SemVer string. */
    public static function next_version(string $version): string {
        $parts = explode('.', trim($version));
        if (count($parts) === 3 && is_numeric($parts[2])) {
            $parts[2] = (string)((int)$parts[2] + 1);
            return implode('.', $parts);
        }
        return $version !== '' ? $version . '.1' : '1.0.1';
    }
}
