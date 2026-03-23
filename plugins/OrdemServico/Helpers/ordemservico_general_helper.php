<?php

if (!function_exists('os_lang')) {
    function os_lang($key)
    {
        static $loaded = false; static $strings = [];
        if (!$loaded) {
            $locale = service('request')->getLocale() ?: 'english';
            $base = PLUGINPATH . 'OrdemServico/Language/';
            $file = is_dir($base . $locale) ? ($base . $locale . '/default_lang.php') : ($base . 'english/default_lang.php');
            if (file_exists($file)) {
                $lang = [];
                $lang = include $file;
                if (is_array($lang)) { $strings = $lang; }
            }
            $loaded = true;
        }
        return isset($strings[$key]) ? $strings[$key] : $key;
    }
}

