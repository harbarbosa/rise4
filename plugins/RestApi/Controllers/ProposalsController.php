<?php

namespace RestApi\Controllers;

use App\Models\Events_model;
use App\Models\Items_model;
use App\Models\Tasks_model;
use Proposals\Models\Proposal_items_model;
use Proposals\Models\Proposal_sections_model;
use Proposals\Models\Proposals_model;
use Proposals\Models\Proposals_module_settings_model;

class ProposalsController extends ModuleApiController
{
    protected Proposals_model $proposalsModel;
    protected Proposal_sections_model $sectionsModel;
    protected Proposal_items_model $itemsModel;
    protected Proposals_module_settings_model $settingsModel;
    protected Items_model $productsModel;
    protected Tasks_model $tasksModel;
    protected Events_model $eventsModel;

    public function __construct()
    {
        parent::__construct();
        $this->proposalsModel = model(Proposals_model::class);
        $this->sectionsModel = model(Proposal_sections_model::class);
        $this->itemsModel = model(Proposal_items_model::class);
        $this->settingsModel = model(Proposals_module_settings_model::class);
        $this->productsModel = model(Items_model::class);
        $this->tasksModel = model(Tasks_model::class);
        $this->eventsModel = model(Events_model::class);
    }

    public function index()
    {
        $query = $this->proposalsModel->get_details($this->proposalFilters());
        $rows = $query ? $query->getResultArray() : [];

        return $this->respond([
            'status' => true,
            'resource' => 'proposals',
            'count' => count($rows),
            'data' => $rows,
        ]);
    }

    public function show(int $id = 0)
    {
        $id = $id ?: (int) $this->request->getGet('id');
        if ($id <= 0) {
            return $this->failValidationErrors('Invalid proposal id.');
        }

        $row = $this->proposalsModel->get_details(['id' => $id])->getRowArray();
        if (!$row) {
            return $this->failNotFound('Proposal not found.');
        }

        return $this->respondData($row, ['resource' => 'proposal', 'id' => $id]);
    }

    public function store(int $id = 0)
    {
        $payload = $this->payload();
        $id = $id > 0 ? $id : (int) ($payload['id'] ?? 0);
        $table = 'proposals_custom';
        $data = $this->filterPayload($table, $payload, ['id']);

        if (!$data) {
            return $this->failValidationErrors('No valid fields were provided.');
        }

        $this->normalizeDecimalFields($data, [
            'price', 'commission_value', 'tax_product_percent', 'tax_service_percent',
            'total_cost_material', 'total_cost_service', 'total_sale', 'taxes_total',
            'commission_total', 'profit_gross', 'profit_net'
        ]);

        if (!array_key_exists('status', $data) && !$id) {
            $data['status'] = 'draft';
        }

        $saved = $this->proposalsModel->save($id ? array_merge($data, ['id' => $id]) : $data);
        if (!$saved) {
            return $this->failValidationErrors('Could not save proposal.');
        }

        $proposalId = $id ?: (int) db_connect('default')->insertID();

        return $this->respondCreated([
            'status' => true,
            'message' => 'Proposal saved successfully.',
            'id' => $proposalId,
            'data' => $this->proposalsModel->get_details(['id' => $proposalId])->getRowArray(),
        ]);
    }

    public function delete(int $id = 0)
    {
        $id = $id ?: (int) $this->request->getPost('id');
        if ($id <= 0) {
            return $this->failValidationErrors('Invalid proposal id.');
        }

        if (!$this->proposalsModel->delete($id)) {
            return $this->failValidationErrors('Could not delete proposal.');
        }

        return $this->respondDeleted([
            'status' => true,
            'message' => 'Proposal deleted successfully.',
        ]);
    }

    public function sections(int $proposalId = 0)
    {
        $proposalId = $proposalId ?: (int) $this->request->getGet('proposal_id');
        if ($proposalId <= 0) {
            return $this->failValidationErrors('Invalid proposal id.');
        }

        $rows = $this->sectionsModel->get_details(['proposal_id' => $proposalId])->getResultArray();
        return $this->respond([
            'status' => true,
            'resource' => 'proposal_sections',
            'proposal_id' => $proposalId,
            'count' => count($rows),
            'data' => $rows,
        ]);
    }

    public function saveSection(int $proposalId = 0)
    {
        $payload = $this->payload();
        $proposalId = $proposalId ?: (int) ($payload['proposal_id'] ?? 0);
        if ($proposalId <= 0) {
            return $this->failValidationErrors('Invalid proposal id.');
        }

		$data = $this->filterPayload('proposal_sections_custom', $payload, ['id']);
		$data['proposal_id'] = $proposalId;
		if (!array_key_exists('sort', $data)) {
			$row = db_connect('default')->table(db_connect('default')->prefixTable('proposal_sections_custom'))
				->select('MAX(sort) AS max_sort')
				->where('proposal_id', $proposalId)
				->where('deleted', 0)
				->get()->getRow();
			$max = (int) ($row->max_sort ?? 0);
			$data['sort'] = $max + 1;
		}

		$saved = $this->sectionsModel->save($data);
		if (!$saved) {
			return $this->failValidationErrors('Could not save section.');
		}

		$savedId = (int) db_connect('default')->insertID();
		return $this->respondCreated([
			'status' => true,
			'message' => 'Section saved successfully.',
			'id' => $savedId,
		]);
    }

    public function deleteSection(int $id = 0)
    {
        $id = $id ?: (int) $this->request->getPost('id');
        if ($id <= 0) {
            return $this->failValidationErrors('Invalid section id.');
        }

        if (!$this->sectionsModel->delete($id)) {
            return $this->failValidationErrors('Could not delete section.');
        }

        return $this->respondDeleted([
            'status' => true,
            'message' => 'Section deleted successfully.',
        ]);
    }

    public function items(int $proposalId = 0)
    {
        $proposalId = $proposalId ?: (int) $this->request->getGet('proposal_id');
        if ($proposalId <= 0) {
            return $this->failValidationErrors('Invalid proposal id.');
        }

        $rows = $this->itemsModel->get_details(['proposal_id' => $proposalId])->getResultArray();
        return $this->respond([
            'status' => true,
            'resource' => 'proposal_items',
            'proposal_id' => $proposalId,
            'count' => count($rows),
            'data' => $rows,
        ]);
    }

    public function saveItem(int $proposalId = 0)
    {
        $payload = $this->payload();
        $proposalId = $proposalId ?: (int) ($payload['proposal_id'] ?? 0);
        if ($proposalId <= 0) {
            return $this->failValidationErrors('Invalid proposal id.');
        }

        $data = $this->filterPayload('proposal_items_custom', $payload, ['id']);
        $data['proposal_id'] = $proposalId;
        $this->normalizeDecimalFields($data, ['cost_unit', 'qty', 'markup_percent', 'sale_unit', 'total']);
        $this->normalizeIntFields($data, ['section_id', 'item_id', 'show_in_proposal', 'show_values_in_proposal', 'in_memory', 'sort']);

        $saved = $this->itemsModel->save($data);
        if (!$saved) {
            return $this->failValidationErrors('Could not save item.');
        }

		return $this->respondCreated([
			'status' => true,
			'message' => 'Item saved successfully.',
			'id' => $payload['id'] ?? (int) db_connect('default')->insertID(),
		]);
    }

    public function deleteItem(int $id = 0)
    {
        $id = $id ?: (int) $this->request->getPost('id');
        if ($id <= 0) {
            return $this->failValidationErrors('Invalid item id.');
        }

        if (!$this->itemsModel->delete($id)) {
            return $this->failValidationErrors('Could not delete item.');
        }

        return $this->respondDeleted([
            'status' => true,
            'message' => 'Item deleted successfully.',
        ]);
    }

    public function products()
    {
        $rows = $this->productsModel->get_details([
            'search' => clean_data($this->request->getGet('search')),
        ])->getResultArray();

        return $this->respond([
            'status' => true,
            'resource' => 'proposal_products',
            'count' => count($rows),
            'data' => $rows,
        ]);
    }

    public function saveProduct(int $id = 0)
    {
        $payload = $this->payload();
        $id = $id ?: (int) ($payload['id'] ?? 0);
        $table = 'items';
        $data = $this->filterPayload($table, $payload, ['id']);
        $this->normalizeDecimalFields($data, ['rate', 'cost', 'sale', 'markup']);

        if (!array_key_exists('title', $data) || trim((string) $data['title']) === '') {
            return $this->failValidationErrors('title is required.');
        }

        if ($saved = $this->productsModel->ci_save($data, $id)) {
            return $this->respondCreated([
                'status' => true,
                'message' => 'Product saved successfully.',
                'id' => $id ?: (int) db_connect('default')->insertID(),
            ]);
        }

        return $this->failValidationErrors('Could not save product.');
    }

    public function deleteProduct(int $id = 0)
    {
        $id = $id ?: (int) $this->request->getPost('id');
        if ($id <= 0) {
            return $this->failValidationErrors('Invalid product id.');
        }

        if (!$this->productsModel->delete($id)) {
            return $this->failValidationErrors('Could not delete product.');
        }

        return $this->respondDeleted([
            'status' => true,
            'message' => 'Product deleted successfully.',
        ]);
    }

    public function settings()
    {
        $rows = $this->settingsModel->get_details()->getResultArray();
        return $this->respond([
            'status' => true,
            'resource' => 'proposal_settings',
            'count' => count($rows),
            'data' => $rows,
        ]);
    }

    public function saveSettings()
    {
        $payload = $this->payload();
        $data = $this->filterPayload('proposals_module_settings_custom', $payload, ['id']);
        $this->normalizeDecimalFields($data, ['default_commission_value', 'default_markup_percent']);

        $id = (int) ($payload['id'] ?? 0);
        $saved = $this->settingsModel->save($id ? array_merge($data, ['id' => $id]) : $data);
        if (!$saved) {
            return $this->failValidationErrors('Could not save settings.');
        }

        return $this->respond([
            'status' => true,
            'message' => 'Settings saved successfully.',
        ]);
    }

    public function dashboard(int $proposalId = 0)
    {
        $proposalId = $proposalId ?: (int) $this->request->getGet('proposal_id');
        if ($proposalId <= 0) {
            return $this->failValidationErrors('Invalid proposal id.');
        }

        $proposal = $this->proposalsModel->get_details(['id' => $proposalId])->getRowArray();
        if (!$proposal) {
            return $this->failNotFound('Proposal not found.');
        }

        return $this->respond([
            'status' => true,
            'resource' => 'proposal_dashboard',
            'data' => $proposal,
        ]);
    }

    public function approve(int $id = 0)
    {
        $id = $id ?: (int) ($this->request->getPost('id') ?? 0);
        if ($id <= 0) {
            return $this->failValidationErrors('Invalid proposal id.');
        }

        $payload = [
            'status' => 'approved',
            'updated_at' => get_my_local_time(),
        ];

        if (!$this->proposalsModel->ci_save($payload, $id)) {
            return $this->failValidationErrors('Could not approve proposal.');
        }

        return $this->respond([
            'status' => true,
            'message' => 'Proposal approved successfully.',
            'id' => $id,
        ]);
    }

    public function duplicate(int $id = 0)
    {
        $id = $id ?: (int) ($this->request->getPost('id') ?? 0);
        if ($id <= 0) {
            return $this->failValidationErrors('Invalid proposal id.');
        }

        $proposal = $this->proposalsModel->get_details(['id' => $id])->getRowArray();
        if (!$proposal) {
            return $this->failNotFound('Proposal not found.');
        }

        unset($proposal['id'], $proposal['created_at'], $proposal['updated_at']);
        $proposal['status'] = 'draft';
        if (!empty($proposal['title'])) {
            $proposal['title'] = $proposal['title'] . ' (Copy)';
        }

        $saved = $this->proposalsModel->save($proposal);
        if (!$saved) {
            return $this->failValidationErrors('Could not duplicate proposal.');
        }

        $newId = (int) db_connect('default')->insertID();
        $this->duplicateProposalSectionsAndItems($id, $newId);

        return $this->respondCreated([
            'status' => true,
            'message' => 'Proposal duplicated successfully.',
            'id' => $newId,
        ]);
    }

    public function tasks(int $proposalId = 0)
    {
        $proposalId = $proposalId ?: (int) $this->request->getGet('proposal_id');
        if ($proposalId <= 0) {
            return $this->failValidationErrors('Invalid proposal id.');
        }

        $taskIds = $this->linkedTaskIds($proposalId);
        if (!$taskIds) {
            return $this->respond(['status' => true, 'proposal_id' => $proposalId, 'count' => 0, 'data' => []]);
        }

        $rows = $this->tasksModel->get_details([
            'task_ids' => implode(',', $taskIds),
            'limit' => 1000,
        ])->getResultArray();

        return $this->respond([
            'status' => true,
            'proposal_id' => $proposalId,
            'count' => count($rows),
            'data' => $rows,
        ]);
    }

    public function reminders(int $proposalId = 0)
    {
        $proposalId = $proposalId ?: (int) $this->request->getGet('proposal_id');
        if ($proposalId <= 0) {
            return $this->failValidationErrors('Invalid proposal id.');
        }

        $eventIds = $this->linkedReminderIds($proposalId);
        if (!$eventIds) {
            return $this->respond(['status' => true, 'proposal_id' => $proposalId, 'count' => 0, 'data' => []]);
        }

        $db = db_connect('default');
        $eventsTable = $db->prefixTable('events');
        $rows = $db->table($eventsTable)
            ->whereIn('id', $eventIds)
            ->where('deleted', 0)
            ->where('type', 'reminder')
            ->orderBy('start_date', 'desc')
            ->get()
            ->getResultArray();

        return $this->respond([
            'status' => true,
            'proposal_id' => $proposalId,
            'count' => count($rows),
            'data' => $rows,
        ]);
    }

    private function proposalFilters(): array
    {
        return [
            'id' => (int) $this->request->getGet('id'),
            'client_id' => (int) $this->request->getGet('client_id'),
            'status' => clean_data($this->request->getGet('status')),
            'search' => clean_data($this->request->getGet('q')),
            'start_date' => clean_data($this->request->getGet('start_date')),
            'end_date' => clean_data($this->request->getGet('end_date')),
        ];
    }

    private function duplicateProposalSectionsAndItems(int $sourceId, int $targetId): void
    {
        $db = db_connect('default');
        $sectionsTable = $db->prefixTable('proposal_sections_custom');
        $itemsTable = $db->prefixTable('proposal_items_custom');

        $sections = $db->table($sectionsTable)->where('proposal_id', $sourceId)->where('deleted', 0)->get()->getResultArray();
        foreach ($sections as $section) {
            unset($section['id'], $section['created_at'], $section['updated_at']);
            $section['proposal_id'] = $targetId;
            $db->table($sectionsTable)->insert($section);
        }

        $items = $db->table($itemsTable)->where('proposal_id', $sourceId)->where('deleted', 0)->get()->getResultArray();
        foreach ($items as $item) {
            unset($item['id'], $item['created_at'], $item['updated_at']);
            $item['proposal_id'] = $targetId;
            $db->table($itemsTable)->insert($item);
        }
    }

    private function linkedTaskIds(int $proposalId): array
    {
        $db = db_connect('default');
        $table = $db->prefixTable('proposal_task_links_custom');
        if (!$db->tableExists($table)) {
            return [];
        }

        $rows = $db->table($table)->select('task_id')->where('proposal_id', $proposalId)->where('deleted', 0)->get()->getResultArray();
        return array_values(array_filter(array_map(static fn ($row) => (int) ($row['task_id'] ?? 0), $rows)));
    }

    private function linkedReminderIds(int $proposalId): array
    {
        $db = db_connect('default');
        $table = $db->prefixTable('proposal_reminder_links_custom');
        if (!$db->tableExists($table)) {
            return [];
        }

        $rows = $db->table($table)->select('event_id')->where('proposal_id', $proposalId)->where('deleted', 0)->get()->getResultArray();
        return array_values(array_filter(array_map(static fn ($row) => (int) ($row['event_id'] ?? 0), $rows)));
    }
}
