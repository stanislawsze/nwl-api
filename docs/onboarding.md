# Developer Onboarding

## Goal

This document helps a new developer become productive quickly on the project.

---

## 1. Required local services

You need:

- PHP 8.4+
- Composer
- PostgreSQL
- Redis
- Node.js LTS
- npm
- Git
- VS Code

---

## 2. First local setup

```bash
git clone <repository-url>
cd nwl-api

composer install
npm install

cp .env.example .env
php artisan key:generate
php artisan migrate
npm run build
```

---

## 3. Start local services

### API

```bash
php artisan serve
```

### Queue worker

```bash
php artisan queue:listen
```

### Reverb

```bash
php artisan reverb:start
```

### Vite

```bash
npm run dev
```

---

## 4. Sanity checks

Verify:

- API responds
- database connection works
- Redis connection works
- `/docs/api` is accessible
- tests pass

---

## 5. VS Code extensions

Recommended extensions:

### PHP / Laravel

- PHP Intelephense
- Laravel Pint
- Laravel Blade
- EditorConfig

### JavaScript / TypeScript / formatting

- ESLint
- Prettier
- Tailwind CSS IntelliSense

### Productivity

- DotENV
- Error Lens
- GitLens
- Markdownlint
- YAML
- Docker

Extension recommendations are also stored in `.vscode/extensions.json`.

---

## 6. Local workflow

Before starting work:

- pull latest changes
- run migrations if needed
- run tests if the branch changed critical code

Before opening a pull request:

```bash
./vendor/bin/pint
./vendor/bin/phpstan analyse
./vendor/bin/pest
```

---

## 7. Documentation to read first

Read these files in order:

1. `README.md`
2. `CODE_STANDARDS.md`
3. `CONTRIBUTING.md`
4. `CHANGELOG.md`

---

## 8. Language rule

All repository-facing content is written in English.
