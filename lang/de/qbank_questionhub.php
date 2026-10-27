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
 * @copyright  2026 Cyril Blum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['account_linked_as'] = 'Mit Question Hub verknüpft als: {$a}';
$string['account_linking_disabled'] = 'Die Administration hat das Verknüpfen von Question-Hub-Konten ausgeschaltet. Sie können öffentliche Pakete durchsuchen und importieren.';
$string['account_not_linked'] = 'Ihr Konto ist noch nicht mit Question Hub verknüpft.';
$string['all_cantons'] = 'Alle Kantone';
$string['all_grades'] = 'Alle Klassenstufen';
$string['all_levels'] = 'Alle Kursstufen';
$string['all_subjects'] = 'Alle Fächer';
$string['allowteacherlinking'] = 'Individuelle Lehrpersonen-Kontoverknüpfung erlauben';
$string['allowteacherlinking_desc'] = 'Wenn aktiviert, können Lehrpersonen ihr persönliches Question Hub-Konto via OAuth 2.0 PKCE verknüpfen.';
$string['badge_changed'] = 'Geändert';
$string['badge_changed_help'] = 'Diese Frage wurde seit der letzten Veröffentlichung auf dem Hub geändert und noch nicht übertragen.';
$string['badge_new'] = 'Neu';
$string['badge_new_help'] = 'Diese Frage wurde nach der letzten Veröffentlichung neu erstellt und noch nicht auf den Hub übertragen.';
$string['badge_synced'] = 'Verknüpft';
$string['badge_synced_help'] = 'Entspricht der auf dem Question Hub veröffentlichten Version.';
$string['badge_unknown'] = 'Unbekannt';
$string['badge_unknown_help'] = 'Das Paket wurde vor der Versionserfassung veröffentlicht.';
$string['browsehub'] = 'Question Hub durchsuchen';
$string['categories_unlinked'] = 'Die Verknüpfung für «{$a->name}» und {$a->count} Kategorie(n) wurde aufgehoben.';
$string['category_delete_button'] = 'Kategorie löschen';
$string['category_delete_confirm'] = 'Kategorie «{$a->name}» mit {$a->subcategories} Unterkategorie(n) und {$a->questions} Frage(n) endgültig löschen? Ein Paket im Hub bleibt bestehen. Dieser Vorgang kann nicht rückgängig gemacht werden.';
$string['category_delete_failed'] = '«{$a}» wurde nicht gelöscht: Nicht alle Fragen konnten entfernt werden. Es wurde nichts geändert.';
$string['category_delete_in_use'] = '«{$a}» wurde nicht gelöscht: Fragen daraus werden in Tests verwendet oder Zufallsfragen greifen auf die Kategorie zu. Entfernen Sie diese Verwendungen zuerst.';
$string['category_deleted'] = '«{$a->name}» wurde gelöscht ({$a->categories} Kategorie(n), {$a->questions} Frage(n)).';
$string['category_naming_note'] = '<strong>Achtung bei Kategorien, die mit dem Question Hub verknüpft sind</strong>
<ul class="mb-0">
<li>Beim Aktualisieren werden Fragen über den Pfad ihrer Unterkategorie und den Fragenamen wiedererkannt. Benennen Sie Unterkategorien eines verknüpften Pakets nicht um und verschieben Sie sie nicht: Sonst werden bestehende Fragen bei der nächsten Version als neu importiert (Duplikate).</li>
<li>Die oberste verknüpfte Kategorie dürfen Sie umbenennen; ihr Name gehört nicht zum Pfad.</li>
<li>Ist eine Umbenennung nötig: zuerst in der Quellkategorie ändern, neue Version veröffentlichen und dieselbe Änderung in allen importierten Kopien vornehmen, bevor diese aktualisiert werden.</li>
<li>Verwenden Sie kurze, eindeutige und dauerhafte Namen ohne Zusätze wie Kürzel, «alt», «neu» oder Versionsnummern (die Version verwaltet der Hub).</li>
<li>Vermeiden Sie gleichnamige Kategorien auf derselben Ebene.</li>
<li>Unterkategorie abspalten: aus der Paketkategorie verschieben und separat veröffentlichen. Bei der nächsten Version des Pakets erscheinen ihre Fragen als entfernt; Moodles, die das Paket importiert haben, behalten sie.</li>
</ul>';
$string['category_placement'] = 'Name und Inhalt der Zielkategorie';
$string['category_unlinked_status'] = 'Verknüpfung entfernt';
$string['category_unlinked_status_help'] = 'Diese Kategorie war mit einem Hub-Paket verknüpft; die Verknüpfung wurde entfernt. Die Fragen bleiben unverändert, und das Paket im Hub ist nicht betroffen. Sie können die Kategorie als neues Paket veröffentlichen oder durch einen erneuten Import wieder verknüpfen.';
$string['clientid'] = 'OAuth 2.0 Client-ID';
$string['clientid_desc'] = 'Client-ID für die OAuth 2.0 PKCE-Authentifizierung mit dem Marketplace.';
$string['conflict_both'] = 'Beide behalten (Frage aus dem Hub als Kopie «… (Hub v{$a})»)';
$string['conflict_hub'] = 'Frage wie im Hub übernehmen';
$string['conflict_keep'] = 'Frage wie in diesem Moodle behalten';
$string['delete_duplicates'] = '{$a} Duplikatfragen löschen';
$string['diff_collapse_all'] = 'Alle zuklappen';
$string['diff_expand_all'] = 'Alle aufklappen';
$string['diff_moved'] = 'verschoben von «{$a->from}» nach «{$a->to}»';
$string['diff_moved_only'] = 'Inhalt unverändert: Die Frage wurde nur in eine andere Kategorie verschoben.';
$string['diff_path_root'] = 'oberste Ebene des Pakets';
$string['diff_removed_no_content'] = 'Der Inhalt dieser gelöschten Frage ist weder in diesem Moodle noch in der veröffentlichten Version auf dem Hub verfügbar.';
$string['diff_removed_unknown'] = 'Gelöschte Frage (Eintrag {$a})';
$string['diff_rename_help'] = 'Umbenannt oder verschoben: inhaltlich dieselbe Frage wie bisher.';
$string['diff_renamed_from'] = 'früher «{$a}»';
$string['diff_show_unchanged'] = '{$a} unveränderte Zeilen anzeigen';
$string['diff_side_current'] = 'Frage aktuell in diesem Moodle';
$string['diff_side_hub'] = 'Frage im Hub, Version {$a}';
$string['diff_side_local'] = 'Frage in diesem Moodle';
$string['diff_side_published'] = 'veröffentlichte Version {$a}';
$string['diff_side_synced'] = 'Frage in der verknüpften Version {$a}';
$string['disconnect_account_button'] = 'Trennen';
$string['disconnect_success'] = 'Die Verbindung zu Question Hub wurde erfolgreich getrennt.';
$string['download_xml_button'] = 'Als Moodle-XML-Datei herunterladen';
$string['duplicate_category'] = 'Kategorie';
$string['duplicate_created'] = 'Erstellt';
$string['duplicate_dates'] = 'Erstellt / geändert';
$string['duplicate_delete_badge'] = 'Löschen';
$string['duplicate_delete_confirm'] = '{$a} Duplikatfragen löschen? Dieser Vorgang kann nicht rückgängig gemacht werden.';
$string['duplicate_entry_id'] = 'Fragebank-Eintrag-ID';
$string['duplicate_keep'] = 'Beibehaltene Frage';
$string['duplicate_keep_badge'] = 'Behalten';
$string['duplicate_matching_questions'] = 'Übereinstimmende Fragen';
$string['duplicate_modified'] = 'Zuletzt geändert';
$string['duplicate_package'] = 'Letztes Question-Hub-Paket dieser Kategorie';
$string['duplicate_package_activity'] = 'Paketaktivität';
$string['duplicate_package_version'] = 'Paketversion';
$string['duplicate_preview'] = 'Vorschau';
$string['duplicate_question'] = 'Frage';
$string['duplicate_question_id'] = 'Frage-ID';
$string['duplicate_question_preview'] = 'Fragevorschau';
$string['duplicate_question_version'] = 'Frageversion';
$string['duplicate_remove'] = 'Zu löschende Fragen';
$string['duplicate_review_heading'] = 'Duplikate löschen bestätigen';
$string['duplicate_review_intro'] = '{$a} Duplikatfragen werden gelöscht; die älteste identische Frage bleibt erhalten.';
$string['duplicate_suggested_action'] = 'Vorgeschlagene Aktion';
$string['duplicates_deleted'] = '{$a} Duplikatfragen gelöscht.';
$string['duplicates_found'] = '{$a} entfernbaren Duplikatfragen gefunden.';
$string['edit_settings'] = 'Einstellungen';
$string['error_conflicts_unresolved'] = 'Für Fragen mit Konflikten fehlt eine Entscheidung. Öffnen Sie die Vorschau und wählen Sie pro Frage, welche gilt.';
$string['error_empty_category'] = 'Die gewählte Kategorie enthält keine exportierbaren Fragen.';
$string['error_forbidden'] = 'Zugriff auf Question Hub verweigert.';
$string['error_fork_link'] = 'Diese Kategorie ist nicht mehr mit dem Paket verknüpft.';
$string['error_fork_relink'] = 'Die Variante {$a} existiert im Hub, aber diese Kategorie konnte nicht damit verknüpft werden. Legen Sie die Variante erneut mit dieser Paket-ID an, um sie zu verknüpfen:';
$string['error_hub_rejected'] = 'Question Hub hat die Anfrage abgelehnt: {$a}';
$string['error_import_failed'] = 'Import bei der Moodle-Fragenerstellung fehlgeschlagen: {$a}';
$string['error_invalid_xml'] = 'Ungültige XML-Syntax im Fragenpaket.';
$string['error_linking_disabled'] = 'Das Verknüpfen eines Question-Hub-Kontos ist auf dieser Website ausgeschaltet.';
$string['error_network'] = 'Netzwerkfehler bei der Kommunikation mit Question Hub: {$a}';
$string['error_notfound'] = 'Die angeforderte Ressource wurde auf Question Hub nicht gefunden.';
$string['error_oauth_failed'] = 'Die Verknüpfung mit Ihrem Question-Hub-Konto ist fehlgeschlagen: {$a}. Bitte versuchen Sie es erneut oder verwenden Sie ein Personal Access Token.';
$string['error_package_withdrawn'] = 'Das Paket wurde aus dem Hub zurückgezogen. {$a}';
$string['error_release_notes_required'] = 'Bitte beschreiben Sie die Änderungen dieser Version.';
$string['error_review_accept_source'] = 'Änderungen in der Paketquelle werden übernommen, indem Sie sie pushen.';
$string['error_review_entry'] = 'Diese Frage gehört nicht mehr zur verbundenen Kategorie, oder es gibt sie in der verknüpften Version nicht mehr.';
$string['error_review_in_use'] = 'Die Frage wird in einem Test verwendet und kann nicht gelöscht werden.';
$string['error_review_not_linked'] = 'Diese Kategorie ist nicht mit einem Hub-Paket verbunden.';
$string['error_switch_variant'] = 'Diese Kategorie kann nicht zu diesem Paket wechseln: Es gehört nicht zum selben Original, oder die Kategorie ist nicht mehr verknüpft.';
$string['error_unauthorized'] = 'Authentifizierung fehlgeschlagen. Bitte überprüfen Sie Ihr Token oder verknüpfen Sie Ihr Konto.';
$string['error_unlink_inherited'] = 'Diese Unterkategorie gehört zum Paket ihrer übergeordneten Kategorie. Heben Sie die Verknüpfung dort auf oder verschieben Sie die Unterkategorie aus der Paketkategorie.';
$string['error_update_source'] = 'Die Kategorie stimmt nicht mit dem angegebenen Paket überein.';
$string['error_update_version'] = 'Die neue Versionsnummer muss höher sein als die bisherige Version v{$a}.';
$string['error_xxe_detected'] = 'Sicherheitsfehler: XML External Entity (XXE) oder DOCTYPE-Deklaration entdeckt. Paket wurde abgewiesen.';
$string['export_success'] = 'Paket erfolgreich mit Version {$a} veröffentlicht!';
$string['exportcategorytohub'] = 'Kategorie auf Hub exportieren';
$string['filter_canton'] = 'Kanton';
$string['filter_grade'] = 'Klassenstufe';
$string['filter_level'] = 'Kursstufe';
$string['filter_subject'] = 'Fach';
$string['fork_button'] = 'Variante anlegen';
$string['fork_button_help'] = 'Eigene Variante dieses Pakets im Hub anlegen. Diese Kategorie ist danach mit der Variante verknüpft: Sie veröffentlichen Ihre Änderungen dort und können sie dem Original vorschlagen.';
$string['fork_done'] = 'Variante {$a->id} (Version {$a->version}) angelegt. Diese Kategorie ist jetzt damit verknüpft.';
$string['fork_id_help'] = 'Kleinbuchstaben, Ziffern, Punkt, Binde- und Unterstrich (3 bis 100 Zeichen)';
$string['fork_id_label'] = 'Paket-ID der Variante (optional)';
$string['fork_intro'] = 'Legt im Hub eine eigene Variante der Version {$a} an und verknüpft diese Kategorie damit. Ihre lokalen Änderungen bleiben erhalten; Push veröffentlicht sie danach in der Variante. Das Original bleibt unverändert, und die Variante erscheint auf seiner Seite im Hub.';
$string['fork_license_note'] = 'Die Variante übernimmt die Lizenz des Originals und nennt es als Quelle.';
$string['fork_mr_button'] = 'Änderungen vorschlagen';
$string['fork_mr_button_help'] = 'Variante im Hub öffnen: Änderungen an {$a} vorschlagen oder dessen neue Versionen übernehmen';
$string['fork_of_help'] = 'Dieses Paket ist eine Variante von {$a}. Änderungen gelangen nur über einen Änderungsvorschlag im Hub ins Original.';
$string['fork_of_status'] = 'Variante von {$a}';
$string['fork_private_note'] = 'Das Original ist privat, deshalb bleibt auch die Variante privat.';
$string['fork_submit'] = 'Variante anlegen';
$string['fork_title_label'] = 'Titel (optional)';
$string['fork_title_placeholder'] = 'Titel des Originals – Variante von Ihnen';
$string['github_commit_done'] = 'Auf GitHub committet: {$a}';
$string['github_commit_failed'] = 'Im Hub ist alles gespeichert, aber der Commit auf GitHub ist fehlgeschlagen: {$a}';
$string['github_connect_hint'] = 'Um die Versionierung auf GitHub einzurichten, verbinden Sie Ihr Hub-Konto unter {$a} per Token mit GitHub (Abschnitt «GitHub-Verbindung»).';
$string['github_create_link_button'] = 'Repository anlegen und verknüpfen';
$string['github_mode_existing'] = 'Bestehendes Repository verwenden:';
$string['github_mode_new'] = 'Neues privates Repository in {$a} anlegen:';
$string['github_mode_none'] = 'Ohne GitHub';
$string['github_new_repo_help'] = 'Wird als privates Repository in Ihrem GitHub-Konto {$a} angelegt. Die aktuelle Version wird sofort committet, jede weitere ebenfalls.';
$string['github_new_repo_label'] = 'Neues GitHub-Repository';
$string['github_push_linked'] = 'Diese Version wird auch auf GitHub committet ({$a}), mit den Versionshinweisen als Commit-Nachricht.';
$string['github_push_linked_unknown'] = 'Diese Version wird auch in das mit dem Paket verknüpfte GitHub-Repository committet, mit den Versionshinweisen als Commit-Nachricht.';
$string['github_push_unlinked'] = 'Dieses Paket ist nicht mit GitHub verknüpft. Admins des Pakets können in den Paket-Einstellungen ein Repository verknüpfen oder anlegen.';
$string['github_repo_created'] = 'Das private GitHub-Repository wurde angelegt.';
$string['github_repo_created_named'] = 'Privates GitHub-Repository angelegt: {$a}';
$string['github_repo_help'] = 'Freiwillig, für den Hub nicht nötig. Jede neue Version wird als Datei PAKET-ID.xml in dieses Repository committet, mit den Versionshinweisen als Commit-Nachricht, und mit v plus Versionsnummer getaggt. Dafür braucht Ihr Hub-Konto eine GitHub-Verbindung (Dashboard, Abschnitt «GitHub-Verbindung») mit Schreibrecht auf das Repository. Ein noch nicht existierendes Repository legt der Hub privat an (dafür braucht der Token Zugriff auf alle Repositorys und «Administration: Read and write»). Beim Verknüpfen eines bestehenden Pakets wird die aktuelle Version sofort committet. Ein leeres Feld löst die Verknüpfung.';
$string['github_repo_label'] = 'GitHub-Repository (optional)';
$string['hub_tab_browse'] = 'Durchsuchen';
$string['hub_tab_library'] = 'Meine Bibliothek';
$string['hub_tab_my_packages'] = 'Meine Pakete';
$string['huburl'] = 'Hub-Basis-URL';
$string['huburl_desc'] = 'Die Basis-URL des Moodle Question Hub Marketplace (z. B. https://hub.cblum.ch).';
$string['import_active_content'] = '{$a->count} Frage(n) dieses Pakets enthalten aktive Inhalte (JavaScript, eingebettete Frames), z. B. {$a->names}. Diese werden in diesem Moodle ausgeführt wie andere Fragetexte. Importieren Sie nur Pakete von Autorinnen und Autoren, denen Sie vertrauen.';
$string['import_active_content_stripped'] = '{$a->count} Frage(n) dieses Pakets enthalten aktive Inhalte (JavaScript, eingebettete Frames), z. B. {$a->names}. Diese Website entfernt sie beim Import.';
$string['import_button'] = 'Fragen importieren';
$string['import_confirm_title'] = 'Question Hub Paket importieren';
$string['import_conflicts_found'] = 'Einige Fragen wurden im Hub und hier geändert. Entscheiden Sie pro Frage, welche gelten soll.';
$string['import_package'] = 'In Fragensammlung importieren';
$string['import_preview_button'] = 'Änderungen ansehen';
$string['import_preview_cancel'] = 'Zurück zu den Paketen';
$string['import_preview_confirm'] = 'Diese Änderungen importieren';
$string['import_preview_conflicts'] = 'Konflikte';
$string['import_preview_conflicts_heading'] = 'Konflikte: im Hub und lokal geändert';
$string['import_preview_conflicts_help'] = 'Diese Fragen unterscheiden sich lokal und auf dem Hub, z. B. eine Frage mit gleichem Namen und Typ, aber anderem Text in einer erstmals verbundenen Kategorie. Standardmässig bleibt die Frage wie in diesem Moodle. Übernehmen Sie die Frage wie im Hub, bleibt Ihre bisherige Frage im Versionsverlauf erhalten und kann wiederhergestellt werden.';
$string['import_preview_explanation'] = 'Es wurden keine Fragen oder Kategorien geändert. Beim Bestätigen wird der Import erneut geprüft.';
$string['import_preview_heading'] = 'Vorschau des Imports';
$string['import_preview_new'] = 'neu';
$string['import_preview_new_heading'] = 'Diese Fragen werden hinzugefügt';
$string['import_preview_renamed'] = 'umbenannt, früher «{$a}»';
$string['import_preview_show_update_diffs'] = 'Änderungen der aktualisierten Fragen anzeigen';
$string['import_preview_uncertain'] = 'Bisheriger Inhalt konnte nicht verglichen werden';
$string['import_preview_unchanged'] = 'unverändert';
$string['import_preview_unchanged_heading'] = 'Diese Fragen werden übersprungen';
$string['import_preview_updated'] = 'zu aktualisieren';
$string['import_preview_updated_heading'] = 'Diese Fragen werden aktualisiert';
$string['import_result_summary'] = 'Import abgeschlossen: {$a->added} hinzugefügt, {$a->updated} aktualisiert, {$a->unchanged} unverändert.';
$string['import_success'] = 'Erfolgreich {$a} Fragen in Ihre Fragensammlung importiert!';
$string['import_update_changed'] = 'Neue Fragen hinzufügen und geänderte aktualisieren';
$string['import_update_changed_help'] = 'Neue Fragen werden hinzugefügt. Geänderte Fragen aus einem früheren Import erhalten eine neue Version; unveränderte werden übersprungen. Fragen ohne Kennung werden anhand von Name und Kategorie erkannt. Nach einer Umbenennung gelten sie als neu. Ohne Häkchen werden alle Fragen als neue Kopien angelegt.';
$string['import_variant_help'] = 'Das Original oder eine Variante, die andere Lehrpersonen daraus weiterentwickelt haben (öffentliche, freigegebene und Ihre eigenen).';
$string['import_variant_label'] = 'Original oder Variante';
$string['import_variant_option'] = 'Variante von {$a->name} · Version {$a->version}';
$string['import_variant_original'] = 'Original · Version';
$string['incl_subcategories'] = 'inkl. Unterkategorien';
$string['inherited_link_status'] = 'Teil von «{$a}»';
$string['inherited_link_status_help'] = 'Diese Unterkategorie gehört zum Paket ihrer übergeordneten Kategorie. Veröffentlichen, Holen und Verknüpfung aufheben erfolgen dort. Zum Abspalten verschieben Sie die Unterkategorie aus der Paketkategorie und veröffentlichen sie separat.';
$string['institutiontoken'] = 'Institutions-Zugriffstoken';
$string['institutiontoken_desc'] = 'Optionales institutionelles Personal Access Token (PAT) als Ausweichlösung für systemweite Abfragen.';
$string['keep_target_name'] = 'Name der Zielkategorie beibehalten';
$string['keep_target_name_description'] = 'Die Fragen kommen in diese Kategorie. Unterkategorien aus dem Paket werden darunter angelegt.';
$string['keep_target_name_help'] = 'Die ausgewählte Kategorie behält ihren Namen. Fragen aus dem obersten Paketordner kommen direkt hierhin. Paket-Unterkategorien werden darunter angelegt.';
$string['library_empty'] = 'In diesem Kurs ist derzeit kein Question-Hub-Paket mit einer Kategorie verknüpft.';
$string['library_restore'] = 'Ältere Version wiederherstellen';
$string['library_restore_button'] = 'Wiederherstellung prüfen';
$string['library_restore_help'] = 'Importiert eine ältere Version dieses Pakets erneut in die verknüpfte Kategorie. Sie sehen zuerst eine Vorschau; seither geänderte Fragen werden auf diese Version zurückgesetzt, später hinzugefügte Fragen bleiben. Danach bietet die Kategorie wieder die neueste Version zum Holen an.';
$string['library_restore_version'] = 'Wiederherzustellende Version';
$string['link_account_button'] = 'Mit Question Hub verbinden';
$string['linked_to_category'] = 'Verknüpft mit';
$string['local_changes_status'] = '{$a} lokale Änderungen';
$string['missing_on_hub'] = 'Nicht im Hub';
$string['missing_on_hub_help'] = 'Der Hub bietet dieses Paket nicht mehr an. Die Fragen in dieser Kategorie bleiben unverändert; veröffentlichen Sie die Kategorie erneut oder heben Sie die Verknüpfung auf.';
$string['my_packages'] = 'Meine Pakete (erstellt oder mitbearbeitet)';
$string['no_packages'] = 'Sie haben noch keine Pakete veröffentlicht.';
$string['no_packages_found'] = 'Keine Pakete gefunden, die den Kriterien entsprechen.';
$string['open_category_questions'] = 'Diese Kategorie in der Fragensammlung öffnen';
$string['open_content_notice'] = 'Mit der Veröffentlichung stellen Sie die Fragen unter die gewählte Creative-Commons-Lizenz. Öffentliche Pakete werden damit zu offenen Bildungsmaterialien: Alle dürfen sie herunterladen, verwenden, forken, verändern und weitergeben, mit Nennung der Urheberschaft (ausser bei CC0) und bei CC BY-SA unter derselben Lizenz. Private Pakete tragen die Lizenz ebenfalls, sind aber nur für freigegebene Personen sichtbar. Veröffentlichen Sie nur Inhalte, an denen Sie die Rechte haben.';
$string['open_hub_package'] = 'Im Hub öffnen';
$string['package_author'] = 'Autor';
$string['package_canton_label'] = 'Zielkanton';
$string['package_grades_label'] = 'Zielstufen (mehrere mit Strg/Cmd-Klick)';
$string['package_id'] = 'Paket-Identifikator (Reverse-Domain, z. B. ch.gym.inf.trees)';
$string['package_level_label'] = 'Kursstufe';
$string['package_license'] = 'Lizenz';
$string['package_license_label'] = 'Lizenz';
$string['package_preview_help'] = 'Musterlösungen und Feedback werden nie angezeigt. Ohne Vorschau sehen Besucher nur die Beschreibung.';
$string['package_preview_label'] = 'Öffentliche Vorschau der Fragen im Hub anzeigen';
$string['package_role_admin'] = 'Admin';
$string['package_role_editor'] = 'Bearbeiter/in';
$string['package_subject_label'] = 'Schweizer Gymnasialfach';
$string['package_summary_label'] = 'Zusammenfassung / Beschreibung';
$string['package_title_label'] = 'Pakettitel';
$string['package_version'] = 'Version';
$string['package_version_label'] = 'Release-Version (SemVer, z. B. 1.0.0)';
$string['package_visibility_label'] = 'Sichtbarkeit';
$string['package_visibility_private'] = 'Privat – Zugriff nur nach Freigabe';
$string['package_visibility_public'] = 'Öffentlich';
$string['pat_token_label'] = 'Oder Personal Access Token (PAT) eingeben:';
$string['pluginname'] = 'Question Hub';
$string['preflight_errors_found'] = 'Vorabprüfung fehlgeschlagen mit blockierenden Fehlern:';
$string['preflight_passed'] = 'Paket hat alle Kompatibilitätsprüfungen bestanden.';
$string['preflight_validation'] = 'Vorabprüfung des Fragensammlungspakets';
$string['preflight_warnings_found'] = 'Vorabprüfungshinweise gefunden (Import ist weiterhin möglich):';
$string['preview_off'] = 'ohne Vorschau';
$string['preview_on'] = 'mit Vorschau';
$string['preview_package'] = 'Vorschau';
$string['privacy:metadata'] = 'Das Question Hub Plugin speichert Benutzer-Zugriffstoken und Verlaufsprotokolle.';
$string['privacy:metadata:history:action'] = 'Ausgeführte Aktion (Import oder Veröffentlichung).';
$string['privacy:metadata:history:package_id'] = 'Question Hub Paket-Identifikator.';
$string['privacy:metadata:history:package_version'] = 'Question Hub Paket-Version.';
$string['privacy:metadata:history:tableexplanation'] = 'Verlaufsprotokoll von Import- und Veröffentlichungsaktionen.';
$string['privacy:metadata:history:timecreated'] = 'Zeitpunkt der Aktion.';
$string['privacy:metadata:history:userid'] = 'Moodle-Benutzer-ID.';
$string['privacy:metadata:marketplace:email'] = 'Zur Kontoverknüpfung übermittelte E-Mail-Adresse.';
$string['privacy:metadata:marketplace:explanation'] = 'Question Hub verbindet sich mit dem externen Question Hub Marketplace, um Fragenpakete zu suchen, herunterzuladen und zu veröffentlichen.';
$string['privacy:metadata:tokens:access_token'] = 'Marketplace-Zugriffstoken.';
$string['privacy:metadata:tokens:hub_email'] = 'Question Hub E-Mail-Adresse.';
$string['privacy:metadata:tokens:hub_name'] = 'Question Hub Anzeigename.';
$string['privacy:metadata:tokens:hub_user_id'] = 'Question Hub Benutzer-UUID.';
$string['privacy:metadata:tokens:tableexplanation'] = 'Speichert Benutzer-OAuth-Token und verknüpftes Marketplace-Profil.';
$string['privacy:metadata:tokens:timecreated'] = 'Zeitpunkt der Token-Erstellung.';
$string['privacy:metadata:tokens:userid'] = 'Moodle-Benutzer-ID.';
$string['private_package_badge'] = 'Privat';
$string['private_package_summary'] = 'Privates Fragenpaket. Es werden nur begrenzte Angaben angezeigt.';
$string['publication_tracking_failed'] = 'Versionserfassung für die Veröffentlichung konnte nicht gespeichert werden.';
$string['publish_button'] = 'Auf Question Hub veröffentlichen';
$string['publish_category_button'] = 'Kategorie veröffentlichen';
$string['publish_category_heading'] = 'Kategorien und Hub-Verknüpfungen';
$string['publish_title'] = 'Auf Question Hub veröffentlichen';
$string['published_badge'] = 'Veröffentlicht';
$string['publishselected'] = 'Ausgewählte auf Question Hub veröffentlichen';
$string['publishtohub'] = 'Auf Question Hub exportieren';
$string['pull_before_push'] = 'Im Hub gibt es eine neuere Version: zuerst holen, dann veröffentlichen.';
$string['pull_button'] = 'Pull v{$a}';
$string['pull_button_help'] = 'v{$a} in diese Kategorie importieren: neue Fragen hinzufügen und geänderte aktualisieren';
$string['pull_preview_button'] = 'Pull (Vorschau)';
$string['pull_preview_help'] = 'Zeigt, was ein Pull hinzufügen oder aktualisieren würde, ohne die Fragensammlung zu ändern';
$string['push_button'] = 'Push';
$string['push_button_help'] = 'Lokale Änderungen als neue Version von {$a} veröffentlichen';
$string['push_changes_unknown'] = 'Die Änderungen seit der letzten Version lassen sich nicht ermitteln (keine Momentaufnahme vorhanden). Sie können trotzdem eine neue Version veröffentlichen.';
$string['push_current_version'] = 'aktuelle Version im Hub: v{$a}';
$string['push_diff_too_large'] = 'Diese Frage ist zu gross für eine zeilenweise Anzeige.';
$string['push_files_heading'] = 'Geänderte Fragen';
$string['push_files_summary'] = '{$a} Frage(n) geändert';
$string['push_heading'] = 'Neue Version von «{$a}»';
$string['push_new_version_link'] = 'Neue Version veröffentlichen';
$string['push_no_changes'] = 'Seit der letzten Version wurden keine Fragen geändert. Sie können trotzdem eine neue Version veröffentlichen, z. B. nach einer rückgängig gemachten Änderung.';
$string['push_same_content'] = '{$a} weitere Frage(n) haben eine neue Versionsnummer, aber unveränderten Inhalt (z. B. rückgängig gemachte Änderung); sie werden nicht aufgeführt.';
$string['push_settings_note'] = 'Titel, Beschreibung, Lizenz und weitere Einstellungen ändern Sie in der Kategorienliste (Zahnrad).';
$string['push_source_category'] = 'Quellkategorie: {$a}';
$string['push_status_added'] = 'Neu';
$string['push_status_modified'] = 'Geändert';
$string['push_status_moved'] = 'Verschoben';
$string['push_status_removed'] = 'Entfernt';
$string['push_status_renamed'] = 'Umbenannt';
$string['push_submit'] = 'Version veröffentlichen';
$string['push_version_help'] = 'Muss höher sein als v{$a}. Kleine Korrekturen: letzte Stelle erhöhen; neue Fragen: mittlere Stelle.';
$string['question_bank_context'] = 'Fragensammlung';
$string['question_bank_course'] = 'Kurs: {$a}';
$string['question_bank_course_category'] = 'Kursbereich: {$a}';
$string['questionbankroot'] = 'Wurzel der Fragensammlung';
$string['questionhub'] = 'Question Hub';


$string['questionhub:export'] = 'Fragen oder Kategorien als Question Hub Pakete exportieren';
$string['questionhub:import'] = 'Question Hub Pakete in Fragensammlungen importieren';
$string['questionhub:manage'] = 'Question Hub Konfiguration verwalten';
$string['questionhub:publish'] = 'Fragenpakete direkt auf Question Hub veröffentlichen';
$string['questionhub:view'] = 'Question Hub Sammlungen ansehen und durchsuchen';

$string['questions_count'] = '{$a} Fragen';
$string['release_notes_label'] = 'Versionshinweis';
$string['release_notes_placeholder'] = 'Was hat sich geändert? z. B. «Tippfehler in Aufgabe 3 korrigiert, zwei Fragen zu Regression ergänzt»';
$string['remote_update_status'] = 'v{$a} im Hub';
$string['rename_empty_target'] = 'Leere Zielkategorie umbenennen zu';
$string['rename_empty_target_help'] = 'Der Name der ausgewählten Kategorie wird durch den Namen des obersten Paketordners ersetzt. Das geht nur, wenn die Kategorie leer ist und keine Unterkategorien hat.';
$string['republish_button'] = 'Erneut veröffentlichen';
$string['republish_button_help'] = 'Diese Kategorie erneut als Paket veröffentlichen (neue Versionsnummer nötig)';
$string['request_access'] = 'Zugriff anfragen';
$string['reset_filters'] = 'Zurücksetzen';
$string['review_accept'] = 'Übernehmen';
$string['review_accept_all'] = 'Alle übernehmen';
$string['review_accept_help'] = 'Ihre Änderung an dieser Frage behalten';
$string['review_accepted'] = '{$a} lokale Änderung(en) übernommen.';
$string['review_back'] = 'Zurück zum Question Hub';
$string['review_bulk_result'] = '{$a->restored} Frage(n) wie in der verknüpften Version wiederhergestellt, {$a->deleted} gelöscht, {$a->failed} nicht möglich (z. B. in einem Test verwendet).';
$string['review_button'] = 'Änderungen prüfen';
$string['review_button_help'] = 'Lokale Änderungen seit der verknüpften Version prüfen und einzeln übernehmen oder verwerfen';
$string['review_deleted'] = 'Die hier hinzugefügte Frage wurde gelöscht.';
$string['review_duplicates'] = 'Duplikate prüfen';
$string['review_duplicates_count'] = '{$a} Duplikate prüfen';
$string['review_heading'] = 'Lokale Änderungen: {$a}';
$string['review_intro_copy'] = 'Diese Fragen wurden seit dem letzten Pull in diesem Moodle geändert. <strong>Übernehmen</strong> behält Ihre Änderung: Sie gilt nicht mehr als lokale Änderung, und ändert der Hub die Frage später, zeigt der Pull einen Konflikt, statt sie zu überschreiben. <strong>Verwerfen</strong> stellt die Frage wie im Hub als neue Version wieder her (Ihre Änderung bleibt im Versionsverlauf) oder löscht eine hier hinzugefügte Frage.';
$string['review_intro_source'] = 'Diese Änderungen sind noch nicht auf dem Hub. Was Sie behalten, wird mit dem nächsten Push veröffentlicht. <strong>Verwerfen</strong> stellt die Frage wie veröffentlicht als neue Version wieder her (Ihre Änderung bleibt im Versionsverlauf) oder löscht eine hier hinzugefügte Frage.';
$string['review_list_heading'] = 'Geänderte Fragen ({$a})';
$string['review_no_changes'] = 'Seit der verknüpften Version gibt es keine lokalen Änderungen.';
$string['review_reject'] = 'Verwerfen';
$string['review_reject_added_all'] = 'Alle neuen lokalen Fragen verwerfen ({$a})';
$string['review_reject_added_all_confirm'] = 'Alle {$a} seit der verknüpften Version hier hinzugefügten Fragen löschen? Fragen, die in einem Test verwendet werden, werden übersprungen.';
$string['review_reject_added_confirm'] = 'Die hier hinzugefügte Frage «{$a}» löschen?';
$string['review_reject_all'] = 'Alle lokalen Änderungen verwerfen ({$a})';
$string['review_reject_all_confirm'] = 'Alle {$a} lokalen Änderungen verwerfen? Geänderte Fragen werden wie in der verknüpften Version wiederhergestellt (als neue Version; Ihre Änderung bleibt im Versionsverlauf); hier hinzugefügte Fragen werden gelöscht. Fragen, die in einem Test verwendet werden, werden nicht gelöscht.';
$string['review_reject_confirm'] = '«{$a}» wie in der verknüpften Version wiederherstellen? Ihre Änderung bleibt im Versionsverlauf.';
$string['review_reject_help'] = 'Frage wie in der verknüpften Version wiederherstellen';
$string['review_restored'] = 'Die Frage wurde wie in der verknüpften Version wiederhergestellt (als neue Version der Frage).';
$string['review_synced_version'] = 'verglichen mit der verknüpften Version {$a}';
$string['review_to_push'] = 'Änderungen pushen';
$string['save_settings'] = 'Einstellungen speichern';
$string['save_token'] = 'Token speichern';
$string['search_button'] = 'Suchen';
$string['search_placeholder'] = 'Sammlungen nach Stichwort, Thema oder Fragentyp durchsuchen...';
$string['settings_saved'] = 'Einstellungen im Question Hub gespeichert.';
$string['stripactivecontent'] = 'Aktive Inhalte beim Import entfernen';
$string['stripactivecontent_desc'] = 'Moodle zeigt Fragetexte ohne HTML-Bereinigung an. Skripte in einem Hub-Paket würden also in diesem Moodle ausgeführt. Ist die Option aktiv, wird das HTML importierter Fragen mit dem HTML-Filter von Moodle bereinigt: Skripte, Event-Handler und eingebettete Frames werden entfernt. Interaktive Fragen, die JavaScript benötigen, funktionieren dann nicht mehr.';
$string['switch_variant_button'] = 'Variante wechseln';
$string['switch_variant_help'] = 'Diese Kategorie mit dem Original oder einer anderen Variante verknüpfen; zuerst sehen Sie eine Vorschau der Änderungen';
$string['switch_variant_intro'] = 'Wählen Sie das Original oder eine andere Variante. Sie sehen zuerst, welche Fragen sich ändern; erst «Diese Änderungen importieren» stellt die Verknüpfung um. Lokale Änderungen bleiben erhalten oder erscheinen als Konflikt.';
$string['switch_variant_preview'] = 'Vorschau des Wechsels zu «{$a}». Erst «Diese Änderungen importieren» verknüpft die Kategorie damit.';
$string['switch_variant_submit'] = 'Änderungen ansehen';
$string['sync_ahead_help'] = 'Fragen in dieser Kategorie, die seit der verknüpften Hub-Version geändert wurden';
$string['sync_behind_help'] = 'Im Hub gibt es eine neuere Version als die mit dieser Kategorie verknüpfte';
$string['sync_column_title'] = 'Hub';
$string['sync_in_sync'] = 'Verknüpft';
$string['sync_in_sync_help'] = 'Mit dem Hub-Paket verknüpft: keine lokalen Änderungen seit der verknüpften Version und keine neuere Version im Hub. Änderungen werden nicht automatisch abgeglichen; verwenden Sie Push oder Pull.';
$string['sync_source_label'] = 'von hier veröffentlicht';
$string['target_category'] = 'Zielkategorie';

$string['target_category_help'] = 'Die Zahl zeigt, wie viele Fragen direkt in dieser Kategorie liegen. Fragen in Unterkategorien sind nicht mitgezählt. Eine Umbenennung ist nur bei einer leeren Kategorie ohne Unterkategorien möglich.';
$string['token_saved'] = 'Personal Access Token erfolgreich gespeichert.';



$string['unlink_category_button'] = 'Verknüpfung aufheben';
$string['update_package_button'] = 'Paket aktualisieren';
$string['variants_apply'] = 'Auswahl anwenden';
$string['variants_apply_confirm'] = '{count} Frage(n) mit allen Versionen löschen?';
$string['variants_created'] = 'Erstellt';
$string['variants_found'] = '{$a} Fragename(n) kommen in derselben Kategorie mehrfach mit unterschiedlichem Inhalt vor (z. B. eine lokale Frage neben der aus dem Hub importierten).';
$string['variants_groups'] = 'Gruppen ({$a})';
$string['variants_heading'] = 'Gleicher Name, anderer Inhalt';
$string['variants_intro'] = 'Jede Gruppe enthält Fragen derselben Kategorie mit gleichem Namen und Typ, aber unterschiedlichem Inhalt. Vergleichen Sie sie, wählen Sie die Frage, die Sie <strong>behalten</strong> möchten, und wenden Sie die Auswahl an: Die übrigen Fragen der Gruppe werden mit allen Versionen gelöscht. Fragen, die in einem Test verwendet werden, werden nie gelöscht. Gruppen mit «Alle behalten» bleiben unverändert. Eine Frage, die in einem Test verwendet wird, kann stattdessen den <strong>Inhalt aus dem Hub übernehmen</strong>: Dieser wird zur neuen Version der verwendeten Frage (Tests verwenden sie weiter, frühere Versuche behalten ihre Version), und die separate Hub-Frage wird gelöscht.';
$string['variants_inuse'] = 'verwendet';
$string['variants_inuse_help'] = 'In einem Test verwendet: kann hier nicht gelöscht werden';
$string['variants_keep'] = 'Behalten';
$string['variants_keep_all'] = 'Alle Fragen dieser Gruppe behalten (keine Änderung)';
$string['variants_keep_hub'] = 'Frage aus dem Hub verwenden, wo es genau eine gibt (bei verwendeten lokalen Fragen als neue Version)';
$string['variants_keep_this'] = 'behalten';
$string['variants_local'] = 'lokal';
$string['variants_merge_help'] = 'Der Inhalt der Hub-Frage wird zur neuen Version dieser verwendeten Frage; die separate Hub-Frage wird gelöscht.';
$string['variants_merge_this'] = 'Inhalt aus dem Hub übernehmen (neue Version)';
$string['variants_modified'] = 'geändert {$a}';
$string['variants_none'] = 'Es gibt keine Fragen mit gleichem Namen und anderem Inhalt.';
$string['variants_number'] = 'Frage {$a}';
$string['variants_origin'] = 'Herkunft';
$string['variants_reset'] = 'Zurücksetzen (alle behalten)';
$string['variants_result'] = '{$a->merged} verwendete Frage(n) auf den Inhalt aus dem Hub aktualisiert, {$a->deleted} Frage(n) gelöscht, {$a->inuse} behalten, weil sie in einem Test verwendet werden.';
$string['variants_review'] = 'Vergleichen und bereinigen';
$string['variants_side'] = 'Frage {$a->number} · {$a->origin} · erstellt {$a->created} · ID {$a->id}';
$string['variants_stays_inuse'] = 'bleibt (verwendet)';
$string['variants_versions'] = 'Version (Anzahl)';
$string['variants_will_be_merged'] = 'Inhalt übernommen, Frage wird gelöscht';
$string['variants_will_delete'] = 'wird gelöscht';
$string['variants_will_merge'] = 'erhält den Inhalt aus dem Hub als neue Version';
$string['version_immutable_note'] = 'Versionsnummern sind unveränderlich: Für geänderte Inhalte veröffentlichen Sie eine neue Version oder ziehen eine bestehende zurück.';
$string['withdraw_done'] = 'Aus dem Question Hub zurückgezogen.';
$string['withdraw_last_version_hint'] = 'Letzte Version: Ziehen Sie stattdessen das ganze Paket zurück.';
$string['withdraw_package'] = 'Paket zurückziehen';
$string['withdraw_package_confirm'] = 'Dieses Paket mit allen Versionen aus dem Hub zurückziehen? Bereits in Moodle importierte Fragen bleiben erhalten.';
$string['withdraw_version'] = 'Version zurückziehen';
$string['withdraw_version_confirm'] = 'Diese Version zurückziehen? Die Versionsnummer kann nie wieder verwendet werden.';
$string['withdraw_versions_heading'] = 'Versionen zurückziehen';



$string['withdrawn_on_hub'] = 'Im Hub zurückgezogen';
