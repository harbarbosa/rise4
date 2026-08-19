<?php

namespace Engenharia\Services;

use Engenharia\Models\Checklists_model;

class ChecklistVersionService
{
    private $checklists;

    public function __construct(?Checklists_model $checklists = null)
    {
        $this->checklists = $checklists ?: model(Checklists_model::class);
    }

    public function snapshotForLaudo(int $checklist_id): array
    {
        return $this->checklists->snapshot($checklist_id);
    }

    public function snapshotJson(int $checklist_id): string
    {
        $snapshot = $this->snapshotForLaudo($checklist_id);
        return $snapshot ? json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
    }

    public function createVersion(int $source_id, array $data, int $user_id): int
    {
        return $this->checklists->create_version($source_id, $data, $user_id);
    }
}
