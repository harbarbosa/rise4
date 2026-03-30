<?php

namespace RestApi\Controllers;

use RestApi\Config\Resources as ResourcesConfig;
use RestApi\Libraries\ResourceRegistry;

class ResourcesController extends Rest_api_Controller
{
    protected ResourceRegistry $resourceRegistry;
    protected ResourcesConfig $resourceConfig;

    public function __construct()
    {
        parent::__construct();
        $this->resourceConfig = new ResourcesConfig();
        $this->resourceRegistry = new ResourceRegistry(db_connect('default'), $this->resourceConfig);
    }

    public function resources()
    {
        return $this->respond([
            'status' => true,
            'data' => array_values($this->resourceRegistry->all())
        ]);
    }

    public function describe(string $resource)
    {
        $resourceInfo = $this->getResourceOrFail($resource);
        if ($resourceInfo instanceof \CodeIgniter\HTTP\ResponseInterface) {
            return $resourceInfo;
        }

        return $this->respond([
            'status' => true,
            'data' => $resourceInfo
        ]);
    }

    public function listResource(string $resource)
    {
        $resourceInfo = $this->getResourceOrFail($resource);
        if ($resourceInfo instanceof \CodeIgniter\HTTP\ResponseInterface) {
            return $resourceInfo;
        }

        $builder = db_connect('default')->table($resourceInfo['table']);
        $filters = $this->request->getGet();
        $columns = $resourceInfo['columns'];

        if ($resourceInfo['has_deleted_flag'] && !$this->toBool($filters['include_deleted'] ?? false)) {
            $builder->where('deleted', 0);
        }

        $search = trim((string) ($filters['q'] ?? ''));
        if ($search !== '') {
            $searchableColumns = array_filter($columns, function ($column) {
                return !in_array($column, ['id', 'deleted'], true);
            });

            if ($searchableColumns) {
                $builder->groupStart();
                foreach ($searchableColumns as $index => $column) {
                    if ($index === 0) {
                        $builder->like($column, $search);
                    } else {
                        $builder->orLike($column, $search);
                    }
                }
                $builder->groupEnd();
            }
        }

        foreach ($filters as $key => $value) {
            if (in_array($key, $this->resourceConfig->reserved_query_parameters, true)) {
                continue;
            }

            if (!in_array($key, $columns, true)) {
                continue;
            }

            if (is_array($value)) {
                $builder->whereIn($key, $value);
                continue;
            }

            $value = trim((string) $value);
            if ($value === '') {
                continue;
            }

            if (str_contains($value, ',')) {
                $builder->whereIn($key, array_map('trim', explode(',', $value)));
            } else {
                $builder->where($key, $value);
            }
        }

        $sort = $filters['sort'] ?? $resourceInfo['primary_key'];
        if (!in_array($sort, $columns, true)) {
            $sort = $resourceInfo['primary_key'];
        }

        $order = strtolower((string) ($filters['order'] ?? 'desc'));
        $order = $order === 'asc' ? 'asc' : 'desc';
        $builder->orderBy($sort, $order);

        $page = max(1, (int) ($filters['page'] ?? 1));
        $limit = (int) ($filters['limit'] ?? $this->resourceConfig->default_limit);
        if ($limit < 1) {
            $limit = $this->resourceConfig->default_limit;
        }
        if ($limit > $this->resourceConfig->max_limit) {
            $limit = $this->resourceConfig->max_limit;
        }
        $offset = ($page - 1) * $limit;

        $selectedFields = $this->filterRequestedFields($filters['fields'] ?? '', $columns);
        if ($selectedFields) {
            $builder->select(implode(',', $selectedFields));
        }

        $countBuilder = clone $builder;
        $total = $countBuilder->countAllResults();
        $rows = $builder->limit($limit, $offset)->get()->getResultArray();

        return $this->respond([
            'status' => true,
            'resource' => $resourceInfo['resource'],
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total
            ],
            'data' => $rows
        ]);
    }

    public function showResource(string $resource, string $id)
    {
        $resourceInfo = $this->getResourceOrFail($resource);
        if ($resourceInfo instanceof \CodeIgniter\HTTP\ResponseInterface) {
            return $resourceInfo;
        }

        $builder = db_connect('default')->table($resourceInfo['table']);
        $builder->where($resourceInfo['primary_key'], $id);

        if ($resourceInfo['has_deleted_flag'] && !$this->toBool($this->request->getGet('include_deleted'))) {
            $builder->where('deleted', 0);
        }

        $row = $builder->get()->getRowArray();
        if (!$row) {
            return $this->failNotFound('Resource item not found.');
        }

        return $this->respond([
            'status' => true,
            'data' => $row
        ]);
    }

    public function createResource(string $resource)
    {
        $resourceInfo = $this->getResourceOrFail($resource);
        if ($resourceInfo instanceof \CodeIgniter\HTTP\ResponseInterface) {
            return $resourceInfo;
        }

        $payload = $this->getPayload();
        $data = $this->sanitizePayload($payload, $resourceInfo['columns'], [$resourceInfo['primary_key']]);

        if (!$data) {
            return $this->failValidationErrors('No valid fields were provided for insert.');
        }

        if ($resourceInfo['has_deleted_flag'] && !array_key_exists('deleted', $data)) {
            $data['deleted'] = 0;
        }

        $builder = db_connect('default')->table($resourceInfo['table']);
        $builder->insert($data);
        $insertId = db_connect('default')->insertID();

        return $this->respondCreated([
            'status' => true,
            'message' => 'Resource created successfully.',
            'id' => $insertId ?: null
        ]);
    }

    public function updateResource(string $resource, string $id)
    {
        $resourceInfo = $this->getResourceOrFail($resource);
        if ($resourceInfo instanceof \CodeIgniter\HTTP\ResponseInterface) {
            return $resourceInfo;
        }

        $payload = $this->getPayload();
        $data = $this->sanitizePayload($payload, $resourceInfo['columns'], [$resourceInfo['primary_key']]);

        if (!$data) {
            return $this->failValidationErrors('No valid fields were provided for update.');
        }

        $builder = db_connect('default')->table($resourceInfo['table']);
        $builder->where($resourceInfo['primary_key'], $id)->update($data);

        return $this->respond([
            'status' => true,
            'message' => 'Resource updated successfully.'
        ]);
    }

    public function deleteResource(string $resource, string $id)
    {
        $resourceInfo = $this->getResourceOrFail($resource);
        if ($resourceInfo instanceof \CodeIgniter\HTTP\ResponseInterface) {
            return $resourceInfo;
        }

        $builder = db_connect('default')->table($resourceInfo['table']);

        if ($resourceInfo['has_deleted_flag']) {
            $builder->where($resourceInfo['primary_key'], $id)->update(['deleted' => 1]);
        } else {
            $builder->where($resourceInfo['primary_key'], $id)->delete();
        }

        return $this->respondDeleted([
            'status' => true,
            'message' => 'Resource deleted successfully.'
        ]);
    }

    protected function getResourceOrFail(string $resource)
    {
        $resource = trim($resource);
        $resourceInfo = $this->resourceRegistry->get($resource);

        if (!$resourceInfo) {
            return $this->failNotFound('Resource not found.');
        }

        return $resourceInfo;
    }

    protected function getPayload(): array
    {
        $json = $this->request->getJSON(true);
        if (is_array($json) && $json) {
            return $json;
        }

        $raw = $this->request->getRawInput();
        if (is_array($raw) && $raw) {
            return $raw;
        }

        $post = $this->request->getPost();
        return is_array($post) ? $post : [];
    }

    protected function sanitizePayload(array $payload, array $columns, array $excludedColumns = []): array
    {
        $allowedColumns = array_diff($columns, $excludedColumns);
        $data = [];

        foreach ($payload as $key => $value) {
            if (!in_array($key, $allowedColumns, true)) {
                continue;
            }

            $data[$key] = $value;
        }

        return $data;
    }

    protected function filterRequestedFields(string $fields, array $columns): array
    {
        if (!$fields) {
            return [];
        }

        $requested = array_map('trim', explode(',', $fields));
        return array_values(array_intersect($requested, $columns));
    }

    protected function toBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'yes'], true);
    }
}
