<?php
namespace qbank_questionhub;

/** Question-level changes since a release, with line diffs of the exported Moodle XML. */
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
    public static function since(int $categoryid, \stdClass $history, package_exporter $exporter,
            bool $inscope = false): ?array {
        $changes = $inscope ? publication_tracker::changes_in_scope($categoryid, $history) :
            publication_tracker::changes($categoryid, $history);
        $previous = publication_tracker::snapshot((int)$history->id);
        if ($changes === null || $previous === null) {
            return null;
        }
        $current = publication_tracker::current($categoryid);
        $files = [];
        $samecontent = 0;
        foreach (array_keys($changes['entries']) as $entryid) {
            $file = self::file($previous[$entryid] ?? 0, $current[$entryid] ?? 0, $exporter);
            if ($file['status'] === 'modified' && !$file['too_large'] && !$file['additions'] && !$file['deletions']) {
                $samecontent++;
                continue;
            }
            $files[] = $file + ['entryid' => (int)$entryid];
        }
        foreach ($changes['removed'] as $entryid => $questionid) {
            $files[] = self::file((int)$questionid, 0, $exporter) + ['entryid' => (int)$entryid];
        }
        usort($files, static fn($a, $b) => strcmp($a['name'], $b['name']));
        return ['files' => self::decorate($files), 'same_content' => $samecontent];
    }

    /** One changed question: added (no old version), removed (no new version) or modified. */
    private static function file(int $oldid, int $newid, package_exporter $exporter): array {
        global $DB;
        $name = (string)$DB->get_field('question', 'name', ['id' => $newid ?: $oldid]);
        $old = $oldid ? self::question_lines($exporter, $oldid) : [];
        $new = $newid ? self::question_lines($exporter, $newid) : [];
        return self::compare($name, $old, $new, !$oldid ? 'added' : (!$newid ? 'removed' : 'modified'));
    }

    /**
     * Diff of two line lists as one file of the diff view.
     *
     * @param string $name question name shown in the file header
     * @param string[] $old lines before (empty for an added question)
     * @param string[] $new lines after (empty for a removed question)
     * @param string $status added, removed or modified
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

    /** Exported XML of one question version as comparable lines (without the export envelope). */
    public static function question_lines(package_exporter $exporter, int $questionid): array {
        try {
            $xml = $exporter->export_questions_xml([$questionid]);
        } catch (\Throwable $e) {
            return ['[' . $e->getMessage() . ']'];
        }
        return self::xml_lines($xml);
    }

    /** Lines of the latest version of a question bank entry. */
    public static function entry_lines(package_exporter $exporter, int $entryid): array {
        global $DB;
        $questionid = (int)$DB->get_field_sql(
            'SELECT questionid FROM {question_versions} WHERE questionbankentryid = ? ORDER BY version DESC',
            [$entryid], IGNORE_MULTIPLE);
        return $questionid ? self::question_lines($exporter, $questionid) : [];
    }

    /** Moodle XML as comparable lines: envelope, category entries and question ID comments removed. */
    public static function xml_lines(string $xml): array {
        $xml = preg_replace('~<question type="category">.*?</question>~s', '', $xml);
        $lines = [];
        foreach (preg_split('/\R/', $xml) as $line) {
            // The export envelope and the question ID comment differ between versions without a content change.
            if (trim($line) === '' || str_starts_with(ltrim($line), '<?xml') || preg_match('~^\s*</?quiz>\s*$~', $line) ||
                    preg_match('~^\s*<!-- question: \d+\s*-->\s*$~', $line)) {
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
     * Question blocks (without categories) of a package XML, in package order.
     *
     * @return string[]|null null if the blocks cannot be told apart reliably
     */
    public static function package_blocks(string $xml, int $expected): ?array {
        preg_match_all('~<question type="(?!category")[^"]*">.*?</question>~s', $xml, $matches);
        return count($matches[0]) === $expected ? $matches[0] : null;
    }

    /** Line diff via longest common subsequence: rows of type ctx, del or add. */
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

    /** Segments of two lines: common prefix, changed middle, common suffix. */
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
        $split = static function(array $chars) use ($prefix, $suffix): array {
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
