<?php

if (!function_exists('app_hooks')) {

    function app_hooks() {
        global $hooks;
        return $hooks;
    }
}

if (!function_exists('get_plugin_meta_data')) {

    function get_plugin_meta_data($plugin_name = "") {
        $plugin_info_array = array();

        if (!file_exists(PLUGINPATH . $plugin_name . "/index.php")) {
            return $plugin_info_array;
        }

        $plugin_index_file_contents = file_get_contents(PLUGINPATH . $plugin_name . "/index.php");

        preg_match('|Plugin Name:(.*)$|mi', $plugin_index_file_contents, $plugin_name);
        preg_match('|Plugin URL:(.*)$|mi', $plugin_index_file_contents, $plugin_url);
        preg_match('|Description:(.*)$|mi', $plugin_index_file_contents, $description);
        preg_match('|Version:(.*)|i', $plugin_index_file_contents, $version);
        preg_match('|Requires at least:(.*)$|mi', $plugin_index_file_contents, $requires_at_least);
        preg_match('|Author:(.*)$|mi', $plugin_index_file_contents, $author);
        preg_match('|Author URL:(.*)$|mi', $plugin_index_file_contents, $author_url);
        preg_match('|Update Repository URL:(.*)$|mi', $plugin_index_file_contents, $update_repository_url);
        preg_match('|Update Release Tag:(.*)$|mi', $plugin_index_file_contents, $update_release_tag);
        preg_match('|Update Manifest URL:(.*)$|mi', $plugin_index_file_contents, $update_manifest_url);
        preg_match('|Update ZIP URL:(.*)$|mi', $plugin_index_file_contents, $update_zip_url);
        preg_match('|Update Source Path:(.*)$|mi', $plugin_index_file_contents, $update_source_path);
        preg_match('|Update Checksum:(.*)$|mi', $plugin_index_file_contents, $update_checksum);

        if (isset($plugin_name[1])) {
            $plugin_info_array['plugin_name'] = trim($plugin_name[1]);
        }

        if (isset($plugin_url[1])) {
            $plugin_info_array['plugin_url'] = trim($plugin_url[1]);
        }

        if (isset($description[1])) {
            $plugin_info_array['description'] = trim($description[1]);
        }

        if (isset($version[1])) {
            $plugin_info_array['version'] = trim($version[1]);
        } else {
            $plugin_info_array['version'] = 0;
        }

        if (isset($requires_at_least[1])) {
            $plugin_info_array['requires_at_least'] = trim($requires_at_least[1]);
        }

        if (isset($author[1])) {
            $plugin_info_array['author'] = trim($author[1]);
        }

        if (isset($author_url[1])) {
            $plugin_info_array['author_url'] = trim($author_url[1]);
        }

        if (isset($update_repository_url[1])) {
            $plugin_info_array['update_repository_url'] = trim($update_repository_url[1]);
        }

        if (isset($update_release_tag[1])) {
            $plugin_info_array['update_release_tag'] = trim($update_release_tag[1]);
        }

        if (isset($update_manifest_url[1])) {
            $plugin_info_array['update_manifest_url'] = trim($update_manifest_url[1]);
        }

        if (isset($update_zip_url[1])) {
            $plugin_info_array['update_zip_url'] = trim($update_zip_url[1]);
        }

        if (isset($update_source_path[1])) {
            $plugin_info_array['update_source_path'] = trim($update_source_path[1]);
        }

        if (isset($update_checksum[1])) {
            $plugin_info_array['update_checksum'] = trim($update_checksum[1]);
        }

        return $plugin_info_array;
    }
}

if (!function_exists('register_installation_hook')) {

    function register_installation_hook($plugin_name, $function) {
        app_hooks()->add_action("app_hook_install_plugin_$plugin_name", $function);
    }
}

if (!function_exists('register_uninstallation_hook')) {

    function register_uninstallation_hook($plugin_name, $function) {
        app_hooks()->add_action("app_hook_uninstall_plugin_$plugin_name", $function);
    }
}

if (!function_exists('register_activation_hook')) {

    function register_activation_hook($plugin_name, $function) {

        app_hooks()->add_action("app_hook_activate_plugin_$plugin_name", $function);
    }
}

if (!function_exists('register_deactivation_hook')) {

    function register_deactivation_hook($plugin_name, $function) {
        app_hooks()->add_action("app_hook_deactivate_plugin_$plugin_name", $function);
    }
}

if (!function_exists('is_unsupported_plugin')) {

    function is_unsupported_plugin($plugin_name) {
        $plugin_info = get_plugin_meta_data($plugin_name);
        $app_version = get_setting("app_version");
        $error = "";

        if (get_array_value($plugin_info, "requires_at_least") && ($app_version < get_array_value($plugin_info, "requires_at_least"))) {
            $error = sprintf(app_lang("plugin_requires_at_least_error_message"), get_array_value($plugin_info, "requires_at_least"));
        }

        return $error;
    }
}

//save activated plugins to a config file as data
if (!function_exists('save_plugins_config')) {

    function save_plugins_config($plugins = array()) {
        $activated_plugins = array();
        foreach ($plugins as $plugin => $status) {
            if ($status === "activated") {
                array_push($activated_plugins, $plugin);
            }
        }

        $contents = json_encode($activated_plugins);
        file_put_contents(APPPATH . "Config/activated_plugins.json", $contents);
    }
}

if (!function_exists('register_update_hook')) {

    function register_update_hook($plugin_name, $function) {
        app_hooks()->add_action("app_hook_update_plugin_$plugin_name", $function);
    }
}

if (!function_exists('get_plugin_update_workdir')) {

    function get_plugin_update_workdir($plugin_name = "") {
        $plugin_name = trim((string) $plugin_name);
        if (!$plugin_name) {
            return "";
        }

        return rtrim(WRITEPATH, '/\\') . DIRECTORY_SEPARATOR . 'plugin_updates' . DIRECTORY_SEPARATOR . $plugin_name;
    }
}

if (!function_exists('get_pending_plugin_updates_file')) {

    function get_pending_plugin_updates_file() {
        return rtrim(WRITEPATH, '/\\') . DIRECTORY_SEPARATOR . 'plugin_updates' . DIRECTORY_SEPARATOR . 'pending.json';
    }
}

if (!function_exists('load_pending_plugin_updates')) {

    function load_pending_plugin_updates() {
        $pending_file = get_pending_plugin_updates_file();
        if (!file_exists($pending_file)) {
            return array();
        }

        $contents = file_get_contents($pending_file);
        $updates = json_decode($contents, true);
        if (!is_array($updates)) {
            return array();
        }

        return $updates;
    }
}

if (!function_exists('save_pending_plugin_updates')) {

    function save_pending_plugin_updates($updates = array()) {
        $pending_file = get_pending_plugin_updates_file();
        $pending_dir = dirname($pending_file);

        if (!is_dir($pending_dir)) {
            @mkdir($pending_dir, 0755, true);
        }

        return (bool) file_put_contents($pending_file, json_encode($updates, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}

if (!function_exists('queue_pending_plugin_update')) {

    function queue_pending_plugin_update($plugin_name = "", $update_data = array()) {
        $plugin_name = trim((string) $plugin_name);
        if (!$plugin_name) {
            return false;
        }

        $updates = load_pending_plugin_updates();
        $updates[$plugin_name] = $update_data;

        return save_pending_plugin_updates($updates);
    }
}

if (!function_exists('clear_pending_plugin_update')) {

    function clear_pending_plugin_update($plugin_name = "") {
        $plugin_name = trim((string) $plugin_name);
        if (!$plugin_name) {
            return false;
        }

        $updates = load_pending_plugin_updates();
        if (array_key_exists($plugin_name, $updates)) {
            unset($updates[$plugin_name]);
            return save_pending_plugin_updates($updates);
        }

        return true;
    }
}

if (!function_exists('apply_pending_plugin_updates')) {

    function apply_pending_plugin_updates() {
        $updates = load_pending_plugin_updates();
        if (!($updates && is_array($updates))) {
            return;
        }

        helper(array('filesystem', 'app_files'));

        foreach ($updates as $plugin_name => $update_data) {
            $plugin_name = trim((string) $plugin_name);
            $staged_path = get_array_value($update_data, 'staged_path');
            $source_path = get_array_value($update_data, 'source_path');
            $backup_path = get_array_value($update_data, 'backup_path');
            $plugin_path = PLUGINPATH . $plugin_name;

            if (!$plugin_name || !$staged_path || !is_dir($staged_path) || !is_dir($source_path)) {
                log_message('error', '[PLUGIN UPDATE] Pending update for "' . $plugin_name . '" is invalid or incomplete.');
                clear_pending_plugin_update($plugin_name);
                continue;
            }

            $backup_root = $backup_path ? $backup_path : rtrim(WRITEPATH, '/\\') . DIRECTORY_SEPARATOR . 'plugin_backups' . DIRECTORY_SEPARATOR . $plugin_name . DIRECTORY_SEPARATOR . date('Ymd_His');
            $backup_dir = $backup_root . DIRECTORY_SEPARATOR . $plugin_name;

            try {
                if (is_dir($backup_dir)) {
                    delete_files($backup_dir, true, false, true);
                    @rmdir($backup_dir);
                }

                if (is_dir($plugin_path)) {
                    if (!is_dir(dirname($backup_dir))) {
                        @mkdir(dirname($backup_dir), 0755, true);
                    }

                    copy_recursively($plugin_path, $backup_dir);
                    delete_files($plugin_path, true, false, true);
                    @rmdir($plugin_path);
                }

                if (!is_dir(dirname($plugin_path))) {
                    @mkdir(dirname($plugin_path), 0755, true);
                }

                copy_recursively($source_path, $plugin_path);

                clear_pending_plugin_update($plugin_name);

                if (is_dir($staged_path)) {
                    delete_files($staged_path, true, false, true);
                    @rmdir($staged_path);
                }

                $package_file = get_array_value($update_data, 'package_file');
                if ($package_file && is_file($package_file)) {
                    @unlink($package_file);
                }
            } catch (\Throwable $e) {
                log_message('error', '[PLUGIN UPDATE] Failed to apply update for "' . $plugin_name . '": ' . $e->getMessage());

                if (is_dir($plugin_path)) {
                    delete_files($plugin_path, true, false, true);
                    @rmdir($plugin_path);
                }

                if (is_dir($backup_dir)) {
                    copy_recursively($backup_dir, $plugin_path);
                }

                clear_pending_plugin_update($plugin_name);
            }
        }
    }
}

if (!function_exists('register_data_insert_hook')) {

    function register_data_insert_hook($function) {
        app_hooks()->add_action("app_hook_data_insert", $function);
    }
}

if (!function_exists('register_data_update_hook')) {

    function register_data_update_hook($function) {
        app_hooks()->add_action("app_hook_data_update", $function);
    }
}

if (!function_exists('register_data_delete_hook')) {

    function register_data_delete_hook($function) {
        app_hooks()->add_action("app_hook_data_delete", $function);
    }
}

if (!function_exists('register_before_insert_filter_hook')) {

    function register_before_insert_filter_hook($function) {
        app_hooks()->add_action("app_filter_data_before_insert", $function);
    }
}
