# Deployment

## Goal

Define a production-minded deployment model even before the final infrastructure is chosen.

## Baseline assumptions

The application runtime is containerized.

Recommended runtime components:

- application container
- nginx or equivalent reverse proxy
- PostgreSQL
- Redis
- queue worker
- Reverb process

## Environment parity

Development, staging, and production should stay structurally close.

Principles:

- same main services
- same queue backend
- same database engine family
- same Redis usage pattern
- explicit environment variables

## Required runtime processes

### HTTP application

Handles:

- API requests
- auth
- validation
- persistence
- dispatch

### Queue worker

Handles:

- command dispatch
- async side effects
- heavy background work

### Reverb process

Handles:

- realtime broadcasting
- channel subscriptions

## Release expectations

A release should include:

- code deployment
- dependency installation
- config cache refresh if used
- migration execution
- queue/reverb restart strategy
- health verification

## Minimum staging checklist

- database connectivity works
- Redis connectivity works
- queue worker is processing jobs
- Reverb starts correctly
- `/docs/api` is accessible if intended
- health endpoint works if present
- logs are being written correctly

## Future enhancements

Possible future additions:

- image-based deployments
- blue/green or rolling deployment
- secret manager integration
- backup automation
- release automation through CI
