<?php

namespace Engenharia\Services;

use Engenharia\Models\Checklists_model;
use Engenharia\Models\Laudo_types_model;
use Engenharia\Models\Laudos_model;

class LaudoDomainService
{
    private $laudos;
    private $types;
    private $checklists;
    private $numbering;

    public function __construct(?Laudos_model $laudos = null, ?Laudo_types_model $types = null, ?Checklists_model $checklists = null, ?LaudoNumberingService $numbering = null)
    {
        $this->laudos = $laudos ?: model(Laudos_model::class);
        $this->types = $types ?: model(Laudo_types_model::class);
        $this->checklists = $checklists ?: model(Checklists_model::class);
        $this->numbering = $numbering ?: new LaudoNumberingService();
    }

    public function createDraft(array $data, $login_user): int
    {
        $this->assertPermission($login_user, 'engenharia_create_laudos');

        $type_id = (int) ($data['type_id'] ?? 0);
        $type = $this->types->get_by_code((string) ($data['type_code'] ?? ''));
        if (!$type && $type_id) {
            $type = $this->types->get_one($type_id);
        }
        if (!$type || empty($type->id) || empty($type->is_enabled) || !empty($type->deleted)) {
            throw new \InvalidArgumentException('Laudo type is unavailable.');
        }

        $data['type_id'] = (int) $type->id;
        $data['created_by'] = (int) $login_user->id;
        $data['updated_by'] = (int) $login_user->id;
        $data['status'] = $data['status'] ?? LaudoWorkflowService::DRAFT;

        $number = $this->numbering->reserve((int) $type->id, (string) ($type->prefix ?? 'ENG-'));
        if (!$number['number']) {
            throw new \RuntimeException('Could not reserve laudo number.');
        }
        $data['number'] = $number['number'];

        $checklist_id = (int) ($data['checklist_id'] ?? 0);
        if ($checklist_id) {
            $checklist = $this->checklists->get_version($checklist_id);
            if (!$checklist || $checklist->status !== 'published') {
                throw new \InvalidArgumentException('Only published checklist versions can be attached.');
            }
            $data['checklist_version'] = (int) $checklist->version;
            $data['checklist_snapshot_json'] = json_encode($this->checklists->snapshot($checklist_id), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return $this->laudos->create($data);
    }

    public function updateDraft(int $laudo_id, array $data, $login_user): bool
    {
        $this->assertPermission($login_user, 'engenharia_edit_laudos');
        $laudo = $this->laudos->get_one($laudo_id);
        if (!$laudo || empty($laudo->id) || in_array($laudo->status, array(LaudoWorkflowService::FINALIZED, LaudoWorkflowService::CANCELED), true)) {
            throw new \RuntimeException('Finalized or canceled laudos cannot be edited.');
        }

        $type = $this->types->get_one((int) ($data['type_id'] ?? $laudo->type_id));
        if (!$type || empty($type->is_enabled) || !empty($type->deleted)) {
            throw new \InvalidArgumentException('Laudo type is unavailable.');
        }

        $data['updated_by'] = (int) $login_user->id;
        unset($data['number'], $data['created_by']);
        $new_checklist_id = (int) ($data['checklist_id'] ?? 0);
        if ($new_checklist_id && $new_checklist_id !== (int) $laudo->checklist_id) {
            $checklist = $this->checklists->get_version($new_checklist_id);
            if (!$checklist || $checklist->status !== 'published' || (int) $checklist->type_id !== (int) $type->id) {
                throw new \InvalidArgumentException('Only a published checklist for the selected type can be attached.');
            }
            $data['checklist_version'] = (int) $checklist->version;
            $data['checklist_snapshot_json'] = json_encode($this->checklists->snapshot($new_checklist_id), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            unset($data['checklist_snapshot_json'], $data['checklist_version']);
        }
        $this->laudos->validate_crm_references($data);
        return $this->laudos->update_domain($laudo_id, $data);
    }

    private function assertPermission($login_user, string $permission): void
    {
        if (!\Engenharia\Plugin::hasPermission($login_user, $permission)) {
            throw new \RuntimeException('Engenharia permission denied: ' . $permission);
        }
    }
}
