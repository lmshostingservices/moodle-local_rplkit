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
 * Shared unit fixture for local_rplkit tests (illustrative data, not an authoritative unit).
 *
 * @package    local_rplkit
 * @copyright  2026 International Trade & Logistics College
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

return [
    'code' => 'BSBWHS211',
    'name' => 'Contribute to the health and safety of self and others',
    'elements' => [
        ['number' => 1, 'title' => 'Work safely'],
        ['number' => 2, 'title' => 'Implement workplace safety requirements'],
        ['number' => 3, 'title' => 'Participate in WHS consultative processes'],
    ],
    'performance_criteria' => [
        ['code' => '1.1', 'text' => 'Identify and follow workplace procedures for working safely'],
        ['code' => '1.2', 'text' => 'Identify and use PPE according to procedures'],
        ['code' => '1.3', 'text' => 'Identify and report hazards & risks in the work area'],
        ['code' => '2.1', 'text' => 'Identify designated persons for reporting safety concerns'],
        ['code' => '2.2', 'text' => 'Report and record hazards according to workplace procedures'],
        ['code' => '3.1', 'text' => 'Contribute to workplace meetings and inspections'],
        ['code' => '10.1', 'text' => 'Element ten criterion (sort-order test)'],
        ['code' => '1.4', 'text' => '   '],
    ],
    'knowledge_evidence' => [
        ['ref' => 'KE1', 'text' => 'Roles and responsibilities of duty holders under WHS legislation'],
        ['ref' => 'KE2', 'text' => 'Hazard identification & risk control, including the "hierarchy of control"'],
        'Workplace emergency procedures, evacuation and first aid',
        ['ref' => 'KE4', 'text' => 'Reporting <incidents> to a designated person\'s supervisor ]]> (CDATA test)'],
        ['ref' => 'KE5', 'text' => 'Māori and Torres Strait Islander cultural safety ✓'],
        '',
    ],
    'performance_evidence' => [
        'Contributed to the health and safety of self and others on at least two occasions',
        ['ref' => 'PE2', 'text' => 'Identified & reported at least one hazard'],
    ],
    'assessment_conditions' => 'Workplace or simulated environment with access to WHS legislation & procedures.',
];
