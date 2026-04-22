# Onboarding

## Goal

This document helps a new developer become productive quickly and safely.

## Prerequisites

Required local tools:

- Docker
- Docker Compose or Docker Desktop
- Git

Optional local tools:

- VS Code
- DBeaver / TablePlus / pgAdmin
- Redis Insight
- Bruno / Postman

## First-time setup

```bash
git clone <repository-url>
cd nwl-api
cp .env.example .env
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

If frontend assets are required inside the app container:

```bash
docker compose exec app npm install
docker compose exec app npm run build
```

## Daily workflow

Start containers:

```bash
docker compose up -d
```

Run commands through the application container:

```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan test
docker compose exec app ./vendor/bin/pint
docker compose exec app ./vendor/bin/phpstan analyse
```

Stop containers:

```bash
docker compose down
```

## Development expectations

Before opening a pull request:

- run formatting
- run static analysis
- run tests
- update docs if behavior changed
- avoid leaving debug helpers such as `dd()` or `dump()`

## Recommended VS Code extensions

- EditorConfig.EditorConfig
- bmewburn.vscode-intelephense-client
- xdebug.php-debug
- open-southeners.laravel-pint
- amiralizadeh9480.laravel-extra-intellisense
- onecentlin.laravel-blade
- ms-azuretools.vscode-docker
- GitHub.vscode-github-actions
- streetsidesoftware.code-spell-checker
- usernamehw.errorlens
- esbenp.prettier-vscode
- dbaeumer.vscode-eslint
- mikestead.dotenv
- yzhang.markdown-all-in-one
- davidanson.vscode-markdownlint
- redhat.vscode-yaml

## Repository conventions

- Use English everywhere in repository-facing text.
- Use conventional commits.
- Keep controllers thin.
- Use policies and permissions explicitly.
- Use DTOs where they improve clarity.
- Log sensitive staff actions.
