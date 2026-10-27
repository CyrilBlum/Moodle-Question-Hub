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
 * Disconnects the teacher's linked Question Hub account.
 *
 * @package    qbank_questionhub
 * @copyright  2026 Cyril Blum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');

require_login();
require_sesskey();

$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);
$defaultreturn = new moodle_url('/question/bank/questionhub/view.php');
$target = !empty($returnurl) ? new moodle_url($returnurl) : $defaultreturn;

\qbank_questionhub\auth_manager::delete_user_token((int)$USER->id);
\core\notification::success(get_string('disconnect_success', 'qbank_questionhub'));

redirect($target);
