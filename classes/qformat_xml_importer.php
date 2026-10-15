<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace qbank_questionhub;

/**
 * XML question importer that can remap package categories before Moodle imports them.
 *
 * @package    qbank_questionhub
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qformat_xml_importer extends \qformat_xml {

    /** @var callable|null Category question transformer. */
    private $categorytransformer;

    /** @var callable|null Question filter applied after category remapping. */
    private $questionfilter;

    /**
     * Set a callback to adjust category question paths after XML parsing and before import.
     *
     * @param callable $transformer
     */
    public function set_category_transformer(callable $transformer): void {
        $this->categorytransformer = $transformer;
    }

    /** Set a callback that removes questions not selected for import. */
    public function set_question_filter(callable $filter): void {
        $this->questionfilter = $filter;
    }

    /**
     * Keep Moodle's XML question-type dispatch when using this subclass.
     * Core derives the handler name from get_class($this), which would otherwise
     * look for import_from_questionhub\\qformat_xml_importer.
     */
    public function try_importing_using_qtypes($data, $question = null, $extra = null, $qtypehint = '') {
        if ($qtypehint !== '') {
            $qtype = \question_bank::get_qtype($qtypehint, false);
            if (is_object($qtype) && method_exists($qtype, 'import_from_xml')) {
                $imported = $qtype->import_from_xml($data, $question, $this, $extra);
                if ($imported) {
                    return $imported;
                }
            }
        }
        foreach (\question_bank::get_all_qtypes() as $qtype) {
            if (method_exists($qtype, 'import_from_xml')) {
                $imported = $qtype->import_from_xml($data, $question, $this, $extra);
                if ($imported) {
                    return $imported;
                }
            }
        }
        return false;
    }

    /**
     * Parse the XML and remap its category markers before Moodle creates categories.
     *
     * @param array $lines
     * @return array|false
     */
    public function readquestions($lines) {
        $questions = parent::readquestions($lines);
        if (is_array($questions) && $this->categorytransformer) {
            ($this->categorytransformer)($questions);
        }
        if (is_array($questions) && $this->questionfilter) {
            $questions = ($this->questionfilter)($questions);
        }
        return $questions;
    }
}
