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

$string['add'] = 'Add';
$string['archived'] = 'Archived';
$string['archiveddate'] = 'Archived on';
$string['archiveposition'] = 'Archive';
$string['assignedpositions'] = 'My department';
$string['assignedpositionsheading'] = 'Positions in the department';
$string['assignedpositionsmanagedheading'] = 'Additional positions in the department';
$string['assignedpositionsmoretasks'] = '{$a} more tasks';
$string['assignuser'] = 'Assign user';
$string['assignuser_errordefault'] = 'An error occurred. Please try again.';
$string['assignuser_success'] = 'User assigned successfully.';
$string['closedescription'] = 'Close description';
$string['collapseallsubtasks'] = 'Collapse all';
$string['collapsealltasks'] = 'Hide tasks';
$string['confirmdelete'] = 'Are you sure you want to delete this entry?';
$string['delete'] = 'Delete';
$string['deleted'] = 'Entry deleted.';
$string['department'] = 'Department';
$string['departmentactions'] = 'Department actions';
$string['departmentcategories'] = 'Categories';
$string['departmentcategoriesnone'] = 'No categories assigned.';
$string['departmentcategoriesnoneavailable'] = 'No available categories.';
$string['departmentcategoriesplaceholder'] = 'Select categories...';
$string['departmentmanagers'] = 'Department leads';
$string['departments'] = 'Departments';
$string['description'] = 'Description';
$string['dutydesk:manageall'] = 'Manage DutyDesk tasks';
$string['dutydesk:manageown'] = 'Manage own DutyDesk tasks';
$string['dutydesk:managepositions'] = 'Manage DutyDesk positions';
$string['dutydesk:viewall'] = 'View all DutyDesk tasks';
$string['dutydesk:viewmydepartment'] = 'View my department';
$string['dutydesk:viewown'] = 'View own DutyDesk tasks';
$string['edit'] = 'Edit';
$string['expandallsubtasks'] = '';
$string['expandalltasks'] = '';
$string['invaliddepartment'] = 'The selected department does not exist.';
$string['learningcontent'] = 'Learning content';
$string['mydepartment'] = 'My department';
$string['mypositions_role_deputy'] = 'Deputy';
$string['mypositions_role_manager'] = 'Department lead';
$string['mypositions_role_primary'] = 'Responsible';
$string['name'] = 'Name';
$string['newdepartmentbutton'] = 'New department';
$string['newpositionbutton'] = 'New position';
$string['newsubtask'] = 'Add subtask';
$string['newtask'] = 'Create new task';
$string['newtaskbutton'] = 'New task';
$string['newtopicareabutton'] = 'New subject area';
$string['noassignedpositions'] = 'You currently have no assigned positions.';
$string['nodepartment'] = 'No department';
$string['nodepartmentlabel'] = 'No department';
$string['nodepartmentmanagers'] = 'No department leads assigned.';
$string['nodepartmentpositions'] = 'No positions assigned.';
$string['nodepartments'] = 'No departments available.';
$string['nodeputy'] = 'No deputy assigned.';
$string['nodescription'] = 'No description provided.';
$string['nopositions'] = 'No positions available.';
$string['nosearchresults'] = 'No tasks match the search.';
$string['nosearchresultspositions'] = 'No positions match the search.';
$string['nosubtaskdescription'] = 'No additional description is available for this subtask.';
$string['nosubtasks'] = 'No subtasks available.';
$string['notasks'] = 'No tasks available.';
$string['notassigned'] = 'Not assigned';
$string['pluginname'] = 'DutyDesk';
$string['position'] = 'Position';
$string['positiondeleterequiresarchive'] = 'Positions can only be deleted after they have been archived.';
$string['positiondeputyuser'] = 'Deputy';
$string['positionprimaryuser'] = 'Employee';
$string['positionrequired'] = 'Please select a position.';
$string['positions'] = 'Positions and subject areas';
$string['positions_active'] = 'Active positions';
$string['positions_archived'] = 'Archived positions';
$string['positiontasks'] = 'Tasks';
$string['positiontasksassignedlabel'] = '(assigned)';
$string['positiontasksnoselection'] = 'No tasks selected';
$string['positiontasksplaceholder'] = 'Select tasks...';
$string['positiontype_position'] = 'Position';
$string['positiontype_topicarea'] = 'Subject area';
$string['positionvacantbadge'] = 'Vacant';
$string['positionvacanthelp'] = 'Marks the position as currently vacant.';
$string['positionvacantlabel'] = 'Position is vacant';
$string['positionworkloadtotal'] = 'Total workload';
$string['privacy:metadata:local_dutydesk_comment'] = 'Stores comments created by users.';
$string['privacy:metadata:local_dutydesk_comment:content'] = 'The comment text entered by the user.';
$string['privacy:metadata:local_dutydesk_comment:created'] = 'The time when the comment was created.';
$string['privacy:metadata:local_dutydesk_comment:userid'] = 'The user who created the comment.';
$string['privacy:metadata:local_dutydesk_deptmgr'] = 'Stores department lead assignments.';
$string['privacy:metadata:local_dutydesk_deptmgr:assignedby'] = 'The user who assigned the department lead.';
$string['privacy:metadata:local_dutydesk_deptmgr:timecreated'] = 'The time when the assignment was created.';
$string['privacy:metadata:local_dutydesk_deptmgr:userid'] = 'The assigned department lead.';
$string['privacy:metadata:local_dutydesk_import'] = 'Stores task import metadata.';
$string['privacy:metadata:local_dutydesk_import:created'] = 'The time when the import was created.';
$string['privacy:metadata:local_dutydesk_import:filename'] = 'The imported file name.';
$string['privacy:metadata:local_dutydesk_import:importedby'] = 'The user who performed the import.';
$string['privacy:metadata:local_dutydesk_posdeputy'] = 'Stores deputy assignments for positions.';
$string['privacy:metadata:local_dutydesk_posdeputy:assignedby'] = 'The user who assigned the deputy.';
$string['privacy:metadata:local_dutydesk_posdeputy:timecreated'] = 'The time when the deputy assignment was created.';
$string['privacy:metadata:local_dutydesk_posdeputy:userid'] = 'The assigned deputy user.';
$string['privacy:metadata:local_dutydesk_position'] = 'Stores positions and their assigned primary users.';
$string['privacy:metadata:local_dutydesk_position:archivedtime'] = 'The time when the position was archived.';
$string['privacy:metadata:local_dutydesk_position:primaryuserid'] = 'The primary user assigned to the position.';
$string['privacy:metadata:local_dutydesk_taskassign'] = 'Stores task assignment metadata.';
$string['privacy:metadata:local_dutydesk_taskassign:assignedby'] = 'The user who assigned the task.';
$string['privacy:metadata:local_dutydesk_taskassign:timestamp'] = 'The time when the task assignment was updated.';
$string['privacy:metadata:local_dutydesk_taskhist'] = 'Stores task activity history.';
$string['privacy:metadata:local_dutydesk_taskhist:action'] = 'The task history action.';
$string['privacy:metadata:local_dutydesk_taskhist:details'] = 'Details about the task history action.';
$string['privacy:metadata:local_dutydesk_taskhist:timecreated'] = 'The time when the history entry was created.';
$string['privacy:metadata:local_dutydesk_taskhist:userid'] = 'The user related to the task history entry.';
$string['privacy:metadata:local_dutydesk_userinfo'] = 'Stores additional DutyDesk user assignment information.';
$string['privacy:metadata:local_dutydesk_userinfo:dutydeskrole'] = 'The DutyDesk role assigned to the user.';
$string['privacy:metadata:local_dutydesk_userinfo:userid'] = 'The Moodle user associated with the DutyDesk information.';
$string['reordersubtasks'] = 'Sort subtasks';
$string['restoreposition'] = 'Restore';
$string['returntotask'] = 'Back to task';
$string['saved'] = 'Entry saved.';
$string['searchpositions'] = 'Search positions';
$string['searchpositionsplaceholder'] = 'Search by position, subject area, department or employee ...';
$string['searchtasks'] = 'Search tasks';
$string['searchtasksplaceholder'] = 'Search by task, position, department or employee ...';
$string['showallpositions'] = 'Show all positions';
$string['showownpositions'] = 'Show only my positions';
$string['showtopicareasonly'] = 'Subject areas only';
$string['subtaskadded'] = 'Subtask saved.';
$string['subtaskdeleted'] = 'Subtask deleted.';
$string['subtaskdescription'] = 'Subtask description';
$string['subtaskdocuments'] = 'Subtask documents';
$string['subtasks'] = 'Subtasks';
$string['subtasktitle'] = 'Subtask title';
$string['subtaskupdated'] = 'Subtask updated.';
$string['taskbacktoposition'] = 'To position';
$string['taskcategoryfilter'] = 'Category';
$string['taskcategoryfilter_all'] = 'All categories';
$string['taskdepartmentfilter'] = 'Department';
$string['taskdepartmentfilter_all'] = 'All departments';
$string['taskdocuments'] = 'Documents';
$string['taskhistory'] = 'History';
$string['taskhistory_action_assignment'] = 'Assignment updated ({$a})';
$string['taskhistory_action_created'] = 'Task created';
$string['taskhistory_action_deleted'] = 'Task deleted';
$string['taskhistory_action_documents'] = 'Documents changed';
$string['taskhistory_action_subtask_created'] = 'Subtask created';
$string['taskhistory_action_subtask_deleted'] = 'Subtask deleted';
$string['taskhistory_action_subtask_updated'] = 'Subtask updated';
$string['taskhistory_action_updated'] = 'Content updated';
$string['taskhistory_detail_assignment_changed'] = 'Assignment changed from "{$a->old}" to "{$a->new}"';
$string['taskhistory_detail_assignment_removed'] = 'Assignment removed (previously "{$a}")';
$string['taskhistory_detail_assignment_set'] = 'Assigned to "{$a}"';
$string['taskhistory_detail_description'] = 'Description updated.';
$string['taskhistory_detail_documents_added'] = 'Added: {$a}';
$string['taskhistory_detail_documents_removed'] = 'Removed: {$a}';
$string['taskhistory_detail_subtask_description'] = 'Subtask: description updated.';
$string['taskhistory_detail_subtask_reference'] = 'Subtask: {$a}';
$string['taskhistory_detail_subtask_title'] = 'Subtask: title changed from "{$a->old}" to "{$a->new}"';
$string['taskhistory_detail_title'] = 'Title changed: "{$a->old}" -> "{$a->new}"';
$string['taskhistory_records'] = 'Activities';
$string['taskhistory_systemuser'] = 'System';
$string['taskhistorybutton'] = 'Activities';
$string['taskhistoryempty'] = 'There are no activities for this task yet.';
$string['taskhistorymodalheading'] = 'Activities - {$a}';
$string['taskimport'] = 'Import tasks';
$string['taskimportbutton'] = 'Import tasks';
$string['taskimportcheck'] = 'Check import';
$string['taskimportconfirm'] = 'Complete import';
$string['taskimportempty'] = 'No importable tasks were found in the file.';
$string['taskimportfile'] = 'Import file';
$string['taskimportinvalidfiletype'] = 'Please upload an Excel or CSV file.';
$string['taskimportmanagedepartments'] = 'Manage departments';
$string['taskimportmissingcolumns'] = 'The file must contain columns for subject area and description.';
$string['taskimportnodepartments'] = 'No departments have been created yet.';
$string['taskimportnofile'] = 'No import file was found.';
$string['taskimportnowarnings'] = 'No similar tasks found.';
$string['taskimportpreview'] = 'Check import';
$string['taskimportsuccess'] = '{$a->tasks} tasks imported. {$a->categories} categories created.';
$string['taskimportsummary'] = '{$a} tasks were found in the file.';
$string['taskimportsummarydepartment'] = 'Department: {$a}';
$string['taskimporttemplatecsv'] = 'Download CSV template';
$string['taskimporttemplates'] = 'Templates';
$string['taskimporttemplatexlsx'] = 'Download Excel template';
$string['taskimportwarningitem'] = 'Row {$a->row}: "{$a->title}" is similar to "{$a->match}"';
$string['taskimportwarningsintro'] = 'Similar tasks were found. Please review the matches before importing.';
$string['taskimportwarningsmore'] = 'And {$a} more possible matches.';
$string['tasks'] = 'Tasks';
$string['taskvacantbadge'] = 'Vacant task';
$string['taskvacantfilter'] = 'Vacant tasks only';
$string['taskworkloadnotset'] = 'Not specified';
$string['taskworkloadpercent'] = 'Workload (%)';
$string['taskworkloadpercent_help'] = 'Specifies the percentage of position capacity used by this task (0-100).';
$string['taskworkloadpercentinvalid'] = 'Please enter a value between 0 and 100.';
$string['timestamp'] = 'Timestamp';
$string['updated'] = 'Entry updated.';
$string['usernotfound'] = 'The selected employee could not be found.';
$string['usersearch'] = 'Employee';
$string['usersearchnoresults'] = 'No matching employees found.';
$string['usersearchnoselection'] = 'No selection';
$string['usersearchplaceholder'] = 'Enter name, email address or username...';
$string['usersearchrequired'] = 'Please select an employee from the list.';
$string['viewfulldescription'] = 'Show full description';
