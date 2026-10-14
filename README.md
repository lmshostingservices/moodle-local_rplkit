# RPL Kit (local_rplkit)

RPL Kit is a Moodle local plugin that generates Recognition of Prior Learning (RPL) assessment
tools for a unit of competency, mapped to the 2025 Standards for RTOs and the ASQA practice guides.

## What it does

Go to **Site administration → RPL Kit → RPL Kit generator**. With Assignment Benchmarks 1.2.40 or later
installed, type the unit code and click **Fetch from training.gov.au** to fill in the form, then check
it. Otherwise (or to adjust it) enter the unit (code, title, and its
Elements, Performance Criteria, Knowledge Evidence, Performance Evidence and Assessment Conditions,
one item per line, copied from training.gov.au), and click **Generate RPL kit**. The page shows
what was found, warns about gaps, and lets you download each tool, or all three as one ZIP:

1. **Theory evidence** (`<CODE>_1_theory_questions_moodlexml.xml`): one Moodle essay question
   per Knowledge Evidence point, each with a model-answer placeholder (general feedback) and a
   marking guide (information for graders). Import it into a course question bank
   (Import → Moodle XML format) and add the questions to a quiz.
2. **Practical, forms-based evidence** (`<CODE>_2_observation_checklist_smartform.json`): a
   SmartForm document (observation / third-party checklist) with one row per Performance
   Criterion, a Performance Evidence sufficiency check, a third-party report, an authenticity
   declaration and the assessor decision. Paste it into the SmartForm app.
3. **Practical skill demonstration** (`<CODE>_3_skill_demonstration_benchmarks.json`): Assignment
   Benchmarks grading criteria with one group per element, one item per Performance Criterion, and
   a Performance Evidence sufficiency group. It needs the `gradingform_benchmarks` plugin and an
   assignment that uses the Assignment Benchmarks grading method.

The ZIP also contains a README with these instructions.

## Assignment Benchmarks link

When the Assignment Benchmarks grading method (`gradingform_benchmarks`) is installed, the kit page
also shows **Add to an assignment**. Pick a course, then either:

- **A new assignment**: RPL Kit creates "RPL Practical Skill Demonstration — <unit>" in the chosen
  section (hidden from students by default, file and online-text submissions on), sets Assignment
  Benchmarks as its grading method and loads the criteria; or
- **An existing assignment**: RPL Kit sets Assignment Benchmarks as its grading method and loads the
  criteria. Existing Benchmarks criteria are only replaced when you tick **Replace existing
  criteria**, and never once a student has been graded with them.

The criteria are saved through gradingform_benchmarks' own controller, exactly as its editor would
save them, and you land on the assignment's Advanced grading page to review them. Requires
`moodle/course:manageactivities` in the course (new assignment) or
`moodle/grade:managegradingforms` on the assignment (existing one).

A **Load example** link fills the form with sample data to show the input format. The sample is
illustrative only and is not an authoritative copy of any unit.

## Input format

| Field | One per line | Example |
|---|---|---|
| Elements | numbered | `1 Work safely` |
| Performance Criteria | starting with the PC code | `1.1 Identify and follow workplace procedures` |
| Knowledge Evidence | point text; optional own ref | `Hazard identification` or `KE4: Emergency procedures` |
| Performance Evidence | requirement text | `Contributed to safety on at least two occasions` |

Leading bullets (`-`, `*`, `•`) are removed. The number before the first dot of a PC code
(`2` in `2.3`) sets its element group.

The builders can also be called directly with a unit array; see `classes/unit.php` for the format.

## Compliance model

Every generated item maps to a unit component (Element / Performance Criterion / Knowledge
Evidence / Performance Evidence / Assessment Condition). The tools embed the ASQA rules of
evidence (valid, sufficient, authentic, current) and the principles of assessment, and treat any
AI grade as an assist-only pre-assessment: the Competent / Not Yet Competent decision is made by a
credentialled assessor, never by a score.

## What it does not do (yet)

- It fetches units from training.gov.au only through Assignment Benchmarks (1.2.40+), and only when
  Benchmarks can reach training.gov.au (see its settings page). You can always paste the unit in.
- It does not create quizzes in a course or produce `.mbz` backups; import the theory XML into a
  question bank yourself. (Assignments are created only through the Assignment Benchmarks link.)
- It does not yet read data from `local_rtocompliance`.

## Requirements

- Moodle 4.1 or later. Tested on Moodle 4.4, 4.5, 5.0, 5.1, 5.2 and 5.3 (PHP 8.3, PostgreSQL).
- Optional: `gradingform_benchmarks` (Assignment Benchmarks 1.2.40) for the Add to an assignment link and
  Fetch from training.gov.au. Both follow Benchmarks' licence check.

## Installation

Upload the ZIP at **Site administration → Plugins → Install plugins**, or extract it so the plugin
is at `local/rplkit` (`public/local/rplkit` on Moodle 5.1 and later), then visit
**Site administration → Notifications**.

## Capability

- `local/rplkit:manage`: use the RPL Kit generator (granted to Manager by default).

## Privacy

RPL Kit stores no personal data. The unit being worked on is kept in the user's session cache
only, so the outputs can be downloaded separately.

## Tests

PHPUnit tests are in `tests/`, including a test that imports the generated theory XML into a real
question bank.

## Licence

GNU GPL v3 or later. See the `LICENSE` file.
