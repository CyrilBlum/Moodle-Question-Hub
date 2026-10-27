<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace qbank_questionhub;

/**
 * Question-level changes since a release, with line diffs of the exported Moodle XML.
 *
 * @package    qbank_questionhub
 * @copyright  2026 Cyril Blum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class release_diff {
    /** Lines of unchanged context around each change, as in git diff. */
    private const CONTEXT = 3;
    /** Longer lines (embedded files) are shortened to a fingerprint. */
    private const MAX_LINE = 240;
    /** Larger exports are listed without a line diff. */
    private const MAX_LINES = 1500;
    /** Lists with more files start collapsed. */
    private const OPEN_FILES = 5;

    /**
     * Changed questions of a category tree since the given release.
     *
     * @param int $categoryid source category of the package
     * @param \stdClass $history the release (history record) to compare with
     * @param package_exporter $exporter exporter for the bank context
     * @param bool $inscope only entries currently in the category tree (imported copies), without removals
     * @return array|null files (name, status, additions, deletions, hunks, entryid) and same_content (new versions
     *         with identical content, e.g. an edit that was undone), null without a snapshot
     */
    public static function since(
        int $categoryid,
        \stdClass $history,
        package_exporter $exporter,
        bool $inscope = false
    ): ?array {
        global $DB;
        $changes = $inscope ? publication_tracker::changes_in_scope($categoryid, $history) :
            publication_tracker::changes($categoryid, $history);
        $previous = publication_tracker::snapshot((int)$history->id);
        if ($changes === null || $previous === null) {
            return null;
        }
        $current = publication_tracker::current($categoryid);
        $moves = $inscope ? [] : self::moves($categoryid, $history, $previous, $current);
        $files = [];
        $samecontent = 0;
        foreach (array_keys($changes['entries']) as $entryid) {
            $file = self::file($previous[$entryid] ?? 0, $current[$entryid] ?? 0, $exporter);
            if (
                $file['status'] === 'modified' && !$file['too_large'] && !$file['additions'] && !$file['deletions'] &&
                    !isset($moves[$entryid])
            ) {
                $samecontent++;
                continue;
            }
            $files[] = self::with_move($file, $moves[$entryid] ?? null) +
                ['entryid' => (int)$entryid, 'questionid' => (int)($current[$entryid] ?? 0)];
        }
        // Moved without a new version: the content is unchanged, only the category differs from the release.
        foreach (array_diff_key($moves, $changes['entries']) as $entryid => $move) {
            $name = (string)$DB->get_field('question', 'name', ['id' => $current[$entryid]]);
            $files[] = self::with_move(self::compare($name, [], [], 'moved'), $move) +
                ['entryid' => (int)$entryid, 'questionid' => (int)$current[$entryid]];
        }
        $deleted = [];
        foreach ($changes['removed'] as $entryid => $questionid) {
            if (self::question_exists((int)$questionid)) {
                $files[] = self::file((int)$questionid, 0, $exporter) +
                    ['entryid' => (int)$entryid, 'questionid' => (int)$questionid];
            } else {
                $deleted[] = (int)$entryid;
            }
        }
        if ($deleted) {
            $files = array_merge($files, self::deleted_from_release($history, $previous, $current, $deleted));
        }
        usort($files, static fn($a, $b) => strcmp($a['name'], $b['name']));
        return ['files' => self::decorate($files), 'same_content' => $samecontent];
    }

    /**
     * Whether a question still exists.
     *
     * @param int $questionid
     * @return bool
     */
    private static function question_exists(int $questionid): bool {
        global $DB;
        return $DB->record_exists('question', ['id' => $questionid]);
    }

    /**
     * The published release from the Hub (downloaded once per request), '' if it is not available.
     */
    private static function release_xml(\stdClass $history): string {
        static $cache = [];
        $key = $history->package_id . '@' . $history->package_version;
        if (!isset($cache[$key])) {
            try {
                $cache[$key] = (new api_client(null, null, 30, true))->download_release(
                    (string)$history->package_id,
                    (string)$history->package_version
                );
            } catch (\Throwable $e) {
                $cache[$key] = '';
            }
        }
        return $cache[$key];
    }

    /**
     * Questions whose category inside the package differs from the published release (moved into another
     * subcategory). A question is identified in the release by the name of its published version; names that
     * occur more than once are skipped.
     *
     * @return array entry ID => ['from' => path in the release, 'to' => current path]
     */
    private static function moves(int $categoryid, \stdClass $history, array $previous, array $current): array {
        global $DB;
        $kept = array_intersect_key($previous, $current);
        if (!$kept) {
            return [];
        }
        // Current path of every category below the package category.
        $paths = [$categoryid => []];
        $pending = [$categoryid];
        while ($pending) {
            $parent = array_shift($pending);
            foreach ($DB->get_records('question_categories', ['parent' => $parent], '', 'id, name') as $child) {
                $paths[(int)$child->id] = array_merge($paths[$parent], [trim($child->name)]);
                $pending[] = (int)$child->id;
            }
        }
        $entrycats = [];
        foreach (array_chunk(array_keys($kept), 500) as $chunk) {
            $entrycats += $DB->get_records_list('question_bank_entries', 'id', $chunk, '', 'id, questioncategoryid');
        }
        // Path of each published question in the release, by name; ambiguous names are dropped.
        $xml = self::release_xml($history);
        if ($xml === '') {
            return [];
        }
        $released = self::release_paths($xml);
        $names = [];
        foreach (array_chunk(array_values($kept), 500) as $chunk) {
            foreach ($DB->get_records_list('question', 'id', $chunk, '', 'id, name') as $question) {
                $names[(int)$question->id] = trim($question->name);
            }
        }
        $moves = [];
        foreach ($kept as $entryid => $questionid) {
            $name = $names[$questionid] ?? '';
            $category = (int)($entrycats[$entryid]->questioncategoryid ?? 0);
            if (!isset($released[$name]) || $released[$name] === false || !isset($paths[$category])) {
                continue;
            }
            if ($released[$name] !== $paths[$category]) {
                $moves[$entryid] = ['from' => self::path_label($released[$name]), 'to' => self::path_label($paths[$category])];
            }
        }
        return $moves;
    }

    /**
     * Category path (below the package root) of each question in a package XML, by question name.
     *
     * @return array name => string[] path, or false if the name occurs more than once
     */
    public static function release_paths(string $xml): array {
        $paths = [];
        $roots = [];
        $current = [];
        $entries = [];
        preg_match_all('~<question type="([^"]*)">.*?</question>~s', $xml, $blocks, PREG_SET_ORDER);
        foreach ($blocks as $block) {
            $node = simplexml_load_string($block[0], 'SimpleXMLElement', LIBXML_NONET);
            if (!$node) {
                continue;
            }
            if ($block[1] === 'category') {
                $segments = array_values(array_filter(
                    explode('/', trim((string)$node->category->text, '/')),
                    static fn($segment) => $segment !== ''
                ));
                if ($segments && preg_match('/^\$[a-z]+\$$/i', $segments[0])) {
                    array_shift($segments);
                }
                if ($segments && $segments[0] === 'top') {
                    array_shift($segments);
                }
                if ($segments) {
                    $roots[$segments[0]] = true;
                }
                $current = $segments;
                continue;
            }
            $entries[] = [trim((string)$node->name->text), $current];
        }
        $root = count($roots) === 1 ? (string)array_key_first($roots) : null;
        foreach ($entries as [$name, $segments]) {
            if ($root !== null && $segments && $segments[0] === $root) {
                array_shift($segments);
            }
            $paths[$name] = array_key_exists($name, $paths) ? false : array_map('trim', $segments);
        }
        return $paths;
    }

    /**
     * Readable category path below the package category.
     */
    public static function path_label(array $segments): string {
        return $segments ? implode(' / ', $segments) : get_string('diff_path_root', 'qbank_questionhub');
    }

    /**
     * Adds a move (old and new category) to a file of the diff view.
     */
    private static function with_move(array $file, ?array $move): array {
        if ($move) {
            $file['moved_label'] = get_string('diff_moved', 'qbank_questionhub', (object)$move);
            $file['is_rename'] = true;
        }
        return $file;
    }

    /**
     * Adds links to preview and edit the local question of each file (not for questions deleted here).
     *
     * @param array[] $files files with questionid (0 or missing: no local question)
     * @param string $returnurl local URL to come back to after editing
     */
    public static function add_links(array $files, \context $context, int $courseid, string $returnurl): array {
        // Question bank pages need the mod_qbank cmid on Moodle 5.0 and the course ID on 4.5 (course context).
        $bankparams = (int)$context->contextlevel === CONTEXT_MODULE ? ['cmid' => $context->instanceid] :
            ['courseid' => $courseid];
        foreach ($files as &$file) {
            $questionid = (int)($file['questionid'] ?? 0);
            if (!$questionid || !self::question_exists($questionid)) {
                continue;
            }
            $file['preview_url'] = (new \moodle_url(
                '/question/bank/previewquestion/preview.php',
                ['id' => $questionid] + $bankparams
            ))->out(false);
            $file['edit_url'] = (new \moodle_url(
                '/question/bank/editquestion/question.php',
                ['id' => $questionid, 'returnurl' => $returnurl] + $bankparams
            ))->out(false);
        }
        unset($file);
        return $files;
    }

    /**
     * Removed questions that were deleted in Moodle (their versions are gone): name and content come from the
     * published release on the Hub. Its questions that do not exist here any more (by name, also under their
     * previous names) are the deleted ones; all their lines are shown as removed.
     *
     * @param int[] $entryids the deleted question bank entries
     */
    private static function deleted_from_release(
        \stdClass $history,
        array $previous,
        array $current,
        array $entryids
    ): array {
        global $DB;
        $ids = array_filter(array_merge(array_values($current), array_values(array_intersect_key($previous, $current))));
        $known = [];
        foreach (array_chunk(array_unique($ids), 500) as $chunk) {
            foreach ($DB->get_records_list('question', 'id', $chunk, '', 'id, name') as $question) {
                $known[trim($question->name)] = true;
            }
        }
        $xml = self::release_xml($history);
        $files = [];
        preg_match_all('~<question type="(?!category")[^"]*">.*?</question>~s', $xml, $matches);
        foreach ($matches[0] as $block) {
            if (count($files) === count($entryids)) {
                break;
            }
            $node = simplexml_load_string($block, 'SimpleXMLElement', LIBXML_NONET);
            $name = $node ? trim((string)$node->name->text) : '';
            if ($name === '' || isset($known[$name])) {
                continue;
            }
            $files[] = self::compare($name, self::xml_lines($block), [], 'removed') + ['entryid' => $entryids[count($files)]];
        }
        // Deleted questions that cannot be identified in the release (e.g. the Hub is not reachable).
        foreach (array_slice($entryids, count($files)) as $entryid) {
            $files[] = self::compare(get_string('diff_removed_unknown', 'qbank_questionhub', $entryid), [], [], 'removed') +
                ['entryid' => $entryid, 'no_content' => true];
        }
        return $files;
    }

    /**
     * One changed question: added (no old version), removed (no new version) or modified.
     */
    private static function file(int $oldid, int $newid, package_exporter $exporter): array {
        global $DB;
        $name = (string)$DB->get_field('question', 'name', ['id' => $newid ?: $oldid]);
        $old = $oldid ? self::question_lines($exporter, $oldid) : [];
        $new = $newid ? self::question_lines($exporter, $newid) : [];
        $status = !$oldid ? 'added' : (!$newid ? 'removed' : 'modified');
        $oldname = $status === 'modified' ? (string)$DB->get_field('question', 'name', ['id' => $oldid]) : $name;
        if (trim($oldname) === trim($name)) {
            return self::compare($name, $old, $new, $status);
        }
        // Only the name changed: shown as a rename, like git does for a file with identical content.
        $file = self::compare($name, $old, $new, self::same_except_name($old, $new) ? 'renamed' : 'modified');
        return $file + ['renamed_from' => trim($oldname), 'is_rename' => true];
    }

    /**
     * Whether two exported questions differ in their name only.
     */
    public static function same_except_name(array $old, array $new): bool {
        if (count($old) !== count($new)) {
            return false;
        }
        foreach ($old as $index => $line) {
            // The name text is the line after <name>.
            $isname = $index > 0 && trim($old[$index - 1]) === '<name>';
            if (!$isname && $line !== $new[$index]) {
                return false;
            }
        }
        return true;
    }

    /**
     * Diff of two line lists as one file of the diff view.
     *
     * @param string $name question name shown in the file header
     * @param string[] $old lines before (empty for an added question)
     * @param string[] $new lines after (empty for a removed question)
     * @param string $status added, removed, modified, renamed (only the name differs) or moved (no new version)
     * @return array
     */
    public static function compare(string $name, array $old, array $new, string $status = 'modified'): array {
        $file = [
            'name' => $name,
            'status' => $status,
            'additions' => 0, 'deletions' => 0, 'hunks' => [], 'too_large' => false,
        ];
        $file['is_' . $status] = true;
        if (count($old) > self::MAX_LINES || count($new) > self::MAX_LINES) {
            $file['too_large'] = true;
            return $file;
        }
        $rows = self::diff($old, $new);
        foreach ($rows as $row) {
            $file['additions'] += $row['type'] === 'add' ? 1 : 0;
            $file['deletions'] += $row['type'] === 'del' ? 1 : 0;
        }
        $file['hunks'] = self::hunks($rows);
        return $file;
    }

    /**
     * Adds what the diff template needs: DOM IDs, the change bar and which files start open.
     *
     * @param array[] $files results of compare()
     * @return array[]
     */
    public static function decorate(array $files): array {
        static $counter = 0;
        $open = count($files) <= self::OPEN_FILES;
        foreach ($files as &$file) {
            $counter++;
            $file['domid'] = 'qh-diff-' . $counter;
            $file['open'] = $open;
            $total = $file['additions'] + $file['deletions'];
            $green = $total ? (int)round(5 * $file['additions'] / $total) : 0;
            $red = $total ? min(5 - $green, (int)ceil(5 * $file['deletions'] / $total)) : 0;
            $file['bar'] = [];
            for ($i = 0; $i < 5; $i++) {
                $file['bar'][] = ['add' => $i < $green, 'del' => $i >= $green && $i < $green + $red];
            }
            foreach ($file['hunks'] as $index => &$hunk) {
                $hunk['gapid'] = $file['domid'] . '-g' . $index;
                $hunk['has_gap'] = !empty($hunk['gap']);
                $hunk['gap_count'] = count($hunk['gap'] ?? []);
                $hunk['tailid'] = $file['domid'] . '-t' . $index;
                $hunk['has_tail'] = !empty($hunk['tail']);
                $hunk['tail_count'] = count($hunk['tail'] ?? []);
            }
            unset($hunk);
        }
        unset($file);
        return $files;
    }

    /**
     * Names both sides of the diffs, shown as a legend above each diff (− old, + new).
     */
    public static function label_sides(array $files, string $old, string $new): array {
        foreach ($files as &$file) {
            $file['old_label'] = $old;
            $file['new_label'] = $new;
        }
        unset($file);
        return $files;
    }

    /**
     * Exported XML of one question version as comparable lines (without the export envelope).
     */
    public static function question_lines(package_exporter $exporter, int $questionid): array {
        try {
            $xml = $exporter->export_questions_xml([$questionid]);
        } catch (\Throwable $e) {
            return ['[' . $e->getMessage() . ']'];
        }
        return self::xml_lines($xml);
    }

    /**
     * Lines of the latest version of a question bank entry.
     */
    public static function entry_lines(package_exporter $exporter, int $entryid): array {
        global $DB;
        $questionid = (int)$DB->get_field_sql(
            'SELECT questionid FROM {question_versions} WHERE questionbankentryid = ? ORDER BY version DESC',
            [$entryid],
            IGNORE_MULTIPLE
        );
        return $questionid ? self::question_lines($exporter, $questionid) : [];
    }

    /**
     * Moodle XML as comparable lines: envelope, category entries and question ID comments removed.
     */
    public static function xml_lines(string $xml): array {
        $xml = preg_replace('~<question type="category">.*?</question>~s', '', self::pretty_xml($xml));
        $lines = [];
        foreach (preg_split('/\R/', $xml) as $line) {
            // The export envelope and the question ID comment differ between versions without a content change.
            if (
                trim($line) === '' || str_starts_with(ltrim($line), '<?xml') || preg_match('~^\s*</?quiz>\s*$~', $line) ||
                    preg_match('~^\s*<!-- question: \d+\s*-->\s*$~', $line)
            ) {
                continue;
            }
            if (strlen($line) > self::MAX_LINE) {
                $line = substr($line, 0, 80) . ' … [' . strlen($line) . ' Zeichen, ' . substr(sha1($line), 0, 8) . ']';
            }
            $lines[] = $line;
        }
        return $lines;
    }

    /**
     * Re-indents XML so that a Moodle export and a package written by another tool give comparable lines.
     * A single question element is accepted as well; unparsable XML is returned unchanged.
     */
    private static function pretty_xml(string $xml): string {
        $doc = new \DOMDocument();
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;
        $source = preg_match('~^\s*(<\?xml|<quiz)~', $xml) ? $xml : '<quiz>' . $xml . '</quiz>';
        $previous = libxml_use_internal_errors(true);
        $loaded = $doc->loadXML($source, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        return $loaded ? $doc->saveXML() : $xml;
    }

    /**
     * Question blocks (without categories) of a package XML, in package order.
     *
     * @return string[]|null null if the blocks cannot be told apart reliably
     */
    public static function package_blocks(string $xml, int $expected): ?array {
        preg_match_all('~<question type="(?!category")[^"]*">.*?</question>~s', $xml, $matches);
        return count($matches[0]) === $expected ? $matches[0] : null;
    }

    /**
     * Line diff via longest common subsequence: rows of type ctx, del or add.
     */
    public static function diff(array $old, array $new): array {
        $n = count($old);
        $m = count($new);
        $lcs = array_fill(0, $n + 1, array_fill(0, $m + 1, 0));
        for ($i = $n - 1; $i >= 0; $i--) {
            for ($j = $m - 1; $j >= 0; $j--) {
                $lcs[$i][$j] = $old[$i] === $new[$j] ? $lcs[$i + 1][$j + 1] + 1 : max($lcs[$i + 1][$j], $lcs[$i][$j + 1]);
            }
        }
        $rows = [];
        $i = $j = 0;
        while ($i < $n || $j < $m) {
            // Position before this row, for hunk headers.
            $at = ['at_old' => $i + 1, 'at_new' => $j + 1];
            if ($i < $n && $j < $m && $old[$i] === $new[$j]) {
                $rows[] = ['type' => 'ctx', 'text' => $old[$i], 'old' => ++$i, 'new' => ++$j] + $at;
            } else if ($i < $n && ($j === $m || $lcs[$i + 1][$j] >= $lcs[$i][$j + 1])) {
                // Deletions before additions, as in git.
                $rows[] = ['type' => 'del', 'text' => $old[$i], 'old' => ++$i, 'new' => ''] + $at;
            } else {
                $rows[] = ['type' => 'add', 'text' => $new[$j], 'old' => '', 'new' => ++$j] + $at;
            }
        }
        return $rows;
    }

    /**
     * Groups changed rows with their context into hunks. Unchanged stretches are not shown but kept
     * as gap (before a hunk) and tail (after the last hunk), so the view can expand them.
     */
    public static function hunks(array $rows): array {
        $keep = [];
        foreach ($rows as $index => $row) {
            if ($row['type'] !== 'ctx') {
                for ($k = max(0, $index - self::CONTEXT); $k <= min(count($rows) - 1, $index + self::CONTEXT); $k++) {
                    $keep[$k] = true;
                }
            }
        }
        if (!$keep) {
            return [];
        }
        $rows = self::mark_words($rows);
        $hunks = [];
        $current = null;
        $hidden = [];
        foreach ($rows as $index => $row) {
            $row += [
                'is_add' => $row['type'] === 'add',
                'is_del' => $row['type'] === 'del',
                'sign' => ['add' => '+', 'del' => '-', 'ctx' => ' '][$row['type']],
            ];
            if (empty($keep[$index])) {
                if ($current) {
                    $hunks[] = $current;
                    $current = null;
                }
                $hidden[] = $row;
                continue;
            }
            if (!$current) {
                $current = ['header' => '@@ -' . $row['at_old'] . ' +' . $row['at_new'] . ' @@', 'rows' => [],
                    'gap' => $hidden];
                $hidden = [];
            }
            $current['rows'][] = $row;
        }
        if ($current) {
            $hunks[] = $current;
        }
        $hunks[count($hunks) - 1]['tail'] = $hidden;
        return $hunks;
    }

    /**
     * Highlights the changed part of a line replaced by another (GitHub-style word diff): the n-th deleted
     * line of a block is compared with the n-th added line; the common start and end stay unmarked.
     */
    private static function mark_words(array $rows): array {
        $count = count($rows);
        for ($start = 0; $start < $count; $start++) {
            if ($rows[$start]['type'] !== 'del' || ($start > 0 && $rows[$start - 1]['type'] === 'del')) {
                continue;
            }
            $dels = [];
            $k = $start;
            while ($k < $count && $rows[$k]['type'] === 'del') {
                $dels[] = $k++;
            }
            $adds = [];
            while ($k < $count && $rows[$k]['type'] === 'add') {
                $adds[] = $k++;
            }
            foreach ($dels as $pair => $delindex) {
                if (!isset($adds[$pair])) {
                    break;
                }
                [$oldparts, $newparts] = self::word_segments($rows[$delindex]['text'], $rows[$adds[$pair]]['text']);
                $rows[$delindex]['segments'] = $oldparts;
                $rows[$adds[$pair]]['segments'] = $newparts;
            }
        }
        foreach ($rows as &$row) {
            $row['segments'] = $row['segments'] ?? [['text' => $row['text'], 'changed' => false]];
        }
        unset($row);
        return $rows;
    }

    /**
     * Segments of two lines: common prefix, changed middle, common suffix.
     */
    public static function word_segments(string $old, string $new): array {
        $a = mb_str_split($old);
        $b = mb_str_split($new);
        $prefix = 0;
        $max = min(count($a), count($b));
        while ($prefix < $max && $a[$prefix] === $b[$prefix]) {
            $prefix++;
        }
        $suffix = 0;
        while ($suffix < $max - $prefix && $a[count($a) - 1 - $suffix] === $b[count($b) - 1 - $suffix]) {
            $suffix++;
        }
        $split = static function (array $chars) use ($prefix, $suffix): array {
            $parts = [
                ['text' => implode('', array_slice($chars, 0, $prefix)), 'changed' => false],
                ['text' => implode('', array_slice($chars, $prefix, count($chars) - $prefix - $suffix)), 'changed' => true],
                ['text' => implode('', array_slice($chars, count($chars) - $suffix)), 'changed' => false],
            ];
            return array_values(array_filter($parts, static fn($part) => $part['text'] !== ''));
        };
        if ($prefix + $suffix === 0) {
            // Nothing in common: the whole line is the change, as the row colour already shows.
            return [[['text' => $old, 'changed' => false]], [['text' => $new, 'changed' => false]]];
        }
        return [$split($a), $split($b)];
    }
}
