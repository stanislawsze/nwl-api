# Security

## Goals

The platform must protect:

- tenant isolation
- staff actions
- authentication tokens
- server credentials
- bridge communication
- moderation records
- audit logs

## Security layers

### Authentication

Primary mechanisms:

- Laravel Sanctum for panel / API authentication
- token-based or secret-based auth for service and bridge flows

Rules:

- never expose raw secrets in logs
- rotate server secrets when compromise is suspected
- scope personal access tokens where relevant

### Authorization

Use:

- Policies for domain-level authorization
- role and permission checks through Spatie Permission
- route middleware for transport-level protection

Rules:

- do not rely on frontend visibility for access control
- every sensitive endpoint must be protected server-side
- authorization must be explicit and testable

### Tenant isolation

All multi-tenant resources must be scoped by tenant context.

Rules:

- every query touching tenant-owned resources must apply tenant scoping
- policies must enforce tenant boundaries
- admin convenience must not bypass isolation by accident

### Input validation

Rules:

- validate every write path
- validate bridge payloads as strictly as panel payloads
- reject unexpected enum values
- reject oversized or malformed payloads early

### Bridge communication

Required controls:

- HMAC signing
- timestamp validation
- nonce or replay protection
- rate limiting
- idempotency for duplicate delivery scenarios

### Logging and secrets

Rules:

- never log raw credentials
- never log secret tokens
- avoid dumping large sensitive payloads into app logs
- use structured audit logs for staff actions
- keep technical logs and audit logs separate

## Threats to account for

- unauthorized panel access
- permission bypass
- cross-tenant data access
- forged game callbacks
- replayed signed requests
- accidental destructive staff actions
- missing audit trails
- overbroad realtime subscriptions

## Minimum controls checklist

- [ ] authenticated routes are protected
- [ ] policies exist for critical models
- [ ] role/permission system is explicit
- [ ] sensitive actions are audit logged
- [ ] bridge requests are signed
- [ ] replay attacks are mitigated
- [ ] rate limiting is configured
- [ ] secrets are not exposed in logs
- [ ] private realtime channels are authorized
- [ ] tenant scoping is test-covered

## Testing expectations

Security-sensitive changes should include tests for:

- unauthorized access
- forbidden-but-authenticated access
- tenant boundary failures
- invalid signatures
- expired timestamps
- duplicate callbacks
- permission regressions
