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
 * RPL Kit main page.
 *
 * @package    local_rplkit
 * @copyright  2026 International Trade & Logistics College
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

require_login();
$context = context_system::instance();
require_capability('local/rplkit:manage', $context);

$PAGE->set_url(new moodle_url('/local/rplkit/index.php'));
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('pluginname', 'local_rplkit'));
$PAGE->set_heading(get_string('heading', 'local_rplkit'));

echo $OUTPUT->header();
echo $OUTPUT->box(get_string('intro', 'local_rplkit'));

// v0.1.0: the three-output generator UI is under construction. The SmartForm builder
// (output #2) is implemented in \local_rplkit\smartform_builder; the theory-quiz and
// Assignment-Benchmarks outputs are wired once the platform schemas are connected.
echo html_writer::tag('p', get_string('pluginname', 'local_rplkit') . ' v0.1.0 — forms-based '
    . 'practical output (SmartForm JSON) is implemented; theory-quiz and practical-assignment '
    . 'outputs are being connected to the platform.');

echo $OUTPUT->footer();
