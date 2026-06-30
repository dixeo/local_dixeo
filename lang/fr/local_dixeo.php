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
$string['pluginname_desc'] = 'Intégration Dixeo AI pour la génération et l\'édition intelligente de contenu.';

// Capabilities.
$string['dixeo:manage'] = 'Gérer les paramètres Dixeo et consulter les rapports';
$string['dixeo:generate'] = 'Générer de nouveaux modules avec l\'IA (page, étiquette, test, glossaire)';
$string['dixeo:edit'] = 'Modifier les modules existants avec l\'IA';
$string['dixeo:create'] = 'Créer des cours avec le Concepteur de Cours Dixeo';
$string['dixeo:viewusage'] = 'Consulter les rapports d\'utilisation des crédits';

// Settings page.
$string['api_configuration'] = 'Configuration de l\'API';
$string['api_configuration_desc'] = 'Configurer la connexion à l\'API Dixeo AI.';
$string['api_url'] = 'URL de l\'API';
$string['api_url_desc'] = 'URL de base de l\'API Dixeo. Par défaut : https://api.dixeo.com';
$string['api_key'] = 'Clé API';
$string['api_key_desc'] = 'Votre clé API Dixeo. Obtenez-en une depuis le tableau de bord Dixeo.';
$string['namespace'] = 'Espace de noms';
$string['namespace_desc'] = 'Requis uniquement lorsque plusieurs sites Moodle partagent la même clé API. Chaque site doit utiliser un espace de noms différent (ex. « production », « staging », « site1 ») pour garder les données séparées. Laissez « default » si c\'est le seul site utilisant cette clé API.';
$string['image_generation'] = 'Génération d\'images';
$string['image_generation_desc'] = 'Contrôle la disponibilité de la génération et de la modification d\'images par IA pour les images de cours et de section.';
$string['image_generation_enabled'] = 'Activer la génération d\'images';
$string['image_generation_enabled_desc'] = 'Si désactivé, toutes les demandes de génération ou de modification d\'images sont bloquées.';
$string['image_generation_course_mode'] = 'Images de cours';
$string['image_generation_course_mode_desc'] = 'Contrôle les actions d\'image IA pour l\'image d\'aperçu du cours.';
$string['image_generation_section_mode'] = 'Images de section';
$string['image_generation_section_mode_desc'] = 'Contrôle les actions d\'image IA pour les images de chapitre ou de section.';
$string['image_generation_mode_disabled'] = 'Désactivé';
$string['image_generation_mode_generate'] = 'Générer';
$string['image_generation_mode_generate_edit'] = 'Générer et modifier';
$string['credit_information'] = 'Informations sur les crédits';
$string['current_balance'] = 'Solde actuel';
$string['current_balance_desc'] = 'Votre solde de crédits Dixeo actuel. Les crédits sont utilisés pour les opérations IA.';
$string['credit_report'] = 'Rapport de crédits';
$string['view_credit_report'] = 'Voir le rapport détaillé des crédits';
$string['configure_api'] = 'Configurer l\'API';

// Credit balance.
$string['state_active'] = 'Actif';
$string['state_frozen'] = 'Gelé';
$string['state_suspended'] = 'Suspendu';

// Credit report page.
$string['usage_statistics'] = 'Statistiques d\'utilisation';
$string['this_week_usage'] = 'Cette semaine';
$string['week_total'] = 'Total cette semaine';
$string['recent_transactions'] = 'Historique des transactions';
$string['total_used'] = 'Total utilisé';
$string['average_per_period'] = 'Moyenne par {$a}';
$string['data_points'] = 'Points de données';
$string['no_usage_data'] = 'Aucune donnée d\'utilisation disponible pour la période sélectionnée.';
$string['no_transactions'] = 'Aucune transaction trouvée.';
$string['usage_chart_label'] = 'Utilisation des crédits';

// Day names (short).
$string['day_mon'] = 'Lun';
$string['day_tue'] = 'Mar';
$string['day_wed'] = 'Mer';
$string['day_thu'] = 'Jeu';
$string['day_fri'] = 'Ven';
$string['day_sat'] = 'Sam';
$string['day_sun'] = 'Dim';

// Day names (full).
$string['day_monday'] = 'Lundi';
$string['day_tuesday'] = 'Mardi';
$string['day_wednesday'] = 'Mercredi';
$string['day_thursday'] = 'Jeudi';
$string['day_friday'] = 'Vendredi';
$string['day_saturday'] = 'Samedi';
$string['day_sunday'] = 'Dimanche';

// Periods.
$string['period'] = 'Période';
$string['period_day'] = 'Quotidien';
$string['period_week'] = 'Hebdomadaire';
$string['period_month'] = 'Mensuel';

// Transaction types.
$string['transaction_type_purchase'] = 'Achat';
$string['transaction_type_deduction'] = 'Utilisation';
$string['transaction_type_refund'] = 'Remboursement';
$string['transaction_type_reset'] = 'Renouvellement';

// Table headers.
$string['date'] = 'Date';
$string['type'] = 'Type';
$string['description'] = 'Description';
$string['amount'] = 'Montant';

// Pagination.
$string['pagination'] = 'Navigation des pages';
$string['page_x_of_y'] = 'Page {$a->current} sur {$a->total}';

// Warnings and errors.
$string['api_key_not_configured'] = 'La clé API Dixeo n\'est pas configurée. Veuillez la configurer dans les paramètres du plugin.';
$string['api_error'] = 'Erreur API : {$a}';
$string['account_frozen_warning'] = 'Votre compte est gelé en raison d\'un solde de crédits insuffisant. Veuillez ajouter des crédits pour continuer à utiliser les fonctionnalités Dixeo AI.';
$string['account_suspended_warning'] = 'Votre compte a été suspendu. Veuillez contacter le support Dixeo pour assistance.';

// Errors (used in exceptions).
$string['error:authentication'] = 'Échec de l\'authentification. Veuillez vérifier votre clé API.';
$string['error:payment_required'] = 'Crédits insuffisants. Veuillez ajouter des crédits pour continuer.';
$string['error:rate_limit'] = 'Limite de requêtes dépassée. Veuillez patienter avant d\'effectuer d\'autres requêtes.';
$string['error:validation'] = 'Requête invalide : {$a}';
$string['error:job_not_found'] = 'Le travail demandé n\'a pas été trouvé.';
$string['error:upstream_ai'] = 'Erreur du service IA. Veuillez réessayer plus tard.';
$string['error:job_failed'] = 'Échec du traitement du travail : {$a}';
$string['error:connection'] = 'Échec de la connexion à l\'API Dixeo. Veuillez vérifier votre connexion réseau.';
$string['error:timeout'] = 'L\'opération a expiré. Vous pouvez vérifier le statut du travail plus tard.';
$string['error:notslideshow'] = 'Le module de cours n\'est pas une activité diaporama.';
$string['error:slidenotinslideshow'] = 'La diapositive demandée n\'appartient pas à ce diaporama.';

// Overview page.
$string['overview'] = 'Aperçu Dixeo';
$string['credit_balance'] = 'Solde de crédits';
$string['credits'] = 'crédits';

// Privacy.
$string['privacy:metadata'] = 'Le plugin Dixeo envoie le contenu des cours à l\'API Dixeo AI pour traitement mais ne stocke pas de données personnelles localement.';

// DSL errors.
$string['dsl_error'] = 'Échec de la création du module : {$a}';

// Quiz question feedback.
$string['feedback_correct'] = 'Correct !';

// Tasks.
$string['task_cleanup_jobs'] = 'Nettoyer les anciens enregistrements de travaux';
$string['task_process_file_sync'] = 'Traiter la synchronisation des fichiers Dixeo';
$string['task_poll_image_generation'] = 'Interroger la tâche de génération d\'images Dixeo';
$string['dixeo_course_image_unsupported_type'] = 'Type d\'image générée non pris en charge.';
$string['dixeo_image_job_empty_result'] = 'La tâche d\'image n\'a renvoyé aucune donnée d\'image.';
$string['dixeo_image_generation_disabled'] = 'La génération d\'images est désactivée par les paramètres du site.';
$string['dixeo_pluginfile_not_found'] = 'Impossible de lire le fichier image depuis le stockage.';

// File sync.
$string['filesync_title'] = 'Synchronisation de fichiers Dixeo';
$string['filesync_label'] = 'Synchroniser';
$string['filesync_status_none'] = 'Aucun fichier synchronisé';
$string['filesync_status_syncing'] = 'Synchronisation en cours...';
$string['filesync_status_synchronized'] = 'Fichiers synchronisés';
$string['filesync_status_error'] = 'Erreur de synchronisation';
$string['filesync_status_outdated'] = 'Contenu modifié, synchronisation nécessaire';
$string['filesync_status_paused'] = 'Synchronisation en pause';
$string['filesync_status_disabled'] = 'Synchronisation désactivée';
$string['filesync_enable'] = 'Activer la synchronisation';
$string['filesync_pause'] = 'Mettre en pause la synchronisation';
$string['filesync_disable_remove'] = 'Désactiver et effacer les données de synchronisation';
$string['filesync_resync'] = 'Synchroniser maintenant';
$string['filesync_files_count'] = '{$a} fichiers synchronisés';
$string['filesync_progress'] = '{$a} % terminé';
$string['last_sync'] = 'Dernière synchronisation';
$string['filesync_error_retry'] = 'Nouvelle tentative automatique';
$string['files'] = 'fichiers';

// Designer structure validation (finalize / course creation).
$string['designerstructurevalidate_failed'] = 'Ce cours ne peut pas être créé tant que ces problèmes ne sont pas résolus :

{$a->details}';
$string['designerstructurevalidate_invalid_root'] = 'Les données de structure du cours sont invalides.';
$string['designerstructurevalidate_sections_not_array'] = 'La liste des sections de la structure du cours est invalide.';
$string['designerstructurevalidate_section_invalid'] = 'La section {$a} de la structure est invalide.';
$string['designerstructurevalidate_modules_not_array'] = 'La liste des modules de la section {$a} est invalide.';
$string['designerstructurevalidate_module_invalid'] = 'Le module à la position {$a->module} dans la section {$a->section} est invalide.';
$string['designerstructurevalidate_aggregate_prefix_section'] = 'Section {$a->section}, activité {$a->module} :';
$string['designerstructurevalidate_aggregate_prefix_section_only'] = 'Section {$a->section} :';
$string['designerstructurevalidate_course_title_required'] = 'Le titre du cours est un champ obligatoire.';
$string['designerstructurevalidate_course_title_too_long'] = 'Le titre du cours doit comporter au maximum {$a->max} caractères.';
$string['designerstructurevalidate_course_summary_too_long'] = 'Le résumé du cours est trop long (maximum {$a->max} caractères).';
$string['designerstructurevalidate_section_title_too_long'] = 'Le titre de la section est trop long (maximum {$a->max} caractères).';
$string['designerstructurevalidate_section_summary_too_long'] = 'Le résumé de la section est trop long (maximum {$a->max} caractères).';
$string['designerstructurevalidate_module_type_required'] = 'Le type d\'activité est un champ obligatoire.';
$string['designerstructurevalidate_module_type_not_usable'] = 'Le type « {$a->type} » ne peut pas être utilisé sur ce site (plugin manquant ou bibliothèque de contenu requise).';
$string['designerstructurevalidate_module_title_required'] = 'Le titre de l\'activité est un champ obligatoire.';
$string['designerstructurevalidate_module_title_placeholder'] = 'Remplacez le titre par défaut « Nouvelle page » par un vrai nom d\'activité.';
$string['designerstructurevalidate_module_title_too_long'] = 'Le titre de l\'activité est trop long (maximum {$a->max} caractères).';
$string['designerstructurevalidate_module_summary_placeholder'] = 'Remplacez le résumé par défaut par une vraie description de ce que couvre cette activité.';
$string['designerstructurevalidate_module_summary_too_long'] = 'Le résumé de l\'activité est trop long (maximum {$a->max} caractères).';
$string['designerstructurevalidate_module_instructions_required'] = 'Les consignes pour l\'IA sont obligatoires (au moins {$a->min} caractères).';
$string['designerstructurevalidate_module_instructions_too_long'] = 'Les consignes sont trop longues (maximum {$a->max} caractères).';
$string['designerstructurevalidate_instructions_api_min'] = 'Les consignes doivent comporter au moins {$a->min} caractères.';
$string['designerstructurevalidate_fill_instructions_too_long'] = 'Les consignes envoyées à l\'IA sont trop longues (maximum {$a->max} caractères).';
