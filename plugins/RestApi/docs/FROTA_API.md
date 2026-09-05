# REST API - Frota

Base URL: `/api/frota`

A API usa a mesma autenticação/token do plugin RestApi.

## Descoberta

- `GET /api/frota/endpoints`
- `GET /api/frota/dashboard`

## Veículos

- `GET /api/frota/vehicles`
  - filtros opcionais: `status`, `q`
- `POST /api/frota/vehicles`
- `GET /api/frota/vehicles/{id}`
- `PUT /api/frota/vehicles/{id}`
- `PATCH /api/frota/vehicles/{id}`
- `DELETE /api/frota/vehicles/{id}`
- `GET /api/frota/vehicles/{id}/issues/open`

Exemplo de criação:

```json
{
  "plate": "ABC-1D23",
  "prefix": "VAN-01",
  "make": "FIAT",
  "model": "Ducato",
  "year": "2025",
  "fuel_type": "Diesel",
  "current_odometer": 45870,
  "next_service_odometer": 50000,
  "next_service_date": "2026-12-10",
  "status": "active"
}
```

Ao excluir um veículo, abastecimentos, manutenções, ocorrências e vínculos relacionados também são excluídos.

## Abastecimentos

- `GET /api/frota/fuelings`
  - filtros: `vehicle_id`, `date_from`, `date_to`
- `POST /api/frota/fuelings`
- `GET /api/frota/fuelings/{id}`
- `PUT /api/frota/fuelings/{id}`
- `PATCH /api/frota/fuelings/{id}`
- `DELETE /api/frota/fuelings/{id}`

Exemplo:

```json
{
  "vehicle_id": 3,
  "fueling_at": "2026-09-05 08:30:00",
  "odometer": 45870,
  "liters": 45.75,
  "unit_price": 5.899,
  "station": "Posto Central",
  "notes": "Abastecimento completo"
}
```

Se `total_amount` não for enviado e `liters` + `unit_price` forem informados, o total é calculado automaticamente no servidor.

## Ocorrências

- `GET /api/frota/issues`
  - filtros: `vehicle_id`, `status`, `severity`, `open_only=1`
- `POST /api/frota/issues`
- `GET /api/frota/issues/{id}`
- `PUT /api/frota/issues/{id}`
- `PATCH /api/frota/issues/{id}`
- `DELETE /api/frota/issues/{id}`
- `POST /api/frota/issues/{id}/resolve`
- `POST /api/frota/issues/{id}/photos`

O usuário autenticado na API é gravado automaticamente em `reported_by` quando uma ocorrência é criada.

Exemplo:

```json
{
  "vehicle_id": 3,
  "title": "Ruído na suspensão",
  "description": "Ruído dianteiro ao passar em irregularidades.",
  "severity": "medium",
  "odometer": 45870
}
```

Para fotos, envie `multipart/form-data` usando `photos[]`. Formatos aceitos: JPG, PNG e WEBP, até 10 MB por foto.

Resolver ocorrência:

```json
{
  "resolution": "Bucha da bandeja substituída."
}
```

## Manutenções

- `GET /api/frota/maintenances`
  - filtros: `vehicle_id`, `status`, `type`
- `POST /api/frota/maintenances`
- `GET /api/frota/maintenances/{id}`
- `PUT /api/frota/maintenances/{id}`
- `PATCH /api/frota/maintenances/{id}`
- `DELETE /api/frota/maintenances/{id}`

Exemplo:

```json
{
  "vehicle_id": 3,
  "type": "corrective",
  "description": "Troca da bucha da bandeja dianteira.",
  "supplier": "Oficina Exemplo",
  "odometer": 45870,
  "service_date": "2026-09-05",
  "cost": 480.00,
  "status": "completed",
  "issue_ids": [12, 15],
  "next_service_odometer": 55000,
  "next_service_date": "2027-03-05"
}
```

`issue_ids` vincula uma ou mais ocorrências à manutenção. Quando a manutenção é salva com `status = completed`, as ocorrências vinculadas são automaticamente encerradas.
