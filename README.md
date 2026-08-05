# RPL Kit (local_rplkit)

RPL Kit is a Moodle local plugin that generates Recognition of Prior Learning (RPL) assessment
kits for a unit of competency, mapped to the 2025 Standards for RTOs and the ASQA practice
guides. It communicates with the RTO Compliance plugin (`local_rtocompliance`) for shared RTO and
qualification data when that plugin is installed.

## What it produces

For a chosen unit of competency, RPL Kit generates three ASQA-mapped evidence outputs:

1. **Theory evidence** — a set of Moodle essay questions (Moodle question-bank XML) mapped to the
   unit's Knowledge Evidence, each with a model answer and a marking guide, ready to import into a
   Moodle quiz and mark with an AI grader under assessor oversight.
2. **Practical, forms-based evidence** — a SmartForm JSON document (an observation / third-party
   checklist) mapped to the unit's Performance Criteria, with authenticity and third-party
   controls, ready to paste into the SmartForm app.
3. **Practical skill demonstration** — a Moodle assignment with Assignment Benchmarks advanced
   grading criteria (skills → sub-skills → scores) mapped to Performance Criteria and Performance
   Evidence. *(Being connected to the Assignment Benchmarks format.)*

Outputs can be created directly in a Moodle course and/or exported as a single `.mbz` backup.

## Compliance model

Every generated item carries an explicit mapping to a unit component (Element / Performance
Criterion / Knowledge Evidence / Performance Evidence / Foundation Skill / Assessment Condition).
The kit embeds the ASQA rules of evidence (valid, sufficient, authentic, current) and principles
of assessment, and treats AI grading as an assist-only pre-assessment — the competency
(Competent / Not Yet Competent) decision is made by a credentialled assessor, never by a score.

## Requirements

- Moodle 4.1 (LTS) or later.
- Optional: `local_rtocompliance` for shared RTO/qualification data.

## Installation

Copy the `rplkit` folder to `moodle/local/rplkit`, then visit **Site administration →
Notifications** to complete the install.

## Capability

- `local/rplkit:manage` — create and manage RPL assessment kits (granted to Manager by default).

## Licence

GNU GPL v3 or later. See the `LICENSE` file.

## Status

Alpha. Outputs #1 (theory quiz) and #2 (SmartForm) are implemented and tested; output #3
(Assignment Benchmarks) and the training.gov.au unit fetch are being connected to the platform.
