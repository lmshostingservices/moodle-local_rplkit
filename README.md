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
   grading criteria (groups → items → scores) mapped to Performance Criteria (one item per
   criterion, grouped by element) and Performance Evidence (sufficiency requirements), ready to
   import into a course assignment graded with the Assignment Benchmarks plugin.

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

Beta. All three ASQA evidence outputs are implemented:

- **Output #1** (theory quiz XML) — implemented and tested.
- **Output #2** (SmartForm JSON) — implemented and tested.
- **Output #3** (Assignment Benchmarks criteria JSON) — implemented; requires
  `gradingform_benchmarks` to be installed and a course assignment configured with the
  Benchmarks advanced-grading method to use the generated criteria.

The training.gov.au unit-fetch integration is still being connected to the platform; unit data
can be supplied directly in the meantime.
