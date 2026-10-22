<?php

namespace ProjectAnalizer\Libraries;

class ProjectMaterialFlowService
{
    private $db;

    public function __construct()
    {
        $this->db = db_connect("default");
    }

    public function getFlow($projectId)
    {
        $projectId = (int) $projectId;
        $required = ["pa_project_materials", "pa_project_task_materials"];
        foreach ($required as $table) {
            if (!$this->db->tableExists($table)) return ["rows" => [], "tasks" => [], "totals" => $this->emptyTotals()];
        }

        $materials = $this->rows("pa_project_materials", ["project_id" => $projectId, "deleted" => 0]);
        $allocations = $this->rows("pa_project_task_materials", ["project_id" => $projectId, "deleted" => 0]);
        $tasks = $this->indexById($this->rows("tasks", ["project_id" => $projectId, "deleted" => 0]));
        $milestones = $this->indexById($this->rows("milestones", ["project_id" => $projectId, "deleted" => 0]));
        $materialMap = $this->indexById($materials);

        $links = $this->db->tableExists("pa_project_material_request_items")
            ? $this->rows("pa_project_material_request_items", ["project_id" => $projectId, "deleted" => 0]) : [];
        $requestIds = $this->ids($links, "purchase_request_id");
        $requestItemIds = $this->ids($links, "purchase_request_item_id");
        $requests = $this->fetchByIds("purchases_requests", $requestIds);
        $requestItems = $this->fetchByIds("purchases_request_items", $requestItemIds);
        $requestMap = $this->indexById($requests);
        $requestItemMap = $this->indexById($requestItems);

        $orders = $this->fetchWhereIn("purchases_orders", "request_id", $requestIds);
        $activeOrders = [];
        foreach ($orders as $order) {
            if (!in_array((string) ($order->status ?? ""), ["cancelled", "rejected"], true)) $activeOrders[] = $order;
        }
        $orderIds = $this->ids($activeOrders, "id");
        $orderMap = $this->indexById($activeOrders);
        $orderItems = $this->fetchWhereIn("purchases_order_items", "order_id", $orderIds);
        $orderItemIds = $this->ids($orderItems, "id");

        $receiptByOrderItem = [];
        if ($orderItemIds && $this->db->tableExists("purchases_goods_receipt_items")) {
            $receiptItems = $this->fetchWhereIn("purchases_goods_receipt_items", "order_item_id", $orderItemIds);
            foreach ($receiptItems as $receiptItem) {
                $id = (int) $receiptItem->order_item_id;
                $receiptByOrderItem[$id] = ($receiptByOrderItem[$id] ?? 0) + (float) ($receiptItem->quantity_received ?? 0);
            }
        }

        $usageByAllocation = [];
        $usageByMaterial = [];
        if ($this->db->tableExists("pa_project_material_usage")) {
            foreach ($this->rows("pa_project_material_usage", ["project_id" => $projectId, "deleted" => 0]) as $usage) {
                $allocationId = (int) $usage->allocation_id;
                $materialId = (int) $usage->project_material_id;
                $quantity = (float) $usage->quantity;
                $usageByAllocation[$allocationId] = ($usageByAllocation[$allocationId] ?? 0) + $quantity;
                $usageByMaterial[$materialId] = ($usageByMaterial[$materialId] ?? 0) + $quantity;
            }
        }

        $deliveryByAllocation = [];
        $deliveryByMaterial = [];
        if ($this->db->tableExists("pa_project_material_deliveries")) {
            foreach ($this->rows("pa_project_material_deliveries", ["project_id" => $projectId, "deleted" => 0]) as $delivery) {
                $allocationId = (int) $delivery->allocation_id;
                $materialId = (int) $delivery->project_material_id;
                $quantity = (float) $delivery->quantity;
                $deliveryByAllocation[$allocationId] = ($deliveryByAllocation[$allocationId] ?? 0) + $quantity;
                $deliveryByMaterial[$materialId] = ($deliveryByMaterial[$materialId] ?? 0) + $quantity;
            }
        }

        $linksByAllocation = [];
        foreach ($links as $link) {
            $request = $requestMap[(int) $link->purchase_request_id] ?? null;
            if (!$request || in_array((string) ($request->status ?? ""), ["rejected", "cancelled"], true)) continue;
            $linksByAllocation[(int) $link->allocation_id][] = $link;
        }

        $ordersByRequest = [];
        foreach ($activeOrders as $order) $ordersByRequest[(int) $order->request_id][] = $order;
        $orderItemsByOrder = [];
        foreach ($orderItems as $item) $orderItemsByOrder[(int) $item->order_id][] = $item;

        $receivedByMaterial = [];
        foreach ($links as $link) {
            $materialId = (int) $link->project_material_id;
            $requestItemId = (int) $link->purchase_request_item_id;
            $requestItem = $requestItemMap[$requestItemId] ?? null;
            foreach ($ordersByRequest[(int) $link->purchase_request_id] ?? [] as $order) {
                foreach ($orderItemsByOrder[(int) $order->id] ?? [] as $orderItem) {
                    if (!$this->orderItemMatchesRequestItem($orderItem, $requestItemId, $requestItem)) continue;
                    $receivedByMaterial[$materialId] = ($receivedByMaterial[$materialId] ?? 0)
                        + (float) ($receiptByOrderItem[(int) $orderItem->id] ?? 0);
                }
            }
        }

        $rows = [];
        $taskSummary = [];
        $totals = $this->emptyTotals();

        foreach ($allocations as $allocation) {
            $allocationId = (int) $allocation->id;
            $materialId = (int) $allocation->project_material_id;
            $taskId = (int) $allocation->task_id;
            $material = $materialMap[$materialId] ?? null;
            $task = $tasks[$taskId] ?? null;
            if (!$material || !$task) continue;

            $allocated = (float) $allocation->quantity;
            $requested = 0;
            $purchased = 0;
            $received = 0;
            $requestCodes = [];

            foreach ($linksByAllocation[$allocationId] ?? [] as $link) {
                $requested += (float) $link->quantity;
                $request = $requestMap[(int) $link->purchase_request_id] ?? null;
                if ($request) $requestCodes[] = $request->request_code ?: ("#".$request->id);
                $requestItemId = (int) $link->purchase_request_item_id;
                $requestItem = $requestItemMap[$requestItemId] ?? null;
                foreach ($ordersByRequest[(int) $link->purchase_request_id] ?? [] as $order) {
                    foreach ($orderItemsByOrder[(int) $order->id] ?? [] as $orderItem) {
                        if (!$this->orderItemMatchesRequestItem($orderItem, $requestItemId, $requestItem)) continue;
                        $purchased += (float) ($orderItem->quantity ?? 0);
                        $received += (float) ($receiptByOrderItem[(int) $orderItem->id] ?? 0);
                    }
                }
            }

            $delivered = (float) ($deliveryByAllocation[$allocationId] ?? 0);
            $used = (float) ($usageByAllocation[$allocationId] ?? 0);
            $manualStock = (float) ($material->stock_quantity ?? 0);
            $materialReceived = (float) ($receivedByMaterial[$materialId] ?? 0);
            $materialDelivered = (float) ($deliveryByMaterial[$materialId] ?? 0);
            $projectAvailable = max(0, $manualStock + $materialReceived - $materialDelivered);
            $remainingDelivery = max(0, $allocated - $delivered);
            $remainingUse = max(0, $delivered - $used);
            $availableForTask = $remainingUse;
            $toRequest = max(0, $allocated - $requested);
            $pendingPurchase = max(0, $requested - $purchased);
            $pendingReceipt = max(0, $purchased - $received);
            $materialReady = $allocated <= 0.0001 || $delivered + 0.0001 >= $allocated;
            $taskState = $this->getTaskState($taskId);
            $taskReleased = $taskState && (int) $taskState->released === 1 && (int) $taskState->blocked === 0;
            $critical = $taskReleased && !$materialReady;

            $status = "planned";
            if ($used > 0) $status = $used + 0.0001 >= $allocated ? "used" : "partially_used";
            elseif ($delivered > 0) $status = $materialReady ? "delivered" : "partially_delivered";
            elseif ($received > 0 || $projectAvailable > 0) $status = "received";
            elseif ($purchased > 0) $status = "purchased";
            elseif ($requested > 0) $status = "requested";
            elseif ($allocated > 0) $status = "allocated";

            $milestone = $milestones[(int) ($task->milestone_id ?? 0)] ?? null;
            $row = [
                "allocation_id" => $allocationId,
                "material_id" => $materialId,
                "material" => $material->description,
                "unit" => $material->unit,
                "task_id" => $taskId,
                "task" => $task->title,
                "milestone_id" => (int) ($task->milestone_id ?? 0),
                "milestone" => $milestone->title ?? "Sem etapa",
                "stock_quantity" => $manualStock,
                "allocated" => $allocated,
                "requested" => $requested,
                "purchased" => $purchased,
                "received" => $received,
                "delivered" => $delivered,
                "used" => $used,
                "project_available" => $projectAvailable,
                "available" => $availableForTask,
                "to_request" => $toRequest,
                "pending_purchase" => $pendingPurchase,
                "pending_delivery" => $pendingReceipt,
                "remaining_delivery" => $remainingDelivery,
                "remaining_use" => $remainingUse,
                "status" => $status,
                "critical" => $critical,
                "material_ready" => $materialReady,
                "request_codes" => array_values(array_unique($requestCodes)),
            ];
            $rows[] = $row;

            if (!isset($taskSummary[$taskId])) {
                $taskSummary[$taskId] = [
                    "task_id" => $taskId, "task" => $task->title, "milestone" => $row["milestone"],
                    "allocated" => 0, "requested" => 0, "received" => 0, "delivered" => 0, "used" => 0,
                    "pending" => 0, "critical" => 0, "ready" => true,
                ];
            }
            $taskSummary[$taskId]["allocated"] += $allocated;
            $taskSummary[$taskId]["requested"] += $requested;
            $taskSummary[$taskId]["received"] += $received;
            $taskSummary[$taskId]["delivered"] += $delivered;
            $taskSummary[$taskId]["used"] += $used;
            $taskSummary[$taskId]["pending"] += $toRequest + $pendingPurchase + $pendingReceipt + $remainingDelivery;
            if ($critical) $taskSummary[$taskId]["critical"]++;
            if (!$materialReady) $taskSummary[$taskId]["ready"] = false;

            foreach (["allocated", "requested", "purchased", "received", "delivered", "used", "to_request", "pending_delivery"] as $key) {
                $totals[$key] += $row[$key];
            }
            if ($critical) $totals["critical"]++;
        }

        usort($rows, function ($a, $b) {
            return [$a["milestone"], $a["task"], $a["material"]] <=> [$b["milestone"], $b["task"], $b["material"]];
        });

        return ["rows" => $rows, "tasks" => array_values($taskSummary), "totals" => $totals];
    }

    public function setStock($projectId, $materialId, $quantity, $userId)
    {
        $quantity = round((float) $quantity, 4);
        if ($quantity < 0) return ["success" => false, "message" => "A quantidade disponível não pode ser negativa."];
        $material = $this->findMaterial($projectId, $materialId);
        if (!$material) return ["success" => false, "message" => "Material inválido para este projeto."];
        if (!$this->db->fieldExists("stock_quantity", $this->db->prefixTable("pa_project_materials"))) {
            return ["success" => false, "message" => "Atualize o plugin para habilitar o saldo de estoque."];
        }
        $ok = $this->db->table("pa_project_materials")->where("id", (int) $materialId)->update([
            "stock_quantity" => $quantity,
            "updated_at" => date("Y-m-d H:i:s"),
        ]);
        return ["success" => (bool) $ok, "message" => "Saldo disponível atualizado."];
    }

    public function recordDelivery($projectId, $allocationId, $quantity, $notes, $userId)
    {
        $quantity = round((float) $quantity, 4);
        if ($quantity <= 0) return ["success" => false, "message" => "Informe uma quantidade entregue maior que zero."];
        if (!$this->db->tableExists("pa_project_material_deliveries")) {
            return ["success" => false, "message" => "Atualize o plugin para habilitar entregas às tarefas."];
        }

        $allocationQuery = $this->db->table("pa_project_task_materials")->where([
            "id" => (int) $allocationId, "project_id" => (int) $projectId, "deleted" => 0,
        ])->get();
        $allocation = $allocationQuery ? $allocationQuery->getRow() : null;
        if (!$allocation) return ["success" => false, "message" => "Associação de material inválida."];

        $flow = $this->getFlow($projectId);
        $row = null;
        foreach ($flow["rows"] as $candidate) {
            if ((int) $candidate["allocation_id"] === (int) $allocationId) { $row = $candidate; break; }
        }
        if (!$row) return ["success" => false, "message" => "Não foi possível calcular o saldo do material."];
        if ($quantity > $row["remaining_delivery"] + 0.0001) {
            return ["success" => false, "message" => "A entrega excede a quantidade associada à tarefa."];
        }
        if ($quantity > $row["project_available"] + 0.0001) {
            return ["success" => false, "message" => "A entrega excede o saldo físico disponível no projeto."];
        }

        $ok = $this->db->table("pa_project_material_deliveries")->insert([
            "project_id" => (int) $projectId,
            "task_id" => (int) $allocation->task_id,
            "project_material_id" => (int) $allocation->project_material_id,
            "allocation_id" => (int) $allocationId,
            "quantity" => $quantity,
            "notes" => $notes ?: null,
            "delivered_by" => (int) $userId,
            "delivered_at" => date("Y-m-d H:i:s"),
            "created_at" => date("Y-m-d H:i:s"),
            "deleted" => 0,
        ]);
        return ["success" => (bool) $ok, "message" => "Material entregue à tarefa."];
    }

    public function recordUsage($projectId, $allocationId, $quantity, $notes, $userId)
    {
        $quantity = round((float) $quantity, 4);
        if ($quantity <= 0) return ["success" => false, "message" => "Informe uma quantidade utilizada maior que zero."];
        if (!$this->db->tableExists("pa_project_material_usage")) {
            return ["success" => false, "message" => "Atualize o plugin para habilitar o consumo de materiais."];
        }

        $allocationQuery = $this->db->table("pa_project_task_materials")->where([
            "id" => (int) $allocationId, "project_id" => (int) $projectId, "deleted" => 0,
        ])->get();
        $allocation = $allocationQuery ? $allocationQuery->getRow() : null;
        if (!$allocation) return ["success" => false, "message" => "Associação de material inválida."];

        $flow = $this->getFlow($projectId);
        $row = null;
        foreach ($flow["rows"] as $candidate) {
            if ((int) $candidate["allocation_id"] === (int) $allocationId) { $row = $candidate; break; }
        }
        if (!$row) return ["success" => false, "message" => "Não foi possível calcular o saldo do material."];
        if ($quantity > $row["remaining_use"] + 0.0001) {
            return ["success" => false, "message" => "A utilização excede o saldo associado à tarefa."];
        }
        if ($quantity > $row["available"] + 0.0001) {
            return ["success" => false, "message" => "A utilização excede o saldo disponível no projeto."];
        }

        $ok = $this->db->table("pa_project_material_usage")->insert([
            "project_id" => (int) $projectId,
            "task_id" => (int) $allocation->task_id,
            "project_material_id" => (int) $allocation->project_material_id,
            "allocation_id" => (int) $allocationId,
            "quantity" => $quantity,
            "notes" => $notes ?: null,
            "used_by" => (int) $userId,
            "used_at" => date("Y-m-d H:i:s"),
            "created_at" => date("Y-m-d H:i:s"),
            "deleted" => 0,
        ]);
        return ["success" => (bool) $ok, "message" => "Utilização do material registrada."];
    }

    private function orderItemMatchesRequestItem($orderItem, $requestItemId, $requestItem)
    {
        if (isset($orderItem->request_item_id) && (int) $orderItem->request_item_id) {
            return (int) $orderItem->request_item_id === (int) $requestItemId;
        }
        if (!$requestItem) return false;
        if ((int) ($requestItem->item_id ?? 0) && (int) ($orderItem->item_id ?? 0)) {
            return (int) $requestItem->item_id === (int) $orderItem->item_id;
        }
        return $this->normalize($requestItem->description ?? "") === $this->normalize($orderItem->description ?? "");
    }

    private function findMaterial($projectId, $materialId)
    {
        $query = $this->db->table("pa_project_materials")->where([
            "id" => (int) $materialId, "project_id" => (int) $projectId, "deleted" => 0,
        ])->get();
        return $query ? $query->getRow() : null;
    }

    private function getTaskState($taskId)
    {
        if (!$this->db->tableExists("pa_task_journey_status")) return null;
        $query = $this->db->table("pa_task_journey_status")->where(["task_id" => (int) $taskId, "deleted" => 0])->get();
        return $query ? $query->getRow() : null;
    }

    private function fetchByIds($table, $ids)
    {
        return $this->fetchWhereIn($table, "id", $ids);
    }

    private function fetchWhereIn($table, $field, $ids)
    {
        if (!$ids || !$this->db->tableExists($table)) return [];
        $builder = $this->db->table($table)->whereIn($field, array_values(array_unique(array_map("intval", $ids))));
        if ($this->db->fieldExists("deleted", $this->db->prefixTable($table))) $builder->where("deleted", 0);
        $query = $builder->get();
        return $query ? $query->getResult() : [];
    }

    private function rows($table, $where)
    {
        if (!$this->db->tableExists($table)) return [];
        $query = $this->db->table($table)->where($where)->get();
        return $query ? $query->getResult() : [];
    }

    private function indexById($rows)
    {
        $map = [];
        foreach ($rows as $row) $map[(int) $row->id] = $row;
        return $map;
    }

    private function ids($rows, $field)
    {
        $ids = [];
        foreach ($rows as $row) if (!empty($row->$field)) $ids[] = (int) $row->$field;
        return array_values(array_unique($ids));
    }

    private function normalize($value)
    {
        return mb_strtolower(trim(preg_replace("/\s+/", " ", (string) $value)));
    }

    private function emptyTotals()
    {
        return ["allocated" => 0, "requested" => 0, "purchased" => 0, "received" => 0, "delivered" => 0, "used" => 0, "to_request" => 0, "pending_delivery" => 0, "critical" => 0];
    }
}
