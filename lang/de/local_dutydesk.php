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

$string['add'] = 'Hinzufügen';
$string['archived'] = 'Archiviert';
$string['archiveddate'] = 'Archiviert am';
$string['archiveposition'] = 'Archivieren';
$string['assignedpositions'] = 'Mein Dezernat';
$string['assignedpositionsheading'] = 'Stellen im Dezernat';
$string['assignedpositionsmanagedheading'] = 'Weitere Stellen im Dezernat';
$string['assignedpositionsmoretasks'] = '{$a} weitere Aufgaben';
$string['assignuser'] = 'Benutzer zuweisen';
$string['assignuser_errordefault'] = 'Es ist ein Fehler aufgetreten. Bitte versuchen Sie es erneut.';
$string['assignuser_success'] = 'Benutzer erfolgreich zugewiesen.';
$string['closedescription'] = 'Beschreibung schließen';
$string['collapseallsubtasks'] = 'Alles einklappen';
$string['collapsealltasks'] = 'Aufgaben ausblenden';
$string['confirmdelete'] = 'Eintrag wirklich löschen?';
$string['delete'] = 'Löschen';
$string['deleted'] = 'Eintrag gelöscht.';
$string['department'] = 'Dezernat';
$string['departmentactions'] = 'Aktionen für das Dezernat';
$string['departmentcategories'] = 'Kategorien';
$string['departmentcategoriesnone'] = 'Keine Kategorien zugewiesen.';
$string['departmentcategoriesnoneavailable'] = 'Keine freien Kategorien verfügbar.';
$string['departmentcategoriesplaceholder'] = 'Kategorien auswählen...';
$string['departmentmanagers'] = 'Dezernatsleitung';
$string['departments'] = 'Dezernate';
$string['description'] = 'Beschreibung';
$string['dutydesk:manageall'] = 'DutyDesk-Aufgaben verwalten';
$string['dutydesk:manageown'] = 'Eigene DutyDesk-Aufgaben bearbeiten';
$string['dutydesk:managepositions'] = 'DutyDesk-Stellen verwalten';
$string['dutydesk:viewall'] = 'Alle DutyDesk-Aufgaben anzeigen';
$string['dutydesk:viewmydepartment'] = 'Mein Dezernat anzeigen';
$string['dutydesk:viewown'] = 'Eigene DutyDesk-Aufgaben anzeigen';
$string['edit'] = 'Bearbeiten';
$string['expandallsubtasks'] = '';
$string['expandalltasks'] = '';
$string['invaliddepartment'] = 'Das ausgewählte Dezernat ist nicht vorhanden.';
$string['learningcontent'] = 'Lerninhalte';
$string['mydepartment'] = 'Mein Dezernat';
$string['mypositions_role_deputy'] = 'Vertretung';
$string['mypositions_role_manager'] = 'Dezernatsleitung';
$string['mypositions_role_primary'] = 'Verantwortlich';
$string['name'] = 'Name';
$string['newdepartmentbutton'] = 'Neues Dezernat';
$string['newpositionbutton'] = 'Neue Stelle';
$string['newsubtask'] = 'Unteraufgabe hinzufügen';
$string['newtask'] = 'Neue Aufgabe anlegen';
$string['newtaskbutton'] = 'Neue Aufgabe';
$string['newtopicareabutton'] = 'Neuer Themenbereich';
$string['noassignedpositions'] = 'Ihnen sind derzeit keine Stellen zugewiesen.';
$string['nodepartment'] = 'Ohne Dezernat';
$string['nodepartmentlabel'] = 'Ohne Dezernat';
$string['nodepartmentmanagers'] = 'Keine Dezernatsleitung zugewiesen.';
$string['nodepartmentpositions'] = 'Keine Stellen zugeordnet.';
$string['nodepartments'] = 'Keine Dezernate vorhanden.';
$string['nodeputy'] = 'Keine Vertretung hinterlegt.';
$string['nodescription'] = 'Keine Beschreibung hinterlegt.';
$string['nopositions'] = 'Keine Stellen vorhanden.';
$string['nosearchresults'] = 'Keine Aufgaben entsprechen der Suche.';
$string['nosearchresultspositions'] = 'Keine Stellen entsprechen der Suche.';
$string['nosubtaskdescription'] = 'Für diese Unteraufgabe liegt keine weitere Beschreibung vor.';
$string['nosubtasks'] = 'Keine Unteraufgaben vorhanden.';
$string['notasks'] = 'Keine Aufgaben vorhanden.';
$string['notassigned'] = 'Nicht zugewiesen';
$string['pluginname'] = 'DutyDesk';
$string['position'] = 'Stelle';
$string['positiondeleterequiresarchive'] = 'Stellen können erst gelöscht werden, nachdem sie archiviert wurden.';
$string['positiondeputyuser'] = 'Vertretung';
$string['positionprimaryuser'] = 'Mitarbeiter';
$string['positionrequired'] = 'Bitte wählen Sie eine Stelle aus.';
$string['positions'] = 'Stellen und Themenbereiche';
$string['positions_active'] = 'Aktive Stellen';
$string['positions_archived'] = 'Archivierte Stellen';
$string['positiontasks'] = 'Aufgaben';
$string['positiontasksassignedlabel'] = '(zugeordnet)';
$string['positiontasksnoselection'] = 'Keine Aufgaben ausgewählt';
$string['positiontasksplaceholder'] = 'Aufgaben auswählen...';
$string['positiontype_position'] = 'Stelle';
$string['positiontype_topicarea'] = 'Themenbereich';
$string['positionvacantbadge'] = 'Unbesetzt';
$string['positionvacanthelp'] = 'Markiert die Stelle als aktuell nicht besetzt.';
$string['positionvacantlabel'] = 'Stelle ist unbesetzt';
$string['positionworkloadtotal'] = 'Gesamtauslastung';
$string['privacy:metadata:local_dutydesk_comment'] = 'Speichert von Nutzern erstellte Kommentare.';
$string['privacy:metadata:local_dutydesk_comment:content'] = 'Der vom Nutzer eingegebene Kommentartext.';
$string['privacy:metadata:local_dutydesk_comment:created'] = 'Der Zeitpunkt, zu dem der Kommentar erstellt wurde.';
$string['privacy:metadata:local_dutydesk_comment:userid'] = 'Der Nutzer, der den Kommentar erstellt hat.';
$string['privacy:metadata:local_dutydesk_deptmgr'] = 'Speichert Zuordnungen von Dezernatsleitungen.';
$string['privacy:metadata:local_dutydesk_deptmgr:assignedby'] = 'Der Nutzer, der die Dezernatsleitung zugeordnet hat.';
$string['privacy:metadata:local_dutydesk_deptmgr:timecreated'] = 'Der Zeitpunkt, zu dem die Zuordnung erstellt wurde.';
$string['privacy:metadata:local_dutydesk_deptmgr:userid'] = 'Die zugeordnete Dezernatsleitung.';
$string['privacy:metadata:local_dutydesk_import'] = 'Speichert Metadaten zu Aufgabenimporten.';
$string['privacy:metadata:local_dutydesk_import:created'] = 'Der Zeitpunkt, zu dem der Import erstellt wurde.';
$string['privacy:metadata:local_dutydesk_import:filename'] = 'Der importierte Dateiname.';
$string['privacy:metadata:local_dutydesk_import:importedby'] = 'Der Nutzer, der den Import durchgeführt hat.';
$string['privacy:metadata:local_dutydesk_posdeputy'] = 'Speichert Vertretungszuordnungen für Stellen.';
$string['privacy:metadata:local_dutydesk_posdeputy:assignedby'] = 'Der Nutzer, der die Vertretung zugeordnet hat.';
$string['privacy:metadata:local_dutydesk_posdeputy:timecreated'] = 'Der Zeitpunkt, zu dem die Vertretung erstellt wurde.';
$string['privacy:metadata:local_dutydesk_posdeputy:userid'] = 'Der als Vertretung zugeordnete Nutzer.';
$string['privacy:metadata:local_dutydesk_position'] = 'Speichert Stellen und deren zugeordnete Hauptnutzer.';
$string['privacy:metadata:local_dutydesk_position:archivedtime'] = 'Der Zeitpunkt, zu dem die Stelle archiviert wurde.';
$string['privacy:metadata:local_dutydesk_position:primaryuserid'] = 'Der Hauptnutzer, der der Stelle zugeordnet ist.';
$string['privacy:metadata:local_dutydesk_taskassign'] = 'Speichert Metadaten zu Aufgabenzuordnungen.';
$string['privacy:metadata:local_dutydesk_taskassign:assignedby'] = 'Der Nutzer, der die Aufgabe zugeordnet hat.';
$string['privacy:metadata:local_dutydesk_taskassign:timestamp'] = 'Der Zeitpunkt, zu dem die Aufgabenzuordnung aktualisiert wurde.';
$string['privacy:metadata:local_dutydesk_taskhist'] = 'Speichert Aktivitätenhistorien zu Aufgaben.';
$string['privacy:metadata:local_dutydesk_taskhist:action'] = 'Die Aktion des Historieneintrags.';
$string['privacy:metadata:local_dutydesk_taskhist:details'] = 'Details zur Aktion des Historieneintrags.';
$string['privacy:metadata:local_dutydesk_taskhist:timecreated'] = 'Der Zeitpunkt, zu dem der Historieneintrag erstellt wurde.';
$string['privacy:metadata:local_dutydesk_taskhist:userid'] = 'Der Nutzer, der dem Historieneintrag zugeordnet ist.';
$string['privacy:metadata:local_dutydesk_userinfo'] = 'Speichert zusätzliche DutyDesk-Nutzerzuordnungen.';
$string['privacy:metadata:local_dutydesk_userinfo:dutydeskrole'] = 'Die dem Nutzer zugewiesene DutyDesk-Rolle.';
$string['privacy:metadata:local_dutydesk_userinfo:userid'] = 'Der verknüpfte Moodle-Nutzer.';
$string['reordersubtasks'] = 'Unteraufgaben sortieren';
$string['restoreposition'] = 'Reaktivieren';
$string['returntotask'] = 'Zurück zur Aufgabe';
$string['saved'] = 'Eintrag gespeichert.';
$string['searchpositions'] = 'Stellen durchsuchen';
$string['searchpositionsplaceholder'] = 'Nach Stelle, Themenbereich, Dezernat oder Mitarbeiter suchen ...';
$string['searchtasks'] = 'Aufgaben durchsuchen';
$string['searchtasksplaceholder'] = 'Nach Aufgabe, Stelle, Dezernat oder Mitarbeiter suchen ...';
$string['showallpositions'] = 'Alle Stellen anzeigen';
$string['showownpositions'] = 'Nur meine Stellen anzeigen';
$string['showtopicareasonly'] = 'Nur Themenbereiche';
$string['subtaskadded'] = 'Unteraufgabe gespeichert.';
$string['subtaskdeleted'] = 'Unteraufgabe gelöscht.';
$string['subtaskdescription'] = 'Beschreibung der Unteraufgabe';
$string['subtaskdocuments'] = 'Dokumente zur Unteraufgabe';
$string['subtasks'] = 'Unteraufgaben';
$string['subtasktitle'] = 'Titel der Unteraufgabe';
$string['subtaskupdated'] = 'Unteraufgabe aktualisiert.';
$string['taskbacktoposition'] = 'Zur Stelle';
$string['taskcategoryfilter'] = 'Kategorie';
$string['taskcategoryfilter_all'] = 'Alle Kategorien';
$string['taskdepartmentfilter'] = 'Dezernat';
$string['taskdepartmentfilter_all'] = 'Alle Dezernate';
$string['taskdocuments'] = 'Dokumente';
$string['taskhistory'] = 'Historie';
$string['taskhistorybutton'] = 'Aktivitäten';
$string['taskhistoryempty'] = 'Für diese Aufgabe liegen noch keine Aktivitäten vor.';
$string['taskhistorymodalheading'] = 'Aktivitäten - {$a}';
$string['taskhistory_action_assignment'] = 'Zuweisung aktualisiert ({$a})';
$string['taskhistory_action_created'] = 'Aufgabe angelegt';
$string['taskhistory_action_deleted'] = 'Aufgabe gelöscht';
$string['taskhistory_action_documents'] = 'Dokumente geändert';
$string['taskhistory_action_subtask_created'] = 'Unteraufgabe angelegt';
$string['taskhistory_action_subtask_deleted'] = 'Unteraufgabe gelöscht';
$string['taskhistory_action_subtask_updated'] = 'Unteraufgabe aktualisiert';
$string['taskhistory_action_updated'] = 'Inhalte aktualisiert';
$string['taskhistory_detail_assignment_changed'] = 'Zuweisung geändert: "{$a->old}" -> "{$a->new}"';
$string['taskhistory_detail_assignment_removed'] = 'Zuweisung aufgehoben (zuvor "{$a}")';
$string['taskhistory_detail_assignment_set'] = 'Zugeteilt an "{$a}"';
$string['taskhistory_detail_description'] = 'Beschreibung angepasst.';
$string['taskhistory_detail_documents_added'] = 'Hinzugefügt: {$a}';
$string['taskhistory_detail_documents_removed'] = 'Entfernt: {$a}';
$string['taskhistory_detail_subtask_description'] = 'Unteraufgabe: Beschreibung angepasst.';
$string['taskhistory_detail_subtask_reference'] = 'Unteraufgabe: {$a}';
$string['taskhistory_detail_subtask_title'] = 'Unteraufgabe: Titel geändert "{$a->old}" -> "{$a->new}"';
$string['taskhistory_detail_title'] = 'Titel geändert: "{$a->old}" -> "{$a->new}"';
$string['taskhistory_records'] = 'Aktivitäten';
$string['taskhistory_systemuser'] = 'System';
$string['taskimport'] = 'Aufgaben importieren';
$string['taskimportbutton'] = 'Aufgaben importieren';
$string['taskimportcheck'] = 'Import prüfen';
$string['taskimportconfirm'] = 'Import abschließen';
$string['taskimportempty'] = 'In der Datei wurden keine importierbaren Aufgaben gefunden.';
$string['taskimportfile'] = 'Importdatei';
$string['taskimportinvalidfiletype'] = 'Bitte laden Sie eine Excel- oder CSV-Datei hoch.';
$string['taskimportmanagedepartments'] = 'Dezernate verwalten';
$string['taskimportmissingcolumns'] = 'Die Datei muss Spalten für Sachgebiet und Beschreibung enthalten.';
$string['taskimportnodepartments'] = 'Es sind noch keine Dezernate vorhanden.';
$string['taskimportnofile'] = 'Es wurde keine Importdatei gefunden.';
$string['taskimportnowarnings'] = 'Keine ähnlichen Aufgaben gefunden.';
$string['taskimportpreview'] = 'Import prüfen';
$string['taskimportsuccess'] = '{$a->tasks} Aufgaben importiert. {$a->categories} Kategorien neu angelegt.';
$string['taskimportsummary'] = '{$a} Aufgaben wurden in der Datei gefunden.';
$string['taskimportsummarydepartment'] = 'Dezernat: {$a}';
$string['taskimporttemplatecsv'] = 'CSV-Vorlage herunterladen';
$string['taskimporttemplates'] = 'Vorlagen';
$string['taskimporttemplatexlsx'] = 'Excel-Vorlage herunterladen';
$string['taskimportwarningitem'] = 'Zeile {$a->row}: "{$a->title}" ähnelt "{$a->match}"';
$string['taskimportwarningsintro'] = 'Es wurden ähnliche Aufgaben gefunden. Bitte prüfen Sie die Treffer vor dem Import.';
$string['taskimportwarningsmore'] = 'Und {$a} weitere mögliche Treffer.';
$string['tasks'] = 'Aufgaben';
$string['taskvacantbadge'] = 'Unbesetzte Aufgabe';
$string['taskvacantfilter'] = 'Nur unbesetzte Aufgaben';
$string['taskworkloadnotset'] = 'Keine Angabe';
$string['taskworkloadpercent'] = 'Auslastung (%)';
$string['taskworkloadpercentinvalid'] = 'Bitte geben Sie einen Wert zwischen 0 und 100 ein.';
$string['taskworkloadpercent_help'] = 'Gibt an, wie viel Prozent der Stellenkapazität diese Aufgabe beansprucht (0-100).';
$string['timestamp'] = 'Zeitstempel';
$string['updated'] = 'Eintrag aktualisiert.';
$string['usernotfound'] = 'Der ausgewählte Mitarbeiter konnte nicht gefunden werden.';
$string['usersearch'] = 'Mitarbeiter';
$string['usersearchnoresults'] = 'Keine passenden Mitarbeiter gefunden.';
$string['usersearchnoselection'] = 'Keine Auswahl';
$string['usersearchplaceholder'] = 'Name, E-Mail oder Benutzername eingeben...';
$string['usersearchrequired'] = 'Bitte wählen Sie einen Mitarbeiter aus der Liste aus.';
$string['viewfulldescription'] = 'Beschreibung vollständig anzeigen';
