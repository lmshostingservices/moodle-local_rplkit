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
 * Download one RPL Kit output, or all three as a ZIP, for the unit in the user's session.
 *
 * @package    local_rplkit
 * @copyright  2026 International Trade & Logistics College
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/filelib.php');

use local_rplkit\kit;

require_login();
require_capability('local/rplkit:manage', context_system::instance());
require_sesskey();

$type = required_param('type', PARAM_ALPHA);
if ($type !== 'all' && !in_array($type, kit::TYPES, true)) {
    throw new moodle_exception('invalidparameter', 'debug');
}

$unit = cache::make('local_rplkit', 'unit')->get('current');
if (!$unit) {
    redirect(
        new moodle_url('/local/rplkit/index.php'),
        get_string('errornounit', 'local_rplkit'),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}

\core\session\manager::write_close();

if ($type === 'all') {
    $path = kit::zip($unit, make_request_directory());
    send_file($path, kit::filename($unit, 'all'), 0, 0, false, true, 'application/zip');
} else {
    $mime = $type === 'quiz' ? 'application/xml' : 'application/json';
    send_file(kit::content($unit, $type), kit::filename($unit, $type), 0, 0, true, true, $mime);
}
