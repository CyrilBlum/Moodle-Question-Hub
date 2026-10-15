<?php
namespace qbank_questionhub;

/**
 * Line diff used by the push view.
 *
 * @covers \qbank_questionhub\release_diff
 */
final class release_diff_test extends \advanced_testcase {
    public function test_diff_marks_changed_and_inserted_lines(): void {
        $old = ['a', 'b', 'c'];
        $new = ['a', 'B', 'c', 'd'];
        $rows = release_diff::diff($old, $new);
        $this->assertSame(['ctx', 'del', 'add', 'ctx', 'add'], array_column($rows, 'type'));
        $this->assertSame('b', $rows[1]['text']);
        $this->assertSame('B', $rows[2]['text']);
    }

    public function test_hunks_skip_unchanged_stretches(): void {
        $old = array_map(static fn($i) => "line $i", range(1, 20));
        $new = $old;
        $new[1] = 'changed';
        $new[17] = 'changed too';
        $hunks = release_diff::hunks(release_diff::diff($old, $new));
        $this->assertCount(2, $hunks);
        $this->assertSame('@@ -1 +1 @@', $hunks[0]['header']);
        $this->assertSame([], release_diff::hunks(release_diff::diff(['x'], ['x'])));
    }
}
