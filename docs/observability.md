# Observability

## Goal

Make the system supportable in development and operations.

## Categories

### Technical logs

Used for:

- exceptions
- integration failures
- infrastructure diagnostics
- runtime errors

### Audit logs

Used for:

- staff actions
- moderation workflows
- privileged changes
- sensitive domain events

### Metrics / visibility

Pulse and runtime dashboards should help observe:

- request volume
- queue pressure
- failed jobs
- performance hotspots

## Recommended rules

- keep audit logs separate from technical logs
- use structured metadata
- add `correlation_id` to multi-step workflows
- do not log secrets
- do not rely only on raw text logs for business auditing

## Important correlation points

Recommended workflows to correlate:

- panel action -> API request -> queue job -> bridge callback
- moderation action -> command dispatch -> result broadcast
- server event -> persistence -> live notification

## Tools

- Laravel logging
- Spatie Activitylog
- Laravel Pulse
- Telescope in local or tightly controlled environments

## Future improvements

Potential future additions:

- job failure dashboard
- alerting hooks
- event tracing improvements
- tenant-aware operational dashboards
