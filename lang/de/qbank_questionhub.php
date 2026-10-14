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
 * Swiss German strings for qbank_questionhub (strictly ss, zero German double-s).
 *
 * @package    qbank_questionhub
 * @copyright  2026 Cyril Wendl
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Question Hub';
$string['questionhub'] = 'Question Hub';
$string['browsehub'] = 'Question Hub durchsuchen';
$string['exportcategorytohub'] = 'Kategorie auf Hub exportieren';
$string['publishtohub'] = 'Auf Question Hub exportieren';
$string['publishselected'] = 'Ausgewählte auf Question Hub veröffentlichen';

// Einstellungen
$string['huburl'] = 'Hub-Basis-URL';
$string['huburl_desc'] = 'Die Basis-URL des Moodle Question Hub Marketplace (z. B. https://hub.cblum.ch).';
$string['clientid'] = 'OAuth 2.0 Client-ID';
$string['clientid_desc'] = 'Client-ID für die OAuth 2.0 PKCE-Authentifizierung mit dem Marketplace.';
$string['institutiontoken'] = 'Institutions-Zugriffstoken';
$string['institutiontoken_desc'] = 'Optionales institutionelles Personal Access Token (PAT) als Ausweichlösung für systemweite Abfragen.';
$string['allowteacherlinking'] = 'Individuelle Lehrpersonen-Kontoverknüpfung erlauben';
$string['allowteacherlinking_desc'] = 'Wenn aktiviert, können Lehrpersonen ihr persönliches Question Hub-Konto via OAuth 2.0 PKCE verknüpfen.';

// Berechtigungen
$string['questionhub:view'] = 'Question Hub Sammlungen ansehen und durchsuchen';
$string['questionhub:import'] = 'Question Hub Pakete in Kurs-Fragensammlungen importieren';
$string['questionhub:export'] = 'Fragen oder Kategorien als Question Hub Pakete exportieren';
$string['questionhub:publish'] = 'Fragenpakete direkt auf Question Hub veröffentlichen';
$string['questionhub:manage'] = 'Question Hub Konfiguration verwalten';

// Browser-Oberfläche
$string['search_placeholder'] = 'Sammlungen nach Stichwort, Thema oder Fragentyp durchsuchen...';
$string['filter_subject'] = 'Fach';
$string['filter_level'] = 'Kursstufe';
$string['filter_canton'] = 'Kanton';
$string['filter_grade'] = 'Klassenstufe';
$string['all_subjects'] = 'Alle Fächer';
$string['all_levels'] = 'Alle Kursstufen';
$string['all_cantons'] = 'Alle Kantone';
$string['all_grades'] = 'Alle Klassenstufen';
$string['search_button'] = 'Suchen';
$string['reset_filters'] = 'Zurücksetzen';
$string['no_packages_found'] = 'Keine Pakete gefunden, die den Kriterien entsprechen.';
$string['package_version'] = 'Version';
$string['package_license'] = 'Lizenz';
$string['package_author'] = 'Autor';
$string['questions_count'] = '{$a} Fragen';
$string['preview_package'] = 'Vorschau';
$string['import_package'] = 'In Kurs importieren';
$string['target_category'] = 'Ziel-Fragenkategorie';
$string['import_preserve_hierarchy'] = 'Kategoriehierarchie aus Paket beibehalten';
$string['import_confirm_title'] = 'Question Hub Paket importieren';
$string['import_button'] = 'Fragen importieren';
$string['import_success'] = 'Erfolgreich {$a} Fragen in Ihre Fragensammlung importiert!';
$string['export_success'] = 'Paket erfolgreich mit Version {$a} veröffentlicht!';

// Kontoverknüpfung
$string['account_linked_as'] = 'Mit Question Hub verknüpft als: {$a}';
$string['account_not_linked'] = 'Ihr Konto ist noch nicht mit Question Hub verknüpft.';
$string['link_account_button'] = 'Mit Question Hub verbinden';
$string['disconnect_account_button'] = 'Trennen';
$string['disconnect_success'] = 'Die Verbindung zu Question Hub wurde erfolgreich getrennt.';
$string['pat_token_label'] = 'Oder Personal Access Token (PAT) eingeben:';
$string['save_token'] = 'Token speichern';
$string['token_saved'] = 'Personal Access Token erfolgreich gespeichert.';

// Veröffentlichungs-Oberfläche
$string['publish_title'] = 'Auf Question Hub veröffentlichen';
$string['package_id'] = 'Paket-Identifikator (Reverse-Domain, z. B. ch.gym.inf.trees)';
$string['package_title_label'] = 'Pakettitel';
$string['package_summary_label'] = 'Zusammenfassung / Beschreibung';
$string['package_subject_label'] = 'Schweizer Gymnasialfach';
$string['package_level_label'] = 'Kursstufe';
$string['package_canton_label'] = 'Zielkanton';
$string['package_grades_label'] = 'Zielstufen (mehrere mit Strg/Cmd-Klick)';
$string['package_version_label'] = 'Release-Version (SemVer, z. B. 1.0.0)';
$string['package_license_label'] = 'Lizenz';
$string['publish_button'] = 'Auf Question Hub veröffentlichen';
$string['download_xml_button'] = 'Als Moodle-XML-Datei herunterladen';

// Vorabprüfung & Fehler
$string['preflight_validation'] = 'Vorabprüfung des Fragensammlungspakets';
$string['preflight_passed'] = 'Paket hat alle Kompatibilitätsprüfungen bestanden.';
$string['preflight_warnings_found'] = 'Vorabprüfungshinweise gefunden (Import ist weiterhin möglich):';
$string['preflight_errors_found'] = 'Vorabprüfung fehlgeschlagen mit blockierenden Fehlern:';
$string['error_xxe_detected'] = 'Sicherheitsfehler: XML External Entity (XXE) oder DOCTYPE-Deklaration entdeckt. Paket wurde abgewiesen.';
$string['error_invalid_xml'] = 'Ungültige XML-Syntax im Fragenpaket.';
$string['error_network'] = 'Netzwerkfehler bei der Kommunikation mit Question Hub: {$a}';
$string['error_unauthorized'] = 'Authentifizierung fehlgeschlagen. Bitte überprüfen Sie Ihr Token oder verknüpfen Sie Ihr Konto.';
$string['error_forbidden'] = 'Zugriff auf Question Hub verweigert.';
$string['error_notfound'] = 'Die angeforderte Ressource wurde auf Question Hub nicht gefunden.';
$string['error_import_failed'] = 'Import bei der Moodle-Fragenerstellung fehlgeschlagen: {$a}';
$string['error_empty_category'] = 'Die gewählte Kategorie enthält keine exportierbaren Fragen.';

// Datenschutz
$string['privacy:metadata'] = 'Das Question Hub Plugin speichert Benutzer-Zugriffstoken und Verlaufsprotokolle.';
$string['privacy:metadata:tokens:tableexplanation'] = 'Speichert Benutzer-OAuth-Token und verknüpftes Marketplace-Profil.';
$string['privacy:metadata:tokens:userid'] = 'Moodle-Benutzer-ID.';
$string['privacy:metadata:tokens:access_token'] = 'Marketplace-Zugriffstoken.';
$string['privacy:metadata:tokens:hub_user_id'] = 'Question Hub Benutzer-UUID.';
$string['privacy:metadata:tokens:hub_email'] = 'Question Hub E-Mail-Adresse.';
$string['privacy:metadata:tokens:hub_name'] = 'Question Hub Anzeigename.';
$string['privacy:metadata:tokens:timecreated'] = 'Zeitpunkt der Token-Erstellung.';
$string['privacy:metadata:history:tableexplanation'] = 'Verlaufsprotokoll von Import- und Veröffentlichungsaktionen.';
$string['privacy:metadata:history:userid'] = 'Moodle-Benutzer-ID.';
$string['privacy:metadata:history:action'] = 'Ausgeführte Aktion (Import oder Veröffentlichung).';
$string['privacy:metadata:history:package_id'] = 'Question Hub Paket-Identifikator.';
$string['privacy:metadata:history:package_version'] = 'Question Hub Paket-Version.';
$string['privacy:metadata:history:timecreated'] = 'Zeitpunkt der Aktion.';
$string['privacy:metadata:marketplace:explanation'] = 'Question Hub verbindet sich mit dem externen Question Hub Marketplace, um Fragenpakete zu suchen, herunterzuladen und zu veröffentlichen.';
$string['privacy:metadata:marketplace:email'] = 'Zur Kontoverknüpfung übermittelte E-Mail-Adresse.';
$string['package_preview_label'] = 'Öffentliche Vorschau der Fragen im Hub anzeigen';
$string['package_preview_help'] = 'Musterlösungen und Feedback werden nie angezeigt. Ohne Vorschau sehen Besucher nur die Beschreibung.';
$string['error_oauth_failed'] = 'Die Verknüpfung mit Ihrem Question-Hub-Konto ist fehlgeschlagen: {$a}. Bitte versuchen Sie es erneut oder verwenden Sie ein Personal Access Token.';
$string['my_packages'] = 'Meine veröffentlichten Pakete';
$string['no_packages'] = 'Sie haben noch keine Pakete veröffentlicht.';
$string['withdraw_package'] = 'Paket zurückziehen';
$string['withdraw_version'] = 'Version zurückziehen';
$string['withdraw_package_confirm'] = 'Dieses Paket mit allen Versionen aus dem Hub zurückziehen? Bereits in Moodle importierte Fragen bleiben erhalten.';
$string['withdraw_version_confirm'] = 'Diese Version zurückziehen? Die Versionsnummer kann nie wieder verwendet werden.';
$string['withdraw_done'] = 'Aus dem Question Hub zurückgezogen.';
$string['preview_on'] = 'mit Vorschau';
$string['preview_off'] = 'ohne Vorschau';
$string['publish_category_heading'] = 'Ganze Kategorie veröffentlichen';
$string['publish_category_button'] = 'Kategorie veröffentlichen';
$string['edit_settings'] = 'Einstellungen';
$string['save_settings'] = 'Einstellungen speichern';
$string['settings_saved'] = 'Einstellungen im Question Hub gespeichert.';
$string['version_immutable_note'] = 'Versionsnummern sind unveränderlich: Für geänderte Inhalte veröffentlichen Sie eine neue Version oder ziehen eine bestehende zurück.';
$string['incl_subcategories'] = 'inkl. Unterkategorien';
