# Documentation Index

This directory contains the long-term technical documentation for the NWL API project.

## Start here

- [Onboarding](./onboarding.md)
- [Architecture](./architecture.md)
- [API Standards](./api-standards.md)
- [Security](./security.md)
- [FiveM Bridge](./fivem-bridge.md)
- [Testing Strategy](./testing-strategy.md)
- [Deployment](./deployment.md)
- [Observability](./observability.md)
- [Coding Standards](./coding-standards.md)
- [Roadmap](./roadmap.md)

## Goals

This documentation system exists to make the project:

- easy to onboard to
- explicit in its architectural decisions
- secure by default
- consistent across contributors
- maintainable over time

## Documentation rules

- All documentation must be written in English.
- Docs must describe the current behavior, not intended behavior unless explicitly marked as planned.
- If a change affects architecture, security, contracts, or contributor workflow, update the relevant document in the same pull request.
- Avoid duplicating the same rule in many places. Prefer one source of truth and link to it.

## Suggested ownership

- `architecture.md` — core maintainers
- `api-standards.md` — backend maintainers
- `security.md` — backend maintainers
- `fivem-bridge.md` — backend + game integration maintainers
- `coding-standards.md` — all contributors
- `testing-strategy.md` — backend maintainers
- `deployment.md` — maintainers responsible for infrastructure
- `observability.md` — maintainers responsible for support and operations

## Architecture Decision Records

Use the `docs/adr` folder for important architectural decisions.

Create a new ADR when a change affects:

- security model
- deployment model
- runtime topology
- data isolation rules
- major package adoption
- API versioning strategy
- FiveM bridge protocol or trust model
