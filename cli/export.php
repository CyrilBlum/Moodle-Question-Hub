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
 * CLI tool for exporting question category to Moodle XML package.
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
    'categoryid' => 0,
    'output' => '',
], [
    'h' => 'help',
    'c' => 'courseid',
    'k' => 'categoryid',
    'o' => 'output',
]);

if ($options['help'] || empty($options['courseid'])) {
    $help = "CLI Question Hub Exporter

Options:
 -h, --help            Print this help
 -c, --courseid        Course ID (required)
 -k, --categoryid      Question category ID (optional; defaults to first non-top category)
 -o, --output          Output file path (optional; prints to stdout if omitted)

Example:
 php export.php --courseid=2 --output=/tmp/export.xml
";
    echo $help;
    exit(0);
}

$courseid = (int)$options['courseid'];
$categoryid = (int)$options['categoryid'];

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = \qbank_questionhub\package_exporter::resolve_course_question_context($course);

if ($categoryid === 0) {
    $categories = $DB->get_records('question_categories', ['contextid' => $context->id], 'id ASC');
    foreach ($categories as $cat) {
        if ($cat->name !== 'top') {
            $categoryid = (int)$cat->id;
            break;
        }
    }
}

if ($categoryid === 0) {
    cli_error("No valid question category found for course ID {$courseid}");
}

$exporter = new \qbank_questionhub\package_exporter($courseid);
$xml = $exporter->export_category_xml($categoryid);

if (!empty($options['output'])) {
    file_put_contents($options['output'], $xml);
    cli_writeln("Exported category {$categoryid} successfully to {$options['output']} (" . strlen($xml) . " bytes)");
} else {
    echo $xml;
}
