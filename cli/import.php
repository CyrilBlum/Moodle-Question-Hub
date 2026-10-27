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
 * CLI tool for importing Moodle XML package into course question bank.
 *
 * @package    qbank_questionhub
 * @copyright  2026 Cyril Blum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

\core\session\manager::set_user(get_admin());

[$options, $unrecognized] = cli_get_params([
    'help' => false,
    'courseid' => 0,
    'file' => '',
    'categoryid' => 0,
    'preservehierarchy' => 1,
], [
    'h' => 'help',
    'c' => 'courseid',
    'f' => 'file',
    'k' => 'categoryid',
    'p' => 'preservehierarchy',
]);

if ($options['help'] || empty($options['courseid']) || empty($options['file'])) {
    $help = "CLI Question Hub Importer

Options:
 -h, --help                Print this help
 -c, --courseid            Target course ID (required)
 -f, --file                Input XML file path (required)
 -k, --categoryid          Destination question category ID (optional)
 -p, --preservehierarchy   Preserve category hierarchy from XML (default: 1)

Example:
 php import.php --courseid=2 --file=/tmp/package.xml
";
    echo $help;
    exit(0);
}

$courseid = (int)$options['courseid'];
$file = $options['file'];
$categoryid = (int)$options['categoryid'];
$preserve = (bool)$options['preservehierarchy'];

if (!file_exists($file)) {
    cli_error("Input file not found: {$file}");
}

$xmlcontent = file_get_contents($file);
if ($xmlcontent === false || trim($xmlcontent) === '') {
    cli_error("Input file is empty or unreadable: {$file}");
}

// Pre-flight check first.
$report = \qbank_questionhub\preflight_validator::validate($xmlcontent);
if (!$report->is_valid()) {
    cli_writeln("Preflight validation failed with errors:");
    foreach ($report->get_errors() as $err) {
        cli_writeln(" - [{$err['code']}] {$err['message']}");
    }
    exit(1);
}

cli_writeln("Preflight validation passed: {$report->get_summary()['totalQuestions']} questions found.");

$importer = new \qbank_questionhub\package_importer($courseid, $categoryid > 0 ? $categoryid : null);
$result = $importer->import_package($xmlcontent, $preserve, ['title' => basename($file)]);

if ($result->success) {
    cli_writeln("SUCCESS: Imported {$result->questioncount} questions into course {$courseid}.");
    if (!empty($result->warnings)) {
        cli_writeln("Warnings encountered (" . count($result->warnings) . "):");
        foreach ($result->warnings as $w) {
            cli_writeln(" - [{$w['code']}] {$w['message']}");
        }
    }
} else {
    cli_error("Import failed.");
}
