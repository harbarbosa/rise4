<?php

namespace RestApi\Controllers;

class ProjectsController extends Rest_api_Controller {
	protected $ProjectsModel = 'RestApi\Models\ProjectsModel';

	public function __construct() {
		parent::__construct();
		
		$this->projects_model         = model('App\Models\Projects_model');
		$this->project_members_model  = model('App\Models\Project_members_model');
		$this->restapi_projects_model = model($this->ProjectsModel);
		$this->restapi_clients_model  = model("RestApi\Models\ClientsModel");
		$this->restapi_labels_model   = model("RestApi\Models\LabelsModel");
		$this->clients_model          = model('App\Models\Clients_model');
		$this->labels_model           = model('App\Models\Labels_model');
	}

	/**
	 * Attach project members to project rows.
	 *
	 * @param array $projects
	 * @return array
	 */
	private function appendProjectMembers(array $projects): array {
		if (empty($projects)) {
			return $projects;
		}

		$project_ids = [];
		foreach ($projects as $project) {
			if (!empty($project->id)) {
				$project_ids[] = (int) $project->id;
			}
		}

		$project_ids = array_values(array_unique(array_filter($project_ids)));
		if (empty($project_ids)) {
			foreach ($projects as $project) {
				$project->members = [];
			}
			return $projects;
		}

		$members_by_project = $this->getActiveProjectMembersByProjectIds($project_ids);

		foreach ($projects as $project) {
			$project_id = (int) ($project->id ?? 0);
			$project->members = $members_by_project[$project_id] ?? [];
		}

		return $projects;
	}

	/**
	 * Attach project members to a single project row.
	 *
	 * @param object|null $project
	 * @return object|null
	 */
	private function appendProjectMembersToSingle($project) {
		if (!$project || empty($project->id)) {
			return $project;
		}

		$active_members = $this->getActiveProjectMembersByProjectIds([(int) $project->id]);
		$project->members = $active_members[(int) $project->id] ?? [];
		return $project;
	}

	/**
	 * Return active staff members attached to the given project IDs.
	 *
	 * @param array $projectIds
	 * @return array<int, array<int, object>>
	 */
	private function getActiveProjectMembersByProjectIds(array $projectIds): array {
		$projectIds = array_values(array_unique(array_filter(array_map('intval', $projectIds))));
		if (empty($projectIds)) {
			return [];
		}

		$db = db_connect('default');
		$projectMembersTable = $db->prefixTable('project_members');
		$usersTable = $db->prefixTable('users');
		$idList = implode(',', $projectIds);

		$sql = "SELECT pm.project_id, pm.user_id, pm.id, pm.deleted,
                    CONCAT(u.first_name, ' ', u.last_name) AS member_name,
                    u.image AS member_image,
                    u.job_title,
                    u.user_type,
                    u.status AS member_status
                FROM $projectMembersTable pm
                INNER JOIN $usersTable u ON u.id = pm.user_id
                WHERE pm.deleted=0
                    AND pm.project_id IN ($idList)
                    AND u.deleted=0
                    AND u.user_type='staff'
                    AND u.status='active'
                ORDER BY pm.project_id ASC, u.first_name ASC";

		$rows = $db->query($sql)->getResult();
		$grouped = [];
		foreach ($rows as $row) {
			$projectId = (int) ($row->project_id ?? 0);
			if ($projectId <= 0) {
				continue;
			}

			if (!isset($grouped[$projectId])) {
				$grouped[$projectId] = [];
			}

			$grouped[$projectId][] = $row;
		}

		return $grouped;
	}

	/**
	 * @api {get} /api/projects/ List all Project Information
	 * @apiVersion 1.0.0
	 * @apiName index
	 * @apiGroup Projects
	 * @apiHeader {String} Authorization Basic Access Authentication token.
	 *
	 * @apiSuccess {Object} Project information
	 * @apiSuccessExample Success-Response:
	 * {
	 * 		"id": "2",
	 * 		"title": "project 1",
	 * 		"description": "",
	 * 		"start_date": null,
	 * 		"deadline": null,
	 * 		"client_id": "2",
	 * 		"created_date": "2021-09-12",
	 * 		"created_by": "1",
	 * 		"status": "open",
	 * 		"labels": "",
	 * 		"price": "0",
	 * 		"starred_by": "",
	 * 		"estimate_id": "0",
	 * 		"order_id": "0",
	 * 		"deleted": "0",
	 * 		"company_name": "jdoe",
	 * 		"currency_symbol": "INR",
	 * 		"total_points": null,
	 * 		"completed_points": null,
	 * 		"labels_list": null
	 * }
	 *
	 * @apiError {Boolean} status Request status
	 * @apiError {String} message No data were found
	 */
	public function index() {
		$user_id = (int) ($this->request->getGet('user_id') ?? 0);
		$options = [];
		if ($user_id > 0) {
			$options['user_id'] = $user_id;
		}

		$list_data = $this->projects_model->get_details($options)->getResult();
		if (empty($list_data)) {
			return $this->failNotFound(app_lang('no_data_were_found'));
		}
		return $this->respond($this->appendProjectMembers($list_data), 200);
	}

	/**
	 * Return the properties of a resource object
	 *
	 * @return mixed
	 */
	public function show($id = null, $searchTerm = "") {
		if (!is_null($id) && is_numeric($id)) {
			$list_data = $this->projects_model->get_details(['id' => $id])->getRow();
			if (empty($list_data)) {
				return $this->failNotFound(app_lang('no_data_were_found'));
			}
			return $this->respond($this->appendProjectMembersToSingle($list_data), 200);
		}
	}

	/**
	 * @api {get} /api/projects/search/:keysearch Search Project information
	 * @apiVersion 1.0.0
	 * @apiName search
	 * @apiGroup Projects
	 * @apiHeader {String} Authorization Basic Access Authentication token.
	 *
	 * @apiParam {String} keysearch Search Keywords
	 *
	 * @apiSuccess {Object} Project information
	 * @apiSuccessExample Success-Response:
	 * {
	 * 		"id": "2",
	 * 		"title": "project 1",
	 * 		"description": "",
	 * 		"start_date": null,
	 * 		"deadline": null,
	 * 		"client_id": "2",
	 * 		"created_date": "2021-09-12",
	 * 		"created_by": "1",
	 * 		"status": "open",
	 * 		"labels": "",
	 * 		"price": "0",
	 * 		"starred_by": "",
	 * 		"estimate_id": "0",
	 * 		"order_id": "0",
	 * 		"deleted": "0",
	 * 		"company_name": "jdoe",
	 * 		"currency_symbol": "INR",
	 * 		"total_points": null,
	 * 		"completed_points": null,
	 * 		"labels_list": null
	 * }
	 *
	 * @apiError {Boolean} status Request status
	 * @apiError {String} message No data were found
	 */
	public function search($key = '') {
		if (!empty($key)) {
			$list_data = $this->restapi_projects_model->get_search_suggestion($key)->getResult();
			if (empty($list_data)) {
				return $this->failNotFound(app_lang('no_data_were_found'));
			}
			return $this->respond($this->appendProjectMembers($list_data), 200);
		}
	}

	/**
	 * @api {post} api/projects Add New Project
	 * @apiVersion 1.0.0
	 * @apiName create
	 * @apiGroup Projects
	 *
	 * @apiHeader {String} Authorization Basic Access Authentication token.
	 *
	 * @apiParam {string} title                         		Mandatory Project Title.
	 * @apiParam {string} client_id		                        Mandatory Project client id.
	 * @apiParam {string} start_date		                    Mandatory Project Start Date.
	 * @apiParam {string} description	                        Optional Project description.
	 * @apiParam {string} deadline	                        	Optional Project deadline.
	 * @apiParam {string} price	                        		Optional Project price.
	 * @apiParam {string} labels	                        	Optional Project labels.
	 *
	 * @apiParamExample Request-Example:
	 *     array (size=6)
	 *        'title' => string 'Project title' (length=9)
	 *        'client_id' => string '1' (length=1)
	 *        'start_date' => string '2021-09-27' (length=1)
	 *        'deadline' => string '2021-11-27' (length=1)
	 *        'price' => string '600' (length=1)
	 *        'labels' => string '1,2' (length=1)  *
	 *
	 * @apiSuccess {Boolean} status Request status.
	 * @apiSuccess {String} message Project add successful.
	 *
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 *     {
	 *       "status": true,
	 *       "message": "Project add successful."
	 *     }
	 *
	 * @apiError {Boolean} status Request status.
	 * @apiError {String} message Project add fail.
	 *
	 * @apiErrorExample Error-Response:
	 *     HTTP/1.1 404 Not Found
	 *     {
	 *       "status": false,
	 *       "message": "Project add fail."
	 *     }
	 *
	 */
	public function create() {
		$posted_data = $this->request->getPost();
		if (!empty($posted_data)) {
			$rules = [
				'title'      => 'required',
				'client_id'  => 'required|numeric',
				'start_date' => 'valid_date|if_exist',
				'deadline'   => 'valid_date|if_exist',
				'price'      => 'numeric|if_exist'
			];
			$error = [
				'title' => [
					'required' => app_lang('project_title_required')
				],
				'client_id' => [
					'required' => app_lang('client_id_required'),
					'numeric'  => app_lang('client_id_invalid')
				],
				'start_date' => [
					'valid_date' => app_lang('start_data_invalid')
				],
				'price' => [
					'numeric' => app_lang('price_is_invalid')
				]
			];
			if (!$this->validate($rules, $error)) {
				$response = [
				  'error' => $this->validator->getErrors(),
				  ];
				return $this->fail($response);
			}

			$insert_data = [
				'title'        => $posted_data['title'],
				'client_id'    => $posted_data['client_id'],
				'description'  => $posted_data['description'] ?? null,
				'start_date'   => $posted_data['start_date'] ?? null,
				'deadline'     => $posted_data['deadline'] ?? null,
				'price'        => unformat_currency($posted_data['price']) ?? 0,
				'labels'       => trim($posted_data['labels'], ',') ?? null,
				'created_date' => date('Y-m-d'),
				'status'       => "open",
			];

			$is_client_exists = $this->restapi_clients_model->get_details(['clients_only' => 1,'id' => $posted_data['client_id']])->getResult();
			if (empty($is_client_exists)) {
				$message = app_lang('client_id_invalid');
				return $this->failValidationError($message);
			}
			if (isset($posted_data['labels'])) {
				$lables = explode(',', $posted_data['labels']);
				foreach ($lables as $value) {
					$is_label_exists = $this->restapi_labels_model->get_details(['context' => 'project','label_ids' => $value])->getRow();
					if (empty($is_label_exists)) {
						$message = app_lang('label_is_invalid')." : ".$value;
						return $this->failValidationError($message);
					}
				}
			}
			
			$success = $this->projects_model->ci_save($insert_data);
			if ($success) {
				$response = [
				  'status'   => 200,
				  'messages' => [
					  'success' => app_lang('project_add_success')
				  ]
				  ];
				return $this->respondCreated($response);
			}
			$response = [
			  'messages' => [
				  'success' => app_lang('project_add_fail')
			  ]
			  ];
			return $this->fail($response);
		}
		$response = [
		  'messages' => [
			  'success' => app_lang('project_add_fail')
		  ]
		  ];
		return $this->fail($response);
	}

	/**
	 * @api {put} api/projects/:id Update a Project
	 * @apiVersion 1.0.0
	 * @apiName update
	 * @apiGroup Projects
	 *
	 * @apiHeader {String} Authorization Basic Access Authentication token.
	 *
	 * @apiParam {Number} id project unique ID.
	 *
	 * @apiParam {string} title                         		Mandatory Project Title.
	 * @apiParam {string} client_id		                        Mandatory Project client id.
	 * @apiParam {string} start_date		                    Mandatory Project Start Date.
	 * @apiParam {string} description	                        Optional Project description.
	 * @apiParam {string} deadline	                        	Optional Project deadline.
	 * @apiParam {string} price	                        		Optional Project price.
	 * @apiParam {string} labels	                        	Optional Project labels.
	 *
	 *
	 * @apiParamExample {json} Request-Example:
	 *{
	 *	"title":"api project",
	 *	"client_id":8,
	 *	"description":"description",
	 *	"start_date":"2021-09-27",
	 *	"deadline":"2021-11-27",
	 *	"price":"500",
	 *	"labels":"3,4"
	 *}
	 * @apiSuccess {Boolean} status Request status.
	 * @apiSuccess {String} message Project Update Successful.
	 *
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 *     {
	 *       "status": true,
	 *       "message": "Project Update Successful."
	 *     }
	 *
	 * @apiError {Boolean} status Request status.
	 * @apiError {String} message Project Update Fail.
	 *
	 * @apiErrorExample Error-Response:
	 *     HTTP/1.1 404 Not Found
	 *     {
	 *       "status": false,
	 *       "message": "Project Update Fail."
	 *     }
	 */
	public function update($id = null) {
		$posted_data      = $this->request->getJSON();
		$is_project_exits = $this->projects_model->get_details(['id' => $id])->getRowArray();
		if (!is_numeric($id) || empty($is_project_exits)) {
			$response = [
			  'messages' => [
				  'success' => app_lang('invalid_project_id')
			  ]
			  ];
			return $this->fail($response);
		}

		if (!empty($posted_data)) {
			$rules = [
				'title'      => 'required|if_exist',
				'client_id'  => 'required|numeric|if_exist',
				'start_date' => 'valid_date|if_exist',
				'deadline'   => 'valid_date|if_exist',
				'price'      => 'numeric|if_exist'
			];
			$error = [
				'title' => [
					'required' => app_lang('project_title_required')
				],
				'client_id' => [
					'required' => app_lang('client_id_required'),
					'numeric'  => app_lang('client_id_invalid')
				],
				'start_date' => [
					'valid_date' => app_lang('start_data_invalid')
				],
				'price' => [
					'numeric' => app_lang('price_is_invalid')
				]
			];
			if (!$this->validate($rules, $error)) {
				$response = [
				  'error' => $this->validator->getErrors(),
				  ];
				return $this->fail($response);
			}

			$client_id = $is_project_exits['client_id'];
			if (isset($posted_data->client_id)) {
				$client_id = $posted_data->client_id;
			}
			
			$insert_data = [
				'title'       => $posted_data->title ?? $is_project_exits['title'],
				'client_id'   => $posted_data->client_id ?? $is_project_exits['client_id'],
				'description' => $posted_data->description ?? $is_project_exits['description'] ?? null,
				'start_date'  => $posted_data->start_date ?? $is_project_exits['start_date'] ?? null,
				'deadline'    => $posted_data->deadline ?? $is_project_exits['deadline'] ?? null,
				'price'       => unformat_currency($posted_data->price) ?? $is_project_exits['price'] ?? null,
				'labels'      => trim($posted_data->labels, ',') ?? $is_project_exits['labels'],
			];

			if (isset($posted_data->client_id)) {
				$is_client_exists = $this->restapi_clients_model->get_details(['clients_only' => 1,'id' => $posted_data->client_id])->getResult();
				if (empty($is_client_exists)) {
					$message = app_lang('client_id_invalid');
					return $this->failValidationError($message);
				}
			}
			if (isset($posted_data->labels)) {
				$lables = explode(',', $posted_data->labels);
				foreach ($lables as $value) {
					$is_label_exists = $this->restapi_labels_model->get_details(['context' => 'project','label_ids' => $value])->getRow();
					if (empty($is_label_exists)) {
						$message = app_lang('label_is_invalid')." : ".$value;
						return $this->failValidationError($message);
					}
				}
			}
			
			$success = $this->projects_model->ci_save($insert_data, $id);
			if ($success) {
				$response = [
				  'status'   => 200,
				  'messages' => [
					  'success' => app_lang('project_update_success')
				  ]
				  ];
				return $this->respondCreated($response);
			}
			$response = [
			  'messages' => [
				  'success' => app_lang('project_update_fail')
			  ]
			  ];
			return $this->fail($response);
		}
		$response = [
		  'messages' => [
			  'success' => app_lang('project_update_fail')
		  ]
		  ];
		return $this->fail($response);
	}

	/**
	 * @api {delete} api/projects/:id Delete a Project
	 * @apiVersion 1.0.0
	 * @apiName Delete
	 * @apiGroup Projects
	 *
	 * @apiHeader {String} Authorization Basic Access Authentication token.
	 *
	 * @apiParam {Number} id project unique ID.
	 *
	 * @apiSuccess {String} status Request status.
	 * @apiSuccess {String} message Project Deleted Successfully.
	 *
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 *     {
	 *       "status": true,
	 *       "message": "Project Deleted Successfully."
	 *     }
	 *
	 * @apiError {Boolean} status Request status.
	 * @apiError {String} message Project Delete Fail.
	 *
	 * @apiErrorExample Error-Response:
	 *     HTTP/1.1 404 Not Found
	 *     {
	 *       "status": false,
	 *       "message": "Project Delete Fail."
	 *     }
	 */
	public function delete($id = null) {
		if (!is_numeric($id)) {
			$response = [
			  'messages' => [
				  'success' => app_lang('invalid_project_id')
			  ]
			  ];
			return $this->fail($response);
		}
		
		if ($this->projects_model->get_details(['id' => $id])->getResult()) {
			if ($this->projects_model->delete_project_and_sub_items($id)) {
				$response = [
						'status'   => 200,
						'messages' => [
							'success' => app_lang('project_delete_success')
						]
					];
				return $this->respondDeleted($response);
			}
			$response = [
				  'messages' => [
					  'success' => app_lang('project_delete_fail')
				  ]
				  ];
			return $this->fail($response);
		}
		$response = [
			  'messages' => [
				  'success' => app_lang('project_delete_fail')
			  ]
			  ];
		return $this->fail($response);
	}
}
