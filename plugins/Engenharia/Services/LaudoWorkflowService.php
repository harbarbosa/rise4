<?php

namespace Engenharia\Services;

use Engenharia\Models\Laudos_model;
use Engenharia\Models\Status_history_model;

class LaudoWorkflowService
{
    public const DRAFT = 'draft';
    public const SCHEDULED = 'scheduled';
    public const INSPECTION = 'inspection';
    public const AWAITING_INFORMATION = 'awaiting_information';
    public const REVIEW = 'review';
    public const FINALIZED = 'finalized';
    public const CANCELED = 'canceled';

    private $laudos;
    private $history;

    public function __construct(?Laudos_model $laudos = null, ?Status_history_model $history = null)
    {
        $this->laudos = $laudos ?: model(Laudos_model::class);
        $this->history = $history ?: model(Status_history_model::class);
    }

    public function assertPermission($login_user, string $permission): void
    {
        if (!\Engenharia\Plugin::hasPermission($login_user, $permission)) {
            throw new \RuntimeException('Engenharia permission denied: ' . $permission);
        }
    }

    public function changeStatus(int $laudo_id, string $to_status, $login_user, string $comment = '', string $source = 'web'): bool
    {
        if (!in_array($to_status, self::statuses(), true)) {
            throw new \InvalidArgumentException('Invalid Engenharia laudo status.');
        }

        $laudo = $this->laudos->get_one($laudo_id);
        if (!$laudo || empty($laudo->id) || !empty($laudo->deleted)) {
            return false;
        }

        $from_status = trim((string) ($laudo->status ?? self::DRAFT));
        if ($from_status === $to_status) {
            return true;
        }

        $permission = $this->permissionForStatus($to_status);
        if ($from_status === self::FINALIZED && $to_status === self::REVIEW) {
            $permission = 'engenharia_reopen_laudos';
        }
        $this->assertPermission($login_user, $permission);

        if (in_array($to_status, array(self::FINALIZED, self::CANCELED), true) && trim($comment) === '') {
            throw new \InvalidArgumentException('A justification is required for this status transition.');
        }
        if ($from_status === self::FINALIZED && $to_status === self::REVIEW && trim($comment) === '') {
            throw new \InvalidArgumentException('A reopening justification is required.');
        }

        if (!$this->canTransition($from_status, $to_status)) {
            throw new \RuntimeException('Invalid Engenharia laudo status transition.');
        }

        $data = array('status' => $to_status, 'updated_by' => (int) $login_user->id);
        if ($to_status === self::FINALIZED) {
            $data['finalized_by'] = (int) $login_user->id;
            $data['finalized_at'] = $this->now();
        }

        if (!$this->laudos->update_domain($laudo_id, $data)) {
            throw new \RuntimeException('Could not update laudo status.');
        }
        $this->history->add($laudo_id, $from_status, $to_status, (int) $login_user->id, $comment, $source);
        return true;
    }

    public static function statuses(): array
    {
        return array(self::DRAFT, self::SCHEDULED, self::INSPECTION, self::AWAITING_INFORMATION, self::REVIEW, self::FINALIZED, self::CANCELED);
    }

    public static function allowedTransitions(string $from): array
    {
        $allowed = array(
            self::DRAFT => array(self::SCHEDULED, self::CANCELED),
            self::SCHEDULED => array(self::INSPECTION, self::AWAITING_INFORMATION, self::CANCELED),
            self::INSPECTION => array(self::AWAITING_INFORMATION, self::REVIEW, self::CANCELED),
            self::AWAITING_INFORMATION => array(self::INSPECTION, self::REVIEW, self::CANCELED),
            self::REVIEW => array(self::INSPECTION, self::FINALIZED, self::CANCELED),
            self::FINALIZED => array(self::REVIEW),
            self::CANCELED => array(),
        );
        return $allowed[$from] ?? array();
    }

    private function permissionForStatus(string $status): string
    {
        if ($status === self::INSPECTION) {
            return 'engenharia_inspect_laudos';
        }
        if ($status === self::REVIEW) {
            return 'engenharia_review_laudos';
        }
        if ($status === self::FINALIZED) {
            return 'engenharia_finalize_laudos';
        }
        return 'engenharia_edit_laudos';
    }

    private function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::allowedTransitions($from), true);
    }

    private function now(): string
    {
        return function_exists('get_current_utc_time') ? get_current_utc_time() : gmdate('Y-m-d H:i:s');
    }
}
