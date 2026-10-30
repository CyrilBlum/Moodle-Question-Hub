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

use SimpleXMLElement;

/**
 * Pre-flight Validator and Zero-Trust XML Security Engine.
 *
 * Enforces zero-trust defense against XXE, entity expansion, and malicious media,
 * validating pedagogical question invariants and producing structured DiagnosticReports.
 *
 * @package    qbank_questionhub
 * @copyright  2026 Cyril Blum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class preflight_validator {
    /** Maximum allowed package size in bytes (50 MB). */
    public const MAX_PACKAGE_BYTES = 52428800;

    /** Script elements, frames, plugins, event-handler attributes and script URLs. */
    public const ACTIVE_CONTENT_PATTERN = '/<\s*(?:script|iframe|frame|object|embed|applet|base|meta)\b|<[a-z][^>]*\son[a-z]+\s*=|'
        . '(?:href|src|action|formaction)\s*=\s*["\']?\s*(?:javascript|vbscript|data:text\/html)/i';

    /** Maximum allowed single media file in bytes (10 MB). */
    public const MAX_MEDIA_FILE_BYTES = 10485760;

    /** Standard Swiss Gymnasium question types supported. */
    public const STANDARD_QTYPES = [
        'multichoice',
        'truefalse',
        'shortanswer',
        'numerical',
        'matching',
        'multianswer',
        'cloze',
        'essay',
        'description',
        'category',
    ];

    /** Disallowed media file extensions. */
    public const DISALLOWED_EXTENSIONS = [
        'exe', 'sh', 'bat', 'cmd', 'bin', 'msi', 'php', 'phtml', 'php3', 'php4', 'php5',
        'php7', 'php8', 'phps', 'js', 'html', 'htm', 'py', 'pl', 'cgi', 'vbs', 'scr',
        // Script-capable formats the SVG check cannot inspect (svgz is compressed).
        'phar', 'mjs', 'xhtml', 'xht', 'shtml', 'svgz', 'hta', 'wsf', 'ps1', 'jar',
    ];

    /**
     * Executes deterministic in-memory validation of an XML package.
     *
     * @param string $xmlcontent Raw XML package string.
     * @return diagnostic_report
     */
    public static function validate(string $xmlcontent): diagnostic_report {
        $report = new diagnostic_report();

        // Normalize multi-byte encodings (e.g. UTF-16 BOM) to UTF-8 to prevent regex evasion.
        if (str_starts_with($xmlcontent, "\xFF\xFE") || str_starts_with($xmlcontent, "\xFE\xFF")) {
            $xmlcontent = mb_convert_encoding($xmlcontent, 'UTF-8', 'UTF-16');
        } else if (str_starts_with($xmlcontent, "<\0")) {
            // UTF-16 without BOM: libxml detects it from the first bytes, the regex checks below would not.
            $xmlcontent = mb_convert_encoding($xmlcontent, 'UTF-8', 'UTF-16LE');
        } else if (str_starts_with($xmlcontent, "\0<")) {
            $xmlcontent = mb_convert_encoding($xmlcontent, 'UTF-8', 'UTF-16BE');
        }

        // 1. Empty payload check.
        $trimmed = trim($xmlcontent);
        if ($trimmed === '') {
            $report->add_error('ERR_EMPTY_PAYLOAD', self::message('empty_payload'));
            return $report;
        }

        // 2. Package size check.
        $bytes = strlen($xmlcontent);
        if ($bytes > self::MAX_PACKAGE_BYTES) {
            $report->add_error(
                'ERR_PACKAGE_SIZE_EXCEEDED',
                self::message('package_too_large', (object)['bytes' => $bytes, 'limit' => self::MAX_PACKAGE_BYTES])
            );
            return $report;
        }

        // 3. Zero-Trust XXE Defense: Strict Regex Pre-checks.
        if (preg_match('/<!DOCTYPE\b/i', $xmlcontent)) {
            $report->add_error(
                'ERR_XXE_DOCTYPE_FORBIDDEN',
                self::message('doctype_forbidden')
            );
            return $report;
        }

        if (preg_match('/<!ENTITY\b/i', $xmlcontent)) {
            $report->add_error(
                'ERR_XXE_ENTITY_FORBIDDEN',
                self::message('entity_forbidden')
            );
            return $report;
        }

        if (preg_match('/<\s*(?:xi:include|include)\b/i', $xmlcontent)) {
            $report->add_error(
                'ERR_XXE_XINCLUDE_FORBIDDEN',
                self::message('xinclude_forbidden')
            );
            return $report;
        }

        // 4. Safe In-Memory XML Parsing (LIBXML_NONET, no entity expansion).
        $prev = libxml_use_internal_errors(true);
        libxml_clear_errors();

        $doc = @simplexml_load_string($xmlcontent, SimpleXMLElement::class, LIBXML_NONET);
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        // Second line of defence against encodings the regex checks miss: the parser saw a document type.
        if ($doc !== false && dom_import_simplexml($doc)->ownerDocument->doctype !== null) {
            $report->add_error(
                'ERR_XXE_DOCTYPE_FORBIDDEN',
                self::message('doctype_forbidden')
            );
            return $report;
        }

        if ($doc === false || !empty($errors)) {
            $errormsgs = [];
            foreach ($errors as $e) {
                $errormsgs[] = self::message('xml_line', (object)['line' => $e->line, 'message' => trim($e->message)]);
            }
            $report->add_error(
                'ERR_XML_INVALID_SYNTAX',
                self::message('malformed_xml', !empty($errormsgs) ? implode('; ', $errormsgs) : self::message('syntax_error'))
            );
            return $report;
        }

        // 5. Root Element Verification.
        if (strtolower($doc->getName()) !== 'quiz') {
            $report->add_error(
                'ERR_MISSING_QUIZ_ROOT',
                self::message('missing_quiz_root', $doc->getName())
            );
            return $report;
        }

        // 6. Inspect Question Elements.
        $questions = $doc->xpath('/quiz/question');
        if (empty($questions)) {
            $report->add_error('ERR_NO_QUESTIONS_FOUND', self::message('no_questions'));
            return $report;
        }

        $realquestioncount = 0;
        $qindex = 0;

        foreach ($questions as $q) {
            $qindex++;
            $attrs = $q->attributes();
            $qtype = isset($attrs['type']) ? (string)$attrs['type'] : '';

            if ($qtype === '') {
                $report->add_error(
                    'ERR_QUESTION_MISSING_TYPE',
                    self::message('missing_type', $qindex),
                    $qindex
                );
                continue;
            }

            $qname = isset($q->name->text) ? (string)$q->name->text : self::message('question_number', $qindex);

            // Category pseudo-question handling.
            if ($qtype === 'category') {
                $report->increment_category_count();
                $cattext = isset($q->category->text) ? (string)$q->category->text : '';
                if ($cattext !== '') {
                    if (!preg_match('/^\$[a-z]*\d*\$/i', $cattext)) {
                        $report->add_warning(
                            'WARN_CATEGORY_MISSING_CONTEXT',
                            self::message('category_missing_context', $cattext),
                            $qindex,
                            $qname
                        );
                    }
                    if (!str_contains($cattext, '/top/')) {
                        $report->add_warning(
                            'WARN_CATEGORY_MISSING_TOP',
                            self::message('category_missing_top', $cattext),
                            $qindex,
                            $qname
                        );
                    }
                }
                continue;
            }

            // Real question.
            $realquestioncount++;
            $report->increment_question_count($qtype);

            // Check question text.
            $qtext = isset($q->questiontext->text) ? (string)$q->questiontext->text : '';
            if (trim(strip_tags($qtext)) === '' && empty($q->xpath('.//file'))) {
                $report->add_error('ERR_EMPTY_QUESTION_TEXT', self::message('empty_question_text', $qname), $qindex, $qname);
            }

            // Check if question type is standard or supported.
            if (!in_array($qtype, self::STANDARD_QTYPES, true)) {
                $installed = class_exists('\question_bank') && \question_bank::is_qtype_installed($qtype);
                if (!$installed) {
                    $report->add_warning(
                        'WARN_UNSUPPORTED_QUESTION_TYPE',
                        self::message('unsupported_type', $qtype),
                        $qindex,
                        $qname
                    );
                }
            }

            // Type-specific validations.
            self::validate_question_type($q, $qtype, $qindex, $qname, $report);

            // Embedded media validation within this question.
            self::validate_embedded_media($q, $qindex, $qname, $report);

            // Moodle displays question text without HTML cleaning, so scripts in a package run in the importing Moodle.
            if (self::has_active_content($q)) {
                $report->add_warning(
                    'WARN_ACTIVE_CONTENT',
                    self::message('active_content', $qname),
                    $qindex,
                    $qname
                );
            }
        }

        if ($realquestioncount === 0) {
            $report->add_error(
                'ERR_NO_QUESTIONS_FOUND',
                self::message('only_categories')
            );
        }

        return $report;
    }

    /**
     * Language string of a validation message.
     *
     * @param string $key Part of the string identifier after "preflight_".
     * @param mixed $a Values for the string.
     * @return string
     */
    private static function message(string $key, $a = null): string {
        return get_string('preflight_' . $key, 'qbank_questionhub', $a);
    }

    /**
     * Whether a text of the question contains markup that runs code in the browser.
     *
     * @param SimpleXMLElement $q
     * @return bool
     */
    public static function has_active_content(SimpleXMLElement $q): bool {
        foreach ($q->xpath('.//text') as $text) {
            if (preg_match(self::ACTIVE_CONTENT_PATTERN, (string)$text)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Validates type-specific pedagogical invariants.
     *
     * @param SimpleXMLElement $q
     * @param string $qtype
     * @param int $qindex
     * @param string $qname
     * @param diagnostic_report $report
     */
    protected static function validate_question_type(
        SimpleXMLElement $q,
        string $qtype,
        int $qindex,
        string $qname,
        diagnostic_report $report
    ): void {
        switch ($qtype) {
            case 'multichoice':
                $single = isset($q->single) ? strtolower((string)$q->single) : 'true';
                $issingle = ($single === 'true' || $single === '1');
                $answers = $q->xpath('answer');

                if (count($answers) < 2) {
                    $report->add_error(
                        'ERR_NOT_ENOUGH_ANSWERS',
                        self::message('not_enough_answers', $qname),
                        $qindex,
                        $qname
                    );
                    break;
                }

                if ($issingle) {
                    $hundredcount = 0;
                    foreach ($answers as $ans) {
                        $frac = (float)($ans['fraction'] ?? 0);
                        if (abs($frac - 100.0) < 0.01) {
                            $hundredcount++;
                        }
                    }
                    if ($hundredcount === 0) {
                        $report->add_error(
                            'ERR_SINGLE_CHOICE_NO_100',
                            self::message('single_no_100', $qname),
                            $qindex,
                            $qname
                        );
                    } else if ($hundredcount > 1) {
                        $report->add_error(
                            'ERR_SINGLE_CHOICE_MULTIPLE_100',
                            self::message('single_multiple_100', (object)['name' => $qname, 'count' => $hundredcount]),
                            $qindex,
                            $qname
                        );
                    }
                } else {
                    $positivesum = 0.0;
                    foreach ($answers as $ans) {
                        $frac = (float)($ans['fraction'] ?? 0);
                        if ($frac > 0) {
                            $positivesum += $frac;
                        }
                    }
                    if (abs($positivesum - 100.0) > 0.5) {
                        $report->add_warning(
                            'WARN_MULTIPLE_CHOICE_SUM',
                            self::message('multiple_sum', (object)['name' => $qname, 'sum' => $positivesum]),
                            $qindex,
                            $qname
                        );
                    }
                }
                break;

            case 'truefalse':
                $answers = $q->xpath('answer');
                if (count($answers) < 2) {
                    $report->add_error(
                        'ERR_TRUEFALSE_INVALID_CHOICES',
                        self::message('truefalse_choices', $qname),
                        $qindex,
                        $qname
                    );
                    break;
                }
                $has100 = false;
                $has0 = false;
                foreach ($answers as $ans) {
                    $frac = (float)($ans['fraction'] ?? 0);
                    if (abs($frac - 100.0) < 0.01) {
                        $has100 = true;
                    }
                    if (abs($frac) < 0.01) {
                        $has0 = true;
                    }
                }
                if (!$has100 || !$has0) {
                    $report->add_error(
                        'ERR_TRUEFALSE_INVALID_FRACTIONS',
                        self::message('truefalse_fractions', $qname),
                        $qindex,
                        $qname
                    );
                }
                break;

            case 'shortanswer':
                $answers = $q->xpath('answer');
                $has100 = false;
                foreach ($answers as $ans) {
                    $frac = (float)($ans['fraction'] ?? 0);
                    if (abs($frac - 100.0) < 0.01) {
                        $has100 = true;
                        break;
                    }
                }
                if (!$has100) {
                    $report->add_error(
                        'ERR_SHORTANSWER_NO_100',
                        self::message('shortanswer_no_100', $qname),
                        $qindex,
                        $qname
                    );
                }
                break;

            case 'numerical':
                $answers = $q->xpath('answer');
                $has100 = false;
                foreach ($answers as $ans) {
                    $frac = (float)($ans['fraction'] ?? 0);
                    if (abs($frac - 100.0) < 0.01) {
                        $has100 = true;
                        break;
                    }
                }
                if (!$has100) {
                    $report->add_error(
                        'ERR_NUMERICAL_NO_100',
                        self::message('numerical_no_100', $qname),
                        $qindex,
                        $qname
                    );
                }
                break;

            case 'matching':
                $subqs = $q->xpath('subquestion');
                if (count($subqs) < 2) {
                    $report->add_error(
                        'ERR_MATCHING_INSUFFICIENT_SUBQS',
                        self::message('matching_subquestions', $qname),
                        $qindex,
                        $qname
                    );
                }
                break;

            case 'multianswer':
            case 'cloze':
                $qtext = isset($q->questiontext->text) ? (string)$q->questiontext->text : '';
                self::validate_cloze_syntax($qtext, $qindex, $qname, $report);
                break;
        }
    }

    /**
     * Validates Cloze syntax tokens within question text.
     *
     * @param string $text
     * @param int $qindex
     * @param string $qname
     * @param diagnostic_report $report
     */
    protected static function validate_cloze_syntax(string $text, int $qindex, string $qname, diagnostic_report $report): void {
        $problem = self::find_cloze_problem($text);
        if ($problem !== null) {
            $report->add_error(
                'ERR_CLOZE_SYNTAX_INVALID',
                self::message('cloze_invalid', (object)['name' => $qname, 'problem' => $problem]),
                $qindex,
                $qname
            );
        }
    }

    /**
     * Returns the first Cloze syntax problem or null.
     *
     * Only real Cloze tokens ({n:TYPE:...}) are checked. Other curly braces (LaTeX such as
     * \begin{pmatrix}, code) are literal text in Moodle as well and are never reported.
     *
     * @param string $text
     * @return string|null
     */
    public static function find_cloze_problem(string $text): ?string {
        $length = strlen($text);
        $remainder = '';
        for ($i = 0; $i < $length; $i++) {
            $char = $text[$i];
            if ($char === '\\') {
                $remainder .= substr($text, $i, 2);
                $i++;
                continue;
            }
            if ($char !== '{') {
                $remainder .= $char;
                continue;
            }
            $rest = substr($text, $i, 80);
            if (preg_match('/^\{\d*:[A-Za-z_]+:/', $rest, $m)) {
                $j = $i + strlen($m[0]);
                $closed = false;
                for (; $j < $length; $j++) {
                    if ($text[$j] === '\\') {
                        $j++;
                        continue;
                    }
                    if ($text[$j] === '}') {
                        $closed = true;
                        break;
                    }
                }
                if (!$closed) {
                    return self::message('cloze_not_closed', substr($rest, 0, 30));
                }
                $i = $j;
            } else if (preg_match('/^\{\d+:/', $rest)) {
                return self::message('cloze_no_type', substr($rest, 0, 30));
            } else {
                $remainder .= '{';
            }
        }
        // A "}" that closes something like 1:SA:=answer without an opening "{".
        if (preg_match('/(?<![{\d])\d+:[A-Za-z_]+:[^{}]*\}/', $remainder, $orphan)) {
            return self::message('cloze_no_opening', substr($orphan[0], 0, 30));
        }
        return null;
    }

    /**
     * Inspects embedded <file> elements for Base64 integrity and safety.
     *
     * @param SimpleXMLElement $q
     * @param int $qindex
     * @param string $qname
     * @param diagnostic_report $report
     */
    protected static function validate_embedded_media(
        SimpleXMLElement $q,
        int $qindex,
        string $qname,
        diagnostic_report $report
    ): void {
        $files = $q->xpath('.//file');
        foreach ($files as $file) {
            $filename = (string)($file['name'] ?? self::message('unnamed_file'));
            $encoding = (string)($file['encoding'] ?? 'base64');
            $rawcontent = (string)$file;

            // 1. Directory traversal and illegal path character defense-in-depth.
            if ($filename === '' || preg_match('/(?:\.\.|\/|\\\\|\x00)/', $filename)) {
                $report->add_error(
                    'ERR_FILE_PATH_TRAVERSAL',
                    self::message('file_path', (object)['file' => $filename, 'name' => $qname]),
                    $qindex,
                    $qname
                );
                continue;
            }

            // Check extension.
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (in_array($ext, self::DISALLOWED_EXTENSIONS, true)) {
                $report->add_error(
                    'ERR_DISALLOWED_MEDIA_MIME',
                    self::message('file_extension', (object)['ext' => $ext, 'file' => $filename, 'name' => $qname]),
                    $qindex,
                    $qname
                );
                continue;
            }

            // Check Base64 characters.
            $cleaned = preg_replace('/\s+/', '', $rawcontent);
            if (!preg_match('/^[A-Za-z0-9+\/]+={0,2}$/', $cleaned)) {
                $report->add_error(
                    'ERR_MALFORMED_MEDIA_BASE64',
                    self::message('file_base64', (object)['file' => $filename, 'name' => $qname]),
                    $qindex,
                    $qname
                );
                continue;
            }

            $decoded = base64_decode($cleaned, true);
            if ($decoded === false) {
                $report->add_error(
                    'ERR_MALFORMED_MEDIA_BASE64',
                    self::message('file_decode', (object)['file' => $filename, 'name' => $qname]),
                    $qindex,
                    $qname
                );
                continue;
            }

            $filesize = strlen($decoded);
            if ($filesize > self::MAX_MEDIA_FILE_BYTES) {
                $report->add_error(
                    'ERR_PACKAGE_SIZE_EXCEEDED',
                    self::message('file_too_large', (object)[
                        'file' => $filename, 'bytes' => $filesize, 'limit' => self::MAX_MEDIA_FILE_BYTES,
                    ]),
                    $qindex,
                    $qname
                );
                continue;
            }

            $report->add_media_file_stat($filesize);

            // SVG active script inspection.
            if ($ext === 'svg' || str_starts_with(ltrim($decoded), '<svg')) {
                if (preg_match('/<\s*script\b|on[a-z]+\s*=|xlink:href\s*=\s*["\']javascript:/i', $decoded)) {
                    $report->add_error(
                        'ERR_SVG_MALICIOUS_SCRIPT',
                        self::message('svg_script', (object)['file' => $filename, 'name' => $qname]),
                        $qindex,
                        $qname
                    );
                }
            }
        }
    }

    /**
     * Finds the largest questions of an XML export without parsing it, so an oversized package can be explained.
     *
     * @param string $xmlcontent Moodle XML export.
     * @param int $limit Number of questions to return.
     * @return array List of ['name' => string, 'bytes' => int], largest first.
     */
    public static function largest_questions(string $xmlcontent, int $limit = 5): array {
        if (!preg_match_all('/<question\s+type="([^"]*)"/', $xmlcontent, $matches, PREG_OFFSET_CAPTURE)) {
            return [];
        }
        $starts = array_column($matches[0], 1);
        $sizes = [];
        foreach ($starts as $i => $start) {
            if ($matches[1][$i][0] === 'category') {
                continue;
            }
            $end = $starts[$i + 1] ?? strlen($xmlcontent);
            $head = substr($xmlcontent, $start, 2000);
            $name = preg_match('/<name>\s*<text>(.*?)<\/text>/s', $head, $m)
                ? html_entity_decode(preg_replace('/^<!\[CDATA\[|\]\]>$/', '', trim($m[1])), ENT_QUOTES | ENT_XML1, 'UTF-8')
                : '?';
            $sizes[] = ['name' => $name, 'bytes' => $end - $start];
        }
        usort($sizes, fn($a, $b) => $b['bytes'] <=> $a['bytes']);
        return array_slice($sizes, 0, $limit);
    }
}
