<?php

namespace PontoRH\Libraries;

class PontoRh_service
{
    public function normalizeDateTime($date, $time = '')
    {
        $date = trim((string) $date);
        $time = trim((string) $time);

        if ($date === '') {
            return null;
        }

        $date = $this->normalizeDate($date);
        if (!$date) {
            return null;
        }

        if ($time === '') {
            $time = '00:00';
        }

        if (preg_match('/^\d{2}:\d{2}$/', $time)) {
            $time .= ':00';
        }
        return $date . ' ' . $time;
    }

    public function normalizeDate($date)
    {
        $date = trim((string) $date);
        if ($date === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date;
        }

        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $date)) {
            $dt = \DateTime::createFromFormat('d/m/Y', $date);
            if ($dt instanceof \DateTime) {
                return $dt->format('Y-m-d');
            }

            $dt = \DateTime::createFromFormat('m/d/Y', $date);
            if ($dt instanceof \DateTime) {
                return $dt->format('Y-m-d');
            }
        }

        $date_format = (string) get_setting('date_format');
        $format_map = array(
            'd-m-Y' => 'd-m-Y',
            'm-d-Y' => 'm-d-Y',
            'Y-m-d' => 'Y-m-d',
            'd/m/Y' => 'd/m/Y',
            'm/d/Y' => 'm/d/Y',
            'Y/m/d' => 'Y/m/d',
            'd.m.Y' => 'd.m.Y',
            'm.d.Y' => 'm.d.Y',
            'Y.m.d' => 'Y.m.d',
        );

        if ($date_format && isset($format_map[$date_format])) {
            $dt = \DateTime::createFromFormat($format_map[$date_format], $date);
            if ($dt instanceof \DateTime) {
                return $dt->format('Y-m-d');
            }
        }

        $timestamp = strtotime(str_replace(array('/', '.', '-'), '-', $date));
        return $timestamp ? date('Y-m-d', $timestamp) : null;
    }

    public function formatMinutes($minutes)
    {
        $minutes = (int) $minutes;
        if ($minutes <= 0) {
            return '0h';
        }

        $hours = floor($minutes / 60);
        $remaining = $minutes % 60;

        return $hours . 'h' . ($remaining ? ' ' . $remaining . 'm' : '');
    }

    public function teamMembersDropdown($include_blank = true)
    {
        return function_exists('pontorh_employee_dropdown') ? pontorh_employee_dropdown($include_blank) : array();
    }
}
