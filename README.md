# Fulgurite

Fulgurite is a source-available, self-hosted web interface for operating Restic backups.

It helps technical teams manage repositories, scheduled backup jobs, copy jobs, snapshot exploration, restores, notifications, access control and API integrations from a browser-based interface.

## Project Status

Fulgurite is under active development. The current repository focuses on the application core, the public interface, operational scripts, tests, translations and curated documentation.

Docker deployment files, website assets, GitHub metadata and some older planning documents are intentionally excluded from the clean repository until they are reviewed.

## What Fulgurite Does

- Manage Restic repositories and secrets.
- Configure local and remote backup jobs.
- Replicate snapshots with copy jobs.
- Browse snapshots, search files and launch restores.
- Run scheduled work through a central scheduler and worker model.
- Send notifications across multiple channels.
- Manage users, roles, scopes, TOTP and WebAuthn.
- Expose a scoped API and signed webhook integrations.
- Support user-facing themes and developer-built advanced themes.

## Documentation

The main documentation is available in English and French:

- [English documentation](docs/en/01-index.md)
- [French documentation](docs/fr/01-index.md)
- [Developer theme documentation](docs/en/dev/01-index.md)
- [Documentation développeur des thèmes](docs/fr/dev/01-index.md)

Recommended first reads:

- [Getting Started](docs/en/02-getting-started.md)
- [Concepts and Navigation](docs/en/03-concepts-and-navigation.md)
- [Restic Repositories](docs/en/04-repositories.md)
- [Backup Jobs](docs/en/05-backup-jobs.md)
- [Scheduler and Worker](docs/en/08-scheduler-and-worker.md)

## Repository Layout

- `src/` contains the application services and domain logic.
- `public/` contains web entrypoints, API endpoints and browser assets.
- `config/` contains distributable configuration defaults.
- `bin/` and `scripts/` contain operational commands.
- `lib/` contains bundled PHP libraries used by the application.
- `tests/` contains the PHP test suite.
- `translations/` contains application translation catalogs.
- `docs/en/` and `docs/fr/` contain user and developer documentation.
- `data/` is committed only with `.gitkeep`; runtime data, databases, secrets and caches must stay untracked.

## Local Setup

Fulgurite targets PHP 8.2 or newer. The setup wizard checks the runtime, required extensions, database access and writable runtime directories.

At a high level:

1. Configure the web server to serve `public/`.
2. Ensure `config/` and `data/` are writable by the web/PHP runtime user.
3. Generate a setup bootstrap token:

```bash
php scripts/setup-bootstrap.php create --ttl=30
```

4. Open `public/setup.php` through the configured virtual host.
5. Complete the setup wizard and create the first administrator account.

See [Getting Started](docs/en/02-getting-started.md) for the full flow.

## Roadmap

The project roadmap lives in [ROADMAP.md](ROADMAP.md). It focuses on reliability, security, restore quality, packaging, internationalization and the longer-term plugin/theme ecosystem.

## Contributing

Public contributions are welcome when they are focused, reviewable and compatible with the project direction.

Before contributing, read:

- [CONTRIBUTING.md](CONTRIBUTING.md)
- [CONTRIBUTOR_TERMS.md](CONTRIBUTOR_TERMS.md)
- [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md)
- [GOVERNANCE.md](GOVERNANCE.md)

## Security

Do not report vulnerabilities in public issues. Read [SECURITY.md](SECURITY.md) for private reporting expectations and supported security scope.

## Licensing

Fulgurite is source-available, not Open Source in the OSI sense.

- The public repository is licensed under [PolyForm Noncommercial 1.0.0](LICENSE).
- Commercial use requires a separate written agreement.
- Licensing details are summarized in [LICENSING.md](LICENSING.md).
- Required notices are listed in [NOTICE](NOTICE).

English root documents are authoritative. French versions in [project/fr](project/fr/README.md) are provided for convenience.
