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
 * Admin settings / navigation for local_rplkit.
 *
 * @package    local_rplkit
 * @copyright  2026 International Trade & Logistics College
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add('root', new admin_category('local_rplkit_cat', get_string('pluginname', 'local_rplkit')));
    $ADMIN->add('local_rplkit_cat', new admin_externalpage(
        'local_rplkit_generate',
        get_string('generator', 'local_rplkit'),
        new moodle_url('/local/rplkit/index.php'),
        'local/rplkit:manage'
    ));
    // Moodle does not show settings for grading-method plugins, so link Assignment Benchmarks' own
    // settings page (1.2.40+) here when it is installed.
    if (file_exists($CFG->dirroot . '/grade/grading/form/benchmarks/admin.php')) {
        $ADMIN->add('local_rplkit_cat', new admin_externalpage(
            'local_rplkit_benchmarkssettings',
            get_string('benchmarkssettings', 'local_rplkit'),
            new moodle_url('/grade/grading/form/benchmarks/admin.php'),
            'moodle/site:config'
        ));
    }
}
