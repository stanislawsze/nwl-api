# FiveM Bridge

## Purpose

The FiveM bridge is the trusted integration layer between the Laravel API and the game server runtime.

It exists to:

- receive commands from the API
- execute game-side actions
- report results and events back to the API
- emit structured operational logs

## Trust model

The bridge is trusted to execute only a limited, explicitly allowed set of actions.

The Laravel API remains the source of truth for:

- who requested the action
- whether the action is authorized
- what command was issued
- what result was returned
- how the action is logged

The game module must not become an unsupervised authority for staff decisions.

## Core communication model

### API to FiveM

The API creates a command record and dispatches it asynchronously.

Typical lifecycle:

1. user triggers action from admin panel
2. API validates auth and permissions
3. API persists command request
4. API dispatches queue job
5. bridge receives command
6. bridge executes command
7. bridge sends callback
8. API stores execution result
9. API broadcasts update to panel
10. API writes audit trail

### FiveM to API

The bridge may also send:

- player connect / disconnect events
- live operational events
- inventory or gameplay events if explicitly supported
- moderation completion or failure callbacks

## Required security controls

- per-server credential or secret
- HMAC signing for sensitive payloads
- timestamp validation
- replay protection
- idempotency keys where duplication is possible
- rate limiting
- input validation on every callback

## Command model

Recommended command record fields:

- `id`
- `tenant_id`
- `server_id`
- `type`
- `payload`
- `status`
- `requested_by`
- `correlation_id`
- `created_at`

Recommended execution record fields:

- `id`
- `command_id`
- `status`
- `started_at`
- `finished_at`
- `response_payload`
- `error_message`
- `attempt`
- `correlation_id`

## Command statuses

Recommended statuses:

- `pending`
- `queued`
- `sent`
- `running`
- `succeeded`
- `failed`
- `expired`
- `cancelled`

## Bridge command categories

Examples:

- moderation actions
- player state queries
- screenshot capture
- live server diagnostics
- inventory-related administrative actions
- safe staff utilities

Each category should be explicitly defined and permission-gated.

## Reliability guidance

- avoid synchronous UI-to-game blocking where possible
- use queue workers for dispatch
- keep callbacks idempotent
- record attempts and outcomes
- do not delete operational history needed for support

## Data contract rules

- all payloads must be version-aware
- all payloads must be explicit JSON structures
- no ambiguous or implicit string protocols
- field names should be stable
- optional fields must be documented

## Recommended future documentation

Once the bridge implementation stabilizes, add:

- exact request/response schemas
- signature examples
- retry policy
- timeout policy
- command taxonomy
- error code catalogue
