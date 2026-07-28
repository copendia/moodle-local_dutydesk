## DutyDesk

DutyDesk is a Moodle local plugin for managing departments, positions, topic areas, and recurring duties in an
organization. It helps document responsibilities transparently and keeps task assignments visible for staff and
administrators.

### Purpose

The plugin provides a central place to describe who is responsible for which duties, which department owns a topic
area, and how tasks are distributed across positions. This is useful for organizational duty plans, responsibility
matrices, process documentation, and internal service desks.

### Main Features

- Department management with assigned task categories.
- Position and topic-area management with employees, deputies, descriptions, and archive handling.
- Task management with categories, workload percentages, descriptions, subtasks, and assignment history.
- Dashboard views for own department responsibilities, assigned positions, and visible topic areas.
- CSV and XLSX task import with downloadable templates.
- GVPL-style XLSX import template for structured subject areas and tasks.
- Category assignment checks during task import for the selected department.
- Search and filter functions for positions, departments, categories, and tasks.
- Modal-based create and edit forms for positions, departments, and tasks.
- PHPUnit test coverage for position, department, task import, task, and dashboard logic.

### User Benefit

DutyDesk makes responsibilities easier to maintain and easier to understand. Staff can see which positions and topic
areas are relevant to them, administrators can maintain department structures, and recurring duties can be imported or
updated consistently instead of being tracked in disconnected spreadsheets.

### AMD / JavaScript

Build AMD modules from the Moodle root directory after changing files in `amd/src`:

```bash
grunt amd --root=local/dutydesk --force
```

The generated files in `amd/build` are committed so the plugin can be installed without running Grunt on the target server.

### Docker Development Site

Start a fresh Moodle 4.5 site with DutyDesk installed:

```bash
docker compose -f docker-compose.moodle45.yml up --build
```

Open `http://localhost:8085` and log in with:

```text
Username: admin
Password: Admin123!
```

The container uses `MOODLE_405_STABLE`, MariaDB 11 and persistent Docker volumes for the database and Moodle data.
To reset the site completely, stop it and remove the volumes:

```bash
docker compose -f docker-compose.moodle45.yml down -v
```

### Unit Tests

Run the current plugin test suite:

```bash
vendor/bin/phpunit \
  local/dutydesk/tests/position_manager_test.php \
  local/dutydesk/tests/position_visibility_test.php \
  local/dutydesk/tests/department_manager_test.php \
  local/dutydesk/tests/department_repository_presenter_test.php \
  local/dutydesk/tests/task_manager_test.php \
  local/dutydesk/tests/task_import_importer_test.php \
  local/dutydesk/tests/task_import_session_store_test.php \
  local/dutydesk/tests/dashboard_manager_test.php \
  local/dutydesk/tests/dashboard_presenter_test.php
```

### Task Import Templates

The task import page provides downloadable CSV and XLSX templates. The XLSX template follows the GVPL-style layout
with two example subject areas and two tasks per subject area.

Empty rows in import files are ignored. During import, categories are matched against the selected department and
existing department-category assignments are reused.

### CI / Quality Gates

GitHub Actions runs the plugin checks on pull requests and pushes to `main`:

```bash
moodle-plugin-ci phplint
moodle-plugin-ci validate
moodle-plugin-ci savepoints
moodle-plugin-ci codechecker
moodle-plugin-ci mustache
moodle-plugin-ci grunt
moodle-plugin-ci phpunit
```

To prevent updates before successful checks, enable branch protection in GitHub:

1. Go to `Settings > Branches > Branch protection rules`.
2. Add a rule for `main`.
3. Enable `Require a pull request before merging`.
4. Enable `Require status checks to pass before merging`.
5. Select the `DutyDesk CI` checks.
6. Disable direct pushes to `main` for regular contributors.
