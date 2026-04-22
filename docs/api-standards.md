# API Standards

## Goal

Provide a stable, predictable API for the admin panel and future integrations.

## General rules

- API routes live under `/api/v1`
- JSON only
- explicit request validation
- explicit response structure
- consistent error format
- no ad hoc response shapes

## Naming conventions

### Routes

Use plural resource naming where relevant.

Examples:

- `GET /api/v1/servers`
- `GET /api/v1/players`
- `POST /api/v1/moderation-actions`

### Controllers

Use descriptive names:

- `ServerController`
- `PlayerController`
- `ModerationActionController`

### Requests

Use intent-based names:

- `StoreServerRequest`
- `UpdatePlayerRequest`
- `CreateModerationActionRequest`

### Resources / DTOs

Use clear entity or operation names:

- `ServerResource`
- `PlayerResource`
- `ModerationActionData`

## Response shape

Recommended success envelope:

```json
{
  "data": {},
  "meta": {},
  "links": {}
}
```

Recommended error envelope:

```json
{
  "message": "Forbidden",
  "code": "authorization_denied",
  "errors": []
}
```

## Pagination

List endpoints should return a consistent paginated structure and not invent custom pagination formats.

## Filtering and sorting

Rules:

- allow only explicit filters
- allow only explicit sorts
- document supported query parameters
- reject unsupported parameters clearly

## Validation

Rules:

- use Form Requests where appropriate
- keep validation out of controllers
- validation messages shown to users should support localization

## Serialization

Rules:

- use Resources or DTOs consistently
- avoid leaking internal columns by default
- avoid returning raw models without transformation

## Versioning

- version from the first public route
- avoid mixing v1 and unversioned public routes
- changes that break existing consumers require a versioning strategy, not silent mutation

## Documentation

Use Scramble-generated docs as the primary API reference and keep route contracts clean enough to generate good documentation automatically.
