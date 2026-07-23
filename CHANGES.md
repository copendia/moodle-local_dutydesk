## Unreleased

### Added

- Added German language pack and converted the English language pack to real English strings.
- Added CSV and XLSX task import templates.
- Added GVPL-style XLSX template with two example subject areas and two tasks each.
- Added PHPUnit tests for position, department, task import, task, and dashboard logic.
- Added GitHub Actions CI for Moodle plugin checks, PHPUnit, Grunt, and linting.
- Added README sections for plugin purpose, features, test execution, AMD builds, import templates, and CI quality gates.

### Changed

- Refactored position logic into Moodle-style classes under `classes/local/position`.
- Refactored department logic into Moodle-style classes under `classes/local/department`.
- Refactored task logic into Moodle-style classes under `classes/local/task`.
- Refactored task import logic into Moodle-style classes under `classes/local/task_import`.
- Refactored dashboard logic into dedicated manager and presenter classes.
- Moved page rendering into output classes.
- Updated task import to ignore empty rows.
- Improved duplicate task detection during import.
- Reused existing department-category assignments during import when available.
- Updated task modals so the create form is rendered in the modal and the page reloads after saving.
- Updated position modals so focus and pagination state are preserved more reliably.
- Updated task descriptions so they can be displayed independently from subtasks.

### Fixed

- Fixed archived positions not being shown correctly.
- Fixed task and position modals not opening after refactoring.
- Fixed full page rendering inside the task modal after saving.
- Fixed missing Moodle form handling required for modal usage.
- Fixed invalid database table name length for PHPUnit installation.
- Fixed PHP syntax, Moodle codechecker, Mustache, and savepoint issues for stricter CI checks.

### Removed

- Removed unused modal helper fallback logic.
- Removed unused or obsolete code paths where no references existed.
