<?php

if (!function_exists('pontorh_employee_dropdown')) {
    function pontorh_employee_dropdown($include_blank = true)
    {
        $users_model = model('App\\Models\\Users_model');
        $rows = $users_model->get_team_members_id_and_name()->getResult();

        $dropdown = array();
        if ($include_blank) {
            $dropdown[] = array('id' => '', 'text' => '-');
        }

        foreach ($rows as $row) {
            $dropdown[] = array(
                'id' => (int) $row->id,
                'text' => $row->user_name,
            );
        }

        return $dropdown;
    }
}

if (!function_exists('pontorh_format_minutes')) {
    function pontorh_format_minutes($minutes)
    {
        $minutes = (int) $minutes;
        if ($minutes <= 0) {
            return '0h';
        }

        $hours = floor($minutes / 60);
        $remaining = $minutes % 60;

        if (!$hours) {
            return $remaining . 'm';
        }

        return $hours . 'h' . ($remaining ? ' ' . $remaining . 'm' : '');
    }
}

if (!function_exists('pontorh_status_options')) {
    function pontorh_status_options()
    {
        return array(
            '' => '-',
            'open' => app_lang('pontorh_status_open'),
            'closed' => app_lang('pontorh_status_closed'),
            'adjusted' => app_lang('pontorh_status_adjusted'),
            'pending' => app_lang('pontorh_status_pending'),
            'approved' => app_lang('pontorh_status_approved'),
            'rejected' => app_lang('pontorh_status_rejected'),
        );
    }
}

if (!function_exists('pontorh_adjustment_status_options')) {
    function pontorh_adjustment_status_options()
    {
        return array(
            '' => '-',
            'pending' => app_lang('pontorh_status_pending'),
            'approved' => app_lang('pontorh_status_approved'),
            'rejected' => app_lang('pontorh_status_rejected'),
        );
    }
}

if (!function_exists('pontorh_adjustment_status_label')) {
    function pontorh_adjustment_status_label($status)
    {
        $options = pontorh_adjustment_status_options();
        return get_array_value($options, $status) ?: $status;
    }
}

if (!function_exists('pontorh_adjustment_type_options')) {
    function pontorh_adjustment_type_options()
    {
        return pontorh_punch_type_options();
    }
}

if (!function_exists('pontorh_adjustment_type_label')) {
    function pontorh_adjustment_type_label($type)
    {
        return pontorh_punch_type_label($type);
    }
}

if (!function_exists('pontorh_punch_type_options')) {
    function pontorh_punch_type_options()
    {
        return array(
            '' => '-',
            'in' => app_lang('pontorh_punch_type_in'),
            'lunch_out' => app_lang('pontorh_punch_type_lunch_out'),
            'lunch_return' => app_lang('pontorh_punch_type_lunch_return'),
            'out' => app_lang('pontorh_punch_type_out'),
        );
    }
}

if (!function_exists('pontorh_punch_type_label')) {
    function pontorh_punch_type_label($punch_type)
    {
        $options = pontorh_punch_type_options();
        return get_array_value($options, $punch_type) ?: $punch_type;
    }
}

if (!function_exists('pontorh_schedule_type_options')) {
    function pontorh_schedule_type_options()
    {
        return array(
            '' => '-',
            'comercial' => app_lang('pontorh_schedule_type_comercial'),
            'flexivel' => app_lang('pontorh_schedule_type_flexivel'),
            'escala' => app_lang('pontorh_schedule_type_escala'),
            '12x36' => app_lang('pontorh_schedule_type_12x36'),
        );
    }
}

if (!function_exists('pontorh_schedule_type_label')) {
    function pontorh_schedule_type_label($schedule_type)
    {
        $options = pontorh_schedule_type_options();
        return get_array_value($options, $schedule_type) ?: $schedule_type;
    }
}

if (!function_exists('pontorh_safe_json')) {
    function pontorh_safe_json($value)
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}

if (!function_exists('pontorh_timezone_name')) {
    function pontorh_timezone_name()
    {
        $candidates = array(
            trim((string) get_setting('timezone')),
            function_exists('app_timezone') ? trim((string) app_timezone()) : '',
            date_default_timezone_get() ?: '',
            'America/Sao_Paulo',
        );

        $fallback = 'America/Sao_Paulo';
        foreach ($candidates as $timezone) {
            if ($timezone === '') {
                continue;
            }

            try {
                new DateTimeZone($timezone);
            } catch (Throwable $e) {
                continue;
            }

            if (strcasecmp($timezone, 'UTC') !== 0 && strcasecmp($timezone, 'Etc/UTC') !== 0) {
                return $timezone;
            }

            if ($fallback === '') {
                $fallback = $timezone;
            }
        }

        return $fallback;
    }
}

if (!function_exists('pontorh_convert_utc_to_local')) {
    function pontorh_convert_utc_to_local($date_time, $format = 'Y-m-d H:i:s')
    {
        $date_time = trim((string) $date_time);
        if ($date_time === '') {
            return '';
        }

        try {
            $date = new DateTime($date_time, new DateTimeZone('UTC'));
            $date->setTimezone(new DateTimeZone(pontorh_timezone_name()));
            return $date->format($format);
        } catch (Throwable $e) {
            $timestamp = strtotime($date_time);
            return $timestamp ? date($format, $timestamp) : $date_time;
        }
    }
}

if (!function_exists('pontorh_local_datetime')) {
    function pontorh_local_datetime($date_time, $format = 'Y-m-d H:i:s')
    {
        $date_time = trim((string) $date_time);
        if ($date_time === '') {
            return '';
        }

        if (function_exists('is_date_exists') && is_date_exists($date_time)) {
            return pontorh_convert_utc_to_local($date_time, $format);
        }

        return $date_time;
    }
}

if (!function_exists('pontorh_extract_time')) {
    function pontorh_extract_time($date_time)
    {
        $date_time = trim((string) $date_time);
        if ($date_time === '') {
            return '';
        }

        if (function_exists('is_date_exists') && is_date_exists($date_time)) {
            $date_time = pontorh_convert_utc_to_local($date_time);
        }

        if (preg_match('/\b(\d{2}:\d{2})(?::\d{2})?\b/', $date_time, $matches)) {
            return $matches[1];
        }

        return $date_time;
    }
}

if (!function_exists('pontorh_record_photo_src')) {
    function pontorh_record_photo_src($photo)
    {
        $photo = trim((string) $photo);
        if ($photo === '') {
            return '';
        }

        if (stripos($photo, 'data:image/') === 0) {
            return $photo;
        }

        $mime = 'image/jpeg';
        if (str_starts_with($photo, 'iVBOR')) {
            $mime = 'image/png';
        } elseif (str_starts_with($photo, 'R0lGOD')) {
            $mime = 'image/gif';
        }

        return 'data:' . $mime . ';base64,' . $photo;
    }
}

if (!function_exists('pontorh_infer_punch_type_from_index')) {
    function pontorh_infer_punch_type_from_index($index)
    {
        $index = max(0, (int) $index);
        $sequence = array('in', 'lunch_out', 'lunch_return', 'out');
        return $sequence[$index % 4];
    }
}

if (!function_exists('pontorh_month_options')) {
    function pontorh_month_options()
    {
        return array(
            1 => app_lang('january'),
            2 => app_lang('february'),
            3 => app_lang('march'),
            4 => app_lang('april'),
            5 => app_lang('may'),
            6 => app_lang('june'),
            7 => app_lang('july'),
            8 => app_lang('august'),
            9 => app_lang('september'),
            10 => app_lang('october'),
            11 => app_lang('november'),
            12 => app_lang('december'),
        );
    }
}

if (!function_exists('pontorh_minutes_to_hours_label')) {
    function pontorh_minutes_to_hours_label($minutes)
    {
        $minutes = (int) round((float) $minutes);
        $sign = $minutes < 0 ? '-' : '';
        $minutes = abs($minutes);
        $hours = floor($minutes / 60);
        $remaining = $minutes % 60;
        return $sign . $hours . 'h ' . str_pad((string) $remaining, 2, '0', STR_PAD_LEFT) . 'm';
    }
}

if (!function_exists('pontorh_treatment_status_options')) {
    function pontorh_treatment_status_options()
    {
        return array(
            '' => '-',
            'complete' => app_lang('pontorh_treatment_status_complete'),
            'incomplete' => app_lang('pontorh_treatment_status_incomplete'),
            'inconsistent' => app_lang('pontorh_treatment_status_inconsistent'),
            'outside_area' => app_lang('pontorh_treatment_status_outside_area'),
            'no_photo' => app_lang('pontorh_treatment_status_no_photo'),
            'adjustment_requested' => app_lang('pontorh_treatment_status_adjustment_requested'),
            'awaiting_justification' => app_lang('pontorh_treatment_status_awaiting_justification'),
            'treated_manual' => app_lang('pontorh_treatment_status_treated_manual'),
            'closed' => app_lang('pontorh_treatment_status_closed'),
        );
    }
}

if (!function_exists('pontorh_treatment_status_label')) {
    function pontorh_treatment_status_label($status)
    {
        $options = pontorh_treatment_status_options();
        return get_array_value($options, $status) ?: $status;
    }
}

if (!function_exists('pontorh_treatment_pending_type_options')) {
    function pontorh_treatment_pending_type_options()
    {
        return array(
            '' => '-',
            'no_entry' => app_lang('pontorh_treatment_pending_no_entry'),
            'no_lunch_out' => app_lang('pontorh_treatment_pending_no_lunch_out'),
            'no_lunch_return' => app_lang('pontorh_treatment_pending_no_lunch_return'),
            'no_exit' => app_lang('pontorh_treatment_pending_no_exit'),
            'extra_marking' => app_lang('pontorh_treatment_pending_extra_marking'),
            'sequence_invalid' => app_lang('pontorh_treatment_pending_sequence_invalid'),
            'outside_area' => app_lang('pontorh_treatment_pending_outside_area'),
            'no_photo' => app_lang('pontorh_treatment_pending_no_photo'),
            'suspicious_time' => app_lang('pontorh_treatment_pending_suspicious_time'),
            'adjustment_requested' => app_lang('pontorh_treatment_pending_adjustment_requested'),
            'awaiting_justification' => app_lang('pontorh_treatment_pending_awaiting_justification'),
            'ignored' => app_lang('pontorh_treatment_pending_ignored'),
            'corrected' => app_lang('pontorh_treatment_pending_corrected'),
        );
    }
}

if (!function_exists('pontorh_treatment_pending_type_label')) {
    function pontorh_treatment_pending_type_label($type)
    {
        $options = pontorh_treatment_pending_type_options();
        return get_array_value($options, $type) ?: $type;
    }
}
