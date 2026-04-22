# Coding Standards

This document centralizes the coding, architecture, naming, documentation, localization, testing, and review standards for the project.

All contributors must follow these standards.

---

## 1. Language policy

- English is mandatory for all repository content.
- This includes code comments, commit messages, documentation, issues, pull requests, ADRs, and inline developer-facing notes.
- Translation files may of course contain non-English user-facing text.

---

## 2. General philosophy

- Prefer clarity over cleverness.
- Prefer explicitness over magic.
- Prefer consistency over personal style.
- Prefer small focused classes over multi-purpose classes.
- Prefer composition over giant inheritance trees.
- Prefer domain naming over framework naming when possible.

---

## 3. Laravel application architecture

### 3.1 Controllers

Controllers must remain thin.

Controllers may:

- receive requests
- call policies / authorization helpers
- delegate to actions or services
- return API resources or structured JSON responses

Controllers must not:

- contain large business workflows
- contain large query-building logic
- perform multi-step side effects directly
- become a dumping ground for cross-domain logic

### 3.2 Actions and services

Use dedicated actions for explicit use cases.

Examples:

- `CreateModerationAction`
- `DispatchGameCommand`
- `RotateServerToken`
- `AssignRoleToUser`

Rules:

- one action = one clear business purpose
- action names must be verbs or verb phrases
- avoid giant generic services such as `ServerService` containing 30 unrelated methods

### 3.3 DTOs

Use DTOs at boundaries.

Recommended use cases:

- validated input mapping
- external payload normalization
- API response transformation
- data passed between layers when arrays become ambiguous

Do not pass large associative arrays across the application when a DTO would clarify intent.

### 3.4 Policies and permissions

Authorization must be explicit.

Rules:

- use Policies for model or domain-specific authorization
- use permissions for coarse-grained capabilities
- do not hardcode authorization assumptions in controllers without using Laravel authorization facilities
- do not rely only on frontend checks

---

## 4. Naming conventions

### 4.1 Classes

Use PascalCase.

Examples:

- `ServerPolicy`
- `ModerationActionResource`
- `CreateBanAction`
- `VerifyGameSignature`

### 4.2 Methods and variables

Use camelCase.

Examples:

- `dispatchCommand()`
- `currentServer`
- `moderationReason`

### 4.3 Database tables

Use snake_case plural names.

Examples:

- `servers`
- `players`
- `moderation_actions`
- `command_executions`

### 4.4 Columns

Use snake_case.

Examples:

- `tenant_id`
- `server_id`
- `performed_by`
- `correlation_id`

### 4.5 Route names

Use dot notation.

Examples:

- `api.v1.players.index`
- `api.v1.players.show`
- `api.v1.moderation.store`

### 4.6 Translation keys

Use lowercase dot notation.

Examples:

- `common.actions.save`
- `moderation.ban.created`
- `servers.status.online`

---

## 5. File and folder conventions

Recommended domain structure:

```text
app/Domain/<DomainName>/
├── Actions/
├── Data/
├── Enums/
├── Events/
├── Exceptions/
├── Jobs/
├── Listeners/
├── Policies/
├── Queries/
└── Services/
```

Rules:

- organize by business domain first
- avoid dumping all logic under `Services`
- extract technical helpers into `Support` only when they are truly cross-domain

---

## 6. Comments and documentation in code

### 6.1 Allowed comments

Comments should explain:

- business invariants
- non-obvious technical constraints
- integration quirks
- security considerations
- why something exists when the intent is not obvious from code

### 6.2 Avoid comments that only restate code

Bad:

```php
// Increment the counter
$counter++;
```

Good:

```php
// The FiveM callback may arrive more than once; this flag prevents duplicate processing.
```

### 6.3 Docblocks

Use docblocks when they add real value:

- generic templates
- array shapes when unavoidable
- integration notes
- public API contracts when type information alone is insufficient

Do not write decorative docblocks on every method if native types already express intent.

---

## 7. API design standards

- all public routes must be versioned under `/api/v1`
- response structures must be consistent
- validation must happen before business logic
- prefer API Resources or DTO-backed transformation
- use pagination for large lists
- avoid surprise response structures across endpoints

### Error format

Use a predictable format:

```json
{
    "message": "Forbidden",
    "code": "authorization_denied",
    "errors": []
}
```

---

## 8. Logging and auditing standards

### 8.1 Technical logs

Use Laravel logging for:

- exceptions
- infrastructure failures
- unexpected integration failures
- debug output in local development

### 8.2 Audit logs

Use business audit logs for:

- staff actions
- moderation decisions
- role changes
- sensitive server actions
- token rotation

### 8.3 Logging rules

- never log secrets
- never log raw credentials
- avoid logging personal data unnecessarily
- include correlation IDs for important workflows
- distinguish `source` clearly: `panel`, `api`, `fivem-agent`, `system`

---

## 9. Localization standards

- do not hardcode user-facing strings in business logic
- use translation keys for messages that may be displayed by the frontend
- keep English as the source language for keys and base copy
- keep translation files grouped by domain
- never use full English sentences as translation keys

---

## 10. Testing standards

Every non-trivial feature must be tested.

### 10.1 Unit tests

Use unit tests for:

- pure domain services
- value objects
- parsers and validators
- signature verification
- simple transformations

### 10.2 Feature tests

Use feature tests for:

- API endpoints
- auth flows
- policies
- permissions
- moderation flows
- audit trail creation
- caching behavior

### 10.3 Integration tests

Use integration tests for:

- Redis-backed workflows
- queue processing
- broadcasting
- FiveM callback contracts

### 10.4 Test naming

Prefer descriptive names.

Examples:

- `it_creates_a_ban_and_records_an_audit_entry`
- `it_rejects_unsigned_game_callbacks`
- `it_allows_server_admins_to_view_their_own_server_players`

---

## 11. Database standards

- migrations must be reversible when possible
- use foreign keys where appropriate
- index frequently queried columns
- use explicit column names
- avoid overusing nullable columns without a clear domain reason
- prefer enums or constrained values for known states

---

## 12. Caching standards

- cache only data that benefits from caching
- do not cache critical mutable state blindly
- use stable, namespaced keys
- invalidate on explicit business events when needed

Example key pattern:

```text
tenant:{tenantId}:server:{serverId}:players:online
```

---

## 13. Security standards

- all sensitive routes must be authenticated and authorized
- game-agent communications must be signed or strongly authenticated
- protect against replay where relevant
- rotate secrets when feasible
- validate all external payloads
- do not trust frontend permissions

---

## 14. Git and review standards

### 14.1 Commit messages

Use Conventional Commits.

Examples:

- `feat(auth): add server token rotation`
- `fix(game-bridge): reject stale callback timestamps`
- `docs(coding-standards): add localization rules`

### 14.2 Pull requests

A pull request must:

- describe the purpose clearly
- mention risks or migration impacts
- include test updates if behavior changed
- include documentation updates if needed

### 14.3 Review expectations

Review for:

- correctness
- readability
- architecture consistency
- security
- test coverage
- unnecessary complexity

---

## 15. Frontend-facing conventions

Even though this repository is backend-first, backend contributors must preserve frontend consistency.

Rules:

- do not break response contracts casually
- use stable enum values
- prefer additive changes over breaking ones
- document breaking changes clearly in the changelog

---

## 16. Formatting and tooling

Mandatory checks before opening a pull request:

```bash
./vendor/bin/pint
./vendor/bin/phpstan analyse
./vendor/bin/pest
```

---

## 17. Final rule

When unsure, choose the most explicit, testable, and maintainable solution.

## Static Analysis

The project uses PHPStan with Larastan.

Rules:

- Shared configuration lives in `phpstan.neon.dist`
- A baseline may exist in `phpstan-baseline.neon`
- Do not add broad `ignoreErrors` patterns without justification
- Prefer fixing the code over weakening the analysis
- New code should not introduce new baseline entries

Run locally:

- `composer analyse`
- `composer analyse:baseline`

## Code Style

The project uses Laravel Pint.

Rules:

- Shared formatting config lives in `pint.json`
- Formatting must be consistent before opening a pull request
- CI validates formatting using `composer lint`

Run locally:

- `composer format`
- `composer lint`

## Before Opening a Pull Request

Run:

- `composer lint`
- `composer analyse`
- `composer test`

Or simply:

- `composer quality`
