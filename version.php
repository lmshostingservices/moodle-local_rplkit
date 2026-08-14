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
 * RPL Kit — Recognition of Prior Learning assessment-kit generator.
 *
 * Generates three ASQA-mapped RPL evidence outputs for a unit of competency:
 *   1. Theory  -> Moodle quiz essay questions (with model answers + marking guides),
 *                 mapped to the unit's Knowledge Evidence.
 *   2. Practical, forms-based -> a SmartForm JSON (observation / third-party checklist),
 *                 mapped to Performance Criteria, ready to paste into SmartForm AI.
 *   3. Practical skill demonstration -> a Moodle assignment with Assignment Benchmarks
 *                 advanced-grading criteria (skills -> sub-skills -> scores), mapped to
 *                 Performance Criteria + Performance Evidence.
 *
 * Communicates with local_rtocompliance (shared RTO / qualification data) when present.
 *
 * @package    local_rplkit
 * @copyright  2026 International Trade & Logistics College
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_rplkit';
$plugin->version   = 2026080500; // v0.3.0 - output #3: Assignment Benchmarks builder (all three ASQA evidence outputs implemented).
$plugin->release   = '0.3.0';
$plugin->requires  = 2022112800;    // Moodle 4.1 (LTS) or later.
$plugin->maturity  = MATURITY_BETA;

// Soft dependency: RPL Kit will use local_rtocompliance's RTO/qualification data when it is
// installed, but does not hard-require it (the generator can also take unit data directly).
$plugin->dependencies = [];
