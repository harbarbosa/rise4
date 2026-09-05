<?php

//Prevent direct access
defined('PLUGINPATH') or exit('No direct script access allowed');

require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/Config/FrotaRoutes.php';

use RestApi\Libraries\Apiinit;

/*
  Plugin Name: API
  Description: Rest API module for RISE CRM
  Version: 1.1.0
  Requires at least: 2.8
  Author: Themesic Interactive
  Author URL: https://codecanyon.net/user/themesic/portfolio
 */



app_hooks()->add_filter('app_filter_staff_left_menu', function ($sidebar_menu) {
	$sidebar_menu["API"] = [
		"name"     => "api",
		"url"      => "api_settings",
		"class"    => "tag",
		"position" => 6
	];

	return $sidebar_menu;
});

app_hooks()->add_filter('app_filter_app_csrf_exclude_uris', function ($urls) {
	Apiinit::check_url("RestApi");
	$urls[] = "api/*";
	return $urls;
});

/*
 * Compatibility layer for the legacy RestApi project create handler.
 * The bundled ProjectsController still writes the old `status` field and
 * ignores newer RISE fields such as status_id/project_type/cost_center_id.
 * Normalize only POST /api/projects inserts, leaving the web UI untouched.
 */
app_hooks()->add_filter('app_filter_data_before_insert', function ($hook_data) {
	if (($hook_data['table_without_prefix'] ?? '') !== 'projects') {
		return $hook_data;
	}

	$request = \Config\Services::request();
	$request_path = strtolower(trim((string) $request->getUri()->getPath(), '/'));
	if (strtolower((string) $request->getMethod()) !== 'post' || $request_path !== 'api/projects') {
		return $hook_data;
	}

	$data = $hook_data['data'] ?? [];
	$posted = $request->getPost();

	// RISE current schema uses status_id. Remove the legacy API field.
	unset($data['status']);
	$status_id = $posted['status_id'] ?? 1;
	$data['status_id'] = (is_numeric($status_id) && (int) $status_id > 0) ? (int) $status_id : 1;

	$project_type = (string) ($posted['project_type'] ?? 'client_project');
	if (!in_array($project_type, ['client_project', 'internal_project'], true)) {
		$project_type = 'client_project';
	}
	$data['project_type'] = $project_type;
	if ($project_type === 'internal_project') {
		$data['client_id'] = 0;
	}

	if (array_key_exists('cost_center_id', $posted)) {
		$data['cost_center_id'] = is_numeric($posted['cost_center_id']) && (int) $posted['cost_center_id'] > 0
			? (int) $posted['cost_center_id']
			: null;
	}

	if (array_key_exists('proposal_id', $posted)) {
		$data['proposal_id'] = is_numeric($posted['proposal_id']) && (int) $posted['proposal_id'] > 0
			? (int) $posted['proposal_id']
			: null;
	}

	if (empty($data['start_date'])) {
		$data['start_date'] = null;
	}
	if (empty($data['deadline'])) {
		$data['deadline'] = null;
	}

	$data['created_date'] = $data['created_date'] ?? get_current_utc_time();

	// Resolve the authenticated API account to the corresponding RISE staff ID.
	$created_by = 0;
	try {
		$token = get_token();
		$api_settings_model = model('RestApi\\Models\\Api_settings_model');
		$api_user = $api_settings_model->check_token($token);
		$email = strtolower(trim((string) ($api_user->user ?? '')));
		if ($email !== '') {
			$users_model = model('App\\Models\\Users_model');
			$staff_user = $users_model->get_one_where([
				'email' => $email,
				'deleted' => 0,
				'status' => 'active',
				'user_type' => 'staff',
			]);
			if (!empty($staff_user->id)) {
				$created_by = (int) $staff_user->id;
			}
		}
	} catch (\Throwable $e) {
		log_message('warning', '[RestApi] Unable to resolve project created_by: ' . $e->getMessage());
	}

	if ($created_by <= 0 && isset($posted['created_by']) && is_numeric($posted['created_by'])) {
		$created_by = (int) $posted['created_by'];
	}
	$data['created_by'] = $created_by;

	$hook_data['data'] = clean_data($data);
	return $hook_data;
});

// Keep the native RISE behavior: the API creator becomes project leader.
app_hooks()->add_action('app_hook_data_insert', function ($hook_data) {
	if (($hook_data['table_without_prefix'] ?? '') !== 'projects') {
		return;
	}

	$request = \Config\Services::request();
	$request_path = strtolower(trim((string) $request->getUri()->getPath(), '/'));
	if (strtolower((string) $request->getMethod()) !== 'post' || $request_path !== 'api/projects') {
		return;
	}

	$project_id = (int) ($hook_data['id'] ?? 0);
	$created_by = (int) (($hook_data['data']['created_by'] ?? 0));
	if ($project_id <= 0 || $created_by <= 0) {
		return;
	}

	try {
		$project_members_model = model('App\\Models\\Project_members_model');
		$existing = $project_members_model->get_one_where([
			'project_id' => $project_id,
			'user_id' => $created_by,
			'deleted' => 0,
		]);
		if (empty($existing->id)) {
			$project_members_model->save_member([
				'project_id' => $project_id,
				'user_id' => $created_by,
				'is_leader' => 1,
			]);
		}
	} catch (\Throwable $e) {
		log_message('warning', '[RestApi] Unable to attach project creator: ' . $e->getMessage());
	}
});

register_installation_hook("RestApi", function ($item_purchase_code) {
		include PLUGINPATH . "RestApi/install/do_install.php";
});

register_uninstallation_hook("RestApi", function () {
    $dbprefix = get_db_prefix();
    $db = db_connect('default');

    $sql_query = "DELETE FROM `" . $dbprefix . "settings` WHERE `" . $dbprefix . "settings`.`setting_name`='RestApi_verification_id';";
    $db->query($sql_query);

    $sql_query = "DELETE FROM `" . $dbprefix . "settings` WHERE `" . $dbprefix . "settings`.`setting_name`='RestApi_verified';";
    $db->query($sql_query);

    $sql_query = "DELETE FROM `" . $dbprefix . "settings` WHERE `" . $dbprefix . "settings`.`setting_name`='RestApi_last_verification';";
    $db->query($sql_query);

});