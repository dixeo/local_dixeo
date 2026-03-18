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
$string['pluginname_desc'] = 'Integração Dixeo AI para geração e edição inteligente de conteúdo.';

// Capabilities.
$string['dixeo:manage'] = 'Gerir definições Dixeo e ver relatórios';
$string['dixeo:generate'] = 'Gerar novos módulos com IA (página, etiqueta, questionário, glossário)';
$string['dixeo:edit'] = 'Editar módulos existentes com IA';
$string['dixeo:viewusage'] = 'Ver relatórios de utilização de créditos';

// Settings page.
$string['api_configuration'] = 'Configuração da API';
$string['api_configuration_desc'] = 'Configurar a ligação à API Dixeo AI.';
$string['api_url'] = 'URL da API';
$string['api_url_desc'] = 'URL base da API Dixeo. Predefinido: https://api.dixeo.com';
$string['api_key'] = 'Chave API';
$string['api_key_desc'] = 'A sua chave API Dixeo. Obtenha uma no painel Dixeo.';
$string['namespace'] = 'Espaço de nomes';
$string['namespace_desc'] = 'Apenas necessário quando vários sites Moodle partilham a mesma chave API. Cada site deve usar um espaço de nomes diferente (ex.: "production", "staging", "site1") para manter os dados separados. Deixe "default" se este for o único site a usar esta chave API.';
$string['credit_information'] = 'Informação de créditos';
$string['current_balance'] = 'Saldo atual';
$string['current_balance_desc'] = 'O seu saldo de créditos Dixeo atual. Os créditos são usados para operações de IA.';
$string['credit_report'] = 'Relatório de créditos';
$string['view_credit_report'] = 'Ver relatório detalhado de créditos';
$string['configure_api'] = 'Configurar API';

// Credit balance.
$string['state_active'] = 'Ativo';
$string['state_frozen'] = 'Congelado';
$string['state_suspended'] = 'Suspenso';

// Credit report page.
$string['usage_statistics'] = 'Estatísticas de utilização';
$string['this_week_usage'] = 'Esta semana';
$string['week_total'] = 'Total desta semana';
$string['recent_transactions'] = 'Histórico de transações';
$string['total_used'] = 'Total utilizado';
$string['average_per_period'] = 'Média por {$a}';
$string['data_points'] = 'Pontos de dados';
$string['no_usage_data'] = 'Sem dados de utilização disponíveis para o período selecionado.';
$string['no_transactions'] = 'Nenhuma transação encontrada.';
$string['usage_chart_label'] = 'Utilização de créditos';

// Day names (short).
$string['day_mon'] = 'Seg';
$string['day_tue'] = 'Ter';
$string['day_wed'] = 'Qua';
$string['day_thu'] = 'Qui';
$string['day_fri'] = 'Sex';
$string['day_sat'] = 'Sáb';
$string['day_sun'] = 'Dom';

// Day names (full).
$string['day_monday'] = 'Segunda-feira';
$string['day_tuesday'] = 'Terça-feira';
$string['day_wednesday'] = 'Quarta-feira';
$string['day_thursday'] = 'Quinta-feira';
$string['day_friday'] = 'Sexta-feira';
$string['day_saturday'] = 'Sábado';
$string['day_sunday'] = 'Domingo';

// Periods.
$string['period'] = 'Período';
$string['period_day'] = 'Diário';
$string['period_week'] = 'Semanal';
$string['period_month'] = 'Mensal';

// Transaction types.
$string['transaction_type_purchase'] = 'Compra';
$string['transaction_type_deduction'] = 'Utilização';
$string['transaction_type_refund'] = 'Reembolso';

// Table headers.
$string['date'] = 'Data';
$string['type'] = 'Tipo';
$string['description'] = 'Descrição';
$string['amount'] = 'Montante';

// Pagination.
$string['pagination'] = 'Navegação de páginas';
$string['page_x_of_y'] = 'Página {$a->current} de {$a->total}';

// Warnings and errors.
$string['api_key_not_configured'] = 'A chave API Dixeo não está configurada. Configure-a nas definições do plugin.';
$string['api_error'] = 'Erro da API: {$a}';
$string['account_frozen_warning'] = 'A sua conta está congelada devido a saldo de créditos insuficiente. Adicione créditos para continuar a usar as funcionalidades Dixeo AI.';
$string['account_suspended_warning'] = 'A sua conta foi suspensa. Contacte o suporte Dixeo para assistência.';

// Errors (used in exceptions).
$string['error:authentication'] = 'Autenticação falhou. Verifique a sua chave API.';
$string['error:payment_required'] = 'Créditos insuficientes. Adicione créditos para continuar.';
$string['error:rate_limit'] = 'Limite de pedidos excedido. Aguarde antes de fazer mais pedidos.';
$string['error:validation'] = 'Pedido inválido: {$a}';
$string['error:job_not_found'] = 'O trabalho solicitado não foi encontrado.';
$string['error:openai'] = 'Erro do serviço de IA. Tente novamente mais tarde.';
$string['error:job_failed'] = 'Falha no processamento do trabalho: {$a}';
$string['error:connection'] = 'Falha na ligação à API Dixeo. Verifique a sua ligação de rede.';
$string['error:timeout'] = 'A operação expirou. Pode verificar o estado do trabalho mais tarde.';

// Overview page.
$string['overview'] = 'Visão geral Dixeo';
$string['credit_balance'] = 'Saldo de créditos';
$string['credits'] = 'créditos';

// Privacy.
$string['privacy:metadata'] = 'O plugin Dixeo envia o conteúdo do curso para a API Dixeo AI para processamento mas não armazena dados pessoais localmente.';

// DSL errors.
$string['dsl_error'] = 'Falha na criação do módulo: {$a}';

// Quiz question feedback.
$string['feedback_correct'] = 'Correto!';

// Tasks.
$string['task_cleanup_jobs'] = 'Limpar registos antigos de trabalhos';
$string['task_process_file_sync'] = 'Processar sincronização de ficheiros Dixeo';

// File sync.
$string['filesync_title'] = 'Sincronização de ficheiros Dixeo';
$string['filesync_label'] = 'Sincronizar';
$string['filesync_status_none'] = 'Nenhum ficheiro sincronizado';
$string['filesync_status_syncing'] = 'A sincronizar ficheiros...';
$string['filesync_status_synchronized'] = 'Ficheiros sincronizados';
$string['filesync_status_error'] = 'Erro de sincronização';
$string['filesync_status_outdated'] = 'Conteúdo alterado, sincronização necessária';
$string['filesync_status_paused'] = 'Sincronização em pausa';
$string['filesync_status_disabled'] = 'Sincronização desativada';
$string['filesync_enable'] = 'Ativar sincronização';
$string['filesync_pause'] = 'Pausar sincronização';
$string['filesync_disable_remove'] = 'Desativar e limpar dados de sincronização';
$string['filesync_resync'] = 'Sincronizar agora';
$string['filesync_files_count'] = '{$a} ficheiros sincronizados';
$string['filesync_progress'] = '{$a}% concluído';
$string['last_sync'] = 'Última sincronização';
$string['filesync_error_retry'] = 'Será repetido automaticamente';
$string['files'] = 'ficheiros';
