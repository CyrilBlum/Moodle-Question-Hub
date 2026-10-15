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
$string['questionhub:import'] = 'Question Hub Pakete in Fragensammlungen importieren';
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
$string['package_visibility_label'] = 'Sichtbarkeit';
$string['package_visibility_public'] = 'Öffentlich';
$string['package_visibility_private'] = 'Privat – Zugriff nur nach Freigabe';
$string['private_package_badge'] = 'Privat';
$string['private_package_summary'] = 'Privates Fragenpaket. Es werden nur begrenzte Angaben angezeigt.';
$string['request_access'] = 'Zugriff anfragen';
$string['package_author'] = 'Autor';
$string['questions_count'] = '{$a} Fragen';
$string['preview_package'] = 'Vorschau';
$string['import_package'] = 'In Fragensammlung importieren';
$string['target_category'] = 'Zielkategorie';
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
$string['question_bank_context'] = 'Fragensammlung';
$string['question_bank_course'] = 'Kurs: {$a}';
$string['question_bank_course_category'] = 'Kursbereich: {$a}';
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
$string['no_packages'] = 'Sie haben noch keine Pakete veröffentlicht.';
$string['withdraw_package'] = 'Paket zurückziehen';
$string['withdraw_version'] = 'Version zurückziehen';
$string['withdraw_package_confirm'] = 'Dieses Paket mit allen Versionen aus dem Hub zurückziehen? Bereits in Moodle importierte Fragen bleiben erhalten.';
$string['withdraw_version_confirm'] = 'Diese Version zurückziehen? Die Versionsnummer kann nie wieder verwendet werden.';
$string['withdraw_done'] = 'Aus dem Question Hub zurückgezogen.';
$string['preview_on'] = 'mit Vorschau';
$string['preview_off'] = 'ohne Vorschau';
$string['publish_category_heading'] = 'Kategorien und Hub-Synchronisation';
$string['publish_category_button'] = 'Kategorie veröffentlichen';
$string['edit_settings'] = 'Einstellungen';
$string['save_settings'] = 'Einstellungen speichern';
$string['settings_saved'] = 'Einstellungen im Question Hub gespeichert.';
$string['version_immutable_note'] = 'Versionsnummern sind unveränderlich: Für geänderte Inhalte veröffentlichen Sie eine neue Version oder ziehen eine bestehende zurück.';
$string['incl_subcategories'] = 'inkl. Unterkategorien';
$string['hub_tab_browse'] = 'Durchsuchen';
$string['hub_tab_library'] = 'Meine Bibliothek';
$string['hub_tab_my_packages'] = 'Meine Pakete';
$string['library_empty'] = 'In diesem Kurs ist derzeit kein Question-Hub-Paket mit einer Kategorie synchronisiert.';
$string['my_packages'] = 'Meine Pakete (erstellt oder mitbearbeitet)';
$string['package_role_admin'] = 'Admin';
$string['package_role_editor'] = 'Bearbeiter/in';
$string['library_restore'] = 'Ältere Version wiederherstellen';
$string['library_restore_help'] = 'Importiert eine ältere Version dieses Pakets erneut in die verknüpfte Kategorie. Sie sehen zuerst eine Vorschau; seither geänderte Fragen werden auf diese Version zurückgesetzt, später hinzugefügte Fragen bleiben. Danach bietet die Kategorie wieder die neueste Version zum Holen an.';
$string['library_restore_version'] = 'Wiederherzustellende Version';
$string['library_restore_button'] = 'Wiederherstellung prüfen';
$string['open_hub_package'] = 'Im Hub öffnen';
$string['questionbankroot'] = 'Wurzel der Fragensammlung';
$string['target_category_help'] = 'Die Zahl zeigt, wie viele Fragen direkt in dieser Kategorie liegen. Fragen in Unterkategorien sind nicht mitgezählt. Eine Umbenennung ist nur bei einer leeren Kategorie ohne Unterkategorien möglich.';
$string['category_placement'] = 'Name und Inhalt der Zielkategorie';
$string['rename_empty_target'] = 'Leere Zielkategorie umbenennen zu';
$string['rename_empty_target_help'] = 'Der Name der ausgewählten Kategorie wird durch den Namen des obersten Paketordners ersetzt. Das geht nur, wenn die Kategorie leer ist und keine Unterkategorien hat.';
$string['keep_target_name'] = 'Name der Zielkategorie beibehalten';
$string['keep_target_name_description'] = 'Die Fragen kommen in diese Kategorie. Unterkategorien aus dem Paket werden darunter angelegt.';
$string['keep_target_name_help'] = 'Die ausgewählte Kategorie behält ihren Namen. Fragen aus dem obersten Paketordner kommen direkt hierhin. Paket-Unterkategorien werden darunter angelegt.';
$string['import_update_changed'] = 'Neue Fragen hinzufügen und geänderte aktualisieren';
$string['import_update_changed_help'] = 'Neue Fragen werden hinzugefügt. Geänderte Fragen aus einem früheren Import erhalten eine neue Version; unveränderte werden übersprungen. Fragen ohne Kennung werden anhand von Name und Kategorie erkannt. Nach einer Umbenennung gelten sie als neu. Ohne Häkchen werden alle Fragen als neue Kopien angelegt.';
$string['import_preview_button'] = 'Änderungen ansehen';
$string['import_preview_heading'] = 'Vorschau des Imports';
$string['import_preview_explanation'] = 'Es wurden keine Fragen oder Kategorien geändert. Beim Bestätigen wird der Import erneut geprüft.';
$string['import_preview_new'] = 'neu';
$string['import_preview_updated'] = 'zu aktualisieren';
$string['import_preview_unchanged'] = 'unverändert';
$string['import_preview_new_heading'] = 'Diese Fragen werden hinzugefügt';
$string['import_preview_updated_heading'] = 'Diese Fragen werden aktualisiert';
$string['import_preview_uncertain'] = 'Bisheriger Inhalt konnte nicht verglichen werden';
$string['import_preview_unchanged_heading'] = 'Diese Fragen werden übersprungen';
$string['import_preview_confirm'] = 'Diese Änderungen importieren';
$string['import_preview_cancel'] = 'Zurück zu den Paketen';
$string['import_result_summary'] = 'Import abgeschlossen: {$a->added} hinzugefügt, {$a->updated} aktualisiert, {$a->unchanged} unverändert.';

// Synchronisierungs- und Aktualisierungs-Status
$string['sync_column_title'] = 'Hub';
$string['badge_changed'] = 'Geändert';
$string['badge_changed_help'] = 'Diese Frage wurde seit der letzten Veröffentlichung auf dem Hub geändert und noch nicht übertragen.';
$string['badge_new'] = 'Neu';
$string['badge_new_help'] = 'Diese Frage wurde nach der letzten Veröffentlichung neu erstellt und noch nicht auf den Hub übertragen.';
$string['badge_synced'] = 'Synchronisiert';
$string['badge_synced_help'] = 'Entspricht der auf dem Question Hub veröffentlichten Version.';
$string['badge_unknown'] = 'Unbekannt';
$string['badge_unknown_help'] = 'Das Paket wurde vor der Versionserfassung veröffentlicht.';
$string['update_package_button'] = 'Paket aktualisieren';
$string['published_badge'] = 'Veröffentlicht';
$string['error_update_source'] = 'Die Kategorie stimmt nicht mit dem angegebenen Paket überein.';
$string['error_update_version'] = 'Die neue Versionsnummer muss höher sein als die bisherige Version v{$a}.';
$string['publication_tracking_failed'] = 'Versionserfassung für die Veröffentlichung konnte nicht gespeichert werden.';
$string['duplicates_found'] = '{$a} entfernbaren Duplikatfragen gefunden.';
$string['review_duplicates'] = 'Duplikate prüfen';
$string['duplicate_review_heading'] = 'Duplikate löschen bestätigen';
$string['duplicate_review_intro'] = '{$a} Duplikatfragen werden gelöscht; die älteste identische Frage bleibt erhalten.';
$string['duplicate_delete_confirm'] = '{$a} Duplikatfragen löschen? Dieser Vorgang kann nicht rückgängig gemacht werden.';
$string['delete_duplicates'] = '{$a} Duplikatfragen löschen';
$string['duplicates_deleted'] = '{$a} Duplikatfragen gelöscht.';
$string['duplicate_keep'] = 'Beibehaltene Frage';
$string['duplicate_remove'] = 'Zu löschende Fragen';
$string['duplicate_category'] = 'Kategorie';
$string['duplicate_question_id'] = 'Frage-ID';
$string['duplicate_entry_id'] = 'Fragebank-Eintrag-ID';
$string['duplicate_question_version'] = 'Frageversion';
$string['duplicate_created'] = 'Erstellt';
$string['duplicate_modified'] = 'Zuletzt geändert';
$string['duplicate_package'] = 'Letztes Question-Hub-Paket dieser Kategorie';
$string['duplicate_package_version'] = 'Paketversion';
$string['duplicate_package_activity'] = 'Paketaktivität';
$string['duplicate_suggested_action'] = 'Vorgeschlagene Aktion';
$string['duplicate_question'] = 'Frage';
$string['duplicate_dates'] = 'Erstellt / geändert';
$string['duplicate_preview'] = 'Vorschau';
$string['duplicate_question_preview'] = 'Fragevorschau';
$string['duplicate_matching_questions'] = 'Übereinstimmende Fragen';
$string['duplicate_keep_badge'] = 'Behalten';
$string['duplicate_delete_badge'] = 'Löschen';
$string['review_duplicates_count'] = '{$a} Duplikate prüfen';
$string['open_category_questions'] = 'Diese Kategorie in der Fragensammlung öffnen';

$string['unlink_category_button'] = 'Verknüpfung aufheben';
$string['categories_unlinked'] = 'Die Verknüpfung für «{$a->name}» und {$a->count} Kategorie(n) wurde aufgehoben.';
$string['category_unlinked_status'] = 'Verknüpfung entfernt';
$string['category_unlinked_status_help'] = 'Diese Kategorie war mit einem Hub-Paket verknüpft; die Verknüpfung wurde entfernt. Die Fragen bleiben unverändert, und das Paket im Hub ist nicht betroffen. Sie können die Kategorie als neues Paket veröffentlichen oder durch einen erneuten Import wieder verknüpfen.';

$string['local_changes_status'] = '{$a} lokale Änderungen';
$string['remote_update_status'] = 'v{$a} im Hub';
$string['sync_in_sync'] = 'Synchron';
$string['linked_to_category'] = 'Verknüpft mit';
$string['sync_source_label'] = 'von hier veröffentlicht';
$string['sync_ahead_help'] = 'Fragen in dieser Kategorie, die seit der verknüpften Hub-Version geändert wurden';
$string['sync_behind_help'] = 'Im Hub gibt es eine neuere Version als die mit dieser Kategorie verknüpfte';
$string['push_button'] = 'Push';
$string['push_button_help'] = 'Lokale Änderungen als neue Version von {$a} veröffentlichen';
$string['pull_button'] = 'Pull v{$a}';
$string['pull_button_help'] = 'v{$a} in diese Kategorie importieren: neue Fragen hinzufügen und geänderte aktualisieren';
$string['pull_preview_button'] = 'Pull (Vorschau)';
$string['pull_preview_help'] = 'Zeigt, was ein Pull hinzufügen oder aktualisieren würde, ohne die Fragensammlung zu ändern';
$string['error_package_withdrawn'] = 'Das Paket wurde aus dem Hub zurückgezogen. {$a}';
$string['withdrawn_on_hub'] = 'Im Hub zurückgezogen';
$string['missing_on_hub'] = 'Nicht im Hub';
$string['missing_on_hub_help'] = 'Der Hub bietet dieses Paket nicht mehr an. Die Fragen in dieser Kategorie bleiben unverändert; veröffentlichen Sie die Kategorie erneut oder heben Sie die Verknüpfung auf.';
$string['republish_button'] = 'Erneut veröffentlichen';
$string['republish_button_help'] = 'Diese Kategorie erneut als Paket veröffentlichen (neue Versionsnummer nötig)';
$string['category_delete_button'] = 'Kategorie löschen';
$string['category_delete_confirm'] = 'Kategorie «{$a->name}» mit {$a->subcategories} Unterkategorie(n) und {$a->questions} Frage(n) endgültig löschen? Ein Paket im Hub bleibt bestehen. Dieser Vorgang kann nicht rückgängig gemacht werden.';
$string['category_deleted'] = '«{$a->name}» wurde gelöscht ({$a->categories} Kategorie(n), {$a->questions} Frage(n)).';
$string['category_delete_in_use'] = '«{$a}» wurde nicht gelöscht: Fragen daraus werden in Tests verwendet oder Zufallsfragen greifen auf die Kategorie zu. Entfernen Sie diese Verwendungen zuerst.';
$string['category_delete_failed'] = '«{$a}» wurde nicht gelöscht: Nicht alle Fragen konnten entfernt werden. Es wurde nichts geändert.';
$string['category_naming_note'] = '<strong>Achtung bei Kategorien, die mit dem Question Hub verknüpft sind</strong>
<ul class="mb-0">
<li>Beim Aktualisieren werden Fragen über den Pfad ihrer Unterkategorie und den Fragenamen wiedererkannt. Benennen Sie Unterkategorien eines verknüpften Pakets nicht um und verschieben Sie sie nicht: Sonst werden bestehende Fragen bei der nächsten Version als neu importiert (Duplikate).</li>
<li>Die oberste verknüpfte Kategorie dürfen Sie umbenennen; ihr Name gehört nicht zum Pfad.</li>
<li>Ist eine Umbenennung nötig: zuerst in der Quellkategorie ändern, neue Version veröffentlichen und dieselbe Änderung in allen importierten Kopien vornehmen, bevor diese aktualisiert werden.</li>
<li>Verwenden Sie kurze, eindeutige und dauerhafte Namen ohne Zusätze wie Kürzel, «alt», «neu» oder Versionsnummern (die Version verwaltet der Hub).</li>
<li>Vermeiden Sie gleichnamige Kategorien auf derselben Ebene.</li>
<li>Unterkategorie abspalten: aus der Paketkategorie verschieben und separat veröffentlichen. Bei der nächsten Version des Pakets erscheinen ihre Fragen als entfernt; Moodles, die das Paket importiert haben, behalten sie.</li>
</ul>';
$string['import_conflicts_found'] = 'Einige Fragen wurden im Hub und hier geändert. Entscheiden Sie pro Frage, welche Fassung gelten soll.';
$string['import_preview_conflicts'] = 'Konflikte';
$string['import_preview_conflicts_heading'] = 'Konflikte: im Hub und lokal geändert';
$string['import_preview_conflicts_help'] = 'Die lokale Fassung bleibt standardmässig erhalten. Übernehmen Sie die Hub-Fassung, bleibt Ihre Fassung im Versionsverlauf der Frage erhalten und kann wiederhergestellt werden.';
$string['conflict_keep'] = 'Lokale Fassung behalten';
$string['conflict_hub'] = 'Hub-Fassung übernehmen';
$string['conflict_both'] = 'Beide behalten (Hub-Fassung als Kopie «… (Hub v{$a})»)';
$string['error_conflicts_unresolved'] = 'Für Fragen mit Konflikten fehlt eine Entscheidung. Öffnen Sie die Vorschau und wählen Sie pro Frage eine Fassung.';
$string['pull_before_push'] = 'Im Hub gibt es eine neuere Version: zuerst holen, dann veröffentlichen.';
$string['error_release_notes_required'] = 'Bitte beschreiben Sie die Änderungen dieser Version.';
$string['push_heading'] = 'Neue Version von «{$a}»';
$string['push_current_version'] = 'aktuelle Version im Hub: v{$a}';
$string['push_source_category'] = 'Quellkategorie: {$a}';
$string['push_files_heading'] = 'Geänderte Fragen';
$string['push_files_summary'] = '{$a} Frage(n) geändert';
$string['push_changes_unknown'] = 'Die Änderungen seit der letzten Version lassen sich nicht ermitteln (keine Momentaufnahme vorhanden). Sie können trotzdem eine neue Version veröffentlichen.';
$string['push_no_changes'] = 'Seit der letzten Version wurden keine Fragen geändert. Sie können trotzdem eine neue Version veröffentlichen, z. B. nach einer rückgängig gemachten Änderung.';
$string['push_status_added'] = 'Neu';
$string['push_status_modified'] = 'Geändert';
$string['push_status_removed'] = 'Entfernt';
$string['push_diff_too_large'] = 'Diese Frage ist zu gross für eine zeilenweise Anzeige.';
$string['release_notes_label'] = 'Versionshinweis';
$string['release_notes_placeholder'] = 'Was hat sich geändert? z. B. «Tippfehler in Aufgabe 3 korrigiert, zwei Fragen zu Regression ergänzt»';
$string['push_version_help'] = 'Muss höher sein als v{$a}. Kleine Korrekturen: letzte Stelle erhöhen; neue Fragen: mittlere Stelle.';
$string['push_settings_note'] = 'Titel, Beschreibung, Lizenz und weitere Einstellungen ändern Sie in der Kategorienliste (Zahnrad).';
$string['push_submit'] = 'Version veröffentlichen';
$string['push_new_version_link'] = 'Neue Version veröffentlichen';
$string['push_same_content'] = '{$a} weitere Frage(n) haben eine neue Versionsnummer, aber unveränderten Inhalt (z. B. rückgängig gemachte Änderung); sie werden nicht aufgeführt.';
$string['inherited_link_status'] = 'Teil von «{$a}»';
$string['inherited_link_status_help'] = 'Diese Unterkategorie gehört zum Paket ihrer übergeordneten Kategorie. Veröffentlichen, Holen und Verknüpfung aufheben erfolgen dort. Zum Abspalten verschieben Sie die Unterkategorie aus der Paketkategorie und veröffentlichen sie separat.';
$string['error_unlink_inherited'] = 'Diese Unterkategorie gehört zum Paket ihrer übergeordneten Kategorie. Heben Sie die Verknüpfung dort auf oder verschieben Sie die Unterkategorie aus der Paketkategorie.';
