<?php

namespace LaudosTecnicos\Controllers;

use App\Controllers\Security_Controller;
use LaudosTecnicos\Libraries\AuditService;
use LaudosTecnicos\Models\LaudoAuditLogs_model;
use LaudosTecnicos\Models\LaudoDocuments_model;
use LaudosTecnicos\Models\LaudoCategories_model;
use LaudosTecnicos\Models\LaudoChecklistResponses_model;
use LaudosTecnicos\Models\LaudoChecklists_model;
use LaudosTecnicos\Models\LaudoInspectionCheckins_model;
use LaudosTecnicos\Models\LaudoInspectionPhotos_model;
use LaudosTecnicos\Models\LaudoInspections_model;
use LaudosTecnicos\Models\LaudoNonconformities_model;
use LaudosTecnicos\Models\LaudoActionPlans_model;
use LaudosTecnicos\Models\LaudoEquipments_model;
use LaudosTecnicos\Models\LaudoMeasurementTypes_model;
use LaudosTecnicos\Models\LaudoMeasurements_model;
use LaudosTecnicos\Models\LaudoNormLinks_model;
use LaudosTecnicos\Models\LaudoNorms_model;
use LaudosTecnicos\Models\LaudoStatusHistory_model;
use LaudosTecnicos\Models\LaudoStatuses_model;
use LaudosTecnicos\Models\LaudoStatusTransitions_model;
use LaudosTecnicos\Models\LaudoSettings_model;
use LaudosTecnicos\Models\LaudoTemplates_model;
use LaudosTecnicos\Models\LaudoTypes_model;
use LaudosTecnicos\Models\Laudos_model;

abstract class LaudosTecnicos_Base_Controller extends Security_Controller
{
    protected Laudos_model $laudos_model;
    protected LaudoTypes_model $types_model;
    protected LaudoCategories_model $categories_model;
    protected LaudoStatuses_model $statuses_model;
    protected LaudoStatusTransitions_model $transitions_model;
    protected LaudoStatusHistory_model $status_history_model;
    protected LaudoSettings_model $settings_model;
    protected LaudoTemplates_model $templates_model;
    protected LaudoChecklists_model $checklists_model;
    protected LaudoChecklistResponses_model $checklist_responses_model;
    protected LaudoInspections_model $inspections_model;
    protected LaudoInspectionCheckins_model $inspection_checkins_model;
    protected LaudoInspectionPhotos_model $inspection_photos_model;
    protected LaudoNonconformities_model $nonconformities_model;
    protected LaudoActionPlans_model $action_plans_model;
    protected LaudoMeasurementTypes_model $measurement_types_model;
    protected LaudoMeasurements_model $measurements_model;
    protected LaudoEquipments_model $equipments_model;
    protected LaudoNorms_model $norms_model;
    protected LaudoNormLinks_model $norm_links_model;
    protected LaudoDocuments_model $documents_model;
    protected LaudoAuditLogs_model $audit_logs_model;
    protected AuditService $audit_service;

    public function __construct()
    {
        // Public document validation/share endpoints must be reachable without
        // a CRM session. All other plugin controllers remain protected.
        $uri = trim((string) uri_string(), '/');
        $is_public_route = (bool) preg_match('#^laudostecnicos/(?:laudos/(?:share/[^/]+(?:/download)?|public/[^/]+/[^/]+)|portal/feedback)$#', $uri);
        parent::__construct(!is_cli() && !$is_public_route);
        \LaudosTecnicos\Plugin::runMigrations();

        $this->laudos_model = model(Laudos_model::class);
        $this->types_model = model(LaudoTypes_model::class);
        $this->categories_model = model(LaudoCategories_model::class);
        $this->statuses_model = model(LaudoStatuses_model::class);
        $this->transitions_model = model(LaudoStatusTransitions_model::class);
        $this->status_history_model = model(LaudoStatusHistory_model::class);
        $this->settings_model = model(LaudoSettings_model::class);
        $this->templates_model = model(LaudoTemplates_model::class);
        $this->checklists_model = model(LaudoChecklists_model::class);
        $this->checklist_responses_model = model(LaudoChecklistResponses_model::class);
        $this->inspections_model = model(LaudoInspections_model::class);
        $this->inspection_checkins_model = model(LaudoInspectionCheckins_model::class);
        $this->inspection_photos_model = model(LaudoInspectionPhotos_model::class);
        $this->nonconformities_model = model(LaudoNonconformities_model::class);
        $this->action_plans_model = model(LaudoActionPlans_model::class);
        $this->measurement_types_model = model(LaudoMeasurementTypes_model::class);
        $this->measurements_model = model(LaudoMeasurements_model::class);
        $this->equipments_model = model(LaudoEquipments_model::class);
        $this->norms_model = model(LaudoNorms_model::class);
        $this->norm_links_model = model(LaudoNormLinks_model::class);
        $this->documents_model = model(LaudoDocuments_model::class);
        $this->audit_logs_model = model(LaudoAuditLogs_model::class);
        $this->audit_service = new AuditService();
    }

    protected function ensureAccess()
    {
        if (!\LaudosTecnicos\Plugin::canAccessModule($this->login_user)) {
            app_redirect('forbidden');
        }
    }

    protected function ensureSettingsAccess()
    {
        if (!\LaudosTecnicos\Plugin::canManageSettings($this->login_user) && !empty($this->login_user) && empty($this->login_user->is_admin)) {
            app_redirect('forbidden');
        }
    }

    protected function ensureTypesAccess()
    {
        if (!\LaudosTecnicos\Plugin::canManageTypes($this->login_user) && !empty($this->login_user) && empty($this->login_user->is_admin)) {
            app_redirect('forbidden');
        }
    }

    protected function ensureCategoriesAccess()
    {
        if (!\LaudosTecnicos\Plugin::canManageCategories($this->login_user) && !empty($this->login_user) && empty($this->login_user->is_admin)) {
            app_redirect('forbidden');
        }
    }

    protected function ensureStatusesAccess()
    {
        if (!\LaudosTecnicos\Plugin::canManageStatuses($this->login_user) && !empty($this->login_user) && empty($this->login_user->is_admin)) {
            app_redirect('forbidden');
        }
    }

    protected function ensureTransitionsAccess()
    {
        if (!\LaudosTecnicos\Plugin::canManageTransitions($this->login_user) && !empty($this->login_user) && empty($this->login_user->is_admin)) {
            app_redirect('forbidden');
        }
    }

    protected function ensureTemplatesAccess()
    {
        if (!\LaudosTecnicos\Plugin::canManageTemplates($this->login_user) && !empty($this->login_user) && empty($this->login_user->is_admin)) {
            app_redirect('forbidden');
        }
    }

    protected function ensureChecklistsAccess()
    {
        if (!\LaudosTecnicos\Plugin::canManageChecklists($this->login_user) && !empty($this->login_user) && empty($this->login_user->is_admin)) {
            app_redirect('forbidden');
        }
    }

    protected function ensureInspectionsAccess()
    {
        if (!\LaudosTecnicos\Plugin::canManageInspections($this->login_user) && !empty($this->login_user) && empty($this->login_user->is_admin)) {
            app_redirect('forbidden');
        }
    }

    protected function ensureNonconformitiesAccess()
    {
        if (!\LaudosTecnicos\Plugin::canManageNonconformities($this->login_user) && !empty($this->login_user) && empty($this->login_user->is_admin)) {
            app_redirect('forbidden');
        }
    }

    protected function ensureRiskMatrixAccess()
    {
        if (!\LaudosTecnicos\Plugin::canManageRiskMatrix($this->login_user) && !empty($this->login_user) && empty($this->login_user->is_admin)) {
            app_redirect('forbidden');
        }
    }

    protected function ensureActionPlansAccess()
    {
        if (!\LaudosTecnicos\Plugin::canManageActionPlans($this->login_user) && !empty($this->login_user) && empty($this->login_user->is_admin)) {
            app_redirect('forbidden');
        }
    }

    protected function ensureMeasurementsAccess()
    {
        if (!\LaudosTecnicos\Plugin::canManageMeasurements($this->login_user) && !empty($this->login_user) && empty($this->login_user->is_admin)) {
            app_redirect('forbidden');
        }
    }

    protected function ensureEquipmentsAccess()
    {
        if (!\LaudosTecnicos\Plugin::canManageEquipments($this->login_user) && !empty($this->login_user) && empty($this->login_user->is_admin)) {
            app_redirect('forbidden');
        }
    }

    protected function ensureNormsAccess()
    {
        if (!\LaudosTecnicos\Plugin::canManageNorms($this->login_user) && !empty($this->login_user) && empty($this->login_user->is_admin)) {
            app_redirect('forbidden');
        }
    }

    protected function ensureLaudosAccess()
    {
        if (!\LaudosTecnicos\Plugin::canViewLaudos($this->login_user) && !empty($this->login_user) && empty($this->login_user->is_admin)) {
            app_redirect('forbidden');
        }
    }

    protected function renderPluginView($relative_path, $data = array())
    {
        $relative_path = trim((string) $relative_path, "/\\");
        $view_path = rtrim(PLUGINPATH, '/\\') . DIRECTORY_SEPARATOR . 'LaudosTecnicos' . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $relative_path) . '.php';

        if (!is_file($view_path)) {
            throw new \RuntimeException('Invalid plugin view file: ' . $view_path);
        }

        extract($data, EXTR_SKIP);
        ob_start();
        include $view_path;
        return ob_get_clean();
    }

    protected function logAudit(string $entity_type, int $entity_id, string $action, string $description = '', array $old_values = array(), array $new_values = array(), string $source = 'web')
    {
        return $this->audit_service->log(array(
            'entity_type' => $entity_type,
            'entity_id' => $entity_id,
            'action' => $action,
            'description' => $description,
            'old_values_json' => laudostecnicos_safe_json($old_values),
            'new_values_json' => laudostecnicos_safe_json($new_values),
            'user_id' => (int) ($this->login_user->id ?? 0),
            'created_by' => (int) ($this->login_user->id ?? 0),
            'ip_address' => $this->request->getIPAddress(),
            'source' => $source,
            'created_at' => get_current_utc_time(),
        ));
    }

    protected function logStatusHistory(int $laudo_id, ?string $from_status_code, string $to_status_code, string $comment = '', string $source = 'web')
    {
        return $this->status_history_model->log_change(array(
            'laudo_id' => $laudo_id,
            'from_status_code' => $from_status_code,
            'to_status_code' => $to_status_code,
            'user_id' => (int) ($this->login_user->id ?? 0),
            'comment' => $comment,
            'source' => $source,
            'ip_address' => $this->request->getIPAddress(),
        ));
    }
}
