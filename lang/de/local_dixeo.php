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
 * Language strings for the Dixeo plugin.
 *
 * @package    local_dixeo
 * @copyright  2025 Edunao SAS (contact@edunao.com)
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// General.
$string['pluginname'] = 'Dixeo AI';
$string['pluginname_desc'] = 'Dixeo-AI-Integration für intelligente Inhaltserstellung und -bearbeitung.';

// Capabilities.
$string['dixeo:manage'] = 'Dixeo-Einstellungen verwalten und Berichte anzeigen';
$string['dixeo:generate'] = 'Neue Module mit KI erstellen (Seite, Beschriftung, Test, Glossar)';
$string['dixeo:edit'] = 'Bestehende Module mit KI bearbeiten';
$string['dixeo:create'] = 'Kurse mit dem Dixeo-Kursdesigner erstellen';
$string['dixeo:viewusage'] = 'Kreditnutzungsberichte anzeigen';

// Settings page.
$string['api_configuration'] = 'API-Konfiguration';
$string['api_configuration_desc'] = 'Verbindung zur Dixeo-AI-API konfigurieren.';
$string['api_url'] = 'API-URL';
$string['api_url_desc'] = 'Basis-URL für die Dixeo-API. Standard: https://api.dixeo.com';
$string['api_key'] = 'API-Schlüssel';
$string['api_key_desc'] = 'Ihr Dixeo-API-Schlüssel. Sie erhalten ihn im Dixeo-Dashboard.';
$string['namespace'] = 'Namespace';
$string['namespace_desc'] = 'Nur erforderlich, wenn mehrere Moodle-Sites denselben API-Schlüssel nutzen. Jede Website sollte einen anderen Namespace verwenden (z. B. "production", "staging", "site1"), um Daten getrennt zu halten. Lassen Sie "default", wenn dies die einzige Website ist, die diesen API-Schlüssel verwendet.';
$string['image_generation'] = 'Bildgenerierung';
$string['image_generation_desc'] = 'Steuert die Verfügbarkeit von KI-Bildgenerierung und Bildbearbeitung für Kurs- und Abschnittsbilder.';
$string['image_generation_enabled'] = 'Bildgenerierung aktivieren';
$string['image_generation_enabled_desc'] = 'Wenn deaktiviert, werden alle Anfragen zum Generieren oder Bearbeiten von Bildern blockiert.';
$string['image_generation_course_mode'] = 'Kursbilder';
$string['image_generation_course_mode_desc'] = 'Steuert KI-Bildaktionen für das Kursübersichtsbild.';
$string['image_generation_section_mode'] = 'Abschnittsbilder';
$string['image_generation_section_mode_desc'] = 'Steuert KI-Bildaktionen für Kapitel-/Abschnittsbilder.';
$string['image_generation_mode_disabled'] = 'Deaktiviert';
$string['image_generation_mode_generate'] = 'Generieren';
$string['image_generation_mode_generate_edit'] = 'Generieren und Bearbeiten';
$string['credit_information'] = 'Kreditinformationen';
$string['current_balance'] = 'Aktueller Kontostand';
$string['current_balance_desc'] = 'Ihr aktueller Dixeo-Kreditstand. Credits werden für KI-Operationen verwendet.';
$string['credit_report'] = 'Kreditbericht';
$string['view_credit_report'] = 'Detaillierten Kreditbericht anzeigen';
$string['configure_api'] = 'API konfigurieren';

// Credit balance.
$string['state_active'] = 'Aktiv';
$string['state_frozen'] = 'Eingefroren';
$string['state_suspended'] = 'Gesperrt';

// Credit report page.
$string['usage_statistics'] = 'Nutzungsstatistiken';
$string['this_week_usage'] = 'Diese Woche';
$string['week_total'] = 'Gesamtverbrauch diese Woche';
$string['recent_transactions'] = 'Transaktionsverlauf';
$string['total_used'] = 'Gesamtverbrauch';
$string['average_per_period'] = 'Durchschnitt pro {$a}';
$string['data_points'] = 'Datenpunkte';
$string['no_usage_data'] = 'Keine Nutzungsdaten für den gewählten Zeitraum verfügbar.';
$string['no_transactions'] = 'Keine Transaktionen gefunden.';
$string['usage_chart_label'] = 'Kreditverbrauch';

// Day names (short).
$string['day_mon'] = 'Mo';
$string['day_tue'] = 'Di';
$string['day_wed'] = 'Mi';
$string['day_thu'] = 'Do';
$string['day_fri'] = 'Fr';
$string['day_sat'] = 'Sa';
$string['day_sun'] = 'So';

// Day names (full).
$string['day_monday'] = 'Montag';
$string['day_tuesday'] = 'Dienstag';
$string['day_wednesday'] = 'Mittwoch';
$string['day_thursday'] = 'Donnerstag';
$string['day_friday'] = 'Freitag';
$string['day_saturday'] = 'Samstag';
$string['day_sunday'] = 'Sonntag';

// Periods.
$string['period'] = 'Zeitraum';
$string['period_day'] = 'Täglich';
$string['period_week'] = 'Wöchentlich';
$string['period_month'] = 'Monatlich';

// Transaction types.
$string['transaction_type_purchase'] = 'Kauf';
$string['transaction_type_deduction'] = 'Nutzung';
$string['transaction_type_refund'] = 'Rückerstattung';
$string['transaction_type_reset'] = 'Erneuerung';

// Table headers.
$string['date'] = 'Datum';
$string['type'] = 'Typ';
$string['description'] = 'Beschreibung';
$string['amount'] = 'Betrag';

// Pagination.
$string['pagination'] = 'Seitennavigation';
$string['page_x_of_y'] = 'Seite {$a->current} von {$a->total}';

// Warnings and errors.
$string['api_key_not_configured'] = 'Der Dixeo-API-Schlüssel ist nicht konfiguriert. Bitte konfigurieren Sie ihn in den Plugin-Einstellungen.';
$string['api_error'] = 'API-Fehler: {$a}';
$string['account_frozen_warning'] = 'Ihr Konto ist wegen niedrigen Kreditstands eingefroren. Bitte laden Sie Credits auf, um Dixeo-AI-Funktionen weiter zu nutzen.';
$string['account_suspended_warning'] = 'Ihr Konto wurde gesperrt. Bitte wenden Sie sich an den Dixeo-Support.';

// Errors (used in exceptions).
$string['error:authentication'] = 'Authentifizierung fehlgeschlagen. Bitte überprüfen Sie Ihren API-Schlüssel.';
$string['error:payment_required'] = 'Unzureichende Credits. Bitte laden Sie Credits auf, um fortzufahren.';
$string['error:rate_limit'] = 'Ratenlimit überschritten. Bitte warten Sie, bevor Sie weitere Anfragen senden.';
$string['error:validation'] = 'Ungültige Anfrage: {$a}';
$string['error:job_not_found'] = 'Der angeforderte Auftrag wurde nicht gefunden.';
$string['error:upstream_ai'] = 'KI-Servicefehler. Bitte versuchen Sie es später erneut.';
$string['error:job_failed'] = 'Auftragsverarbeitung fehlgeschlagen: {$a}';
$string['error:connection'] = 'Verbindung zur Dixeo-API fehlgeschlagen. Bitte überprüfen Sie Ihre Netzwerkverbindung.';
$string['error:timeout'] = 'Zeitüberschreitung. Sie können den Auftragsstatus später prüfen.';
$string['error:notslideshow'] = 'Das Kursmodul ist keine Slideshow-Aktivität.';
$string['error:slidenotinslideshow'] = 'Die angeforderte Folie gehört nicht zu dieser Slideshow.';

// Overview page.
$string['overview'] = 'Dixeo-Übersicht';
$string['credit_balance'] = 'Kreditstand';
$string['credits'] = 'Credits';

// Privacy.
$string['privacy:metadata'] = 'Das Dixeo-Plugin sendet Kursinhalte zur Verarbeitung an die Dixeo-AI-API, speichert aber keine personenbezogenen Daten lokal.';

// DSL errors.
$string['dsl_error'] = 'Modulerstellung fehlgeschlagen: {$a}';

// Quiz question feedback.
$string['feedback_correct'] = 'Richtig!';

// Tasks.
$string['task_cleanup_jobs'] = 'Alte Auftragsdatensätze bereinigen';
$string['task_process_file_sync'] = 'Dixeo-Dateisynchronisation verarbeiten';
$string['task_poll_image_generation'] = 'Dixeo-Bildgenerierungsauftrag abfragen';
$string['dixeo_course_image_unsupported_type'] = 'Nicht unterstützter Typ des generierten Bildes.';
$string['dixeo_image_job_empty_result'] = 'Der Bildauftrag lieferte keine Bilddaten.';
$string['dixeo_image_generation_disabled'] = 'Die Bildgenerierung ist in den Website-Einstellungen deaktiviert.';
$string['dixeo_pluginfile_not_found'] = 'Die Bilddatei konnte nicht aus dem Speicher gelesen werden.';

// File sync.
$string['filesync_title'] = 'Dixeo-Dateisynchronisation';
$string['filesync_label'] = 'Synchronisieren';
$string['filesync_status_none'] = 'Keine Dateien synchronisiert';
$string['filesync_status_syncing'] = 'Dateien werden synchronisiert...';
$string['filesync_status_synchronized'] = 'Dateien synchronisiert';
$string['filesync_status_error'] = 'Synchronisationsfehler';
$string['filesync_status_outdated'] = 'Inhalt geändert, Synchronisation nötig';
$string['filesync_status_paused'] = 'Synchronisation pausiert';
$string['filesync_status_disabled'] = 'Synchronisation deaktiviert';
$string['filesync_enable'] = 'Synchronisation aktivieren';
$string['filesync_pause'] = 'Synchronisation pausieren';
$string['filesync_disable_remove'] = 'Deaktivieren und Sync-Daten löschen';
$string['filesync_resync'] = 'Jetzt synchronisieren';
$string['filesync_files_count'] = '{$a} Dateien synchronisiert';
$string['filesync_progress'] = '{$a}% abgeschlossen';
$string['last_sync'] = 'Letzte Synchronisation';
$string['filesync_error_retry'] = 'Wird automatisch erneut versucht';
$string['files'] = 'Dateien';

// Designer structure validation (finalize / course creation).
$string['designerstructurevalidate_failed'] = 'Dieser Kurs kann erst erstellt werden, wenn diese Probleme behoben sind:

{$a->details}';
$string['designerstructurevalidate_invalid_root'] = 'Die Kursstrukturdaten sind ungültig.';
$string['designerstructurevalidate_sections_not_array'] = 'Die Abschnittsliste der Kursstruktur ist ungültig.';
$string['designerstructurevalidate_section_invalid'] = 'Abschnitt {$a} in der Struktur ist ungültig.';
$string['designerstructurevalidate_modules_not_array'] = 'Die Modulliste in Abschnitt {$a} ist ungültig.';
$string['designerstructurevalidate_module_invalid'] = 'Das Modul an Position {$a->module} in Abschnitt {$a->section} ist ungültig.';
$string['designerstructurevalidate_aggregate_prefix_section'] = 'Abschnitt {$a->section}, Aktivität {$a->module}:';
$string['designerstructurevalidate_aggregate_prefix_section_only'] = 'Abschnitt {$a->section}:';
$string['designerstructurevalidate_course_title_required'] = 'Der Kurstitel ist ein Pflichtfeld.';
$string['designerstructurevalidate_course_title_too_long'] = 'Der Kurstitel darf höchstens {$a->max} Zeichen lang sein.';
$string['designerstructurevalidate_course_summary_too_long'] = 'Die Kurszusammenfassung ist zu lang (maximal {$a->max} Zeichen).';
$string['designerstructurevalidate_section_title_too_long'] = 'Der Abschnittstitel ist zu lang (maximal {$a->max} Zeichen).';
$string['designerstructurevalidate_section_summary_too_long'] = 'Die Abschnittszusammenfassung ist zu lang (maximal {$a->max} Zeichen).';
$string['designerstructurevalidate_module_type_required'] = 'Der Aktivitätstyp ist ein Pflichtfeld.';
$string['designerstructurevalidate_module_type_not_usable'] = 'Der Typ „{$a->type}" kann auf dieser Website nicht verwendet werden (fehlendes Plugin oder erforderliche Inhaltsbibliothek).';
$string['designerstructurevalidate_module_title_required'] = 'Der Aktivitätstitel ist ein Pflichtfeld.';
$string['designerstructurevalidate_module_title_placeholder'] = 'Ersetzen Sie den Standardtitel „Neue Seite" durch einen echten Aktivitätsnamen.';
$string['designerstructurevalidate_module_title_too_long'] = 'Der Aktivitätstitel ist zu lang (maximal {$a->max} Zeichen).';
$string['designerstructurevalidate_module_summary_placeholder'] = 'Ersetzen Sie die Standardzusammenfassung durch eine echte Beschreibung dessen, was diese Aktivität abdeckt.';
$string['designerstructurevalidate_module_summary_too_long'] = 'Die Aktivitätszusammenfassung ist zu lang (maximal {$a->max} Zeichen).';
$string['designerstructurevalidate_module_instructions_required'] = 'Anweisungen für die KI sind erforderlich (mindestens {$a->min} Zeichen).';
$string['designerstructurevalidate_module_instructions_too_long'] = 'Die Anweisungen sind zu lang (maximal {$a->max} Zeichen).';
$string['designerstructurevalidate_instructions_api_min'] = 'Anweisungen müssen mindestens {$a->min} Zeichen lang sein.';
$string['designerstructurevalidate_fill_instructions_too_long'] = 'Die an die KI gesendeten Anweisungen sind zu lang (maximal {$a->max} Zeichen).';
