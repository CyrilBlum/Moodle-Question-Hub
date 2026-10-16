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

    public function test_hunks_keep_hidden_lines_for_expanding(): void {
        $old = array_map(static fn($i) => "line $i", range(1, 20));
        $new = $old;
        $new[9] = 'changed';
        $hunks = release_diff::hunks(release_diff::diff($old, $new));
        $this->assertCount(1, $hunks);
        // Lines 1-6 before and 14-20 after the context of three lines around line 10.
        $this->assertSame(range(1, 6), array_column($hunks[0]['gap'], 'old'));
        $this->assertSame(range(14, 20), array_column($hunks[0]['tail'], 'old'));
    }

    public function test_replaced_line_marks_only_the_changed_words(): void {
        [$old, $new] = release_diff::word_segments('<text>Grösse 24 MB</text>', '<text>Grösse 25 MB</text>');
        $this->assertSame(['<text>Grösse 2', '4', ' MB</text>'], array_column($old, 'text'));
        $this->assertSame([false, true, false], array_column($new, 'changed'));
        // Nothing in common: no marking, the row colour shows the change.
        [$old, $new] = release_diff::word_segments('abc', 'xyz');
        $this->assertSame([['text' => 'abc', 'changed' => false]], $old);
        $hunks = release_diff::hunks(release_diff::diff(['a', 'value 1', 'b'], ['a', 'value 2', 'b']));
        $this->assertSame(['value ', '1'], array_column($hunks[0]['rows'][1]['segments'], 'text'));
    }

    public function test_package_blocks_skip_categories(): void {
        $xml = '<quiz><question type="category"><category><text>$course$/top/A</text></category></question>' .
            '<question type="shortanswer"><name><text>Q1</text></name></question>' .
            '<question type="multichoice"><name><text>Q2</text></name></question></quiz>';
        $blocks = release_diff::package_blocks($xml, 2);
        $this->assertCount(2, $blocks);
        $this->assertStringContainsString('Q2', $blocks[1]);
        $this->assertNull(release_diff::package_blocks($xml, 3));
    }

    public function test_same_except_name_detects_pure_rename(): void {
        $old = ['<question type="numerical">', '  <name>', '    <text>Anzahl Pixel</text>', '  </name>', '  <defaultgrade>1</defaultgrade>'];
        $renamed = $old;
        $renamed[2] = '    <text>K3 - Anzahl Pixel</text>';
        $this->assertTrue(release_diff::same_except_name($old, $renamed));
        $edited = $renamed;
        $edited[4] = '  <defaultgrade>2</defaultgrade>';
        $this->assertFalse(release_diff::same_except_name($old, $edited));
        $this->assertFalse(release_diff::same_except_name($old, array_slice($old, 0, 4)));
    }

    public function test_release_paths_below_package_root(): void {
        $xml = '<quiz><question type="category"><category><text>$course$/top/Paket</text></category></question>' .
            '<question type="shortanswer"><name><text>Q1</text></name></question>' .
            '<question type="category"><category><text>$course$/top/Paket/Teil A</text></category></question>' .
            '<question type="shortanswer"><name><text>Q2</text></name></question>' .
            '<question type="shortanswer"><name><text>Q2</text></name></question></quiz>';
        $paths = release_diff::release_paths($xml);
        $this->assertSame([], $paths['Q1']);
        $this->assertFalse($paths['Q2']);
    }
}
