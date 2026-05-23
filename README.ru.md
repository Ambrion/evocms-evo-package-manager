[🇷🇺 Русский](README.ru.md) | [🇬🇧 English](README.md)

# Evo Package Manager для EvolutionCMS CE 3

Единое решение для управления пакетами в **EvolutionCMS CE 3** — через консоль или удобный веб-интерфейс.

Модуль добавляет консольные команды и UI для удобной работы с жизненным циклом пакета в рамках EvolutionCMS CE 3.

Доступна автоматическая пост-инсталляция - миграции, публикация ресурсов - в случае, когда разработчик пакета настроил это действие в composer.json.

Создан единый реестр установленных пакетов в БД с просмотром информации о пакете.

## 📦 Установка

```bash
cd /core
php artisan package:installrequire ambrion/evocms-evo-package-manager "v0.1.0-alpha"
php artisan vendor:publish --provider="EvolutionCMS\EvoPackageManager\EvoPackageManagerServiceProvider"
php artisan migrate
```

## ⚙️ Требования

| Требование          | Версия    | Примечание                                                    |
|---------------------|-----------|---------------------------------------------------------------|
| **PHP**             | `^8.3`    | Требуется для typed properties и readonly-классов             |
| **EvolutionCMS CE** | `≥3.1.30` | Протестировано на v3.1.30; может работать на более ранних 3.x |
| **Composer**        | `^2.0`    | Для установки пакета и управления зависимостями               |

> 💡 **Примечание**: Модуль использует возможности современного PHP 8.3 (`readonly`-классы, типизированные свойства, match-выражения). Версии PHP 8.1–8.2 **не поддерживаются**.

## ⚙️ Быстрый старт

1. Откройте **Менеджер → Модули → Evo Package Manager** в админке EvolutionCMS
2. Установите или синхронизируйте пакеты

## 🎯 Для кого этот модуль?

| Роль              | Выгода                                                       |
|-------------------|--------------------------------------------------------------|
| **Разработчик**   | Быстрая установка зависимостей без доступа к SSH             |
| **Администратор** | Визуальный контроль над пакетами, аудит изменений            |
| **DevOps**        | CLI-команды для CI/CD, идемпотентные операции                |
| **Команда**       | Единый стандарт работы с пакетами, документация в интерфейсе |

### Ключевые возможности

**Управление пакетами**

- **Установка**: добавление в `composer.json` + `composer update` + пост-инсталляция
- **Просмотр**: список установленных пакетов с метаданными (версия, автор, зависимости)
- **Удаление**: безопасная очистка из `composer.json`, `vendor/`, реестра БД и провайдеров
- **Синхронизация**: приведение реестра БД в соответствие с `installed.json`

### Консольные команды

```bash
# Установка пакета
php artisan evo:package:install vendor/package "*"

# Пропустить пост-инсталляцию
php artisan evo:package:install ambrion/evocms-feature-flags "*" --skip-post-install

# Список всех пакетов
php artisan evo:package:list

# Только активные библиотеки из packagist
php artisan evo:package:list --status=active --type=library --source=packagist

# JSON-вывод для скриптов
php artisan evo:package:list --format=json --limit=100

# Только количество
php artisan evo:package:list --count

# Пагинация
php artisan evo:package:list --limit=20 --offset=40

# Удаление пакета
php artisan evo:package:remove vendor/package

# Синхронизация реестра
php artisan evo:package:sync

# Синхронизация выбранного пакета
php artisan evo:package:sync vendor/new-pkg
```

### Автоматическая пост-инсталляция

Пакеты могут указывать шаги пост-инсталляции в `composer.json`:

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

**Поддерживаемые шаги:**
- `publish` — публикация файлов через `vendor:publish`
- `migrate` — запуск миграций с опцией `--force`
- `seed` — выполнение сидеров (планируется)
- `commands` — произвольные Artisan-команды (планируется)

## 📬 Поддержка

- 🐛 Баги: [GitHub Issues](https://github.com/Ambrion/evocms-evo-package-manager/issues)
- ✉️ Email: ping@ambrion.dev
- 💬 Telegram: [@ambrion_dev](https://t.me/ambrion_dev)
- [Сайт автора](https://ambrion.dev/?site=EvoPackageManager)

## 📜 Лицензия

MIT © [Ambrion](https://ambrion.dev)

---

> 💡 **Примечание**: For English documentation, see [README.md](README.md).