# Testing Strategy

## Goal

Protect the project against regressions in behavior, security, and integration contracts.

## Test layers

### Unit tests

Focus on:

- pure services
- value objects
- enums
- signature validation
- mappers
- small domain helpers

### Feature tests

Focus on:

- authentication flows
- policies and permissions
- API endpoints
- validation behavior
- moderation workflows
- audit logging
- tenant isolation

### Integration tests

Focus on:

- Redis-backed queue flows
- broadcasting
- bridge callbacks
- command lifecycle
- package integration smoke tests

## What must be tested

At minimum, critical features should cover:

- authorized success
- unauthenticated failure
- authenticated but forbidden failure
- invalid input
- expected persistence changes
- expected audit side effects
- expected async dispatches where relevant

## FiveM bridge testing

The bridge contract should be tested for:

- valid signature accepted
- invalid signature rejected
- expired timestamp rejected
- duplicate callback not double-processed
- command status transitions

## Quality gate

Before opening a pull request, run:

```bash
docker compose exec app ./vendor/bin/pint
docker compose exec app ./vendor/bin/phpstan analyse
docker compose exec app ./vendor/bin/pest
```

## Test philosophy

Test business behavior, not framework trivia.

Prefer:

- expressive feature tests for externally visible behavior
- unit tests for security or pure logic
- integration coverage where external components matter
