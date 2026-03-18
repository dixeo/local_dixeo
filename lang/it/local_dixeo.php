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
$string['pluginname_desc'] = 'Integrazione Dixeo AI per la generazione e modifica intelligente dei contenuti.';

// Capabilities.
$string['dixeo:manage'] = 'Gestire le impostazioni Dixeo e visualizzare i report';
$string['dixeo:generate'] = 'Generare nuovi moduli con IA (pagina, etichetta, quiz, glossario)';
$string['dixeo:edit'] = 'Modificare i moduli esistenti con IA';
$string['dixeo:viewusage'] = 'Visualizzare i report sull\'utilizzo dei crediti';

// Settings page.
$string['api_configuration'] = 'Configurazione API';
$string['api_configuration_desc'] = 'Configurare la connessione all\'API Dixeo AI.';
$string['api_url'] = 'URL API';
$string['api_url_desc'] = 'URL base per l\'API Dixeo. Predefinito: https://api.dixeo.com';
$string['api_key'] = 'Chiave API';
$string['api_key_desc'] = 'La tua chiave API Dixeo. Ottienila dalla dashboard Dixeo.';
$string['namespace'] = 'Namespace';
$string['namespace_desc'] = 'Necessario solo quando più siti Moodle condividono la stessa chiave API. Ogni sito deve usare un namespace diverso (es. "production", "staging", "site1") per mantenere i dati separati. Lascia "default" se questo è l\'unico sito che usa questa chiave API.';
$string['credit_information'] = 'Informazioni sui crediti';
$string['current_balance'] = 'Saldo attuale';
$string['current_balance_desc'] = 'Il tuo saldo crediti Dixeo attuale. I crediti sono usati per le operazioni IA.';
$string['credit_report'] = 'Report crediti';
$string['view_credit_report'] = 'Visualizza report dettagliato crediti';
$string['configure_api'] = 'Configura API';

// Credit balance.
$string['state_active'] = 'Attivo';
$string['state_frozen'] = 'Congelato';
$string['state_suspended'] = 'Sospeso';

// Credit report page.
$string['usage_statistics'] = 'Statistiche di utilizzo';
$string['this_week_usage'] = 'Questa settimana';
$string['week_total'] = 'Totale questa settimana';
$string['recent_transactions'] = 'Cronologia transazioni';
$string['total_used'] = 'Totale utilizzato';
$string['average_per_period'] = 'Media per {$a}';
$string['data_points'] = 'Punti dati';
$string['no_usage_data'] = 'Nessun dato di utilizzo disponibile per il periodo selezionato.';
$string['no_transactions'] = 'Nessuna transazione trovata.';
$string['usage_chart_label'] = 'Utilizzo crediti';

// Day names (short).
$string['day_mon'] = 'Lun';
$string['day_tue'] = 'Mar';
$string['day_wed'] = 'Mer';
$string['day_thu'] = 'Gio';
$string['day_fri'] = 'Ven';
$string['day_sat'] = 'Sab';
$string['day_sun'] = 'Dom';

// Day names (full).
$string['day_monday'] = 'Lunedì';
$string['day_tuesday'] = 'Martedì';
$string['day_wednesday'] = 'Mercoledì';
$string['day_thursday'] = 'Giovedì';
$string['day_friday'] = 'Venerdì';
$string['day_saturday'] = 'Sabato';
$string['day_sunday'] = 'Domenica';

// Periods.
$string['period'] = 'Periodo';
$string['period_day'] = 'Giornaliero';
$string['period_week'] = 'Settimanale';
$string['period_month'] = 'Mensile';

// Transaction types.
$string['transaction_type_purchase'] = 'Acquisto';
$string['transaction_type_deduction'] = 'Utilizzo';
$string['transaction_type_refund'] = 'Rimborso';

// Table headers.
$string['date'] = 'Data';
$string['type'] = 'Tipo';
$string['description'] = 'Descrizione';
$string['amount'] = 'Importo';

// Pagination.
$string['pagination'] = 'Navigazione pagine';
$string['page_x_of_y'] = 'Pagina {$a->current} di {$a->total}';

// Warnings and errors.
$string['api_key_not_configured'] = 'La chiave API Dixeo non è configurata. Configurala nelle impostazioni del plugin.';
$string['api_error'] = 'Errore API: {$a}';
$string['account_frozen_warning'] = 'Il tuo account è congelato per saldo crediti insufficiente. Aggiungi crediti per continuare a usare le funzionalità Dixeo AI.';
$string['account_suspended_warning'] = 'Il tuo account è stato sospeso. Contatta il supporto Dixeo per assistenza.';

// Errors (used in exceptions).
$string['error:authentication'] = 'Autenticazione fallita. Controlla la tua chiave API.';
$string['error:payment_required'] = 'Crediti insufficienti. Aggiungi crediti per continuare.';
$string['error:rate_limit'] = 'Limite di richieste superato. Attendi prima di effettuare altre richieste.';
$string['error:validation'] = 'Richiesta non valida: {$a}';
$string['error:job_not_found'] = 'Il lavoro richiesto non è stato trovato.';
$string['error:openai'] = 'Errore del servizio IA. Riprova più tardi.';
$string['error:job_failed'] = 'Elaborazione del lavoro fallita: {$a}';
$string['error:connection'] = 'Connessione all\'API Dixeo fallita. Controlla la connessione di rete.';
$string['error:timeout'] = 'Operazione scaduta. Puoi controllare lo stato del lavoro in seguito.';

// Overview page.
$string['overview'] = 'Panoramica Dixeo';
$string['credit_balance'] = 'Saldo crediti';
$string['credits'] = 'crediti';

// Privacy.
$string['privacy:metadata'] = 'Il plugin Dixeo invia il contenuto del corso all\'API Dixeo AI per l\'elaborazione ma non memorizza dati personali localmente.';

// DSL errors.
$string['dsl_error'] = 'Creazione del modulo non riuscita: {$a}';

// Quiz question feedback.
$string['feedback_correct'] = 'Corretto!';

// Tasks.
$string['task_cleanup_jobs'] = 'Pulire i vecchi record dei lavori';
$string['task_process_file_sync'] = 'Elaborare la sincronizzazione file Dixeo';

// File sync.
$string['filesync_title'] = 'Sincronizzazione file Dixeo';
$string['filesync_label'] = 'Sincronizza';
$string['filesync_status_none'] = 'Nessun file sincronizzato';
$string['filesync_status_syncing'] = 'Sincronizzazione in corso...';
$string['filesync_status_synchronized'] = 'File sincronizzati';
$string['filesync_status_error'] = 'Errore di sincronizzazione';
$string['filesync_status_outdated'] = 'Contenuto modificato, sincronizzazione necessaria';
$string['filesync_status_paused'] = 'Sincronizzazione in pausa';
$string['filesync_status_disabled'] = 'Sincronizzazione disabilitata';
$string['filesync_enable'] = 'Abilita sincronizzazione';
$string['filesync_pause'] = 'Metti in pausa sincronizzazione';
$string['filesync_disable_remove'] = 'Disattiva e cancella dati di sincronizzazione';
$string['filesync_resync'] = 'Sincronizza ora';
$string['filesync_files_count'] = '{$a} file sincronizzati';
$string['filesync_progress'] = '{$a}% completato';
$string['last_sync'] = 'Ultima sincronizzazione';
$string['filesync_error_retry'] = 'Riprova automatica';
$string['files'] = 'file';
