# NWL API

English-first Laravel API for a SaaS platform that administrates FiveM servers.

This repository is the backend core of the platform. It is responsible for:

- tenant and server management
- staff users, roles, and permissions
- moderation workflows
- audit logging and business logs
- secure communication with the FiveM game module
- real-time updates for the React admin panel
- API documentation
- testing and code quality
- localization support

---

## Table of contents

- [1. Project goals](#1-project-goals)
- [2. Technical stack](#2-technical-stack)
- [3. Core architecture principles](#3-core-architecture-principles)
- [4. Prerequisites](#4-prerequisites)
- [5. First-time installation](#5-first-time-installation)
- [6. Detailed setup](#6-detailed-setup)
- [7. Environment configuration](#7-environment-configuration)
- [8. Localization](#8-localization)
- [9. Running the project locally](#9-running-the-project-locally)
- [10. Project structure](#10-project-structure)
- [11. Required packages](#11-required-packages)
- [12. Code quality](#12-code-quality)
- [13. Testing](#13-testing)
- [14. API documentation](#14-api-documentation)
- [15. Real-time features](#15-real-time-features)
- [16. Logs and auditing](#16-logs-and-auditing)
- [17. Git workflow](#17-git-workflow)
- [18. VS Code setup](#18-vs-code-setup)
- [19. New developer onboarding](#19-new-developer-onboarding)
- [20. Useful commands](#20-useful-commands)
- [21. Before opening a pull request](#21-before-opening-a-pull-request)

---

## 1. Project goals

This project is built as a long-term, production-grade API.

Main goals:

- clean and explicit architecture
- strong authorization model
- internationalized codebase and UI-facing messages
- auditable staff actions
- reliable server-to-game communication
- scalable caching and queueing
- strong local developer experience
- stable versioning and release process

---

## 2. Technical stack

### Backend

- PHP 8.4+
- Laravel 13
- PostgreSQL
- Redis
- Laravel Sanctum
- Laravel Reverb
- Laravel Pulse
- Laravel Pennant
- Laravel Telescope (local / controlled environments)
- Pest
- Laravel Pint
- Larastan
- Scramble

### Domain and API support

- spatie/laravel-permission
- spatie/laravel-activitylog
- spatie/laravel-data
- spatie/laravel-query-builder

### Related frontend

- React + TypeScript admin panel in a separate repository or package

### Language policy

- **All repository-facing content must be written in English**.
- This includes code comments, commit messages, docs, PR descriptions, ADRs, issue titles, and API-facing developer documentation.

Laravel provides official support for API setup, broadcasting / Reverb, Pulse, Pennant, and Telescope. Scramble automatically exposes `/docs/api` and `/docs/api.json` after installation. citeturn583322search0turn583322search1turn583322search2turn583322search6

---

## 3. Core architecture principles

This project follows these principles:

- API-first design
- explicit API versioning under `/api/v1`
- thin controllers
- business logic in dedicated actions / services
- authorization through Policies + Permissions
- audit logs separated from technical logs
- DTO-based boundaries for request / response shaping
- domain events and queued jobs for decoupling
- localization from day one
- tests for all critical business workflows

### Recommended high-level flow

- React panel -> Laravel API
- Laravel API -> FiveM module
- FiveM module -> Laravel API callbacks / logs
- Laravel API -> React panel via Reverb broadcasting

---

## 4. Prerequisites

Install locally:

- PHP 8.4+
- Composer
- PostgreSQL
- Redis
- Node.js LTS
- npm
- Git

Recommended tools:

- VS Code
- TablePlus / DBeaver / pgAdmin
- Redis Insight
- Bruno or Postman

---

## 5. First-time installation

```bash
composer create-project laravel/laravel nwl-api
cd nwl-api

php artisan install:api
php artisan install:broadcasting

composer require laravel/pulse
composer require laravel/pennant
composer require dedoc/scramble
composer require spatie/laravel-permission
composer require spatie/laravel-activitylog
composer require spatie/laravel-data
composer require spatie/laravel-query-builder

composer require laravel/telescope --dev
composer require --dev larastan/larastan

composer remove phpunit/phpunit
composer require pestphp/pest --dev --with-all-dependencies
./vendor/bin/pest --init

php artisan vendor:publish --provider="Laravel\Pulse\PulseServiceProvider"
php artisan vendor:publish --provider="Laravel\Pennant\PennantServiceProvider"
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan vendor:publish --provider="Spatie\LaravelData\LaravelDataServiceProvider" --tag="data-config"
php artisan vendor:publish --provider="Dedoc\Scramble\ScrambleServiceProvider" --tag="scramble-config"

php artisan telescope:install

cp .env.example .env
php artisan key:generate

npm install
```

Then configure PostgreSQL and Redis in `.env`, and run:

```bash
php artisan migrate
npm run build
```

---

## 6. Detailed setup

### 6.1 Create the project

```bash
composer create-project laravel/laravel nwl-api
cd nwl-api
```

### 6.2 Install the official API stack

```bash
php artisan install:api
```

This sets up API-related scaffolding and Sanctum support. citeturn583322search12turn583322search14

### 6.3 Install broadcasting and Reverb

```bash
php artisan install:broadcasting
```

Laravel documents that `install:broadcasting` installs broadcasting support and Reverb with sensible defaults. citeturn583322search0turn583322search2

### 6.4 Install core packages

```bash
composer require laravel/pulse
composer require laravel/pennant
composer require dedoc/scramble
composer require spatie/laravel-permission
composer require spatie/laravel-activitylog
composer require spatie/laravel-data
composer require spatie/laravel-query-builder
```

### 6.5 Install development packages

```bash
composer require laravel/telescope --dev
composer require --dev larastan/larastan
```

### 6.6 Replace PHPUnit with Pest

```bash
composer remove phpunit/phpunit
composer require pestphp/pest --dev --with-all-dependencies
./vendor/bin/pest --init
```

Laravel 13 upgrade guidance explicitly references Pest 3 support, and Pest provides its own initialization step. citeturn583322search4

### 6.7 Publish package configuration

```bash
php artisan vendor:publish --provider="Laravel\Pulse\PulseServiceProvider"
php artisan vendor:publish --provider="Laravel\Pennant\PennantServiceProvider"
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan vendor:publish --provider="Spatie\LaravelData\LaravelDataServiceProvider" --tag="data-config"
php artisan vendor:publish --provider="Dedoc\Scramble\ScrambleServiceProvider" --tag="scramble-config"
```

### 6.8 Install Telescope

```bash
php artisan telescope:install
```

### 6.9 Frontend assets used by Laravel tooling

```bash
npm install
npm run build
```

---

## 7. Environment configuration

### 7.1 Create `.env`

```bash
cp .env.example .env
php artisan key:generate
```

### 7.2 Example local `.env`

```env
APP_NAME="NWL API"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US
APP_TIMEZONE=Europe/Paris

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=debug

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=nwl_api
DB_USERNAME=postgres
DB_PASSWORD=postgres

SESSION_DRIVER=database
SESSION_LIFETIME=120

CACHE_STORE=redis
QUEUE_CONNECTION=redis

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

BROADCAST_CONNECTION=reverb

REVERB_APP_ID=local-app-id
REVERB_APP_KEY=local-app-key
REVERB_APP_SECRET=local-app-secret
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http

PULSE_ENABLED=true
```

### 7.3 Create the PostgreSQL database

```sql
CREATE DATABASE nwl_api;
```

Then run:

```bash
php artisan migrate
```

---

## 8. Localization

Localization is a first-class requirement.

### Repository rules

- English is the default language for all source code and internal documentation.
- Translation keys must be stable and explicit.
- Do not hardcode user-facing strings inside controllers, services, policies, notifications, or frontend components.
- Prefer translation keys for:
    - validation messages
    - API error messages intended for frontend display
    - notification titles and bodies
    - admin panel labels
    - moderation reasons shown to users

### Laravel localization conventions

Recommended structure:

```text
lang/
├── en/
│   ├── auth.php
│   ├── validation.php
│   ├── permissions.php
│   ├── moderation.php
│   ├── servers.php
│   └── common.php
└── fr/
    ├── auth.php
    ├── validation.php
    ├── permissions.php
    ├── moderation.php
    ├── servers.php
    └── common.php
```

### Translation key style

Use dot notation:

- `common.actions.save`
- `moderation.ban.created`
- `permissions.players.kick`
- `servers.status.online`

### Rules

- keys are lowercase
- segments are short and semantic
- never use English sentences as translation keys
- keep backend and frontend key naming aligned where possible

---

## 9. Running the project locally

The project usually requires 4 terminals.

### Terminal 1 — API server

```bash
php artisan serve
```

### Terminal 2 — queue worker

```bash
php artisan queue:listen
```

### Terminal 3 — Reverb

```bash
php artisan reverb:start
```

### Terminal 4 — Vite

```bash
npm run dev
```

---

## 10. Project structure

```text
app/
├── Domain/
│   ├── Auth/
│   ├── AuditLogs/
│   ├── GameBridge/
│   ├── Moderation/
│   ├── Players/
│   ├── RolesPermissions/
│   ├── Servers/
│   ├── Tenancy/
│   └── Shared/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       └── V1/
│   ├── Middleware/
│   ├── Requests/
│   └── Resources/
├── Models/
├── Policies/
├── Providers/
└── Support/
```

### Structure rules

- `Controllers` orchestrate HTTP only
- `Requests` validate input
- `Resources` shape API output
- `Domain/*/Actions` hold application use cases
- `Domain/*/Data` hold DTOs
- `Policies` hold authorization logic
- `Jobs` handle async work
- `Events` and `Listeners` decouple side effects
- `Support` contains framework-agnostic helpers or infra helpers

---

## 11. Required packages

### Sanctum

Used for:

- SPA authentication
- personal access tokens
- service-to-service token flows where appropriate

### Reverb

Used for:

- real-time updates
- staff presence channels
- moderation action status updates
- live log streams

### Pulse

Used for:

- application observability
- workload and performance visibility

### Pennant

Used for:

- feature flags
- staged rollout of features

### Telescope

Used for:

- local request and exception inspection
- queue, cache, query, and notification visibility

### Scramble

Used for:

- automatic OpenAPI generation
- `/docs/api`
- `/docs/api.json`

Scramble documents request parameters from validation rules and stores JSON resources as reusable schemas in the OpenAPI document. citeturn583322search1turn583322search9turn583322search13turn583322search19

### Spatie Laravel Permission

Used for:

- roles
- permissions
- integration with Laravel authorization checks

### Spatie Activitylog

Used for:

- business audit trail
- sensitive action history

### Spatie Laravel Data

Used for:

- DTOs
- explicit request/response boundaries
- strong typing and transformation

---

## 12. Code quality

### Formatting

```bash
./vendor/bin/pint
```

### Static analysis

```bash
./vendor/bin/phpstan analyse
```

### Rules

- keep controllers thin
- avoid writing business logic directly in routes or controllers
- prefer named actions over large service classes
- make authorization explicit
- test critical paths
- keep API responses consistent
- prefer enums and DTOs over stringly-typed code

---

## 13. Testing

### Run all tests

```bash
./vendor/bin/pest
```

or

```bash
php artisan test
```

### Test coverage

```bash
php artisan test --coverage
```

### Parallel execution

```bash
php artisan test --parallel
```

### Expected test layers

#### Unit

- pure services
- value objects
- signature / HMAC validation
- DTO mapping
- feature flag evaluation helpers

#### Feature

- API endpoints
- auth and token flows
- policies and permissions
- moderation flows
- audit logging
- cache invalidation

#### Integration

- Redis queueing
- broadcasting
- FiveM callback flows
- docs generation smoke tests

---

## 14. API documentation

After installing Scramble, the documentation is available at:

- `/docs/api`
- `/docs/api.json`

Scramble registers those routes by default and allows customizing them if needed. citeturn583322search1turn583322search9turn583322search11

### Documentation rules

- all public API routes must live under `/api/v1`
- every endpoint must use clear request validation
- every endpoint must return consistent resource shapes
- avoid undocumented ad-hoc response formats

---

## 15. Real-time features

### Start Reverb

```bash
php artisan reverb:start
```

### Expected use cases

- player connected / disconnected events
- moderation action progress
- game command completion
- live staff notifications
- live server events

---

## 16. Logs and auditing

### Technical logs

Use Laravel logging for:

- exceptions
- infrastructure errors
- local debug visibility
- integration diagnostics

### Business audit logs

Use `spatie/laravel-activitylog` for:

- who performed an action
- on which target
- under which tenant / server
- at what time
- with which metadata

### Best practices

- never mix technical logs with business audit logs
- add `correlation_id` to important multi-step workflows
- log all staff-sensitive actions
- do not log secrets or raw credentials

---

## 17. Git workflow

### Branches

Recommended:

- `main`
- `develop`
- `feature/...`
- `fix/...`
- `hotfix/...`

### Conventional commits

Examples:

```text
feat(auth): add server token rotation
fix(players): prevent duplicate moderation dispatch
docs(readme): improve local setup section
refactor(game-bridge): extract signature validator
test(api): add player moderation feature tests
```

### Versioning

This project follows Semantic Versioning.

### Changelog

This project maintains a `CHANGELOG.md` using Keep a Changelog structure.

---

## 18. VS Code setup

Recommended extensions are listed in `.vscode/extensions.json`.

Main ones:

- PHP Intelephense
- Laravel Extension Pack or targeted Laravel extensions
- EditorConfig
- ESLint
- Prettier
- DotENV
- Error Lens
- GitLens
- Markdownlint
- YAML
- Docker
- PostgreSQL client extension if desired

See the dedicated VS Code section in `docs/onboarding.md`.

---

## 19. New developer onboarding

### Steps

1. clone the repository
2. install Composer dependencies
3. install npm dependencies
4. create `.env`
5. configure PostgreSQL and Redis
6. generate the app key
7. run migrations
8. start local services
9. verify `/docs/api`
10. run the full test suite

### Commands

```bash
git clone <repository-url>
cd nwl-api

composer install
npm install

cp .env.example .env
php artisan key:generate
php artisan migrate
npm run build

php artisan serve
php artisan queue:listen
php artisan reverb:start
```

### Minimum validation checklist

- API responds correctly
- migrations pass
- Redis connection works
- Scramble docs are accessible
- tests pass
- Reverb starts without errors

---

## 20. Useful commands

### Code generation

```bash
php artisan make:model Server -mfs
php artisan make:model Player -mfs
php artisan make:model ModerationAction -mfs

php artisan make:controller Api/V1/ServerController --api
php artisan make:controller Api/V1/PlayerController --api
php artisan make:controller Api/V1/ModerationController --api

php artisan make:policy ServerPolicy --model=Server
php artisan make:policy PlayerPolicy --model=Player
php artisan make:policy ModerationActionPolicy --model=ModerationAction

php artisan make:job DispatchGameCommand
php artisan make:event PlayerConnected
php artisan make:listener BroadcastPlayerConnected

php artisan make:test Feature/Api/Players/ListPlayersTest
php artisan make:test Unit/GameBridge/SignatureServiceTest
```

### Maintenance

```bash
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

### Quality

```bash
./vendor/bin/pint
./vendor/bin/phpstan analyse
./vendor/bin/pest
```

---

## 21. Before opening a pull request

Run:

```bash
./vendor/bin/pint
./vendor/bin/phpstan analyse
./vendor/bin/pest
php artisan optimize:clear
```

Check also:

- no secrets committed
- no `dd()` or `dump()` left behind
- no dead code added without reason
- docs updated if behavior changed
- translations updated when user-facing text changed
- tests added or updated for the changed behavior

---

## Required manual code changes after installation

### Add `HasRoles` to `User`

`app/Models/User.php`

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles;
}
```

Spatie documents adding the `HasRoles` trait to the `User` model. citeturn583322search4

### Register Telescope only in local environments

Depending on your Laravel version and provider registration approach, keep Telescope restricted to local or tightly controlled environments. Laravel documents Telescope primarily as a local development companion. citeturn583322search6

---

## License

Internal / proprietary
