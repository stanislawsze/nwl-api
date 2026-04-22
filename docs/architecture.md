# Architecture Overview

## Main components

### Laravel API
Responsible for:
- authentication
- authorization
- tenant and server management
- moderation workflows
- audit logs
- game command orchestration
- real-time events to the panel

### FiveM module
Responsible for:
- authenticating against the API
- receiving or pulling commands
- executing game-side actions
- sending callbacks, status, and logs

### React admin panel
Responsible for:
- staff workflows
- live dashboards
- player and server management
- moderation UI
- audit review

---

## Communication flow

- React panel -> Laravel API
- Laravel API -> FiveM module
- FiveM module -> Laravel API callback endpoints
- Laravel API -> React panel via broadcasting

---

## Main architectural principles

- versioned API
- explicit authorization
- event-driven side effects
- auditable sensitive actions
- isolated domain logic
- localization support from day one
