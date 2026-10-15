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

    /**
     * Changed questions of a category tree since the given release.
     *
     * @param int $categoryid source category of the package
     * @param \stdClass $history the release (history record) to compare with
     * @param package_exporter $exporter exporter for the bank context
     * @return array|null files (name, status, additions, deletions, hunks) and same_content (new versions with
     *         identical content, e.g. an edit that was undone), null without a snapshot
     */
    public static function since(int $categoryid, \stdClass $history, package_exporter $exporter): ?array {
        $changes = publication_tracker::changes($categoryid, $history);
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
            $files[] = $file;
        }
        foreach ($changes['removed'] as $questionid) {
            $files[] = self::file((int)$questionid, 0, $exporter);
        }
        usort($files, static fn($a, $b) => strcmp($a['name'], $b['name']));
        return ['files' => $files, 'same_content' => $samecontent];
    }

    /** One changed question: added (no old version), removed (no new version) or modified. */
    private static function file(int $oldid, int $newid, package_exporter $exporter): array {
        global $DB;
        $name = (string)$DB->get_field('question', 'name', ['id' => $newid ?: $oldid]);
        $old = $oldid ? self::lines($exporter, $oldid) : [];
        $new = $newid ? self::lines($exporter, $newid) : [];
        $file = [
            'name' => $name,
            'status' => !$oldid ? 'added' : (!$newid ? 'removed' : 'modified'),
            'additions' => 0, 'deletions' => 0, 'hunks' => [], 'too_large' => false,
        ];
        $file['is_' . $file['status']] = true;
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

    /** Exported XML of one question version as comparable lines (without the export envelope). */
    private static function lines(package_exporter $exporter, int $questionid): array {
        try {
            $xml = $exporter->export_questions_xml([$questionid]);
        } catch (\Throwable $e) {
            return ['[' . $e->getMessage() . ']'];
        }
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

    /** Groups changed rows with their context into hunks; unchanged stretches are left out. */
    public static function hunks(array $rows): array {
        $keep = [];
        foreach ($rows as $index => $row) {
            if ($row['type'] !== 'ctx') {
                for ($k = max(0, $index - self::CONTEXT); $k <= min(count($rows) - 1, $index + self::CONTEXT); $k++) {
                    $keep[$k] = true;
                }
            }
        }
        $hunks = [];
        $current = null;
        $previous = -2;
        foreach ($rows as $index => $row) {
            if (empty($keep[$index])) {
                continue;
            }
            if ($index !== $previous + 1) {
                if ($current) {
                    $hunks[] = $current;
                }
                $current = ['header' => '@@ -' . $row['at_old'] . ' +' . $row['at_new'] . ' @@', 'rows' => []];
            }
            $current['rows'][] = $row + [
                'is_add' => $row['type'] === 'add',
                'is_del' => $row['type'] === 'del',
                'sign' => ['add' => '+', 'del' => '-', 'ctx' => ' '][$row['type']],
            ];
            $previous = $index;
        }
        if ($current) {
            $hunks[] = $current;
        }
        return $hunks;
    }
}
