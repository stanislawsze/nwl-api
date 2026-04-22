# Contributing

Thank you for contributing.

This repository is intended to stay clean, explicit, and stable over time. Please read this document before opening a pull request.

---

## Language

All repository-facing content must be written in English:

- code comments
- commit messages
- pull request descriptions
- issue titles and descriptions
- docs and ADRs

---

## Branch naming

Use one of the following patterns:

- `feature/<short-description>`
- `fix/<short-description>`
- `hotfix/<short-description>`
- `chore/<short-description>`
- `docs/<short-description>`
- `refactor/<short-description>`
- `test/<short-description>`

Examples:

- `feature/server-token-rotation`
- `fix/duplicate-command-ack`
- `docs/update-readme-installation`

---

## Commit convention

This repository uses Conventional Commits.

Examples:

- `feat(auth): add server token rotation`
- `fix(players): prevent duplicate moderation dispatch`
- `docs(readme): improve onboarding instructions`
- `refactor(game-bridge): split callback validation`
- `test(api): add moderation feature coverage`

---

## Pull request requirements

A pull request should:

- have a clear title
- describe the purpose of the change
- mention migration, cache, queue, or broadcasting impact when relevant
- mention whether the change is breaking
- update tests when behavior changes
- update documentation when developer workflows change

---

## Before opening a pull request

Run:

```bash
./vendor/bin/pint
./vendor/bin/phpstan analyse
./vendor/bin/pest
php artisan optimize:clear
```

Also verify:

- no secrets are committed
- no `dd()` or `dump()` remains in the code
- no dead code was introduced without reason
- localization keys were added for new user-facing messages
- docs were updated when setup or conventions changed

---

## Review standards

Reviewers are expected to check:

- correctness
- readability
- architecture consistency
- security implications
- test coverage
- unnecessary complexity

---

## Documentation rule

If your change modifies one of these, documentation must be updated in the same pull request:

- installation flow
- environment variables
- code standards
- domain workflow behavior
- API contracts
- localization conventions

---

## Questions

When in doubt, follow `CODE_STANDARDS.md` and the project README.
