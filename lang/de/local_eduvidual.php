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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package   local_eduvidual
 * @copyright 2017 Digital Education Society (http://www.dibig.at)
 * @author    Robert Schrenk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'eduvidual';
$string['pluginname:settings'] = 'eduvidual Konfiguration';
$string['manage'] = 'eduvidual Management-Interface';

$string['access_denied'] = 'Zugriff nicht gestattet';
$string['back'] = 'zurück';
$string['proceed'] = 'weiter';
$string['secret'] = 'Secret';
$string['store'] = 'speichern';
$string['store:success'] = 'Daten erfolgreich gespeichert!';
$string['store:error'] = 'Daten konnten nicht gespeichert werden!';

$string['Accesscard'] = 'Zugangskarte';
$string['Accesscards'] = 'Zugangskarten';
$string['Administration'] = 'Administration';
$string['Attributions'] = 'Namensnennung';
$string['Browse_org'] = 'Meine Schulen';
$string['Courses'] = 'Meine Kurse';
$string['Management'] = 'Management';
$string['Login'] = 'Anmelden';
$string['Logout'] = 'Abmelden';
$string['Preferences'] = 'Einstellungen';
$string['Registration'] = 'Registrierung';
$string['Manager'] = 'Manager';
$string['Student'] = 'Schüler/in';
$string['Teacher'] = 'Lehrer/in';
$string['User'] = 'Benutzer/in';


$string['access_only_because_admin_category'] = 'Sie haben nur Zugriff, weil Sie Administrator/in sind. Sie gehören nicht zu dieser Organisation!';
$string['access_only_because_admin_course'] = 'Sie haben nur Zugriff, weil Sie Administrator/in sind. Sie sind in diesem Kurs nicht eingeschrieben!';
$string['access_only_because_admin_user'] = 'Sie haben nur Zugriff, weil Sie Administrator/in sind. Sie haben mit dieser Person keine gemeinsame Organisation!';

$string['accesscard:description'] = 'Die Zugangskarte wird verwendet, um Sie in zusätzlichen Schulen aufzunehmen. Mittels des "secret" (Kombination aus Benutzer-ID, # und einer zufälligen Buchstabenfolge) kann ein/e Manager/in einer Schule Sie zum Mitglied der Schule ernennen.<br /><br />Sofern Ihnen die Zugangskarte von einer dritten Person ausgehändigt wurde und Sie kein separates Passwort erhalten haben, ist die Buchstabenfolge zugleich Ihr Startpasswort. <strong>In diesem Fall sollten Sie dieses möglichst rasch ändern</strong>!<br /><br />Möglicherweise stellt Ihnen Ihre Schule einen eigenen Code bereit, mit dem Sie sich selbst aktiv in die Schule aufnehmen können. In diesem Fall geben Sie bitte die Schulkennzahl und den bereitgestellten Code im folgenden Formular ein:';
$string['accesscard:card_access'] = 'Aufnahme mittels Zugangskarte (passiv)';
$string['accesscard:code_invalid'] = 'Dieser Code ist falsch!';
$string['accesscard:code_obsolete'] = 'Dieser Code ist abgelaufen!';
$string['accesscard:enrol'] = 'einschreiben';
$string['accesscard:not_for_guest'] = 'Gastkonten haben leider keine Zugangskarte!';
$string['accesscard:orgcode'] = 'Code oder Passphrase';
$string['accesscard:orgcode_access'] = 'Zugang mittels Code (aktiv)';
$string['accesscard:orgid'] = 'Die Schulkennzahl';

$string['admin:backgrounds:filearealabel'] = '';
$string['admin:backgrounds:title'] = 'Hintergrund';
$string['admin:backgrounds:description'] = 'Sie können an dieser Stelle Hintergrundbilder hochladen, aus denen die Nutzer/innen auswählen können.';
$string['admin:backgrounds:files:send'] = 'Bilder speichern';
$string['admin:backgrounds_cards:title'] = 'Hintergrund für Zugangskarten';
$string['admin:backgrounds_cards:description'] = 'Sie können hier Hintergrundbilder für Zugangskarten hochladen!';
$string['admin:blockfooter:title'] = 'Fußzeile des eduvidual Blocks';
$string['admin:blockfooter:description'] = 'Sie können eine Fußzeile setzen, die systemweit im eduvidual-Block aufscheint.';
$string['admin:coursebasements:title'] = 'Kategorien mit Kursvorlagen';
$string['admin:coursebasements:description'] = 'Sie können mehrere Kategorien angeben, in denen Kursvorlagen gespeichert werden. Diese Kursvorlagen können von Lehrer/innen bei der Erstellung von neuen Kursen als Vorlage benutzt und dupliziert werden. Trennen Sie mehrere Kategorien mit einem ","!';
$string['admin:coursestuff:title'] = 'Kurseinstellungen';
$string['admin:dropzone:description'] = 'Setzen Sie das Verzeichnis zu einem Dateisystem-Repository. Wenn Nutzer/innen etwas hochladen, wird Ihnen der Zugriff aufs Repository für 24 h gewährt.';
$string['admin:dropzone:notset'] = 'Kein Verzeichnis für die Dropzone gesetzt!';
$string['admin:dropzone:title'] = 'Dropzone';
$string['admin:formmodificator:description'] = 'Hier können Sie angeben, inwiefern Formular zur Erstellung von Aktivitäten/Ressourcen entsprechend dem Erfahrungslevel angepasst werden sollen. Geben Sie den Typ der Ressource und die Rollen-ID an. In der Spalte "Verstecke" können Sie nun zeilenweise die CSS-Selektoren angeben, die ausgeblendet werden sollen. In der Spalte "Default Werte" geben Sie Wertepaare an, welche Standardwerte im Formular gesetzt werden sollen, bspw. css_selektor1=default_wert1\n css_selektor2=default_wert2 usw.';
$string['admin:formmodificator:ids_to_hide'] = 'Verstecke';
$string['admin:formmodificator:ids_to_set'] = 'Default Werte';
$string['admin:formmodificator:removenotice'] = 'Sie können eine Zeile entfernen, indem Sie das Feld "Typen" leeren und dann speichern!';
$string['admin:formmodificator:roleids'] = 'Rollen-IDs';
$string['admin:formmodificator:types'] = 'Typen';
$string['admin:globalfiles:title'] = 'Globale Dateien';
$string['admin:globalfiles:description'] = 'Global verfügbare Dateien hochladen. Der Dateipfad lautet wie folgt: /pluginfile.php/1/local_eduvidual/globalfiles/0/{verzeichnisse}/{dateiname}';
$string['admin:ltiresourcekey:title'] = 'Standard LTI Resource Key';
$string['admin:ltiresourcekey:description'] = 'Falls Sie LTI Ressourcen mit eduvidual verwalten, stellen Sie sicher, dass Sie überall denselben LTI Resource Key setzen. Sie können den Standardwert hier setzen.';
$string['admin:map'] = 'Interaktive Schulkarte';
$string['admin:map:both'] = 'eduvidual + lernplattform';
$string['admin:map:count_invisibles'] = 'Unsichtbare Punkte zählen';
$string['admin:map:eduv'] = 'Nur eduvidual.at';
$string['admin:map:includenonegroup'] = 'Schulen mit unbekanntem Status anzeigen';
$string['admin:map:lpf'] = 'Nur lernplattform.schule.at';
$string['admin:map:none'] = 'Unbekannt';
$string['admin:map:google:apikey'] = 'Google API Key';
$string['admin:map:google:apikey:description'] = '<a href="https://cloud.google.com/console/google/maps-apis/overview?hl=de" target="_blank">Google</a> wird genutzt, um GPS Koordinaten von Schulen zu erfragen. Wenn Sie dieses Feature verwenden möchten, müssen Sie sich registrieren und den API Key hier eingeben.';
$string['admin:map:mapquest:apikey'] = 'MapQuest API Key';
$string['admin:map:mapquest:apikey:description'] = '<a href="https://www.mapquest.com" target="_blank">MapQuest</a> wird genutzt, um GPS Koordinaten von Schulen zu erfragen. Wenn Sie dieses Feature verwenden möchten, müssen Sie sich registrieren und den API Key hier eingeben.';
$string['admin:map:nominatim:directly'] = 'Wenn Sie MapQuest nicht verwenden möchten, können Sie den Nominatim-Service auch direkt abfragen. Hierbei kommt es aber zu einer Limitierung bei der Menge und Geschwindigkeit von Abfragen.';
$string['admin:module:filearealabel'] = '';
$string['admin:module:files:send'] = 'Modul speichern';
$string['admin:module:generaldata'] = 'Allgemein Daten';
$string['admin:module:ltidata'] = 'LTI Daten';
$string['admin:module:lticartridge'] = 'LTI Cartridge URL';
$string['admin:module:ltilaunch'] = 'LTI Launch URL';
$string['admin:module:ltiresourcekey'] = 'LTI Resource Key';
$string['admin:module:ltisecret'] = 'LTI Secret';
$string['admin:module:payload'] = 'Payload';
$string['admin:module:payload:jsoneditor'] = 'Für LTI-Ressourcen wird der "Payload" automatisch zusammengestellt. Für alle anderen Typen nutzen Sie bitte einen JSON-Editor wie bspw. <a href="https://jsoneditoronline.org/" target="_blank">json editor online</a>!';
$string['admin:module:type'] = 'Typ';
$string['admin:modulecats:title'] = 'Modulkategorien';
$string['admin:modulecat:edit'] = 'Module ändern';
$string['admin:modulecat:generaldata'] = 'Allgemeine Daten';
$string['admin:modulecat:title'] = 'Modulkategorie';
$string['admin:modulecat:images'] = 'Bild für Modulkategorie';
$string['admin:modulecat:filearealabel'] = '';
$string['admin:modulecat:files:send'] = 'Kategorie speichern';
$string['admin:modules:title'] = 'Module';
$string['admin:modulesimport:datavalidated'] = 'Daten sind in Ordnung, übertrage in Datenbank!';
$string['admin:modulesimport:downloadfile'] = 'Module wurden aktualisiert. Bitte laden Sie das Excel-Sheet mit den Modulen herunter. Mittels dieser Liste können Sie die Module später komfortabel ändern.';
$string['admin:navbar:title'] = 'Navbar Zusätze';
$string['admin:navbar:description'] = 'Sie können Links festlegen, die immer dem eduvidual-Menü hinzugefügt werden. Geben Sie einen Link pro Zeile im folgenden Format an:<br /><br /><i>{title}|{url}|{iconurl}</i>';
$string['admin:orgcoursebasement:title'] = 'Kursvorlage für Organisations-Kurse';
$string['admin:orgcoursebasement:description'] = 'Geben Sie eine Kursvorlage für Schulvernetzungskurse an.';
$string['admin:orgcoursebasement:nocoursebasementsgiven'] = 'Keine Kategorien für Kursvorlagen angegeben. Bitte geben Sie zuerst Kategorien für Kursvorlagen an!';
$string['admin:orgs:title'] = 'Organisationen und Schulen';
$string['admin:orgs:description'] = 'Verwalten Sie hier jene Organisationen und Schulen, die sich selbst in der Plattform registrieren dürfen!';
$string['admin:orgs:fields:categoryid'] = 'Kursbereich';
$string['admin:orgs:fields:city'] = 'Stadt';
$string['admin:orgs:fields:country'] = 'Land';
$string['admin:orgs:fields:orgid'] = 'Schulkennzahl';
$string['admin:orgs:fields:mail'] = 'e-Mail';
$string['admin:orgs:fields:lat'] = 'Breitengrad';
$string['admin:orgs:fields:lon'] = 'Längengrad';
$string['admin:orgs:fields:name'] = 'Name';
$string['admin:orgs:fields:phone'] = 'Telefonnummer';
$string['admin:orgs:fields:street'] = 'Straße';
$string['admin:orgs:fields:zip'] = 'PLZ';
$string['admin:phplist:title'] = 'phpList Einstellungen';
$string['admin:phplist:description'] = 'Hier können Sie die Synchronisation mit phpList konfigurieren!';
$string['admin:phplist:sync'] = 'Komplette Synchronisation durchführen!';
$string['admin:protectedorgs:title'] = 'Intime Organisationen';
$string['admin:protectedorgs:description'] = 'Nutzer/innen intimer Organisationen werden trotz gemeinsamer Mitgliedschaft nicht gegenseitig aufgelistet!';
$string['admin:questioncategories:title'] = 'Kernsystem-Fragenkategorien';
$string['admin:questioncategories:description'] = 'Wählen Sie jene Kernsystem-Fragenkategorien aus, die von Benutzer/innen gesondert ausgewählt werden müssen. Kategorien, die Sie hier NICHT angeben, werden IMMER gezeigt. Kategorien, die hier angegeben werden, können von den Nutzer/innen selbstständig ein- und ausgeblendet werden!';
$string['admin:registrationcc:title'] = 'BCC-Info bei Registrierung';
$string['admin:registrationcc:description'] = 'BCC-Info bei Registrierung neuer Organisationen. Bitte trennen Sie mehrere Mailadressen mit einem ","';
$string['admin:registrationsupport:title'] = 'Support-Kontakt für Registrierung';
$string['admin:registrationsupport:description'] = 'Bitte geben Sie hier einen Kontakt an für Probleme mit der Registrierung. Diese Angabe wird Teil eines "mailto"-Links!';
$string['admin:stats:all'] = 'Schulen gesamt';
$string['admin:stats:lpf_and_eduv'] = 'Bundes-Moodle';
$string['admin:stats:lpf'] = 'Lernplattform';
$string['admin:stats:lpfeduv'] = 'beides';
$string['admin:stats:migrated'] = 'umgestiegen';
$string['admin:stats:neweduv'] = 'eduvidual.at';
$string['admin:stats:rate'] = 'Anteil';
$string['admin:stats:registered'] = 'Registriert';
$string['admin:stats:state'] = 'Bundesland';
$string['admin:stats:states'] = 'Bundesländer';
$string['admin:stats:title'] = 'Statistiken';
$string['admin:stats:type'] = 'Schultyp';
$string['admin:stats:types'] = 'Schultypen';
$string['admin:supportcourse_template'] = 'Vorlage für Supportkurs';
$string['admin:supportcourse_template:description'] = 'Wenn dieser Wert gesetzt wurde, erhält jede Schule einen eigenen Supportkurs auf Basis dieser Vorlage. Dieser Supportkurs wird zusammen mit dem Plugin block_edusupport genutzt. Bitte geben Sie in diesem Textfeld die KursID des Vorlagekurses ein.';
$string['admin:supportcourse:missingsetup'] = 'Vorlage für Supportkurse wurde nicht gesetzt. Bitte prüfen Sie die Plugin-Einstellungen in der Website-Administration!';
$string['admin:supportcourses'] = 'Supportkurse';
$string['admin:termsofuse:title'] = 'Nutzungsbedingungen';

$string['allmanagerscourses:title'] = 'Kurse für Manager/innen';
$string['allmanagerscourses:description'] = 'Sie können alle Manager/innen automatisch in bestimmte Kurse einschreiben lassen (bspw. Dokumentationskurs, Supportkurs, ...). Bitte trennen Sie mehrere Kurs-IDs mit einem ","!';

$string['app:back_to_app'] = 'Zurück zur App';
$string['app:redirect_to_courses'] = 'Weiterleitung zur Kursübersicht!';
$string['app:login_successfull'] = 'Login erfolgreich!';
$string['app:login_successfull_token'] = 'Erfolgreicher Login mittels gültigem Token!';
$string['app:login_successfull_username_password'] = 'Erfolgreicher Login mittels Benutzername und Passwort!';
$string['app:login_with_x'] = 'Login über {$a}';
$string['app:login_wrong_credentials'] = 'Benutzer oder Passwort sind falsch!';
$string['app:login_wrong_token'] = 'Der Login-Token ist falsch!';
$string['app:open_in_app'] = 'Öffne eduvidual-App';

$string['cachedef_appcache'] = 'Das ist der Session-Cache des App-Modus';

$string['categories:coursecategories'] = 'Kurse & Kategorien';

$string['check_js:description'] = 'Die angeforderte Seite benötigt aktiviertes JavaScript. Falls Sie nicht weitergeleitet werden, ist JavaScript in Ihrem Browser möglicherweise deaktiviert.';
$string['check_js:title'] = 'JavaScript';

$string['courses:enrol:byqrcode'] = 'Aufnahme via QR Code';
$string['courses:enrol:courseusers'] = 'Benutzer in {$a->name}';
$string['courses:enrol:enrol'] = 'Aufnehmen';
$string['courses:enrol:orgusers'] = 'Benutzer von {$a->name}';
$string['courses:enrol:roletoset'] = 'Rolle setzen';
$string['courses:enrol:searchforuser'] = 'Suche und wähle Nutzer/innen';
$string['courses:enrol:searchtoomuch'] = 'Zu viele Nutzer/innen, bitte nutzen Sie das Suchfeld!';
$string['courses:enrol:unenrol'] = 'Abmelden';
$string['courses:noaccess'] = 'Leider sind Sie in diesem Kurs nicht eingeschrieben!';

$string['cron:title'] = 'eduvidual Cron';
$string['cron:trashbin:title'] = 'eduvidual Papierkorb';

$string['defaultroles:title'] = 'Rollen';
$string['defaultroles:course:title'] = 'Rollen (für Kurse)';
$string['defaultroles:course:description'] = 'Definieren Sie hier jene Rollen, die in Kursen durch dieses Plugin vergeben werden!';
$string['defaultroles:course:parent'] = 'Erziehungsberechtige/r';
$string['defaultroles:course:student'] = 'Lernende/r';
$string['defaultroles:course:teacher'] = 'Lernbegleiter/in';
$string['defaultroles:course:unmanaged'] = 'Nicht verwaltet';

$string['defaultroles:orgcategory:title'] = 'Rollen (für Organisationen)';
$string['defaultroles:orgcategory:description'] = 'Definieren Sie hier jene Rollen, die im Kursbereich einer Organisation vergeben werden!';
$string['defaultroles:orgcategory:manager'] = 'Manager/in';
$string['defaultroles:orgcategory:parent'] = 'Erziehungsberechtige/r';
$string['defaultroles:orgcategory:student'] = 'Schüler/in';
$string['defaultroles:orgcategory:teacher'] = 'Lehrer/in';

$string['defaultroles:global:title'] = 'Rollen (Systemkontext)';
$string['defaultroles:global:description'] = 'Definieren Sie hier jene Rollen, die im globalen Kontext vergeben werden!';
$string['defaultroles:global:manager'] = 'Manager/in';
$string['defaultroles:global:parent'] = 'Erziehungsberechtige/r';
$string['defaultroles:global:student'] = 'Schüler/in';
$string['defaultroles:global:teacher'] = 'Lehrer/in';
$string['defaultroles:global:inuse'] = 'Rolle wird bereits verwendet.';

$string['defaultroles:refreshroles'] = 'Rollen in Kurskategorien neu setzen';

$string['edutube:edutubeauthurl'] = 'eduTube Auth URL';
$string['edutube:edutubeauthtoken'] = 'eduTube Auth Token';
$string['edutube:invalid_url'] = 'Ungültige URL erhalten ({$a->url}). Weiterleitung zu edutube.at leider nicht möglich!';
$string['edutube:no_org'] = 'Entschuldigung, leider wurde Ihnen an keiner Schule eine Rolle als Schüler/in oder Lehrer/in zugewiesen. Bitte kontaktieren Sie die Ansprechpersonen Ihrer Schule, damit man Ihnen die notwendigen Rechte zuweist!<br /><br />Falls Ihre Schule noch nicht in eduvidual.at registriert wurde, können Sie diesen Schritt über die <a href="{$a->wwwroot}/local/eduvidual/pages/register.php">Registrierung</a> nachholen und sofort auch edutube.at nutzen!';
$string['edutube:title'] = 'eduTube';
$string['edutube:missing_configuration'] = 'eduTube wurde noch nicht konfiguriert';

$string['eduvidual:addinstance'] = 'eduvidual Block hinzufügen';
$string['eduvidual:canaccess'] = 'Erlaube Zugriff auf diesen Kontext und seine Subkontexte.';
$string['eduvidual:manage'] = 'eduvidual Block verwalten';
$string['eduvidual:myaddinstance'] = 'eduvidual Block zum Kurs hinzufügen';
$string['eduvidual:useinstance'] = 'eduvidual Block verwenden';

$string['explevel:title'] = 'Erfahrungslevel';
$string['explevel:description'] = 'Erfahrungslevel-Rollen können verwendet werden, um bspw. das Moodle-Userinterface zu vereinfachen.';
$string['explevel:role_1:description'] = 'Die Basisrolle ist ideal für Anfänger/innen. Ihnen werden in Kursen die wichtigsten Aktivitäten, Blöcke und Ressourcen angeboten, und die Eingabeformulare werden auf das Wesentliche reduziert!';
$string['explevel:role_2:description'] = 'Die erweiterte Rolle bietet mehr Aktivitäten, Blöcke und Ressourcen. Außerdem können Sie in den Formularen schon mehr Einstellungen vornehmen!';
$string['explevel:role_3:description'] = 'Die Stufe für die Moodle-Experts ermöglicht die Nutzung aller Aktivitäten und Blöcke, die wir in eduvidual zur Verfügung haben!';
$string['explevel:select'] = 'Wählen Sie jene Rollen aus, die Nutzer/innen sich im Systemkontext selbst zuweisen dürfen, um das Verhalten von Moodle zu personalisieren.';
$string['export'] = 'Export';

$string['guestuser:nopermission'] = 'Diese Aktion ist für Gäste nicht möglich!';

$string['action'] = 'Aktion';
$string['back'] = 'Zurück';
$string['categoryadd:title'] = 'Name der neuen Kategorie?';
$string['categoryadd:text'] = 'Der Name muss zwischen 3 und 255 Zeichen umfassen!';
$string['categoryadd:title:length:title'] = 'Fehler';
$string['categoryadd:title:length:text'] = 'Der Name der Kategorie muss zwischen 3 und 255 Zeichen umfassen!';
$string['categoryedit:title'] = 'Neue Name der Kategorie?';
$string['categoryremove:text'] = 'Kategorie, Unterkategorien und alle Kurse endgültig löschen!!!';
$string['categoryremove:title'] = 'Wirklich löschen?';
$string['close'] = 'schließen';
$string['config_not_set'] = 'Konfiguration konnte nicht gespeichert werden!';
$string['courseremove:title'] = 'Kurs löschen';
$string['courseremove:text'] = 'Wollen Sie den Kurs wirklich löschen?';
$string['create'] = 'Erstellen';
$string['createcategory:here'] = 'Hier Kurskategorie erstellen';
$string['createcategory:remove'] = 'Kurskategorie löschen';
$string['createcategory:rename'] = 'Kurskategorie umbenennen';
$string['createcourse:basement'] = 'Kursvorlage wählen';
$string['createcourse:catcreateerror'] = 'Konnte den erforderlichen Kursbereich nicht anlegen';
$string['createcourse:coursenameemptyerror'] = 'Kursname ist leer';
$string['createcourse:created'] = 'Kurs erfolgreich erstellt';
$string['createcourse:createerror'] = 'Kurs konnte nicht erstellt werden';
$string['createcourse:extra'] = 'Extra';
$string['createcourse:hint_orgclasses'] = 'Hinweis: Als eduvidual-Manager/in können Sie verfügbare Klassen und Gegenstände im <a href="/local/eduvidual/pages/manage.php?act=classes">Management-Interface</a> festlegen!';
$string['createcourse:invalidbasement'] = 'Ungültige Vorlage';
$string['createcourse:org'] = 'Schule';
$string['createcourse:here'] = 'Kurs erstellen';
$string['createcourse:name'] = 'Name des Kurses';
$string['createcourse:nameinfo'] = 'Wir empfehlen einen Namen, der das Schuljahr der Erstellung und eine Gruppenbezeichnung bzw. den Gegenstand beinhaltet. So behalten Sie über mehrere Schuljahre hinweg den Überblick!';
$string['createcourse:nametooshort'] = 'Name zu kurz';
$string['createcourse:setteacher'] = 'Jemand anderen als Lernbegleiter/in setzen';
$string['createcourse:subcat1emptyerror'] = 'Die erste Unterkategorie darf nicht leer sein!';
$string['createcourse:subcat1'] = 'Schuljahr';
$string['createcourse:subcat1:defaults'] = "SJ19/20\nSJ20/21\nSchuljahresübergreifend";
$string['createcourse:subcat2'] = 'Klasse';
$string['createcourse:subcat3'] = 'Gegenstand';
$string['createcourse:subcat4'] = 'Zusatzinfo';
$string['createmodule:create'] = 'erstellen';
$string['createmodule:failed'] = 'Konnte das Modul nicht erstellen!';
$string['createmodule:invalid'] = 'Ungültige Moduldaten';
$string['createmodule:requiredfield'] = 'Dieses Feld ist erforderlich!';
$string['db_error'] = 'Datenbank-Fehler!';
$string['invalid_character'] = 'Ungültiges Zeichen';
$string['invalid_orgcoursebasement'] = 'Ungültige Kursvorlage gewählt!';
$string['invalid_secret'] = 'Ungültiges "Secret" angegeben!';
$string['invalid_type'] = 'Ungültiger Typ!';
$string['missing_permission'] = 'Fehlende Rechte!';
$string['open'] = 'öffnen';

$string['help_and_tutorials'] = 'Hilfe & Anleitungen';
$string['imprint'] = 'Impressum';

$string['mailregister:confirmation'] = 'Bestätigung';
$string['mailregister:confirmation:mailsent'] = 'Die eMail wurde versendet!';
$string['mailregister:footer'] = 'Mit freundlichen Grüßen';
$string['mailregister:footer:signature'] = '<img src="https://www.eduvidual.at/pluginfile.php/1/local_eduvidual/globalfiles/0/_sys/register/signature.png" width="200" alt="" /><br />Robert Schrenk';
$string['mailregister:header'] = 'Registrierung';
$string['mailregister:proceed'] = 'Um mit der Registrierung fortzufahren klicken Sie bitte diesen <a href="{$a->registrationurl}" target="_blank">Link</a>!';
$string['mailregister:text'] = '<a href="{$a->wwwroot}/user/profile.php?id={$a->userid}">{$a->userfullname}</a> möchte Ihre Schule mit der Kennzahl <b>{$a->orgid}</b> in der Plattform <a href="{$a->wwwroot}" target="_blank">{$a->sitename}</a> registrieren. Um die Registrierung abzuschließen, benötigt er/sie den Inhalt dieser e-Mail. Bitte leiten Sie diese Nachricht daher weiter, wenn Sie damit einverstanden sind.<br /><br />Um die Registrierung abzuschließen geben Sie bitte den folgenden Token bei der Registrierung an:';
$string['mailregister:subject'] = 'Registrierung';
$string['mailregister:2:gotocategory'] = 'Der Bereich für Ihre Organisation befindet sich unter <b><a href="{$a->categoryurl}" target="_blank">{$a->orgname}</a></b>.';
$string['mailregister:2:header'] = 'Registrierung abgeschlossen';
$string['mailregister:2:text'] = 'Die Registrierung Ihrer Organisation {$a->orgid} ist abgeschlossen. Mehr Informationen zur Verwaltung Ihres Schul-Bereichs finden Sie im Kurs für <a href="{$a->managerscourseurl}">eduvidual-Manager/innen</a>!';
$string['mailregister:2:footer'] = 'Mit freundlichen Grüßen';
$string['mailregister:2:footer:signature'] = '<img src="https://www.eduvidual.at/pluginfile.php/1/local_eduvidual/globalfiles/0/_sys/register/signature.png" width="200" alt="" /><br />Robert Schrenk';
$string['mailregister:2:subject'] = 'Registrierung abgeschlossen';

$string['mainmenu'] = 'Hauptmenü';

$string['manage:accesscodes'] = 'Zugangscode';
$string['manage:accesscodes:create'] = 'Zugangscode erstellen';
$string['manage:accesscodes:code'] = 'Code oder Passphrase';
$string['manage:accesscodes:description'] = 'Sie können verschiedene Zugangscodes erstellen, mit denen die Nutzer/innen Ihrer Schule sich selbst zur Schule über die Funktion "<a href="{$a->wwwroot}/local/eduvidual/pages/accesscard.php">Zugangskarte</a>" hinzufügen können!';
$string['manage:accesscodes:issuer'] = 'Aussteller/in';
$string['manage:accesscodes:issuer:short'] = 'von';
$string['manage:accesscodes:maturity'] = 'Ablauf (YYYY-mm-dd HH:ii:ss)';
$string['manage:accesscodes:maturity:short'] = 'Ablauf';
$string['manage:accesscodes:revoke'] = 'Stornieren';
$string['manage:accesscodes:role'] = 'Rolle';
$string['manage:addparent'] = 'Mentor/in zuordnen';
$string['manage:addparent:changestate'] = 'Mentorenstatus setzen/entfernen';
$string['manage:addparent:description'] = 'Sobald der Mentorenstatus zugeordnet wurde, kann der/die Mentor/in bestimmte Daten, wie bspw. Foreneinträge, Bewertungen, Kursaktivitäten der/s Schüler/in einsehen. Außerdem kann der/die Mentor/in im Namen der/s Schüler/in Zustimmung zu den Site Policies vornehmen!';
$string['manage:addparent:studentfilter'] = 'Schüler/innen suchen';
$string['manage:addparent:studentfilter:init'] = 'Geben Sie einen Teil des Namens ein';
$string['manage:addparent:parentfilter'] = 'Mentor/in suchen';
$string['manage:addparent:parentfilter:init'] = 'Zuerst eine/n Schüler/in wählen';
$string['manage:addparent:warning'] = 'Achtung, stellen Sie sicher, dass der/die Mentor/in gemäß den jeweils gültigen Datenschutzgesetzen (bspw. DSGVO) auf diese Daten zugreifen darf!';
$string['manage:adduser'] = 'Nutzer/innen zur Schule hinzufügen';
$string['manage:adduser:description'] = 'Jeder in Ihrer Schule (Sie eingeschlossen) kann nur Nutzer/innen Ihrer Schule sehen. Sie können bestehende Nutzer/innen (bspw. nach einem Schulwechsel) in Ihre Schule mittels des "Secrets" aufnehmen, welches auf der Zugangskarte ersichtlich ist (bspw. <i>1234#tan</i>).';
$string['manage:adduser:qrscan'] = 'Nutzer/in mittels QR Code aufnehmen';
$string['manage:archive'] = 'Archivieren';
$string['manage:archive:action'] = 'Aktion';
$string['manage:archive:action:coursecannotmanage'] = 'Sie können den Kurs {$a->name} nicht verwalten!';
$string['manage:archive:action:coursemoved'] = 'Kurs {$a->name} wurde verschoben!';
$string['manage:archive:action:courseNOTmoved'] = 'Achtung: Kurs {$a->name} konnte <strong>nicht</strong> verschoben werden!';
$string['manage:archive:action:failures'] = 'Es sind {$a->failures} Fehler aufgetreten!';
$string['manage:archive:action:successes'] = '{$a->successes} Kurse wurden verschoben!';
$string['manage:archive:action:title'] = 'Kurse verschieben';
$string['manage:archive:action:targetinvalid'] = 'Zielkategorie ist ungültig!';
$string['manage:archive:action:targetok'] = 'Zielkategorie geprüft und ok!';
$string['manage:archive:confirmation'] = 'Bestätigung';
$string['manage:archive:confirmation:description'] = 'Die folgenden Kurse werden nach {$a->name} verschoben:';
$string['manage:archive:restart'] = 'Neustart';
$string['manage:archive:source'] = 'Quelle';
$string['manage:archive:source:title'] = 'Kurse wählen';
$string['manage:archive:source:description'] = 'Sie können Kurse en masse auswählen, um sie in eine andere Kategorie (bspw. ein Archiv) zu schieben.';
$string['manage:archive:target'] = 'Ziel';
$string['manage:archive:target:title'] = 'Ziel wählen';
$string['manage:archive:target:description'] = 'Sie haben {$a->count} Kurs(e) gewählt.';
$string['manage:archive:trashbin'] = 'Papierkorb';
$string['manage:archive:trashbin:description'] = 'Kurse können in einen systemweiten Papierkorb geschoben werden. Solange Kurse im Papierkorb sind, können sie wiederhergestellt werden. Der Papierkorb wird regelmäßig geleert!';
$string['manage:bunch:all'] = 'Alle';
$string['manage:bunch:allwithoutbunch'] = 'Alle Nutzer/innen ohne "globale Gruppe"';
$string['manage:bunch:allparents'] = 'Alle Erziehungsberechtigten';
$string['manage:bunch:allstudents'] = 'Alle Schüler/innen';
$string['manage:bunch:allteachers'] = 'Alle Lehrer/innen';
$string['manage:bunch:allmanagers'] = 'Alle Manager/innen';
$string['manage:coursecategories'] = 'Kurskategorien';
$string['manage:createuseranonymous'] = 'Anonyme Nutzer/innen erstellen';
$string['manage:createuseranonymous:amount'] = 'Anzahl';
$string['manage:createuseranonymous:bunch'] = 'Globale Gruppe';
$string['manage:createuseranonymous:bunches'] = 'Globale Gruppen';
$string['manage:createuseranonymous:created'] = 'Erfolgreich {$a->amount} Nutzer/innen mit "globaler Gruppe" {$a->bunch} erstellt.';
$string['manage:createuseranonymous:description'] = 'Das ist der leichteste Weg, um Nutzerkonten zu erstellen. Die Nutzer/innen erhalten zufällig vergebene Pseudonyme. Im Anschluss können Sie die Zugangskarten ausdrucken. Daher ist es sinnvoll eine "globale Gruppe" zu vergeben, um zusammengehörende Zugangskarten gemeinsam drucken zu können, bspw. "neue_lehrer/innen-2019/05".';
$string['manage:createuseranonymous:exceededmax:title'] = 'Maximum überschritten';
$string['manage:createuseranonymous:exceededmax:text'] = 'Sie können höchstens {$a->maximum} Konten auf einmal generieren.';
$string['manage:createuseranonymous:role'] = 'Rolle';
$string['manage:createuseranonymous:send'] = 'Nutzer/innen erstellen';
$string['manage:createuseranonymous:success'] = ' Nutzer/innen erstellt';
$string['manage:createuseranonymous:failed'] = ' Nutzer/innen <strong>nicht</strong> erstellt';
$string['manage:createuserspreadsheet'] = 'Nutzer/innen mit Excel erstellen';
$string['manage:createuserspreadsheet:import:datavalidated'] = 'Daten sind in Ordnung, sende zur Datenbank';
$string['manage:createuserspreadsheet:import:description'] = 'Sie können Nutzer/innen auf Basis eines Excel- oder OpenOffice-Tabellendokuments erstellen. Die erste Zeile sollte die Spaltenüberschriften beinhalten, diese sind:';
$string['manage:createuserspreadsheet:import:description:bunch'] = 'Mit der "globalen Gruppe" können Sie erstelle Nutzerkonten zusammenfassen, um sie gezielter in Kurse aufnehmen oder die Zugangskarten ausdrucken zu können.';
$string['manage:createuserspreadsheet:import:description:email'] = 'Die eMail-Adresse. Falls keine angegeben wird, wird das Feld mit einer (nicht funktionierenden) dummy-Adresse befüllt. Falls hier ein Wert angegeben wird, dient die Mailadresse zugleich als Nutzername.';
$string['manage:createuserspreadsheet:import:description:firstname'] = 'Der Vorname. Falls keiner angegeben wird, wird automatisch ein Pseudonym gewählt.';
$string['manage:createuserspreadsheet:import:description:id'] = 'Die Nutzer-ID. Um neue Nutzer/innen zu erstellen, lassen Sie das Feld leer. Falls die Nutzer-ID angegeben wird, können Sie den Vor- und Nachnamen, die "Globale Gruppe" und die Rolle ändern.';
$string['manage:createuserspreadsheet:import:description:lastname'] = 'Der Nachname. Falls keiner angegeben wird, wird automatisch ein Pseudonym gewählt.';
$string['manage:createuserspreadsheet:import:description:role'] = 'Die Rolle. Entweder "Manager", "Teacher", "Student" oder "Parent"). Aktuelle Enschreibungen werden dadurch nicht verändert.';
$string['manage:createuserspreadsheet:import:downloadfile'] = 'Nutzer/innen wurden aktualisiert. Bitte laden Sie die folgende Datei herunter, die die Nutzer/innen mit Nutzer-IDs enthält. Mit dieser Tabelle können Sie geänderte Nutzerdaten sehr einfach wieder einspielen.';
$string['manage:data'] = 'Daten';
$string['manage:enrolmeasteacher'] = 'Schreibe mich mit Trainer-Rechten ein!';
$string['manage:maildomain'] = 'Maildomain';
$string['manage:maildomain:description'] = 'Wenn dieser Wert gesetzt wird, werden Nutzer/innen mit einer solchen Mailadresse automatisch dieser Organisation zugeordnet!';
$string['manage:mnet:action'] = 'Logineinstellungen';
$string['manage:mnet'] = 'MNet Host';
$string['manage:mnet:adminonly'] = 'Nur Administrator/innen können hier Änderungen vornehmen!';
$string['manage:mnet:enrol'] = 'Alle Nutzer/innen zuordnen, die diesen Maildomains entsprechen!';
$string['manage:mnet:send'] = 'speichern';
$string['manage:mnet:selectnone'] = 'Keine';
$string['manage:mnet:selectorg'] = 'Zuerst Schule wählen!';
$string['manage:mnet:filearealabel'] = 'Logo';
$string['manage:orgmenu:title'] = 'Schulspezifisches Menü';
$string['manage:orgmenu:description'] = 'Alle hier angegebenen Menüeinträge werden dem Hauptmenü hinzugefügt. Bitte geben Sie die Einträge zeilenweise nach dem folgenden Format an:<br /><br />Titel|URL|Ziel|Benötigte Rolle(n)<br /><br />Beispiel: UnsereHomepage|http://www.ourhomepage.org|_blank|Teacher+Student<br /><br />Gültige Ziele: <i>leer</i> oder _blank<br />Gültige Rollen: <i>leer</i>, Manager, Teacher, Student, Parent';
$string['manage:profile:clickusername'] = '<strong>Hinweis:</strong> Sie können einige Profildaten ändern, indem Sie auf den Benutzernamen klicken!';
$string['manage:profile:invalidmail'] = 'Ungültige e-Mail angegeben!';
$string['manage:profile:tooshort'] = '{$a->fieldname} zu kurz, mindestens {$a->minchars} Zeichen erforderlich!';
$string['manage:selectfunction'] = 'Funktion wählen';
$string['manage:selectorganization'] = 'Schule wählen';
$string['manage:stats'] = 'Statistik';
$string['manage:stats:currentconsumption'] = 'Der aktuelle Verbrauch ist';
$string['manage:style:orgfiles:title'] = 'Bilder hochladen';
$string['manage:style:orgbanner:header'] = 'Organisationsbanner';
$string['manage:style:orgbanner:filearealabel'] = 'Sie können hier eine Bannergrafik hochladen, die im Boost-Theme als Header in allen Kursen und Kursbereichen angezeigt wird. Diese Grafik sollte etwa 2.200px * 1.200px aufweisen. Beachten Sie bitte, dass die Grafik in unterschiedlichen Seitenverhältnissen angezeigt wird und ggfs. Teile des Bildes abgeschnitten werden!';
$string['manage:style:orgfiles:header'] = 'Eigene Bilder für Styles';
$string['manage:style:orgfiles:filearealabel'] = 'Sie können Bilder hochladen, die Sie im Zusammenhang mit individuellen CSS nutzen können. Um das Bild zu referenzieren, hängen Sie den Dateinamen an folgende URL an: {$a->url}';
$string['manage:style:files:send'] = 'Bilder speichern';
$string['manage:subcats:forcategories'] = 'Felder für Kursbereiche und Kursname';
$string['manage:subcats:forcoursename'] = 'Felder nur für Kursname';
$string['manage:subcats:title'] = 'Kursbereichsstruktur';
$string['manage:subcats:description'] = 'Wenn Lehrer/innen Ihrer Schule Kurse erstellen, müssen sie gewisse Informationen angeben, um den Kurs korrekt in die Kursstruktur einzuordnen. Um diese Informationen auf bestimmte Optionen einzuschränken, können Sie diese in den folgenden Textfeldern angeben, wobei einzelne Auswahloptionen durch Zeilenumbrüche getrennt werden müssen. Die ersten beiden Ebenen resultieren in eigenen Unterkursbereichen in Ihrer Schule. Der Kursname generiert sich automatisch aus den angegebenen Informationen.';
$string['manage:subcats:subcat1'] = 'Erste Ebene';
$string['manage:subcats:subcat2'] = 'Zweite Ebene';
$string['manage:subcats:subcat3'] = 'Dritte Ebene';
$string['manage:subcats:subcat4'] = 'Vierte Ebene';
$string['manage:user_bunches:format:cards'] = 'Zugangskarten';
$string['manage:user_bunches:format:list'] = 'Liste';
$string['manage:users:title'] = 'Nutzer/innen Ihrer Schule';
$string['manage:users:description'] = 'Um die Rolle einzelner Nutzer/innen zu ändern wählen Sie diesen bitte aus dem Suchfeld aus und wählen Sie die Rolle.';
$string['manage:users:printcards'] = 'Zugangskarten drucken';
$string['manage:users:setpwreset'] = 'Passwort zurücksetzen';
$string['manage:users:setpwreset:description'] = 'Passwörter zurücksetzen funktioniert nur mit manuell erstellten Konten. Bei Microsoft-, MNet- oder anderen Konten hat die Funktion keinen Effekt. Diese Funktion setzt das Passwort auf den Code der Zugangskarte (rot geschrieben) der jeweiligen Nutzer/innen.';
$string['manage:users:setpwreset:failed'] = 'Fehler';
$string['manage:users:setpwreset:updated'] = 'Zurückgesetzt';
$string['manage:users:setrole'] = 'Rolle setzen';
$string['manage:users:searchforuser'] = 'Suchen und wählen Sie Nutzer/innen';

$string['manage:users'] = 'Nutzer/innen';
$string['manage:categories'] = 'Kategorien';
$string['manage:style'] = 'Stil';

$string['minimum_x_chars'] = 'Mehr als {$a} Buchstaben erforderlich!';

$string['missing_capability'] = 'Erforderliches Recht fehlt';

$string['n_a'] = 'n/a';
$string['name_too_short'] = 'Der Name ist zu kurz';

$string['or'] = 'oder';
$string['orgrole:role_already_in_use'] = 'Rolle wird bereits benutzt!';
$string['orgsizes:title'] = 'Dateisystemgröße';

$string['login:direct'] = 'melden Sie sich direkt an:';
$string['login:default'] = 'Standard Login Seite';
$string['login:external'] = 'Externer Login';
$string['login:external:description'] = 'Manche Schulen nutzen das MNet-Protokoll, um ein externes Moodle-System zur Authentifizierung zu verwenden. Falls Ihre Schule hier nicht gelistet ist, benötigen Sie diese Seite nicht.';
$string['login:failed'] = 'Login fehlgeschlagen';
$string['login:internal'] = 'Interner Login';
$string['login:network_btn'] = 'Eduvidual Verbund';
$string['login:network'] = 'Über Verbund';
$string['login:qrscan:btn'] = 'Login mit QR Code';
$string['login:qrscan:description'] = 'Falls Sie Ihr Passwort noch nicht geändert haben können Sie mit dem auf der Zugangskarte aufgedrucken QR Code einloggen.';

$string['preferences:defaultorg:title'] = 'Bevorzugte Schule';
$string['preferences:explevel'] = 'Moodle Erfahrungslevels';
$string['preferences:explevel:description'] = 'Sie können Moodle personalisieren, indem Sie aus den folgenden Erfahrungslevel auswählen. Als Folge Ihrer Auswahl wird die Menge an Funktionen für Sie reduziert.';
$string['preferences:questioncategories'] = 'Kernsystem-Fragenkategorien';
$string['preferences:questioncategories:description'] = 'Sie werden nur Fragen des zentralen Fragenpools aus jenen Kategorien sehen, die Sie hier gewählt haben!';
$string['preferences:request:moolevel'] = '<strong>Einstiegsfrage für Lehrer/innen</strong><br /><br />Bitte schätzen Sie Ihre Moodle-Kenntnisse ein. Auf Basis dieser Einschätzung versuchen wir die Moodle-Oberfläche für Sie zu optimieren. Je höher Sie Ihre Kenntnisse einschätzen, desto mehr Funktionen von Moodle werden Sie sehen. Bitte wählen Sie aus den "Moodle Erfahrungslevels" das für Sie Passendste aus! <br /><br />Ihre Auswahl wird sofort gespeichert. <strong>Nachdem Sie Ihre Auswahl getroffen haben</strong> kommen Sie mit einem Klick auf den "ok"-Button direkt zu Ihrem Dashboard.';
$string['preferences:selectbg:title'] = 'Hintergrund wählen';
$string['preferences:usemode:appmode'] = 'App Modus';
$string['preferences:usemode:desktopmode'] = 'Desktop Modus';
$string['preferences:usemode:switchmode'] = 'Wechsle zu';
$string['preferences:usemode:title'] = 'UI Modus';

$string['print'] = 'Drucken';

$string['privacy'] = 'Datenschutz';

$string['privacy:metadata:privacy:metadata:local_eduvidual_courseshow'] = 'Speichert für den app-Modus, welche Kurse der Kursliste angezeigt oder versteckt werden sollen.';
$string['privacy:metadata:privacy:metadata:local_eduvidual_orgid_userid'] = 'Speichert die Zuordnung und Rolle in verschiedenen Schulen.';
$string['privacy:metadata:privacy:metadata:local_eduvidual_userbunch'] = 'Wird von Organisationen verwendet, um Nutzer/innen für den Ausdruck von Zugangskarten zu gruppieren.';
$string['privacy:metadata:privacy:metadata:local_eduvidual_userbunch:orgid'] = 'Die Schulkennzahl';
$string['privacy:metadata:privacy:metadata:local_eduvidual_userbunch:bunch'] = 'Die Gruppenbezeichnung';
$string['privacy:metadata:privacy:metadata:local_eduvidual_userextra'] = 'Persönliche Zusatzeinstellungen';
$string['privacy:metadata:privacy:metadata:local_eduvidual_userextra:background'] = 'Der persönliche Hintergrund';
$string['privacy:metadata:privacy:metadata:local_eduvidual_userextra:backgroundcard'] = 'Der Hintergrund der Zugangskarte';
$string['privacy:metadata:privacy:metadata:local_eduvidual_userextra:defaultorg'] = 'Die standardmäßig ausgewählte Schule (sofern jemand in mehreren Schulen tätig ist)';
$string['privacy:metadata:privacy:metadata:local_eduvidual_userqcats'] = 'Die Kernsystem-Fragenkategorien, die angezeigt werden sollen.';
$string['privacy:metadata:privacy:metadata:local_eduvidual_usertoken'] = 'Nutzertoken für den automatischen Login';
$string['privacy:metadata:privacy:metadata:local_eduvidual_usertoken:token'] = 'Der Token';
$string['privacy:metadata:privacy:metadata:local_eduvidual_usertoken:created'] = 'Der Zeitpunkt der Erstellung des Tokens';
$string['privacy:metadata:privacy:metadata:local_eduvidual_usertoken:used'] = 'Der Zeitpunkt der Einlösung des Tokens';

$string['qrscan:cameratoobject'] = 'Richten Sie nun die Kamera auf den QR Code!';
$string['questioncategoryfilter:label'] = 'Kategoriefilter';

$string['register:individual'] = 'Als Einzelperson registrieren';
$string['register:org'] = 'Als Schule registrieren';

$string['registration:alreadyregistered'] = 'Bereits registriert!';
$string['registration:title'] = 'Registrierung';
$string['registration:description'] = 'Bitte starten Sie die Registrierung, indem Sie die Schulkennzahl eingeben. Dies löst eine e-Mail an die offizielle Mailadresse der Schule aus. In dieser e-Mail wird ein Token zugestellt, der für den Abschluss der Registrierung erforderlich ist.';
$string['registration:loginfirst'] = 'Bitte melden Sie sich an bevor Sie die Registrierung einer Schule starten!';
$string['registration:loginlink'] = 'Zur Anmeldeseite';
$string['registration:name'] = 'Name der Schule';
$string['registration:name:description'] = 'Frei wählbar, möglichst eindeutig wie bspw. "Hertha Firnberg Schulen" oder "HLW Deutschlandsberg". Die maximale Länge beträgt 30 Zeichen!';
$string['registration:token'] = 'Token';
$string['registration:stage1'] = 'Die Schulkennzahl ist korrekt. Sie können nun einen Token anfordern, der an die offizielle Mailadresse der Schule zugestellt wird.';
$string['registration:stage1:supportinfo'] = 'Ist die e-Mailadresse falsch? Bitte kontaktieren Sie <a href="mailto:{$a}" target="_blank">{$a}</a>!';
$string['registration:stage2'] = 'Bitte geben Sie den Token ein, der Ihnen an die offizielle Mailadresse zugestellt wurde:';
$string['registration:request'] = 'Token anfordern';
$string['registration:validate'] = 'Token validieren';
$string['registration:success'] = '<h3>Gratulation!</h3><p>Die Registrierung war erfolgreich! Sie können nun zum Kursbereich Ihrer Schule navigieren.</p>';

$string['restricted:title'] = 'Privater Bereich';
$string['restricted:description'] = '<p>Wir nehmen die Privatsphäre unserer Schulen und Nutzer/innen ernst. Deshalb verhindern wir den Zugriff auf Bereiche anderer Schulen, zu denen Sie nicht gehören.</p>';

$string['role:Administrator'] = 'Administrator/in';
$string['role:Manager'] = 'Manager/in';
$string['role:Parent'] = 'Erziehungsberechtige/r';
$string['role:Remove'] = 'Von Schule entfernen';
$string['role:Student'] = 'Schüler/in';
$string['role:Teacher'] = 'Lehrer/in';

$string['start_with_at'] = 'Starte mit einem  "@"-Zeichen';
$string['supportarea'] = 'Supportbereich';

$string['teacher:addfromcatalogue'] = 'Ressourcenkatalog';
$string['teacher:course:enrol'] = 'Nutzer/innen aufnehmen';
$string['teacher:course:gradings'] = 'Kursbewertungen öffnen';
$string['teacher:coursebasements:ofuser'] = 'Eigene Kurse als Vorlage';
$string['teacher:createcourse'] = 'Kurs erstellen';
$string['teacher:createcourse:here'] = 'Kurs hier erstellen';
$string['teacher:createmodule'] = 'Modul erstellen';
$string['teacher:createmodule:here'] = 'Modul hier erstellen';
$string['teacher:createmodule:missing_capability'] = 'Ihnen fehlt das Recht Module in diesem Kurs anzulegen!';
$string['teacher:createmodule:selectcourse'] = 'Kurs wählen';
$string['teacher:createmodule:selectmodule'] = 'Modul wählen';
$string['teacher:createmodule:selectsection'] = 'Kursabschnitt wählen';

$string['trashcategory:title'] = 'Kurskategorie für Papierkorb';
$string['trashcategory:description'] = 'Sie können eine Kurskategorie als Papierkorb angeben. Dieser wird täglich geleert.';

$string['user:categories:adminshowall'] = 'Alle Schulen';
$string['user:categories:adminshowmine'] = 'Nur meine Schulen';
$string['user:courselist:showhidden'] = 'Versteckte Kurse anzeigen/ausblenden';
$string['user:landingpage:description'] = 'Nach dem Login jedesmal automatisch zur aktuellen Seite wechseln.';
$string['user:landingpage:title'] = 'Startseite setzen';
$string['user:merge_accounts'] = 'Konten zusammenlegen';
$string['user:merge_accounts:cancel'] = 'Alle Loginmethoden behalten (nicht empfohlen!)';
$string['user:merge_accounts:description'] = 'Wir haben festgestellt, dass für Sie mehrere Konten mit derselben e-Mailadresse existieren. Dies liegt vermutlich an der Nutzung unterschiedlicher Loginmethoden und kann zur Verwirrung führen. Es wird empfohlen die Nutzerkonten zusammenzulegen.<br /><br /><strong>Es kann eine Zeit dauern, bis die Daten aller Accounts zusammengeführt wurden. Bitte unterbrechen Sie diesen Vorgang nicht. <u>Keinesfalls dürfen Sie die Seite neu laden oder von der Seite wegnavigieren</u>!</strong><br /><br />Bitte wählen Sie jene Loginmethode aus, die Sie zukünftig behalten möchten:';
$string['user:merge_accounts:ok:dashboard'] = 'Weiter zum Dashboard';
$string['user:merge_accounts:ok:description'] = 'Alles bestens - es gibt nur einen Account mit Ihrer e-Mailadresse!';
$string['user:merge_accounts:merge'] = 'Konten zusammenlegen';
$string['user:preference:editor:'] = 'Standardeditor';
$string['user:preference:editor:atto'] = 'Atto';
$string['user:preference:editor:tinymce'] = 'TinyMCE';
$string['user:preference:editor:textarea'] = 'Unformatierter Text';
$string['user:preference:editor:title'] = 'Bevorzugter Texteditor';
$string['user:support:showbox'] = 'Hilfe anfordern!';

$string['your_learning_environment'] = 'deine persönliche Lernplattform';
