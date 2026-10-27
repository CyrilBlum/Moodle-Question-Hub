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
 * Settings for qbank_questionhub.
 *
 * @package    qbank_questionhub
 * @copyright  2026 Cyril Blum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Core (plugininfo\qbank) creates the page $settings and adds it to the admin tree.
if ($ADMIN->fulltree) {
    // Question Hub Base URL.
    $settings->add(new admin_setting_configtext(
        'qbank_questionhub/huburl',
        get_string('huburl', 'qbank_questionhub'),
        get_string('huburl_desc', 'qbank_questionhub'),
        'https://hub.cblum.ch',
        PARAM_URL
    ));

    // OAuth 2.0 Client ID.
    $settings->add(new admin_setting_configtext(
        'qbank_questionhub/clientid',
        get_string('clientid', 'qbank_questionhub'),
        get_string('clientid_desc', 'qbank_questionhub'),
        'moodle-plugin-client',
        PARAM_ALPHANUMEXT
    ));

    // Institutional Token (PAT fallback).
    $settings->add(new admin_setting_configpasswordunmask(
        'qbank_questionhub/institutiontoken',
        get_string('institutiontoken', 'qbank_questionhub'),
        get_string('institutiontoken_desc', 'qbank_questionhub'),
        ''
    ));

    // Test the saved Hub URL and tokens.
    $settings->add(new admin_setting_description(
        'qbank_questionhub/testconnection',
        get_string('testconnection', 'qbank_questionhub'),
        html_writer::div(get_string('testconnection_desc', 'qbank_questionhub'), 'mb-2') .
            html_writer::link(
                new moodle_url('/question/bank/questionhub/testconnection.php'),
                get_string('testconnection', 'qbank_questionhub'),
                ['class' => 'btn btn-secondary']
            )
    ));

    // Allow Individual Teacher Account Linking.
    $settings->add(new admin_setting_configcheckbox(
        'qbank_questionhub/allowteacherlinking',
        get_string('allowteacherlinking', 'qbank_questionhub'),
        get_string('allowteacherlinking_desc', 'qbank_questionhub'),
        1
    ));

    // Clean HTML of imported packages (off: authors' scripts stay, as with Moodle's own XML import).
    $settings->add(new admin_setting_configcheckbox(
        'qbank_questionhub/stripactivecontent',
        get_string('stripactivecontent', 'qbank_questionhub'),
        get_string('stripactivecontent_desc', 'qbank_questionhub'),
        0
    ));
}
