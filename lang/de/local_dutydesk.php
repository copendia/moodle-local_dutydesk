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
$string['departments'] = 'Dezernate';
$string['department'] = 'Dezernat';
$string['nodepartment'] = 'Ohne Dezernat';
$string['invaliddepartment'] = 'Das ausgewählte Dezernat ist nicht vorhanden.';
$string['positions'] = 'Stellen und Themenbereiche';
$string['positions_active'] = 'Aktive Stellen';
$string['positions_archived'] = 'Archivierte Stellen';
$string['archived'] = 'Archiviert';
$string['archiveddate'] = 'Archiviert am';
$string['newpositionbutton'] = 'Neue Stelle';
$string['newtopicareabutton'] = 'Neuer Themenbereich';
$string['position'] = 'Stelle';
$string['positiontype_position'] = 'Stelle';
$string['positiontype_topicarea'] = 'Themenbereich';
$string['assignedpositions'] = 'Mein Dezernat';
$string['assignedpositionsheading'] = 'Stellen im Dezernat';
$string['mypositions_role_primary'] = 'Verantwortlich';
$string['mypositions_role_deputy'] = 'Vertretung';
$string['mypositions_role_manager'] = 'Dezernatsleitung';
$string['noassignedpositions'] = 'Ihnen sind derzeit keine Stellen zugewiesen.';
$string['nodepartmentlabel'] = 'Ohne Dezernat';
$string['mydepartment'] = 'Mein Dezernat';
$string['departmentmanagers'] = 'Dezernatsleitung';
$string['nodepartmentmanagers'] = 'Keine Dezernatsleitung zugewiesen.';
$string['departmentcategories'] = 'Kategorien';
$string['departmentcategoriesnone'] = 'Keine Kategorien zugewiesen.';
$string['departmentcategoriesplaceholder'] = 'Kategorien auswählen...';
$string['departmentcategoriesnoneavailable'] = 'Keine freien Kategorien verfügbar.';
$string['dutydesk:viewown'] = 'Eigene DutyDesk-Aufgaben anzeigen';
$string['dutydesk:manageown'] = 'Eigene DutyDesk-Aufgaben bearbeiten';
$string['dutydesk:viewall'] = 'Alle DutyDesk-Aufgaben anzeigen';
$string['dutydesk:manageall'] = 'DutyDesk-Aufgaben verwalten';
$string['dutydesk:managepositions'] = 'DutyDesk-Stellen verwalten';
$string['dutydesk:viewmydepartment'] = 'Mein Dezernat anzeigen';
$string['tasks'] = 'Aufgaben';
$string['departmentactions'] = 'Aktionen für das Dezernat';
$string['add'] = 'Hinzufügen';
$string['edit'] = 'Bearbeiten';
$string['delete'] = 'Löschen';
$string['confirmdelete'] = 'Eintrag wirklich löschen?';
$string['name'] = 'Name';
$string['description'] = 'Beschreibung';
$string['timestamp'] = 'Zeitstempel';
$string['saved'] = 'Eintrag gespeichert.';
$string['deleted'] = 'Eintrag gelöscht.';
$string['updated'] = 'Eintrag aktualisiert.';
$string['usersearch'] = 'Mitarbeiter';
$string['usersearchplaceholder'] = 'Name, E-Mail oder Benutzername eingeben...';
$string['usersearchrequired'] = 'Bitte wählen Sie einen Mitarbeiter aus der Liste aus.';
$string['usersearchnoselection'] = 'Keine Auswahl';
$string['usersearchnoresults'] = 'Keine passenden Mitarbeiter gefunden.';
$string['usernotfound'] = 'Der ausgewählte Mitarbeiter konnte nicht gefunden werden.';
$string['assignuser_success'] = 'Benutzer erfolgreich zugewiesen.';
$string['assignuser_errordefault'] = 'Es ist ein Fehler aufgetreten. Bitte versuchen Sie es erneut.';
$string['assignuser'] = 'Benutzer zuweisen';
$string['positionrequired'] = 'Bitte wählen Sie eine Stelle aus.';
$string['archiveposition'] = 'Archivieren';
$string['restoreposition'] = 'Reaktivieren';
$string['positiondeleterequiresarchive'] = 'Stellen können erst gelöscht werden, nachdem sie archiviert wurden.';
$string['nopositions'] = 'Keine Stellen vorhanden.';
$string['notassigned'] = 'Nicht zugewiesen';
$string['taskhistory'] = 'Historie';
$string['newdepartmentbutton'] = 'Neues Dezernat';
$string['newtaskbutton'] = 'Neue Aufgabe';
$string['taskimport'] = 'Aufgaben importieren';
$string['taskimportbutton'] = 'Aufgaben importieren';
$string['taskimportfile'] = 'Importdatei';
$string['taskimporttemplates'] = 'Vorlagen';
$string['taskimporttemplatecsv'] = 'CSV-Vorlage herunterladen';
$string['taskimporttemplatexlsx'] = 'Excel-Vorlage herunterladen';
$string['taskimportcheck'] = 'Import prüfen';
$string['taskimportconfirm'] = 'Import abschließen';
$string['taskimportpreview'] = 'Import prüfen';
$string['taskimportsummary'] = '{$a} Aufgaben wurden in der Datei gefunden.';
$string['taskimportsummarydepartment'] = 'Dezernat: {$a}';
$string['taskimportmanagedepartments'] = 'Dezernate verwalten';
$string['taskimportnodepartments'] = 'Es sind noch keine Dezernate vorhanden.';
$string['taskimportwarningsintro'] = 'Es wurden ähnliche Aufgaben gefunden. Bitte prüfen Sie die Treffer vor dem Import.';
$string['taskimportwarningitem'] = 'Zeile {$a->row}: "{$a->title}" ähnelt "{$a->match}"';
$string['taskimportwarningsmore'] = 'Und {$a} weitere mögliche Treffer.';
$string['taskimportnowarnings'] = 'Keine ähnlichen Aufgaben gefunden.';
$string['taskimportsuccess'] = '{$a->tasks} Aufgaben importiert. {$a->categories} Kategorien neu angelegt.';
$string['taskimportmissingcolumns'] = 'Die Datei muss Spalten für Sachgebiet und Beschreibung enthalten.';
$string['taskimportempty'] = 'In der Datei wurden keine importierbaren Aufgaben gefunden.';
$string['taskimportnofile'] = 'Es wurde keine Importdatei gefunden.';
$string['taskimportinvalidfiletype'] = 'Bitte laden Sie eine Excel- oder CSV-Datei hoch.';
$string['taskhistorybutton'] = 'Aktivitäten';
$string['taskbacktoposition'] = 'Zur Stelle';
$string['taskhistorymodalheading'] = 'Aktivitäten - {$a}';
$string['taskhistoryempty'] = 'Für diese Aufgabe liegen noch keine Aktivitäten vor.';
$string['taskhistory_action_created'] = 'Aufgabe angelegt';
$string['taskhistory_action_updated'] = 'Inhalte aktualisiert';
$string['taskhistory_action_documents'] = 'Dokumente geändert';
$string['taskhistory_action_deleted'] = 'Aufgabe gelöscht';
$string['taskhistory_action_subtask_created'] = 'Unteraufgabe angelegt';
$string['taskhistory_action_subtask_updated'] = 'Unteraufgabe aktualisiert';
$string['taskhistory_action_subtask_deleted'] = 'Unteraufgabe gelöscht';
$string['taskhistory_action_assignment'] = 'Zuweisung aktualisiert ({$a})';
$string['taskhistory_systemuser'] = 'System';
$string['taskhistory_detail_title'] = 'Titel geändert: "{$a->old}" -> "{$a->new}"';
$string['taskhistory_detail_description'] = 'Beschreibung angepasst.';
$string['taskhistory_detail_subtask_title'] = 'Unteraufgabe: Titel geändert "{$a->old}" -> "{$a->new}"';
$string['taskhistory_detail_subtask_description'] = 'Unteraufgabe: Beschreibung angepasst.';
$string['taskhistory_detail_subtask_reference'] = 'Unteraufgabe: {$a}';
$string['taskhistory_detail_documents_added'] = 'Hinzugefügt: {$a}';
$string['taskhistory_detail_documents_removed'] = 'Entfernt: {$a}';
$string['taskhistory_detail_assignment_set'] = 'Zugeteilt an "{$a}"';
$string['taskhistory_detail_assignment_changed'] = 'Zuweisung geändert: "{$a->old}" -> "{$a->new}"';
$string['taskhistory_detail_assignment_removed'] = 'Zuweisung aufgehoben (zuvor "{$a}")';
$string['taskhistory_records'] = 'Aktivitäten';
$string['taskcategoryfilter'] = 'Kategorie';
$string['taskcategoryfilter_all'] = 'Alle Kategorien';
$string['taskdepartmentfilter'] = 'Dezernat';
$string['taskdepartmentfilter_all'] = 'Alle Dezernate';
$string['assignedpositionsmoretasks'] = '{$a} weitere Aufgaben';
$string['assignedpositionsmanagedheading'] = 'Weitere Stellen im Dezernat';
$string['newtask'] = 'Neue Aufgabe anlegen';
$string['subtasks'] = 'Unteraufgaben';
$string['newsubtask'] = 'Unteraufgabe hinzufügen';
$string['nosubtasks'] = 'Keine Unteraufgaben vorhanden.';
$string['nodepartmentpositions'] = 'Keine Stellen zugeordnet.';
$string['nosubtaskdescription'] = 'Für diese Unteraufgabe liegt keine weitere Beschreibung vor.';
$string['nodescription'] = 'Keine Beschreibung hinterlegt.';
$string['subtasktitle'] = 'Titel der Unteraufgabe';
$string['subtaskdescription'] = 'Beschreibung der Unteraufgabe';
$string['subtaskdocuments'] = 'Dokumente zur Unteraufgabe';
$string['expandallsubtasks'] = '';
$string['collapseallsubtasks'] = 'Alles einklappen';
$string['subtaskadded'] = 'Unteraufgabe gespeichert.';
$string['subtaskupdated'] = 'Unteraufgabe aktualisiert.';
$string['subtaskdeleted'] = 'Unteraufgabe gelöscht.';
$string['notasks'] = 'Keine Aufgaben vorhanden.';
$string['nodepartments'] = 'Keine Dezernate vorhanden.';
$string['reordersubtasks'] = 'Unteraufgaben sortieren';
$string['searchtasks'] = 'Aufgaben durchsuchen';
$string['searchtasksplaceholder'] = 'Nach Aufgabe, Stelle, Dezernat oder Mitarbeiter suchen ...';
$string['searchpositions'] = 'Stellen durchsuchen';
$string['searchpositionsplaceholder'] = 'Nach Stelle, Themenbereich, Dezernat oder Mitarbeiter suchen ...';
$string['showallpositions'] = 'Alle Stellen anzeigen';
$string['showownpositions'] = 'Nur meine Stellen anzeigen';
$string['showtopicareasonly'] = 'Nur Themenbereiche';
$string['nosearchresultspositions'] = 'Keine Stellen entsprechen der Suche.';
$string['nosearchresults'] = 'Keine Aufgaben entsprechen der Suche.';
$string['taskdocuments'] = 'Dokumente';
$string['taskworkloadpercent'] = 'Auslastung (%)';
$string['taskworkloadpercent_help'] = 'Gibt an, wie viel Prozent der Stellenkapazität diese Aufgabe beansprucht (0-100).';
$string['taskworkloadpercentinvalid'] = 'Bitte geben Sie einen Wert zwischen 0 und 100 ein.';
$string['taskworkloadnotset'] = 'Keine Angabe';
$string['viewfulldescription'] = 'Beschreibung vollständig anzeigen';
$string['closedescription'] = 'Beschreibung schließen';
$string['positionprimaryuser'] = 'Mitarbeiter';
$string['positiondeputyuser'] = 'Vertretung';
$string['positionworkloadtotal'] = 'Gesamtauslastung';
$string['positiontasks'] = 'Aufgaben';
$string['positiontasksplaceholder'] = 'Aufgaben auswählen...';
$string['positiontasksnoselection'] = 'Keine Aufgaben ausgewählt';
$string['positiontasksassignedlabel'] = '(zugeordnet)';
$string['positionvacantlabel'] = 'Stelle ist unbesetzt';
$string['positionvacanthelp'] = 'Markiert die Stelle als aktuell nicht besetzt.';
$string['positionvacantbadge'] = 'Unbesetzt';
$string['nodeputy'] = 'Keine Vertretung hinterlegt.';
$string['returntotask'] = 'Zurück zur Aufgabe';
$string['taskvacantbadge'] = 'Unbesetzte Aufgabe';
$string['taskvacantfilter'] = 'Nur unbesetzte Aufgaben';
$string['expandalltasks'] = '';
$string['collapsealltasks'] = 'Aufgaben ausblenden';
$string['learningcontent'] = 'Lerninhalte';
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
$string['privacy:metadata:local_dutydesk_position'] = 'Speichert Stellen und deren zugeordnete Hauptnutzer.';
$string['privacy:metadata:local_dutydesk_position:archivedtime'] = 'Der Zeitpunkt, zu dem die Stelle archiviert wurde.';
$string['privacy:metadata:local_dutydesk_position:primaryuserid'] = 'Der Hauptnutzer, der der Stelle zugeordnet ist.';
$string['privacy:metadata:local_dutydesk_posdeputy'] = 'Speichert Vertretungszuordnungen für Stellen.';
$string['privacy:metadata:local_dutydesk_posdeputy:assignedby'] = 'Der Nutzer, der die Vertretung zugeordnet hat.';
$string['privacy:metadata:local_dutydesk_posdeputy:timecreated'] = 'Der Zeitpunkt, zu dem die Vertretung erstellt wurde.';
$string['privacy:metadata:local_dutydesk_posdeputy:userid'] = 'Der als Vertretung zugeordnete Nutzer.';
$string['privacy:metadata:local_dutydesk_taskhist'] = 'Speichert Aktivitätenhistorien zu Aufgaben.';
$string['privacy:metadata:local_dutydesk_taskhist:action'] = 'Die Aktion des Historieneintrags.';
$string['privacy:metadata:local_dutydesk_taskhist:details'] = 'Details zur Aktion des Historieneintrags.';
$string['privacy:metadata:local_dutydesk_taskhist:timecreated'] = 'Der Zeitpunkt, zu dem der Historieneintrag erstellt wurde.';
$string['privacy:metadata:local_dutydesk_taskhist:userid'] = 'Der Nutzer, der dem Historieneintrag zugeordnet ist.';
$string['privacy:metadata:local_dutydesk_taskassign'] = 'Speichert Metadaten zu Aufgabenzuordnungen.';
$string['privacy:metadata:local_dutydesk_taskassign:assignedby'] = 'Der Nutzer, der die Aufgabe zugeordnet hat.';
$string['privacy:metadata:local_dutydesk_taskassign:timestamp'] = 'Der Zeitpunkt, zu dem die Aufgabenzuordnung aktualisiert wurde.';
$string['privacy:metadata:local_dutydesk_userinfo'] = 'Speichert zusätzliche DutyDesk-Nutzerzuordnungen.';
$string['privacy:metadata:local_dutydesk_userinfo:dutydeskrole'] = 'Die dem Nutzer zugewiesene DutyDesk-Rolle.';
$string['privacy:metadata:local_dutydesk_userinfo:userid'] = 'Der Moodle-Nutzer, der mit den DutyDesk-Informationen verknüpft ist.';
