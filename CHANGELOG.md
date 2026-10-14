# Changelog - RPL Kit

## [v0.6.1] - 2026-10-14

### Changed
- Release-pipeline fixes: the unit form's text fields use PARAM_TEXT (was PARAM_RAW_TRIMMED); the
  fetch button check uses PARAM_TEXT; tests no longer write to $_POST; the change log is CHANGELOG.md.

## [v0.6.0] - 2026-10-14

- New: **Fetch from training.gov.au** on the generator. Type the unit code, click the button, and the
  form is filled in (title, elements, performance criteria with codes, knowledge and performance
  evidence, assessment conditions) for you to check before generating. Uses Assignment Benchmarks'
  training.gov.au connection (Benchmarks 1.2.40 or later), so credentials and caching are set once there.
- New: Site administration -> RPL Kit links to the Assignment Benchmarks settings page.
- Changed: RPL Kit applies Assignment Benchmarks' own licence check. While Benchmarks is locked it does
  not add criteria to assignments or use its training.gov.au connection (downloads still work).
- PHPUnit: 5 new tests (26 in total).

## [v0.5.0] - 2026-10-14

- New: Assignment Benchmarks link. From the kit page, add the practical skill demonstration
  criteria to a new assignment (created hidden, with Benchmarks as its grading method) or to an
  existing one, through gradingform_benchmarks' own controller. Existing criteria are replaced
  only on request and never once students have been graded with them.
- New: `\local_rplkit\benchmarks` API (is_available, get_assignments, get_status,
  apply_to_assignment, create_assignment).
- PHPUnit: 4 new tests, including grading a student with the generated criteria and checking the
  gradebook (22 tests in total).
- Tested with gradingform_benchmarks 1.2.39 on Moodle 4.4, 4.5, 5.0, 5.1, 5.2 and 5.3.

## [v0.4.0] - 2026-10-14

- New: working generator page. Enter a unit (one item per line), see what was found and any gaps,
  then download each tool or all three as a ZIP (with a README). Includes edit, start-new and a
  "Load example" link showing the input format.
- New: `\local_rplkit\unit` normalises unit data so all three builders accept the same input
  (strings or code/ref + text arrays); `\local_rplkit\kit` assembles file names and the ZIP.
- Fix: SmartForm output printed "Array" (and a PHP warning) for Performance Evidence given as
  ref/text pairs.
- Fix: SmartForm labels and options were HTML-encoded (`&amp;`) while matrix rows were not; all
  labels/options are now plain text and only rich paragraph content is HTML-escaped.
- Fix: SmartForm no longer emits empty rows for blank Performance Criteria.
- Fix: theory XML with only blank Knowledge Evidence now contains the placeholder question.
- Fix: Benchmarks definitions now fit 255 bytes as well as 255 characters.
- Fix: automatic KE/PE references no longer duplicate references entered explicitly.
- Fix: the release ZIP's top folder is `rplkit`, so it installs from Site administration ->
  Install plugins (previously rejected by Moodle's validator).
- README corrected: removed claims of course creation, .mbz export and local_rtocompliance
  integration, which are not implemented.
- PHPUnit tests added (18 tests), including a real import into a question bank.
- Coding style: clean against moodle-extra (moodle-cs).
- Tested on Moodle 4.4, 4.5, 5.0, 5.1, 5.2 and 5.3.
