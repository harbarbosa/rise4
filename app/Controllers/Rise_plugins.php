<?php

namespace App\Controllers;

class Rise_plugins extends Security_Controller {

    function __construct() {
        parent::__construct();
        $this->access_only_admin();
    }

    //load plugin list view
    function index() {
        return $this->template->rander("plugins/index");
    }

    //load plugin upload modal form
    function modal_form() {
        return $this->template->view('plugins/modal_form');
    }

    //load plugin list view
    function install_modal_form($plugin) {
        $view_data["plugin"] = $plugin;
        return $this->template->view('plugins/install_modal_form', $view_data);
    }

    /* upload a post file */

    function upload_file() {
        upload_file_to_temp(true);
    }

    /* check valid file for plugin */

    function validate_plugin_file() {
        $file_name = $this->request->getPost("file_name");
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        if (!is_valid_file_to_upload($file_name)) {
            echo json_encode(array("success" => false, 'message' => app_lang('invalid_file_type')));
            exit();
        }

        if ($file_ext == "zip") {
            echo json_encode(array("success" => true));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('please_upload_a_zip_file') . " (.zip)"));
        }
    }

    //install plugin
    function save() {
        $this->validate_submitted_data(array(
            "file_name" => "required"
        ));

        $temp_file_path = get_setting("temp_file_path");
        $file_name = $this->request->getPost("file_name");
        $plugin_zip_file = $temp_file_path . $file_name;
        $plugin_name = "";

        if (!class_exists('ZipArchive')) {
            echo json_encode(array("success" => false, 'message' => "Please install the ZipArchive package in your server."));
            exit();
        }

        if (!file_exists($plugin_zip_file)) {
            echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
            exit();
        }

        $zip = new \ZipArchive;
        $zip->open($plugin_zip_file);

        //the index.php is required
        $has_index_file = false;

        //extract zip
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $file_info_array = $zip->statIndex($i);
            $file_name = get_array_value($file_info_array, "name");
            $dir = dirname($file_name);

            if (!$plugin_name) {
                //first folder should be the plugin name
                $plugin_name = explode('/', $file_name);
                $plugin_name = get_array_value($plugin_name, 0);

                if ($this->this_plugin_exists($plugin_name)) {
                    //this plugin is already installed
                    echo json_encode(array("success" => false, 'message' => app_lang("this_plugin_is_already_installed")));
                    exit();
                }
            }

            if (substr($file_name, -1, 1) == '/') {
                continue;
            }

            //create new directory if it's not exists
            if (!is_dir(PLUGINPATH . $dir)) {
                mkdir(PLUGINPATH . $dir, 0755, true);
            }

            //overwrite the existing file
            if (!is_dir(PLUGINPATH . $file_name)) {
                $contents = $zip->getFromIndex($i);

                if ($file_name == $plugin_name . '/index.php') {
                    $has_index_file = true;
                }

                file_put_contents(PLUGINPATH . $file_name, $contents);
            }
        }

        if (!($has_index_file && $plugin_name)) {
            //required files are missing
            echo json_encode(array("success" => false, 'message' => app_lang("the_required_files_missing")));
            exit();
        }

        $this->save_status_of_plugin($plugin_name, "installed");

        $zip->close(); //unset zip extraction variable to delete temp file
        delete_file_from_directory($plugin_zip_file); //delete temp file

        echo json_encode(array("success" => true, 'message' => app_lang('record_saved')));
    }

    private function get_plugins_array($include_directories = false) {
        $plugins = get_setting("plugins");
        $plugins = @unserialize($plugins);
        if (!($plugins && is_array($plugins))) {
            $plugins = array();
        }

        //get indexed folders
        if ($include_directories && is_dir(PLUGINPATH)) {
            if ($dh = opendir(PLUGINPATH)) {
                while (($file = readdir($dh)) !== false) {
                    if ($file && $file != "." && $file != ".." && $file != "index.html" && $file != ".gitkeep" && $file != ".DS_Store" && !array_key_exists($file, $plugins)) {
                        $plugins[$file] = "indexed";
                    }
                }
                closedir($dh);
            }
        }

        return $plugins;
    }

    private function this_plugin_exists($plugin_name = "") {
        $plugins = $this->get_plugins_array();
        if (array_key_exists($plugin_name, $plugins)) {
            return true;
        }
    }

    //save status of plugin
    function save_status_of_plugin($plugin_name = "", $status = "", $echo_json = false) {
        if (!($status === "installed" || $status === "activated" || $status === "deactivated")) {
            show_404();
        }

        $plugins = $this->get_plugins_array();

        if ($status === "installed") {
            if (!file_exists(PLUGINPATH . $plugin_name . "/index.php")) {
                //required files are missing
                echo json_encode(array("success" => false, 'message' => app_lang("the_required_files_missing")));
                exit();
            }

            if ($this->this_plugin_exists($plugin_name)) {
                //this plugin is already installed
                echo json_encode(array("success" => false, 'message' => app_lang("this_plugin_is_already_installed")));
                exit();
            }

            //install plugin 
            $this->install_plugin($plugin_name);
        } else if ($status === "activated") {
            $unsupported_error = is_unsupported_plugin($plugin_name);
            if ($unsupported_error) {
                echo json_encode(array("success" => false, 'message' => $unsupported_error));
                exit();
            }

            //since this plugin isn't activated, the index file won't be loaded
            //that's why, load it's index file to register activation hook
            if (file_exists(PLUGINPATH . $plugin_name . "/index.php")) {
                include(PLUGINPATH . $plugin_name . "/index.php");
            }

            app_hooks()->do_action("app_hook_activate_plugin_$plugin_name");
        } else if ($status === "deactivated") {
            app_hooks()->do_action("app_hook_deactivate_plugin_$plugin_name");
        }

        $plugins[$plugin_name] = $status;
        save_plugins_config($plugins);

        $plugins = clean_data($plugins);
        $plugins = serialize($plugins);

        $this->Settings_model->save_setting("plugins", $plugins);

        if ($echo_json) {
            echo json_encode(array("success" => true));
        }
    }

    //install plugin
    private function install_plugin($plugin_name = "") {
        $unsupported_error = is_unsupported_plugin($plugin_name);
        if ($unsupported_error) {
            echo json_encode(array("success" => false, 'message' => $unsupported_error));
            exit();
        }

        include(PLUGINPATH . $plugin_name . '/index.php');

        //call plugin installation hook
        $item_purchase_code = $this->request->getPost("file_description");
        app_hooks()->do_action("app_hook_install_plugin_$plugin_name", $item_purchase_code);
    }

    //delete/undo a plugin
    function delete($plugin_name = "") {
        if (!$plugin_name) {
            show_404();
        }

        $plugins = $this->get_plugins_array();
        $plugin_folder = PLUGINPATH . $plugin_name;


        if (array_key_exists($plugin_name, $plugins)) {
            //this is not on indexed state, means installed before
            $plugin_index_file = PLUGINPATH . $plugin_name . '/index.php';
            if (file_exists($plugin_index_file)) {
                include($plugin_index_file);

                //call plugin uninstallation hook
                app_hooks()->do_action("app_hook_uninstall_plugin_$plugin_name");
            }
        }

        //delete files
        if (is_dir($plugin_folder)) {
            helper("filesystem");
            delete_files($plugin_folder, true, false, true);

            //delete empty folder
            rmdir($plugin_folder);
        }

        //save plugins
        if (array_key_exists($plugin_name, $plugins)) {
            unset($plugins[$plugin_name]);
            $plugins = clean_data($plugins);
            $plugins = serialize($plugins);
            $this->Settings_model->save_setting("plugins", $plugins);
        }

        echo json_encode(array("success" => true, 'message' => app_lang('record_deleted')));
    }

    //get data for plugins plugin list
    function list_data() {
        $result = array();

        $plugins = $this->get_plugins_array(true);
        foreach ($plugins as $plugin => $status) {
            $result[] = $this->_make_row($plugin, $status);
        }

        echo json_encode(array("data" => $result));
    }

    //prepare an plugin list row
    //indexed, installed, activated, deactivated
    private function _make_row($plugin, $status) {

        $main_plugin_name = $plugin;
        $plugin_info = get_plugin_meta_data($plugin);

        //status: installed
        $action_type = "activated";
        $icon = "play";
        $status_class = "bg-warning";
        $lang_key = "activate";

        if ($status === "indexed") {
            $action_type = "installed";
            $lang_key = "install";
            $icon = "download";
            $status_class = "bg-secondary";
        } else if ($status === "activated") {
            $action_type = "deactivated";
            $lang_key = "deactivate";
            $icon = "pause";
            $status_class = "bg-success";
        } else if ($status === "deactivated") {
            $status_class = "bg-danger";
        }


        if ($action_type == "installed") {
            $action = '<li role="presentation">' . modal_anchor(get_uri("rise_plugins/install_modal_form/$plugin"), "<i data-feather='$icon' class='icon-16'></i> " . app_lang($lang_key), array("title" => app_lang("install") . " " . $plugin, "class" => "dropdown-item")) . '</li>';
        } else {
            $action = '<li role="presentation">' . ajax_anchor(get_uri("rise_plugins/save_status_of_plugin/$plugin/$action_type/1"), "<i data-feather='$icon' class='icon-16'></i> " . app_lang($lang_key), array("data-reload-on-success" => true, "class" => "dropdown-item", "data-show-response" => true)) . '</li>';
        }


        $update = "";
        if ($status === "activated") {
            $update = '<li role="presentation">' . modal_anchor(get_uri("rise_plugins/updates/$plugin"), "<i data-feather='refresh-cw' class='icon-16'></i> " . app_lang('updates'), array("title" => app_lang('updates'), "class" => "dropdown-item")) . '</li>';
        }

        $delete = "";
        if ($status !== "activated") {
            $delete = '<li role="presentation">' . js_anchor("<i data-feather='x' class='icon-16'></i>" . app_lang('delete'), array('title' => app_lang('delete'), "class" => "delete dropdown-item", "data-action-url" => get_uri("rise_plugins/delete/$plugin"), "data-action" => "delete-confirmation", "data-reload-on-success" => true)) . '</li>';
        }

        $option = '
                <span class="dropdown inline-block">
                    <button class="btn btn-default dropdown-toggle caret mt0 mb0" type="button" data-bs-toggle="dropdown" aria-expanded="true" data-bs-display="static">
                        <i data-feather="tool" class="icon-16"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" role="menu">' . $action . $update . $delete . '</ul>
                </span>';

        $plugin = "<b>" . (get_array_value($plugin_info, "plugin_name") ? get_array_value($plugin_info, "plugin_name") : $plugin) . "</b>";

        $action_links = "";
        $action_links_array = app_hooks()->apply_filters("app_filter_action_links_of_$main_plugin_name", array());
        if ($action_links_array && is_array($action_links_array)) {
            $action_links = "<br />";

            foreach ($action_links_array as $action_link) {
                if ($action_links === "<br />") {
                    $action_links .= $action_link;
                } else {
                    $action_links .= " | ";
                    $action_links .= $action_link;
                }
            }
        }

        if (get_array_value($plugin_info, "version")) {
            $plugin .= "<br />" . "<small>" . app_lang("version") . " " . get_array_value($plugin_info, "version") . "</small>";
        } else {
            $plugin .= "<br />";
        }

        $plugin .= "<small>" . $action_links . "</small>";

        return array(
            $plugin,
            $this->prepare_plugin_description($plugin_info),
            "<span class='mt0 badge $status_class'>" . app_lang($status) . "</span>",
            $option
        );
    }

    private function prepare_plugin_description($plugin_info) {
        $description = get_array_value($plugin_info, "description");
        $other_desc = "";

        $author = get_array_value($plugin_info, "author");
        $author_url = get_array_value($plugin_info, "author_url");
        $plugin_url = get_array_value($plugin_info, "plugin_url");

        if ($author) {
            if ($author_url) {
                $other_desc .= app_lang("by") . " " . anchor($author_url, $author, array("target" => "_blank"));
            } else {
                $other_desc .= app_lang("by") . " " . $author;
            }
        }

        if ($plugin_url) {
            if ($other_desc) {
                $other_desc .= " | ";
            }

            $other_desc .= anchor($plugin_url, app_lang("visit_plugin_site"), array("target" => "_blank"));
        }

        if ($other_desc) {
            $other_desc = "<br />" . "<small>" . $other_desc . "</small>";
        }

        $description .= $other_desc;

        return $description;
    }

    private function _curl_get_contents($url) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64)");

        $data = curl_exec($ch);
        curl_close($ch);

        return $data;
    }

    private function _get_remote_contents($url, $download = false) {
        $contents = $this->_curl_get_contents($url);
        if (!$contents) {
            if ($download) {
                $contents = fopen($url, "r");
            } else {
                $contents = @file_get_contents($url);
            }
        }

        return $contents;
    }

    private function _get_update_manifest($plugin_name = "")
    {
        $plugin_info = get_plugin_meta_data($plugin_name);
        $manifest_url = get_array_value($plugin_info, 'update_manifest_url');
        $manifest = array(
            'success' => false,
            'message' => '',
            'local_version' => get_array_value($plugin_info, 'version'),
            'remote_version' => get_array_value($plugin_info, 'version'),
            'repository_url' => get_array_value($plugin_info, 'update_repository_url'),
            'release_tag' => get_array_value($plugin_info, 'update_release_tag'),
            'zip_url' => get_array_value($plugin_info, 'update_zip_url'),
            'source_path' => get_array_value($plugin_info, 'update_source_path') ?: ('plugins/' . $plugin_name),
            'checksum' => get_array_value($plugin_info, 'update_checksum'),
            'manifest_url' => $manifest_url,
            'raw' => array(),
        );

        if (!$manifest_url && !$manifest['zip_url'] && (!$manifest['repository_url'] || !$manifest['release_tag'])) {
            $manifest['message'] = 'Este plugin nao possui uma fonte de atualizacao configurada.';
            return (object) $manifest;
        }

        if ($manifest_url) {
            $raw_manifest = $this->_get_remote_contents($manifest_url);
            if (!$raw_manifest) {
                $manifest['message'] = 'Nao foi possivel consultar o manifest de atualizacao.';
                return (object) $manifest;
            }

            $remote_manifest = json_decode($raw_manifest, true);
            if (!is_array($remote_manifest)) {
                $manifest['message'] = 'O manifest de atualizacao retornou um JSON invalido.';
                return (object) $manifest;
            }

            $manifest['raw'] = $remote_manifest;
            $manifest['remote_version'] = get_array_value($remote_manifest, 'version') ?: $manifest['remote_version'];
            $manifest['repository_url'] = get_array_value($remote_manifest, 'repository_url') ?: $manifest['repository_url'];
            $manifest['release_tag'] = get_array_value($remote_manifest, 'release_tag') ?: $manifest['release_tag'];
            $manifest['zip_url'] = get_array_value($remote_manifest, 'zip_url') ?: $manifest['zip_url'];
            $manifest['source_path'] = get_array_value($remote_manifest, 'source_path') ?: $manifest['source_path'];
            $manifest['checksum'] = get_array_value($remote_manifest, 'checksum') ?: $manifest['checksum'];
            $manifest['notes'] = get_array_value($remote_manifest, 'notes');
            $manifest['success'] = true;
            if (!$manifest['zip_url'] && $manifest['repository_url'] && $manifest['release_tag']) {
                $manifest['zip_url'] = rtrim($manifest['repository_url'], '/').'/archive/refs/tags/'.rawurlencode($manifest['release_tag']).'.zip';
            }
            return (object) $manifest;
        }

        if (!$manifest['zip_url'] && $manifest['repository_url'] && $manifest['release_tag']) {
            $manifest['zip_url'] = rtrim($manifest['repository_url'], '/').'/archive/refs/tags/'.rawurlencode($manifest['release_tag']).'.zip';
        }

        $manifest['success'] = true;
        return (object) $manifest;
    }

    private function _find_update_source_dir($extract_root = "", $source_path = "", $plugin_name = "")
    {
        $extract_root = rtrim((string) $extract_root, "/\\");
        $source_path = trim(str_replace('\\', '/', (string) $source_path), '/');
        $plugin_name = trim((string) $plugin_name);

        if (!$extract_root || !is_dir($extract_root)) {
            return "";
        }

        $normalized_source_path = $source_path ? str_replace('/', DIRECTORY_SEPARATOR, $source_path) : '';
        $top_level_dirs = glob($extract_root . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR);

        foreach ($top_level_dirs as $top_level_dir) {
            if ($normalized_source_path) {
                $candidate = rtrim($top_level_dir, "/\\") . DIRECTORY_SEPARATOR . $normalized_source_path;
                if (is_dir($candidate) && is_file($candidate . DIRECTORY_SEPARATOR . 'index.php')) {
                    return $candidate;
                }
            }

            if (!$normalized_source_path && basename($top_level_dir) === $plugin_name && is_file($top_level_dir . DIRECTORY_SEPARATOR . 'index.php')) {
                return $top_level_dir;
            }
        }

        if ($normalized_source_path) {
            $candidate = $extract_root . DIRECTORY_SEPARATOR . $normalized_source_path;
            if (is_dir($candidate) && is_file($candidate . DIRECTORY_SEPARATOR . 'index.php')) {
                return $candidate;
            }
        }

        return "";
    }

    function stage_update($plugin_name = "")
    {
        if (!$plugin_name) {
            show_404();
        }

        $plugins = $this->get_plugins_array();
        if (get_array_value($plugins, $plugin_name) !== "activated") {
            return $this->response->setJSON(array("success" => false, "message" => "Ative o plugin antes de atualizar."));
        }

        $manifest = $this->_get_update_manifest($plugin_name);
        if (!$manifest->success) {
            return $this->response->setJSON(array("success" => false, "message" => $manifest->message));
        }

        if (!$manifest->zip_url) {
            return $this->response->setJSON(array("success" => false, "message" => "O plugin nao informou uma URL de pacote para atualizacao."));
        }

        $local_version = (string) $manifest->local_version;
        $remote_version = (string) $manifest->remote_version;

        if ($remote_version && $local_version && version_compare($remote_version, $local_version, '<=')) {
            return $this->response->setJSON(array(
                "success" => false,
                "message" => "Nao ha uma versao mais nova disponivel para este plugin."
            ));
        }

        if (!class_exists('ZipArchive')) {
            return $this->response->setJSON(array("success" => false, "message" => "Por favor, instale o pacote ZipArchive no servidor."));
        }

        $workdir = get_plugin_update_workdir($plugin_name);
        $download_dir = $workdir . DIRECTORY_SEPARATOR . 'download';
        $extract_dir = $workdir . DIRECTORY_SEPARATOR . 'extract';
        $staged_dir = $workdir . DIRECTORY_SEPARATOR . 'staged';
        $package_file = $download_dir . DIRECTORY_SEPARATOR . 'package.zip';
        $stage_target = $staged_dir . DIRECTORY_SEPARATOR . $plugin_name;

        helper('filesystem');

        if (!is_dir($download_dir)) {
            @mkdir($download_dir, 0755, true);
        }
        if (!is_dir($extract_dir)) {
            @mkdir($extract_dir, 0755, true);
        }
        if (!is_dir($staged_dir)) {
            @mkdir($staged_dir, 0755, true);
        }

        if (!is_dir(dirname($stage_target))) {
            @mkdir(dirname($stage_target), 0755, true);
        }

        $zip_contents = $this->_get_remote_contents($manifest->zip_url, true);
        if (!$zip_contents) {
            return $this->response->setJSON(array("success" => false, "message" => "Nao foi possivel baixar o pacote de atualizacao."));
        }

        if (file_put_contents($package_file, is_string($zip_contents) ? $zip_contents : stream_get_contents($zip_contents)) === false) {
            return $this->response->setJSON(array("success" => false, "message" => "Nao foi possivel salvar o pacote baixado."));
        }

        if (!empty($manifest->checksum)) {
            $downloaded_checksum = hash_file('sha256', $package_file);
            if ($downloaded_checksum !== trim((string) $manifest->checksum)) {
                @unlink($package_file);
                return $this->response->setJSON(array("success" => false, "message" => "A verificacao de integridade do pacote falhou."));
            }
        }

        $zip = new \ZipArchive();
        if ($zip->open($package_file) !== true) {
            @unlink($package_file);
            return $this->response->setJSON(array("success" => false, "message" => "Nao foi possivel abrir o pacote baixado."));
        }

        $extract_path = $extract_dir . DIRECTORY_SEPARATOR . uniqid($plugin_name . '_', true);
        if (!is_dir($extract_path)) {
            @mkdir($extract_path, 0755, true);
        }

        if (!$zip->extractTo($extract_path)) {
            $zip->close();
            @unlink($package_file);
            return $this->response->setJSON(array("success" => false, "message" => "Nao foi possivel extrair o pacote de atualizacao."));
        }
        $zip->close();

        $source_dir = $this->_find_update_source_dir($extract_path, $manifest->source_path, $plugin_name);
        if (!$source_dir) {
            delete_files($extract_path, true, false, true);
            @rmdir($extract_path);
            @unlink($package_file);
            return $this->response->setJSON(array("success" => false, "message" => "Nao foi possivel localizar a pasta do plugin dentro do pacote."));
        }

        if (is_dir($stage_target)) {
            delete_files($stage_target, true, false, true);
            @rmdir($stage_target);
        }

        copy_recursively($source_dir, $stage_target);

        $pending_data = array(
            'plugin_name' => $plugin_name,
            'local_version' => $local_version,
            'remote_version' => $remote_version,
            'manifest_url' => $manifest->manifest_url,
            'zip_url' => $manifest->zip_url,
            'source_path' => $manifest->source_path,
            'checksum' => $manifest->checksum,
            'staged_path' => $stage_target,
            'package_file' => $package_file,
            'created_at' => get_my_local_time(),
            'notes' => get_array_value($manifest, 'notes'),
        );

        if (!queue_pending_plugin_update($plugin_name, $pending_data)) {
            delete_files($stage_target, true, false, true);
            @rmdir($stage_target);
            delete_files($extract_path, true, false, true);
            @rmdir($extract_path);
            @unlink($package_file);

            return $this->response->setJSON(array("success" => false, "message" => "Nao foi possivel agendar a atualizacao."));
        }

        delete_files($extract_path, true, false, true);
        @rmdir($extract_path);

        return $this->response->setJSON(array(
            "success" => true,
            "message" => "Atualizacao preparada. Ela sera aplicada antes do proximo carregamento do sistema."
        ));
    }

    function updates($plugin_name = "") {
        $plugins = $this->get_plugins_array();
        if (get_array_value($plugins, $plugin_name) !== "activated") {
            show_404();
        }

        $plugin_info = get_plugin_meta_data($plugin_name);
        if (get_array_value($plugin_info, 'update_manifest_url') || get_array_value($plugin_info, 'update_zip_url')) {
            $update_info = $this->_get_update_manifest($plugin_name);
            $view_data = array(
                'plugin_name' => $plugin_name,
                'plugin_info' => $plugin_info,
                'update_info' => $update_info,
                'can_stage_update' => $update_info->success && $update_info->zip_url && $update_info->remote_version && version_compare((string) $update_info->remote_version, (string) $update_info->local_version, '>'),
            );

            return $this->template->view('plugins/update_modal_form', $view_data);
        }

        if (app_hooks()->has_action("app_hook_update_plugin_$plugin_name")) {
            app_hooks()->do_action("app_hook_update_plugin_$plugin_name");
        } else {
            return $this->template->view('plugins/no_hook_modal');
        }
    }
}

/* End of file plugins.php */
/* Location: ./app/controllers/plugins.php */
