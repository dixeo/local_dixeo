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
$string['pluginname_desc'] = 'Integración Dixeo AI para generación y edición inteligente de contenido.';

// Capabilities.
$string['dixeo:manage'] = 'Gestionar la configuración de Dixeo y ver informes';
$string['dixeo:generate'] = 'Generar nuevos módulos con IA (página, etiqueta, cuestionario, glosario)';
$string['dixeo:edit'] = 'Editar módulos existentes con IA';
$string['dixeo:viewusage'] = 'Ver informes de uso de créditos';

// Settings page.
$string['api_configuration'] = 'Configuración de la API';
$string['api_configuration_desc'] = 'Configurar la conexión a la API Dixeo AI.';
$string['api_url'] = 'URL de la API';
$string['api_url_desc'] = 'URL base de la API Dixeo. Por defecto: https://api.dixeo.com';
$string['api_key'] = 'Clave API';
$string['api_key_desc'] = 'Su clave API de Dixeo. Obténgala desde el panel de Dixeo.';
$string['namespace'] = 'Espacio de nombres';
$string['namespace_desc'] = 'Solo necesario cuando varios sitios Moodle comparten la misma clave API. Cada sitio debe usar un espacio de nombres diferente (ej. "production", "staging", "site1") para mantener sus datos separados. Deje "default" si este es el único sitio que usa esta clave API.';
$string['credit_information'] = 'Información de créditos';
$string['current_balance'] = 'Saldo actual';
$string['current_balance_desc'] = 'Su saldo de créditos Dixeo actual. Los créditos se utilizan para operaciones de IA.';
$string['credit_report'] = 'Informe de créditos';
$string['view_credit_report'] = 'Ver informe detallado de créditos';
$string['configure_api'] = 'Configurar API';

// Credit balance.
$string['state_active'] = 'Activo';
$string['state_frozen'] = 'Congelado';
$string['state_suspended'] = 'Suspendido';

// Credit report page.
$string['usage_statistics'] = 'Estadísticas de uso';
$string['this_week_usage'] = 'Esta semana';
$string['week_total'] = 'Total esta semana';
$string['recent_transactions'] = 'Historial de transacciones';
$string['total_used'] = 'Total utilizado';
$string['average_per_period'] = 'Media por {$a}';
$string['data_points'] = 'Puntos de datos';
$string['no_usage_data'] = 'No hay datos de uso disponibles para el período seleccionado.';
$string['no_transactions'] = 'No se encontraron transacciones.';
$string['usage_chart_label'] = 'Uso de créditos';

// Day names (short).
$string['day_mon'] = 'Lun';
$string['day_tue'] = 'Mar';
$string['day_wed'] = 'Mié';
$string['day_thu'] = 'Jue';
$string['day_fri'] = 'Vie';
$string['day_sat'] = 'Sáb';
$string['day_sun'] = 'Dom';

// Day names (full).
$string['day_monday'] = 'Lunes';
$string['day_tuesday'] = 'Martes';
$string['day_wednesday'] = 'Miércoles';
$string['day_thursday'] = 'Jueves';
$string['day_friday'] = 'Viernes';
$string['day_saturday'] = 'Sábado';
$string['day_sunday'] = 'Domingo';

// Periods.
$string['period'] = 'Período';
$string['period_day'] = 'Diario';
$string['period_week'] = 'Semanal';
$string['period_month'] = 'Mensual';

// Transaction types.
$string['transaction_type_purchase'] = 'Compra';
$string['transaction_type_deduction'] = 'Uso';
$string['transaction_type_refund'] = 'Reembolso';
$string['transaction_type_reset'] = 'Renovación';

// Table headers.
$string['date'] = 'Fecha';
$string['type'] = 'Tipo';
$string['description'] = 'Descripción';
$string['amount'] = 'Cantidad';

// Pagination.
$string['pagination'] = 'Navegación de páginas';
$string['page_x_of_y'] = 'Página {$a->current} de {$a->total}';

// Warnings and errors.
$string['api_key_not_configured'] = 'La clave API de Dixeo no está configurada. Configúrela en los ajustes del plugin.';
$string['api_error'] = 'Error de API: {$a}';
$string['account_frozen_warning'] = 'Su cuenta está congelada por saldo de créditos insuficiente. Añada créditos para seguir usando las funciones de Dixeo AI.';
$string['account_suspended_warning'] = 'Su cuenta ha sido suspendida. Contacte con el soporte de Dixeo para ayuda.';

// Errors (used in exceptions).
$string['error:authentication'] = 'Error de autenticación. Compruebe su clave API.';
$string['error:payment_required'] = 'Créditos insuficientes. Añada créditos para continuar.';
$string['error:rate_limit'] = 'Límite de solicitudes superado. Espere antes de hacer más solicitudes.';
$string['error:validation'] = 'Solicitud no válida: {$a}';
$string['error:job_not_found'] = 'No se encontró el trabajo solicitado.';
$string['error:upstream_ai'] = 'Error del servicio de IA. Inténtelo de nuevo más tarde.';
$string['error:job_failed'] = 'Error al procesar el trabajo: {$a}';
$string['error:connection'] = 'Error de conexión con la API Dixeo. Compruebe su conexión de red.';
$string['error:timeout'] = 'La operación ha caducado. Puede comprobar el estado del trabajo más tarde.';

// Overview page.
$string['overview'] = 'Resumen de Dixeo';
$string['credit_balance'] = 'Saldo de créditos';
$string['credits'] = 'créditos';

// Privacy.
$string['privacy:metadata'] = 'El plugin Dixeo envía el contenido del curso a la API Dixeo AI para su procesamiento pero no almacena datos personales localmente.';

// DSL errors.
$string['dsl_error'] = 'Error al crear el módulo: {$a}';

// Quiz question feedback.
$string['feedback_correct'] = '¡Correcto!';

// Tasks.
$string['task_cleanup_jobs'] = 'Limpiar registros de trabajos antiguos';
$string['task_process_file_sync'] = 'Procesar sincronización de archivos Dixeo';

// File sync.
$string['filesync_title'] = 'Sincronización de archivos Dixeo';
$string['filesync_label'] = 'Sincronizar';
$string['filesync_status_none'] = 'Ningún archivo sincronizado';
$string['filesync_status_syncing'] = 'Sincronizando archivos...';
$string['filesync_status_synchronized'] = 'Archivos sincronizados';
$string['filesync_status_error'] = 'Error de sincronización';
$string['filesync_status_outdated'] = 'Contenido modificado, sincronización necesaria';
$string['filesync_status_paused'] = 'Sincronización en pausa';
$string['filesync_status_disabled'] = 'Sincronización desactivada';
$string['filesync_enable'] = 'Activar sincronización';
$string['filesync_pause'] = 'Pausar sincronización';
$string['filesync_disable_remove'] = 'Desactivar y borrar datos de sincronización';
$string['filesync_resync'] = 'Sincronizar ahora';
$string['filesync_files_count'] = '{$a} archivos sincronizados';
$string['filesync_progress'] = '{$a}% completado';
$string['last_sync'] = 'Última sincronización';
$string['filesync_error_retry'] = 'Se reintentará automáticamente';
$string['files'] = 'archivos';
