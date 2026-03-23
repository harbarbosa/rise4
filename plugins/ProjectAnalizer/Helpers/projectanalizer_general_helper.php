<?php

/**
 * get the defined config value by a key
 * @param string $key
 * @return config value
 */
if (!function_exists('get_projectanalizer_setting')) {

    function get_projectanalizer_setting($key = "") {
        $config = new ProjectAnalizer\Config\ProjectAnalizer();

        $setting_value = get_array_value($config->app_settings_array, $key);
        if ($setting_value !== NULL) {
            return $setting_value;
        } else {
            return "";
        }
    }

}

if (!function_exists('business_days')) {

    function business_days($start, $end) {
        if (!$start || !$end) {
            return 0;
        }

        $start_date = substr($start, 0, 10);
        $end_date = substr($end, 0, 10);

        $start_dt = \DateTime::createFromFormat("Y-m-d", $start_date);
        $end_dt = \DateTime::createFromFormat("Y-m-d", $end_date);
        if (!$start_dt || !$end_dt) {
            return 0;
        }

        if ($end_dt < $start_dt) {
            return 0;
        }

        $weekends_setting = trim((string)get_setting("weekends"));
        $weekend_days = array();
        if ($weekends_setting !== "") {
            $parts = array_map("trim", explode(",", $weekends_setting));
            foreach ($parts as $part) {
                if ($part === "" || !is_numeric($part)) {
                    continue;
                }
                $weekend_days[(int)$part] = true;
            }
        }

        $count = 0;
        $cursor = clone $start_dt;
        while ($cursor <= $end_dt) {
            $weekday = (int)$cursor->format("w");
            if (!isset($weekend_days[$weekday])) {
                $count++;
            }
            $cursor->modify("+1 day");
        }

        return $count;
    }
}

/**
 * link the css files 
 * 
 * @param array $array
 * @return print css links
 */
if (!function_exists('projectanalizer_load_css')) {

    function projectanalizer_load_css(array $array) {
        $version = get_setting("app_version");

        foreach ($array as $uri) {
            echo "<link rel='stylesheet' type='text/css' href='" . base_url(PLUGIN_URL_PATH . "ProjectAnalizer/$uri") . "?v=$version' />";
        }
    }

}

if (!function_exists('projectanalizer_get_source_url')) {

    function projectanalizer_get_source_url($projectanalizer_file = "") {
        if (!$projectanalizer_file) {
            return "";
        }

        try {
            $file = unserialize($projectanalizer_file);
            if (is_array($file)) {
                return get_source_url_of_file($file, get_projectanalizer_setting("projectanalizer_file_path"), "thumbnail", false, false, true);
            }
        } catch (\Exception $ex) {
            
        }
    }

}
