# Coding Standards

## Language

All repository-facing content must be written in English.

This includes:

- code comments
- commit messages
- PR descriptions
- issue titles
- architecture documents
- API-facing developer docs

## PHP standards

- Prefer clear names over comments.
- Keep controllers thin.
- Keep validation in Form Requests or equivalent request objects.
- Prefer small explicit actions over large multipurpose service classes.
- Use enums or value objects instead of magic strings where practical.
- Keep authorization explicit.
- Avoid hidden side effects.

## Laravel standards

- transport logic in controllers
- business logic in actions
- authorization in policies and permissions
- async work in jobs
- side effects through events/listeners where useful
- reusable response shaping through resources or DTOs

## Documentation standards

- document current behavior
- avoid stale TODO prose in permanent docs
- update docs in the same PR when contracts change

## Review standards

A pull request should be easy to review.

Prefer:

- one concern per PR
- explicit names
- good test coverage for changed behavior
- minimal unrelated formatting churn

## Static analysis and formatting

Run before opening a PR:

```bash
docker compose exec app ./vendor/bin/pint
docker compose exec app ./vendor/bin/phpstan analyse
docker compose exec app ./vendor/bin/pest
```

## Forbidden patterns

Avoid:

- business logic in routes
- business logic hidden in resources
- auth decisions in the frontend only
- broad silent catch blocks
- untyped arrays used as implicit contracts everywhere
- raw secret logging
