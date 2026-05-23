[🇷🇺 Русский](README.ru.md) | [🇬🇧 English](README.md)

# Evo Package Manager for EvolutionCMS CE 3

Unified package management solution for **EvolutionCMS CE 3** — via CLI or convenient web UI.

This module adds console commands and a user interface for streamlined package lifecycle management within EvolutionCMS CE 3.

Automatic post-installation support is available — migrations, asset publishing — when package developers configure these actions in `composer.json`.

A unified database registry of installed packages provides centralized package information viewing.

## 📦 Installation

```bash
cd /core
php artisan package:installrequire evocms-evo-package-manager "v0.1.0-alpha"
php artisan vendor:publish --provider="EvolutionCMS\EvoPackageManager\EvoPackageManagerServiceProvider"
php artisan migrate
```

## ⚙️ Requirements

| Requirement         | Version   | Notes                                                         |
|---------------------|-----------|---------------------------------------------------------------|
| **PHP**             | `^8.3`    | Required for typed properties and readonly classes            |
| **EvolutionCMS CE** | `≥3.1.30` | Tested on v3.1.30; may work on earlier 3.x versions           |
| **Composer**        | `^2.0`    | For package installation and dependency management            |

> 💡 **Note**: This module leverages modern PHP 8.3 features (`readonly` classes, typed properties, match expressions). PHP 8.1–8.2 versions are **not supported**.

## ⚙️ Quick Start

1. Open **Manager → Modules → Evo Package Manager** in the EvolutionCMS admin panel
2. Install or sync packages via UI or CLI

## 🎯 Who is this module for?

| Role              | Benefit                                                      |
|-------------------|--------------------------------------------------------------|
| **Developer**     | Quick dependency installation without SSH access             |
| **Administrator** | Visual package control, change auditing                      |
| **DevOps**        | CLI commands for CI/CD, idempotent operations                |
| **Team**          | Unified package management standard, in-interface docs       |

### Key Features

**Package Management**

- **Install**: Add to `composer.json` + `composer update` + post-installation
- **List**: View installed packages with metadata (version, author, dependencies)
- **Remove**: Safe cleanup from `composer.json`, `vendor/`, DB registry, and providers
- **Sync**: Align database registry with `installed.json`

### Console Commands

```bash
# Install a package
php artisan evo:package:install vendor/package "*"

# Skip post-installation steps
php artisan evo:package:install ambrion/evocms-feature-flags "*" --skip-post-install

# List all packages
php artisan evo:package:list

# Filter: active libraries from Packagist only
php artisan evo:package:list --status=active --type=library --source=packagist

# JSON output for scripts
php artisan evo:package:list --format=json --limit=100

# Count only
php artisan evo:package:list --count

# Pagination
php artisan evo:package:list --limit=20 --offset=40

# Remove a package
php artisan evo:package:remove vendor/package

# Sync registry with installed.json
php artisan evo:package:sync

# Sync a specific package only
php artisan evo:package:sync vendor/new-pkg
```

### Automatic Post-Installation

Packages can specify post-installation steps in `composer.json`:

```json
{
  "extra": {
    "evo": {
      "post-install": {
        "publish": {
          "provider": "Vendor\\Package\\ServiceProvider"
        },
        "migrate": {
          "run": true,
          "force": false
        },
        "commands": [
          "cache:clear",
          "config:clear"
        ]
      }
    }
  }
}
```

**Supported steps:**
- `publish` — Publish files via `vendor:publish`
- `migrate` — Run migrations with `--force` option
- `seed` — Execute seeders (planned)
- `commands` — Custom Artisan commands (planned)

## 📬 Support

- 🐛 Bugs: [GitHub Issues](https://github.com/Ambrion/evocms-evo-package-manager/issues)
- ✉️ Email: ping@ambrion.dev
- 💬 Telegram: [@ambrion_dev](https://t.me/ambrion_dev)
- [Author's Website](https://ambrion.dev/?site=EvoPackageManager)

## 📜 License

MIT © [Ambrion](https://ambrion.dev)

---

> 💡 **Note**: For Russian documentation, see [README.ru.md](README.ru.md).