<?php

namespace GED\Libraries;

class GedExpirationService
{
    public function getDaysToExpire($expiration_date)
    {
        $expiration_date = $this->normalizeDate($expiration_date);
        if ($expiration_date === null) {
            return null;
        }

        $today = $this->today();
        $expiration = new \DateTimeImmutable($expiration_date, $this->timezone());
        $diff = $today->diff($expiration);

        return (int) $diff->format('%r%a');
    }

    public function isExpired($expiration_date)
    {
        $days = $this->getDaysToExpire($expiration_date);
        return $days !== null && $days < 0;
    }

    public function isExpiring($expiration_date, $days = 30)
    {
        $days_to_expire = $this->getDaysToExpire($expiration_date);
        if ($days_to_expire === null) {
            return false;
        }

        return $days_to_expire >= 0 && $days_to_expire <= (int) $days;
    }

    public function getExpirationStatus($expiration_date)
    {
        $days = $this->getDaysToExpire($expiration_date);
        if ($days === null) {
            return 'no_expiration';
        }

        if ($days < 0) {
            return 'expired';
        }

        if ($days === 0) {
            return 'expires_today';
        }

        if ($days <= 7) {
            return 'expiring_7';
        }

        if ($days <= 15) {
            return 'expiring_15';
        }

        if ($days <= 30) {
            return 'expiring_30';
        }

        return 'valid';
    }

    public function getExpirationBadge($expiration_date)
    {
        $status = $this->getExpirationStatus($expiration_date);
        $days = $this->getDaysToExpire($expiration_date);

        $map = array(
            'no_expiration' => array('label' => 'Sem vencimento', 'class' => 'bg-secondary'),
            'valid' => array('label' => 'Valido', 'class' => 'bg-success', 'style' => 'background-color: #198754 !important; color: #fff;'),
            'expiring_30' => array('label' => 'Vence em ate 30 dias', 'class' => 'bg-warning text-dark'),
            'expiring_15' => array('label' => 'Vence em ate 15 dias', 'class' => 'bg-warning text-dark'),
            'expiring_7' => array('label' => 'Vence em ate 7 dias', 'class' => 'bg-warning text-dark'),
            'expires_today' => array('label' => 'Vence hoje', 'class' => 'bg-danger'),
            'expired' => array('label' => 'Vencido', 'class' => 'bg-danger'),
        );

        $cfg = isset($map[$status]) ? $map[$status] : $map['valid'];
        if ($status === 'expired' && $days !== null) {
            $cfg['label'] = 'Vencido ha ' . abs((int) $days) . ' dias';
        } elseif ($status === 'expiring_7' && $days !== null) {
            $cfg['label'] = 'Vence em ' . (int) $days . ' dias';
        } elseif ($status === 'expiring_15' && $days !== null) {
            $cfg['label'] = 'Vence em ' . (int) $days . ' dias';
        } elseif ($status === 'expiring_30' && $days !== null) {
            $cfg['label'] = 'Vence em ' . (int) $days . ' dias';
        }

        $style = !empty($cfg['style']) ? " style='" . $cfg['style'] . "'" : '';
        return "<span class='badge {$cfg['class']}'{$style}>" . esc($cfg['label']) . '</span>';
    }

    public function getDocumentStatusLabel($status)
    {
        return $this->getLabel($status, array(
            'no_expiration' => array('label' => 'Sem vencimento', 'class' => 'bg-secondary'),
            'valid' => array('label' => 'Valido', 'class' => 'bg-success', 'style' => 'background-color: #198754 !important; color: #fff;'),
            'expiring_30' => array('label' => 'Vencendo em 30 dias', 'class' => 'bg-warning text-dark'),
            'expiring_15' => array('label' => 'Vencendo em 15 dias', 'class' => 'bg-warning text-dark'),
            'expiring_7' => array('label' => 'Vencendo em 7 dias', 'class' => 'bg-warning text-dark'),
            'expires_today' => array('label' => 'Vence hoje', 'class' => 'bg-danger'),
            'expired' => array('label' => 'Vencido', 'class' => 'bg-danger'),
            'pending' => array('label' => 'Pendente', 'class' => 'bg-secondary'),
            'archived' => array('label' => 'Arquivado', 'class' => 'bg-dark'),
        ), 'valid');
    }

    public function getPortalStatusLabel($portal_status)
    {
        return $this->getLabel($portal_status, array(
            'pending' => array('label' => 'Pendente', 'class' => 'bg-secondary'),
            'submitted' => array('label' => 'Enviado', 'class' => 'bg-primary'),
            'approved' => array('label' => 'Aprovado', 'class' => 'bg-success'),
            'rejected' => array('label' => 'Rejeitado', 'class' => 'bg-danger'),
            'expired' => array('label' => 'Expirado', 'class' => 'bg-dark'),
        ), 'pending');
    }

    private function getLabel($value, array $map, $default_key)
    {
        $value = trim((string) $value);
        $cfg = isset($map[$value]) ? $map[$value] : $map[$default_key];
        $style = !empty($cfg['style']) ? " style='" . $cfg['style'] . "'" : '';
        return "<span class='badge {$cfg['class']}'{$style}>" . esc($cfg['label']) . '</span>';
    }

    private function normalizeDate($expiration_date)
    {
        $expiration_date = trim((string) $expiration_date);
        if ($expiration_date === '') {
            return null;
        }

        if (strpos($expiration_date, 'T') !== false) {
            $expiration_date = str_replace('T', ' ', $expiration_date);
        }

        return substr($expiration_date, 0, 10);
    }

    private function today()
    {
        return new \DateTimeImmutable(date('Y-m-d') . ' 00:00:00', $this->timezone());
    }

    private function timezone()
    {
        $timezone = get_setting('timezone');
        if (!$timezone) {
            $timezone = date_default_timezone_get() ?: 'UTC';
        }

        try {
            return new \DateTimeZone($timezone);
        } catch (\Throwable $e) {
            return new \DateTimeZone('UTC');
        }
    }
}
