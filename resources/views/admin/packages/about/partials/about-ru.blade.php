<div class="card">
    <div class="card-header bg-primary text-white">
        <i class="fa fa-cube me-2"></i> Evo Package Manager: Пакеты — без головной боли
    </div>
    <div class="card-body">
        <h3 class="mb-3">🤔 Что это вообще такое?</h3>
        <p><strong>Простыми словами:</strong> это «умный менеджер», который отвечает на вопрос:</p>
        <div class="alert alert-info">
            <i class="fa fa-cube me-2"></i>
            <em>"Какие пакеты установлены в моём EvolutionCMS, как их обновить и как управлять зависимостями?"</em>
        </div>

        <p>❌ <strong>Нет</strong>, это не замена <code>php artisan package:installrequire</code>. Это как если бы вы открыли терминал, но с удобным интерфейсом, подсказками и защитой от ошибок.</p>
        <p>✅ <strong>Да</strong>, это единая точка управления: установка пакетов одной кнопкой, просмотр списка, синхронизация реестра и безопасное удаление — всё в стиле админки EvolutionCMS.</p>

        <hr class="my-4">

        <h4 class="mb-3">⚡ Доступные консольные команды</h4>
        <p>Модуль также предоставляет набор команд для работы через CLI (полезно для CI/CD и автоматизации):</p>

        <div class="table-responsive">
            <table class="table table-sm table-bordered">
                <thead class="table-light">
                <tr>
                    <th style="width: 40%;">Команда</th>
                    <th>Описание</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td><code>evo:package:install</code></td>
                    <td>Установить пакет с автоматической пост-инсталляцией (публикация ресурсов, миграции)</td>
                </tr>
                <tr>
                    <td><code>evo:package:list</code></td>
                    <td>Показать список установленных пакетов из реестра</td>
                </tr>
                <tr>
                    <td><code>evo:package:remove</code></td>
                    <td>Удалить пакет из <code>composer.json</code>, <code>vendor/</code>, реестра и списка провайдеров</td>
                </tr>
                <tr>
                    <td><code>evo:package:sync</code></td>
                    <td>Синхронизировать реестр БД с данными из <code>composer/installed.json</code></td>
                </tr>
                </tbody>
            </table>
        </div>

        <div class="alert alert-secondary small">
            <i class="fa fa-terminal me-1"></i>
            <strong>Пример:</strong> <code>php artisan evo:package:install ambrion/evocms-feature-flags "*"</code>
        </div>

        <hr class="my-4">

        <h4 class="mb-3">🔧 Как это работает</h4>
        <div class="row g-3">
            <div class="col-md-6">
                <div class="card h-100 border-primary">
                    <div class="card-body">
                        <h6 class="card-title text-primary">
                            <i class="fa fa-download me-1"></i> Установка
                        </h6>
                        <ol class="small mb-0 ps-3">
                            <li>Добавление требования в <code>composer.json</code></li>
                            <li>Запуск <code>composer update</code></li>
                            <li>Автоматическая пост-инсталляция (если настроена)</li>
                            <li>Синхронизация с реестром БД</li>
                        </ol>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100 border-success">
                    <div class="card-body">
                        <h6 class="card-title text-success">
                            <i class="fa fa-trash me-1"></i> Удаление
                        </h6>
                        <ol class="small mb-0 ps-3">
                            <li>Чтение данных пакета из <code>installed.json</code></li>
                            <li>Удаление файлов сервис-провайдеров</li>
                            <li>Удаление из <code>composer.json</code> + <code>composer update</code></li>
                            <li>Очистка записи в реестре БД</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <hr class="my-4">

        <h4 class="mb-3">📦 Пост-инсталляция пакетов</h4>
        <p>Модуль автоматически выполняет шаги пост-инсталляции, если пакет их предоставляет в <code>composer.json</code>:</p>

        <div class="card bg-light">
            <div class="card-body small">
        <pre class="mb-0"><code>{
  "extra": {
    "evo": {
      "post-install": {
        "publish": {
          "provider": "Vendor\\Package\\ServiceProvider"
        },
        "migrate": {
          "run": true,
          "force": false
        }
      }
    }
  }
}</code></pre>
            </div>
        </div>

        <p class="mt-2 small text-muted">
            <i class="fa fa-info-circle me-1"></i>
            <strong>Поддерживается:</strong> публикация файлов (<code>vendor:publish</code>), запуск миграций.
        </p>
        <p class="mt-2 small text-muted">
            <i class="fa fa-info-circle me-1"></i>
            <strong>Планируется:</strong> сидирование, выполнение произвольных команд.
        </p>

        <hr class="my-4">

        <h4 class="mb-3">🛠 Технические детали</h4>
        <div class="row g-3">
            <div class="col-md-4">
                <strong>Версия модуля</strong><br>
                <span class="text-muted">3.1.x</span>
            </div>
            <div class="col-md-4">
                <strong>Совместимость</strong><br>
                <span class="text-muted">EvolutionCMS CE 3.1+, PHP 8.3+</span>
            </div>
        </div>

        <p class="mb-0 mt-3"><small class="text-muted">
                <i class="fa fa-heart text-danger"></i>
                Разработано с ❤️ для сообщества EvolutionCMS.
                Исходный код: <a href="https://github.com/Ambrion/evocms-evo-package-manager" target="_blank">GitHub</a>.
            </small></p>
    </div>
</div>
<!-- Блок связи с автором -->
<div class="card border-primary mt-4">
    <div class="card-header bg-primary text-white">
        <i class="fa fa-user-circle me-2"></i> Связь с автором модуля
    </div>
    <div class="card-body">
        <p class="mb-3">
            <strong>Ambrion</strong> — разработчик модуля.<br>
            Есть вопросы, идеи или нашли баг? Пишите — отвечу! 🤝
        </p>

        <div class="row g-3">
            <!-- Сайт -->
            <div class="col-md-4">
                <a href="https://ambrion.dev/?site=EvoPackageManager" target="_blank"
                   class="d-flex align-items-center p-3 border rounded hover-shadow text-decoration-none h-100"
                   style="transition: all 0.2s;">
                    <i class="fa fa-globe fa-2x text-primary me-3"></i>
                    <div>
                        <div class="fw-bold">Сайт</div>
                        <small class="text-muted">ambrion.dev</small>
                    </div>
                </a>
            </div>

            <!-- Telegram -->
            <div class="col-md-4">
                <a href="https://t.me/ambrion_dev" target="_blank"
                   class="d-flex align-items-center p-3 border rounded hover-shadow text-decoration-none h-100"
                   style="transition: all 0.2s;">
                    <i class="fa fa-telegram fa-2x text-info me-3"></i>
                    <div>
                        <div class="fw-bold">Telegram</div>
                        <small class="text-muted">Канал @ambrion_dev</small>
                    </div>
                </a>
            </div>

            <!-- Email -->
            <div class="col-md-4">
                <a href="mailto:ping@ambrion.dev"
                   class="d-flex align-items-center p-3 border rounded hover-shadow text-decoration-none h-100"
                   style="transition: all 0.2s;">
                    <i class="fa fa-envelope fa-2x text-success me-3"></i>
                    <div>
                        <div class="fw-bold">Email</div>
                        <small class="text-muted">ping@ambrion.dev</small>
                    </div>
                </a>
            </div>
        </div>

        <div class="alert alert-light border mt-3 mb-0 small">
            <i class="fa fa-lightbulb text-warning me-1"></i>
            <strong>Совет:</strong> Перед вопросом проверьте <a href="https://github.com/Ambrion/evocms-evo-package-manager/issues" target="_blank">GitHub Issues</a> — возможно, ответ уже есть!
        </div>
    </div>
</div>
