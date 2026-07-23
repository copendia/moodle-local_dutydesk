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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * DutyDesk local plugin.
 *
 * @package    local_dutydesk
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'DutyDesk';
$string['departments'] = 'Departments';
$string['department'] = 'Department';
$string['nodepartment'] = 'No department';
$string['invaliddepartment'] = 'The selected department does not exist.';
$string['positions'] = 'Positions and subject areas';
$string['positions_active'] = 'Active positions';
$string['positions_archived'] = 'Archived positions';
$string['archived'] = 'Archived';
$string['archiveddate'] = 'Archived on';
$string['newpositionbutton'] = 'New position';
$string['newtopicareabutton'] = 'New subject area';
$string['position'] = 'Position';
$string['positiontype_position'] = 'Position';
$string['positiontype_topicarea'] = 'Subject area';
$string['assignedpositions'] = 'My department';
$string['assignedpositionsheading'] = 'Positions in the department';
$string['mypositions_role_primary'] = 'Responsible';
$string['mypositions_role_deputy'] = 'Deputy';
$string['mypositions_role_manager'] = 'Department lead';
$string['noassignedpositions'] = 'You currently have no assigned positions.';
$string['nodepartmentlabel'] = 'No department';
$string['mydepartment'] = 'My department';
$string['departmentmanagers'] = 'Department leads';
$string['nodepartmentmanagers'] = 'No department leads assigned.';
$string['departmentcategories'] = 'Categories';
$string['departmentcategoriesnone'] = 'No categories assigned.';
$string['departmentcategoriesplaceholder'] = 'Select categories...';
$string['departmentcategoriesnoneavailable'] = 'No available categories.';
$string['dutydesk:viewown'] = 'View own DutyDesk tasks';
$string['dutydesk:manageown'] = 'Manage own DutyDesk tasks';
$string['dutydesk:viewall'] = 'View all DutyDesk tasks';
$string['dutydesk:manageall'] = 'Manage DutyDesk tasks';
$string['dutydesk:managepositions'] = 'Manage DutyDesk positions';
$string['dutydesk:viewmydepartment'] = 'View my department';
$string['tasks'] = 'Tasks';
$string['departmentactions'] = 'Department actions';
$string['add'] = 'Add';
$string['edit'] = 'Edit';
$string['delete'] = 'Delete';
$string['confirmdelete'] = 'Are you sure you want to delete this entry?';
$string['name'] = 'Name';
$string['description'] = 'Description';
$string['timestamp'] = 'Timestamp';
$string['saved'] = 'Entry saved.';
$string['deleted'] = 'Entry deleted.';
$string['updated'] = 'Entry updated.';
$string['usersearch'] = 'Employee';
$string['usersearchplaceholder'] = 'Enter name, email address or username...';
$string['usersearchrequired'] = 'Please select an employee from the list.';
$string['usersearchnoselection'] = 'No selection';
$string['usersearchnoresults'] = 'No matching employees found.';
$string['usernotfound'] = 'The selected employee could not be found.';
$string['assignuser_success'] = 'User assigned successfully.';
$string['assignuser_errordefault'] = 'An error occurred. Please try again.';
$string['assignuser'] = 'Assign user';
$string['positionrequired'] = 'Please select a position.';
$string['archiveposition'] = 'Archive';
$string['restoreposition'] = 'Restore';
$string['positiondeleterequiresarchive'] = 'Positions can only be deleted after they have been archived.';
$string['nopositions'] = 'No positions available.';
$string['notassigned'] = 'Not assigned';
$string['taskhistory'] = 'History';
$string['newdepartmentbutton'] = 'New department';
$string['newtaskbutton'] = 'New task';
$string['taskimport'] = 'Import tasks';
$string['taskimportbutton'] = 'Import tasks';
$string['taskimportfile'] = 'Import file';
$string['taskimporttemplates'] = 'Templates';
$string['taskimporttemplatecsv'] = 'Download CSV template';
$string['taskimporttemplatexlsx'] = 'Download Excel template';
$string['taskimportcheck'] = 'Check import';
$string['taskimportconfirm'] = 'Complete import';
$string['taskimportpreview'] = 'Check import';
$string['taskimportsummary'] = '{$a} tasks were found in the file.';
$string['taskimportsummarydepartment'] = 'Department: {$a}';
$string['taskimportmanagedepartments'] = 'Manage departments';
$string['taskimportnodepartments'] = 'No departments have been created yet.';
$string['taskimportwarningsintro'] = 'Similar tasks were found. Please review the matches before importing.';
$string['taskimportwarningitem'] = 'Row {$a->row}: "{$a->title}" is similar to "{$a->match}"';
$string['taskimportwarningsmore'] = 'And {$a} more possible matches.';
$string['taskimportnowarnings'] = 'No similar tasks found.';
$string['taskimportsuccess'] = '{$a->tasks} tasks imported. {$a->categories} categories created.';
$string['taskimportmissingcolumns'] = 'The file must contain columns for subject area and description.';
$string['taskimportempty'] = 'No importable tasks were found in the file.';
$string['taskimportnofile'] = 'No import file was found.';
$string['taskimportinvalidfiletype'] = 'Please upload an Excel or CSV file.';
$string['taskhistorybutton'] = 'Activities';
$string['taskbacktoposition'] = 'To position';
$string['taskhistorymodalheading'] = 'Activities - {$a}';
$string['taskhistoryempty'] = 'There are no activities for this task yet.';
$string['taskhistory_action_created'] = 'Task created';
$string['taskhistory_action_updated'] = 'Content updated';
$string['taskhistory_action_documents'] = 'Documents changed';
$string['taskhistory_action_deleted'] = 'Task deleted';
$string['taskhistory_action_subtask_created'] = 'Subtask created';
$string['taskhistory_action_subtask_updated'] = 'Subtask updated';
$string['taskhistory_action_subtask_deleted'] = 'Subtask deleted';
$string['taskhistory_action_assignment'] = 'Assignment updated ({$a})';
$string['taskhistory_systemuser'] = 'System';
$string['taskhistory_detail_title'] = 'Title changed: "{$a->old}" -> "{$a->new}"';
$string['taskhistory_detail_description'] = 'Description updated.';
$string['taskhistory_detail_subtask_title'] = 'Subtask: title changed from "{$a->old}" to "{$a->new}"';
$string['taskhistory_detail_subtask_description'] = 'Subtask: description updated.';
$string['taskhistory_detail_subtask_reference'] = 'Subtask: {$a}';
$string['taskhistory_detail_documents_added'] = 'Added: {$a}';
$string['taskhistory_detail_documents_removed'] = 'Removed: {$a}';
$string['taskhistory_detail_assignment_set'] = 'Assigned to "{$a}"';
$string['taskhistory_detail_assignment_changed'] = 'Assignment changed from "{$a->old}" to "{$a->new}"';
$string['taskhistory_detail_assignment_removed'] = 'Assignment removed (previously "{$a}")';
$string['taskhistory_records'] = 'Activities';
$string['taskcategoryfilter'] = 'Category';
$string['taskcategoryfilter_all'] = 'All categories';
$string['taskdepartmentfilter'] = 'Department';
$string['taskdepartmentfilter_all'] = 'All departments';
$string['assignedpositionsmoretasks'] = '{$a} more tasks';
$string['assignedpositionsmanagedheading'] = 'Additional positions in the department';
$string['newtask'] = 'Create new task';
$string['subtasks'] = 'Subtasks';
$string['newsubtask'] = 'Add subtask';
$string['nosubtasks'] = 'No subtasks available.';
$string['nodepartmentpositions'] = 'No positions assigned.';
$string['nosubtaskdescription'] = 'No additional description is available for this subtask.';
$string['nodescription'] = 'No description provided.';
$string['subtasktitle'] = 'Subtask title';
$string['subtaskdescription'] = 'Subtask description';
$string['subtaskdocuments'] = 'Subtask documents';
$string['expandallsubtasks'] = '';
$string['collapseallsubtasks'] = 'Collapse all';
$string['subtaskadded'] = 'Subtask saved.';
$string['subtaskupdated'] = 'Subtask updated.';
$string['subtaskdeleted'] = 'Subtask deleted.';
$string['notasks'] = 'No tasks available.';
$string['nodepartments'] = 'No departments available.';
$string['reordersubtasks'] = 'Sort subtasks';
$string['searchtasks'] = 'Search tasks';
$string['searchtasksplaceholder'] = 'Search by task, position, department or employee ...';
$string['searchpositions'] = 'Search positions';
$string['searchpositionsplaceholder'] = 'Search by position, subject area, department or employee ...';
$string['showallpositions'] = 'Show all positions';
$string['showownpositions'] = 'Show only my positions';
$string['showtopicareasonly'] = 'Subject areas only';
$string['nosearchresultspositions'] = 'No positions match the search.';
$string['nosearchresults'] = 'No tasks match the search.';
$string['taskdocuments'] = 'Documents';
$string['taskworkloadpercent'] = 'Workload (%)';
$string['taskworkloadpercent_help'] = 'Specifies the percentage of position capacity used by this task (0-100).';
$string['taskworkloadpercentinvalid'] = 'Please enter a value between 0 and 100.';
$string['taskworkloadnotset'] = 'Not specified';
$string['viewfulldescription'] = 'Show full description';
$string['closedescription'] = 'Close description';
$string['positionprimaryuser'] = 'Employee';
$string['positiondeputyuser'] = 'Deputy';
$string['positionworkloadtotal'] = 'Total workload';
$string['positiontasks'] = 'Tasks';
$string['positiontasksplaceholder'] = 'Select tasks...';
$string['positiontasksnoselection'] = 'No tasks selected';
$string['positiontasksassignedlabel'] = '(assigned)';
$string['positionvacantlabel'] = 'Position is vacant';
$string['positionvacanthelp'] = 'Marks the position as currently vacant.';
$string['positionvacantbadge'] = 'Vacant';
$string['nodeputy'] = 'No deputy assigned.';
$string['returntotask'] = 'Back to task';
$string['taskvacantbadge'] = 'Vacant task';
$string['taskvacantfilter'] = 'Vacant tasks only';
$string['expandalltasks'] = '';
$string['collapsealltasks'] = 'Hide tasks';
$string['learningcontent'] = 'Learning content';
