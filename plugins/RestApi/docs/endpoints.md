# RestApi plugin - endpoint guide

This document summarizes the HTTP surface exposed by `plugins/RestApi`.
It covers authentication, generic resource endpoints, fixed CRM endpoints, and the module-specific APIs that are wired into `plugins/RestApi/Config/Routes.php`.

## 1. Authentication

All endpoints inherit from `Rest_api_Controller`, which reads the API token from the request header defined in `plugins/RestApi/Config/JWT.php`.

- Header name: `authtoken`
- Algorithm: `HS256`
- Invalid or missing token response:

```json
{
  "status": false,
  "message": "Token not found"
}
```

## 2. Common response patterns

The plugin uses a few repeatable response shapes:

- Success list:

```json
{
  "status": true,
  "data": []
}
```

- Paginated list:

```json
{
  "status": true,
  "resource": "resource_name",
  "pagination": {
    "page": 1,
    "limit": 50,
    "total": 0
  },
  "data": []
}
```

- Created / updated item:

```json
{
  "status": true,
  "message": "Resource saved successfully.",
  "id": 123,
  "data": {}
}
```

- Delete:

```json
{
  "status": true,
  "message": "Resource deleted successfully."
}
```

- Not found / validation errors are returned through CodeIgniter helpers such as `failNotFound()` and `failValidationErrors()`.

## 3. Admin and API management endpoints

These are UI/admin endpoints for managing API users and browsing available resources.

| Method | Route | Notes |
|---|---|---|
| `GET` | `/api_settings` | Admin screen for API users. |
| `POST` | `/restapi/table` | Datatable feed for API users. |
| `POST` | `/restapi/modal/{id?}` | Modal form for create/edit. |
| `POST` | `/restapi/manage` | Create/update API user. |
| `POST` | `/restapi/remove/{id}` | Delete API user. |
| `GET` | `/api/resources` | Lists database resources plus active plugin route catalogs. |
| `GET` | `/api/resources/plugins` | Lists plugin route catalogs only. |
| `GET` | `/api/resources/plugins/{plugin}` | Describes one plugin route catalog. |
| `GET` | `/api/resources/{resource}` | Describes one database resource. |
| `GET` | `/api/{resource}` | Generic list endpoint for any registered resource table. |
| `GET` | `/api/{resource}/{id}` | Generic single-item endpoint. |
| `POST` | `/api/{resource}` | Generic create endpoint. |
| `PUT` | `/api/{resource}/{id}` | Generic update endpoint. |
| `PATCH` | `/api/{resource}/{id}` | Generic update endpoint. |
| `DELETE` | `/api/{resource}/{id}` | Generic delete endpoint. |

## 4. Mobile authentication

These endpoints were added for the mobile app and do not require a preexisting API token.

| Method | Route | Notes |
|---|---|---|
| `POST` | `/api/auth/login` | Authenticates a staff user with email and password, returns a JWT token. |
| `POST` | `/api/auth/logout` | Expires the current token. |

### Login payload

- Required: `email`, `password`

### Login response

```json
{
  "status": true,
  "message": "Login successful.",
  "token_type": "Bearer",
  "token": "jwt-token-string",
  "expires_at": "2026-06-04 18:30:00",
  "user": {
    "id": 1,
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@company.com",
    "phone": "",
    "image": null,
    "user_type": "staff",
    "client_id": 0,
    "job_title": "Technician",
    "role_id": 2,
    "status": "active",
    "is_admin": 0
  }
}
```

### Logout behavior

- Accepts the current token in `authtoken` or `Authorization: Bearer ...`
- Marks the stored token as expired so subsequent API calls are rejected

## 5. Generic resource API

The generic API is backed by `RestApi\Libraries\ResourceRegistry`.
It discovers database tables at runtime, strips the configured prefix, and exposes the table as a resource.

### 4.1 Discovery payload

`GET /api/resources` returns:

```json
{
  "status": true,
  "data": [
    {
      "resource": "clients",
      "table": "clients",
      "module": "Core",
      "primary_key": "id",
      "columns": ["id", "..."],
      "has_deleted_flag": true,
      "route": "/api/clients"
    }
  ],
  "plugins": [
    {
      "plugin": "Fotovoltaico",
      "route_count": 10,
      "routes": []
    }
  ]
}
```

### 4.2 Query parameters for `GET /api/{resource}`

Reserved query params are handled by the controller:

- `page`
- `limit`
- `sort`
- `order`
- `fields`
- `q`
- `include_deleted`

Extra query keys that match table columns are applied as filters.

### 4.3 Generic list response

```json
{
  "status": true,
  "resource": "clients",
  "pagination": {
    "page": 1,
    "limit": 50,
    "total": 10
  },
  "data": []
}
```

### 4.4 Generic create/update payload

The payload is filtered against the table columns. Only real columns are accepted.

- `id` is never accepted as an insert field.
- If the table has `deleted`, new inserts default to `deleted = 0`.
- Selected decimal fields are not auto-normalized in the generic endpoint; they are stored as sent.

### 4.5 Generic delete behavior

- If the table has `deleted`, the endpoint performs a soft delete by setting `deleted = 1`.
- Otherwise it performs a physical delete.

### 4.6 Discovery filter behavior

`GET /api/{resource}` supports:

- `q`: search across non-id, non-deleted columns
- `fields`: comma-separated projection
- `sort`: any valid column, defaults to primary key
- `order`: `asc` or `desc`
- `include_deleted`: `true`, `1`, `yes`, `on`, `sim`

## 6. Fixed CRM endpoints

These endpoints are defined explicitly in `plugins/RestApi/Config/Routes.php`.

### 5.1 Clients

Routes:

- `GET /api/clients`
- `GET /api/clients/{id}`
- `GET /api/clients/search/{key}`
- `POST /api/clients`

Request fields for create:

- Required: `company_name`, `owner_id`
- Optional: `group_ids`, `address`, `city`, `state`, `zip`, `country`, `phone`, `website`, `vat_number`, `disable_online_payment`

Validation notes:

- `company_name` must be alphabetic/spaces
- `owner_id` must be numeric and point to an active user
- `phone` must be numeric if present
- `website` must be a valid URL if present
- `disable_online_payment` must be `0` or `1`

Return:

- List/show/search return client objects with fields from the underlying `Clients_model`
- Create returns:

```json
{
  "status": true,
  "message": "Client add successful."
}
```

### 5.2 Leads

Routes:

- `GET /api/leads`
- `GET /api/leads/{id}`
- `GET /api/leads/search/{key}`
- `POST /api/leads`

Request fields for create:

- Required: `company_name`, `owner_id`, `lead_status_id`, `lead_source_id`
- Optional: `address`, `city`, `state`, `zip`, `country`, `phone`, `website`, `vat_number`

Validation notes:

- `company_name` must be alphabetic/spaces
- `owner_id`, `lead_status_id`, `lead_source_id` must be numeric and valid
- `phone` must be numeric if present
- `website` must be a valid URL if present

Return:

- Responses are lead/client rows plus a derived `lead_source_title`
- Create returns:

```json
{
  "status": true,
  "message": "Lead add successful."
}
```

### 5.3 Projects

Routes:

- `GET /api/projects`
- `GET /api/projects/{id}`
- `GET /api/projects/search/{key}`
- `POST /api/projects`

Request fields for create:

- Required: `title`, `client_id`, `start_date`
- Optional: `description`, `deadline`, `price`, `labels`

Validation notes:

- `client_id` must exist in clients
- `start_date` and `deadline` must be valid dates if sent
- `price` must be numeric if present
- `labels` is validated against project labels

Return:

- List/show/search return project rows with client and label summary fields
- Create returns:

```json
{
  "status": true,
  "message": "Project add successful."
}
```

### 5.4 Tickets

Routes:

- `GET /api/tickets`
- `GET /api/tickets/{id}`
- `GET /api/tickets/search/{key}`
- `POST /api/tickets`

Request fields for create:

- Required: `title`, `client_id`, `requested_by_id`, `ticket_type_id`, `description`, `assigned_to`
- Optional: `ticket_labels`

Validation notes:

- `client_id` must exist
- `requested_by_id` must be a client contact for the given client
- `ticket_type_id` must exist
- `assigned_to` must be a valid staff user
- `ticket_labels` is validated against ticket labels

Return:

- List/show/search return ticket rows with type, client, project, assignment, and labels summary fields
- Create returns:

```json
{
  "status": true,
  "message": "Ticket add successful."
}
```

### 5.5 Invoices

Routes:

- `GET /api/invoices`
- `GET /api/invoices/{id}`
- `GET /api/invoices/search/{key}`
- `POST /api/invoices`

Request fields for create:

- Required: `invoice_bill_date`, `invoice_due_date`, `invoice_client_id`
- Optional: `invoice_project_id`, `tax_id`, `tax_id2`, `tax_id3`, `recurring`, `invoice_note`, `labels`

Recurring-specific fields when `recurring = 1`:

- `repeat_every`
- `repeat_type`
- `no_of_cycles`

Validation notes:

- Dates must be `Y-m-d`
- `invoice_client_id`, project/tax ids must be numeric and valid
- `recurring` must be `0` or `1`

Return:

- List/show/search return invoice rows with client, project, totals, tax percentages, and labels summary fields
- Create returns:

```json
{
  "status": true,
  "message": "Invoice add successful."
}
```

## 7. Miscellaneous utility endpoints

| Method | Route | Return |
|---|---|---|
| `GET` | `/api/client_groups` | Client groups list |
| `GET` | `/api/project_labels` | Project labels list |
| `GET` | `/api/invoice_labels` | Invoice labels list |
| `GET` | `/api/ticket_labels` | Ticket labels list |
| `GET` | `/api/invoice_tax` | Invoice tax list |
| `GET` | `/api/contact_by_clientid/{clientid}` | Contacts for a client |
| `GET` | `/api/ticket_type` | Ticket types list |
| `GET` | `/api/staff_owner` | Staff list |
| `GET` | `/api/project_members` | Project members list |

These endpoints return the underlying model rows directly, or `404` with `No data were found` when empty.

## 8. Team members

Routes:

- `GET /api/team_members`
- `GET /api/team_members/{id}`

Query params:

- `include_inactive`
- `q`
- `sort`
- `order`
- `page`
- `limit`

Response:

```json
{
  "status": true,
  "resource": "team_members",
  "pagination": {
    "page": 1,
    "limit": 50,
    "total": 0
  },
  "data": []
}
```

Returned columns include the core user columns and, when available in schema, role and job info such as:

- `disable_login`
- `created_at`
- `last_online`
- `skype`
- `whatsapp`
- `alternative_phone`
- `role_title`
- `date_of_hire`
- `salary`
- `salary_term`

## 9. ProjectAnalizer API

This module exposes both a route catalog helper and direct CRUD-ish endpoints.

Routes:

- `GET /api/projectanalizer/endpoints`
- `GET,POST /api/projectanalizer/team-activities`
- `GET,POST /api/projectanalizer/timelogs`
- `GET /api/projectanalizer/timelogs/{id}/photos`
- `GET,POST /api/projectanalizer/tasks/{project_id}`
- `GET,POST /api/projectanalizer/tasks/{project_id}/{task_id}`
- `GET /api/projectanalizer/timesheets/{project_id}`
- `GET /api/projectanalizer/timesheets/{project_id}/{id}`
- `POST /api/projectanalizer/timesheets/{project_id}`
- `PUT /api/projectanalizer/timesheets/{project_id}/{id}`
- `PATCH /api/projectanalizer/timesheets/{project_id}/{id}`
- `DELETE /api/projectanalizer/timesheets/{project_id}/{id}`
- `GET,POST /api/projectanalizer/execution-schedules`
- `GET,POST /api/projectanalizer/execution-schedules/{id}`
- `DELETE /api/projectanalizer/execution-schedules/{id}`

Important payload rules:

- Timesheets:
  - required `user_id`
  - you must send either `start_time/end_time` or `hours`
  - if `task_id` is present, `percentage_executed` becomes required
  - returns `pagination`, `summation`, and `data`
- Execution schedules:
  - required `project_id`
  - required `start_date` and `end_date`
  - required `user_ids`
  - `end_date` cannot be earlier than `start_date`
  - completed projects cannot receive new schedules

Response shapes commonly include:

- `status`
- `resource`
- `project_id`
- `task_id`
- `count`
- `data`
- `pagination`

## 10. Proposals API

Routes:

- `GET /api/proposals`
- `POST /api/proposals`
- `GET /api/proposals/{id}`
- `POST /api/proposals/{id}`
- `DELETE /api/proposals/{id}`
- `GET,POST /api/proposals/{proposal_id}/sections`
- `POST /api/proposals/{proposal_id}/sections/save`
- `DELETE /api/proposals/sections/{id}`
- `GET,POST /api/proposals/{proposal_id}/items`
- `POST /api/proposals/{proposal_id}/items/save`
- `DELETE /api/proposals/items/{id}`
- `GET /api/proposals/{proposal_id}/dashboard`
- `GET /api/proposals/{proposal_id}/tasks`
- `GET /api/proposals/{proposal_id}/reminders`
- `POST /api/proposals/{proposal_id}/approve`
- `POST /api/proposals/{proposal_id}/duplicate`
- `GET /api/proposals/products`
- `POST /api/proposals/products`
- `POST /api/proposals/products/{id}`
- `DELETE /api/proposals/products/{id}`
- `GET /api/proposals/settings`
- `POST /api/proposals/settings`

Payload rules:

- Proposal header, section, item, product, and settings saves are table-driven
- `store()` accepts only columns from `proposals_custom`
- Decimal-like fields are normalized by the controller
- Section/item/product save operations only accept columns that exist in the corresponding table

Return patterns:

- Lists: `status`, `resource`, `count`, `data`
- Save: `status`, `message`, `id`, `data`
- Delete: `status`, `message`

## 11. TravelRefunds API

Routes:

- `GET /api/travelrefunds/dashboard`
- `GET,POST /api/travelrefunds/trips`
- `GET,POST /api/travelrefunds/trips/{id}`
- `POST /api/travelrefunds/trips/save`
- `POST /api/travelrefunds/trips/save/{id}`
- `DELETE /api/travelrefunds/trips/{id}`
- `GET,POST /api/travelrefunds/trips/{trip_id}/expenses`
- `GET,POST /api/travelrefunds/trips/{trip_id}/expenses/{id}`
- `POST /api/travelrefunds/trips/{trip_id}/expenses/save`
- `POST /api/travelrefunds/trips/{trip_id}/expenses/save/{id}`
- `DELETE /api/travelrefunds/trips/{trip_id}/expenses/{id}`
- `GET,POST /api/travelrefunds/reimbursements`
- `GET,POST /api/travelrefunds/reimbursements/{id}`
- `POST /api/travelrefunds/reimbursements/save`
- `POST /api/travelrefunds/reimbursements/save/{id}`
- `DELETE /api/travelrefunds/reimbursements/{id}`
- `GET,POST /api/travelrefunds/approvals`
- `GET,POST /api/travelrefunds/approvals/{id}`
- `POST /api/travelrefunds/approvals/trip/approve/{id}`
- `POST /api/travelrefunds/approvals/trip/reject/{id}`
- `POST /api/travelrefunds/approvals/expense/approve/{trip_id}/{expense_id}`
- `POST /api/travelrefunds/approvals/expense/reject/{trip_id}/{expense_id}`
- `GET,POST /api/travelrefunds/categories`
- `GET,POST /api/travelrefunds/categories/{id}`
- `POST /api/travelrefunds/categories/save`
- `POST /api/travelrefunds/categories/save/{id}`
- `DELETE /api/travelrefunds/categories/{id}`
- `GET /api/travelrefunds/settings`
- `POST /api/travelrefunds/settings`
- `GET /api/travelrefunds/reports`
- `GET /api/travelrefunds/reports/export/{type}`
- `GET /api/travelrefunds/reports/export-xlsx/{type}`

Payload rules:

- Trips and reimbursements are table-driven and filtered against their schema
- Decimal fields are normalized
- Integer ids are normalized
- `traveler_ids` may be sent as an array and is stored as JSON
- New trips default to `draft`
- New expenses/reimbursements are created with the filtered payload

Return patterns:

- `dashboard` and `reports` return summary blocks plus filtered rows
- `trips/{id}` returns trip data plus `expenses` and `approvals`
- `approvals/{id}` returns the trip, its expenses, and approval logs

## 12. GED API

Routes:

- `GET,POST /api/ged/documents`
- `GET,POST /api/ged/documents/{id}`
- `POST /api/ged/documents/save`
- `POST /api/ged/documents/save/{id}`
- `DELETE /api/ged/documents/{id}`
- `GET,POST /api/ged/document_types`
- `GET,POST /api/ged/document_types/{id}`
- `POST /api/ged/document_types/save`
- `POST /api/ged/document_types/save/{id}`
- `POST /api/ged/document_types/toggle_status/{id}`
- `DELETE /api/ged/document_types/{id}`
- `GET,POST /api/ged/suppliers`
- `GET,POST /api/ged/suppliers/{id}`
- `POST /api/ged/suppliers/save`
- `POST /api/ged/suppliers/save/{id}`
- `DELETE /api/ged/suppliers/{id}`
- `GET,POST /api/ged/submissions`
- `GET,POST /api/ged/submissions/{id}`
- `POST /api/ged/submissions/save`
- `POST /api/ged/submissions/save/{id}`
- `DELETE /api/ged/submissions/{id}`
- `GET /api/ged/settings`
- `POST /api/ged/settings`
- `GET /api/ged/reports`
- `POST /api/ged/reports`
- `GET,POST /api/ged/notifications/run`

Payload rules:

- Document, type, supplier, and submission saves are table-driven
- `expiration_date` is allowed to be empty and becomes `null`
- Document type status toggle flips `is_active`

## 13. Organizador API

Routes:

- `GET,POST /api/organizador/tasks`
- `GET,POST /api/organizador/tasks/{id}`
- `POST /api/organizador/tasks/save`
- `POST /api/organizador/tasks/save/{id}`
- `DELETE /api/organizador/tasks/{id}`
- `POST /api/organizador/tasks/{id}/duplicate`
- `POST /api/organizador/tasks/{id}/complete`
- `POST /api/organizador/tasks/{id}/favorite`
- `POST /api/organizador/tasks/{id}/status`
- `GET,POST /api/organizador/tasks/{id}/comments`
- `POST /api/organizador/tasks/{id}/comments/save`
- `DELETE /api/organizador/tasks/comments/{id}`
- `GET,POST /api/organizador/tasks/{id}/reminders`
- `POST /api/organizador/tasks/{id}/reminders/save`
- `POST /api/organizador/tasks/reminders/status/{id}`
- `DELETE /api/organizador/tasks/reminders/{id}`
- `GET /api/organizador/kanban`
- `GET /api/organizador/kanban_data`
- `GET /api/organizador/calendar`
- `GET /api/organizador/calendar_data`
- `GET,POST /api/organizador/categories`
- `GET,POST /api/organizador/categories/{id}`
- `POST /api/organizador/categories/save`
- `POST /api/organizador/categories/save/{id}`
- `DELETE /api/organizador/categories/{id}`
- `GET,POST /api/organizador/tags`
- `GET,POST /api/organizador/tags/{id}`
- `POST /api/organizador/tags/save`
- `POST /api/organizador/tags/save/{id}`
- `DELETE /api/organizador/tags/{id}`
- `GET,POST /api/organizador/phases`
- `GET,POST /api/organizador/phases/{id}`
- `POST /api/organizador/phases/save`
- `POST /api/organizador/phases/save/{id}`
- `DELETE /api/organizador/phases/{id}`
- `GET /api/organizador/settings`
- `POST /api/organizador/settings`
- `GET,POST /api/organizador/dashboard`

Payload rules:

- Task saves are table-driven against `my_tasks`
- `title` is required, but the controller also accepts `task_title` or `name` as aliases
- Labels are normalized
- New tasks default to `pending` and `medium`
- `position` is auto-assigned when omitted

## 14. ContaAzul integration API

Routes:

- `GET /api/contaazul/endpoints`
- `GET /api/contaazul/query/{key}`

`GET /api/contaazul/endpoints` returns the catalog of supported remote queries, with:

- `key`
- `label`
- `path`
- `path_params`
- `query_params`
- `docs_url`
- `route`

`GET /api/contaazul/query/{key}` executes the remote Conta Azul request and returns:

```json
{
  "status": true,
  "endpoint": "products_list",
  "request": {
    "path": "/v1/produtos",
    "path_params": {},
    "query": {}
  },
  "supported_query_params": [],
  "data": {}
}
```

On error, the response may also include:

- `message`
- `http_status`
- `raw_body`

If the selected endpoint declares path parameters, they must be supplied as query-string values to the proxy route.

## 15. Practical notes

- The generic `/api/{resource}` surface is the most flexible endpoint family.
- The specific controllers (`clients`, `leads`, `projects`, `tickets`, `invoices`) have explicit validation and are safer when you need stable business rules.
- The module-specific controllers are mostly table-driven. Their exact accepted fields come from the table schema and the controller payload filters.
- For any endpoint that returns `No data were found`, the controller is intentionally using a `404` path rather than returning an empty array.
