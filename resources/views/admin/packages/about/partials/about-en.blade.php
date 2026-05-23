<div class="card">
    <div class="card-header bg-primary text-white">
        <i class="fa fa-cube me-2"></i> Evo Package Manager: Install packages, not problems
    </div>
    <div class="card-body">
        <h3 class="mb-3">🤔 What is this, anyway?</h3>
        <p><strong>In simple terms:</strong> it's a "smart manager" that answers the question:</p>
        <div class="alert alert-info">
            <i class="fa fa-cube me-2"></i>
            <em>"Which packages are installed in my EvolutionCMS, how do I update them, and how do I manage dependencies?"</em>
        </div>

        <p>❌ <strong>No</strong>, this is not a replacement for <code>php artisan package:installrequire</code>. It's like opening a terminal, but with a convenient interface, helpful hints, and protection against mistakes.</p>
        <p>✅ <strong>Yes</strong>, this is a single point of control: install packages with one click, view the list, sync the registry, and safely remove packages — all in the EvolutionCMS admin style.</p>

        <hr class="my-4">

        <h4 class="mb-3">⚡ Available CLI Commands</h4>
        <p>The module also provides a set of commands for CLI usage (useful for CI/CD and automation):</p>

        <div class="table-responsive">
            <table class="table table-sm table-bordered">
                <thead class="table-light">
                <tr>
                    <th style="width: 40%;">Command</th>
                    <th>Description</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td><code>evo:package:install</code></td>
                    <td>Install a package with automatic post-installation (publish assets, run migrations)</td>
                </tr>
                <tr>
                    <td><code>evo:package:list</code></td>
                    <td>Show the list of installed packages from the registry</td>
                </tr>
                <tr>
                    <td><code>evo:package:remove</code></td>
                    <td>Remove a package from <code>composer.json</code>, <code>vendor/</code>, the registry, and the providers list</td>
                </tr>
                <tr>
                    <td><code>evo:package:sync</code></td>
                    <td>Synchronize the database registry with data from <code>composer/installed.json</code></td>
                </tr>
                </tbody>
            </table>
        </div>

        <div class="alert alert-secondary small">
            <i class="fa fa-terminal me-1"></i>
            <strong>Example:</strong> <code>php artisan evo:package:install ambrion/evocms-feature-flags "*"</code>
        </div>

        <hr class="my-4">

        <h4 class="mb-3">🔧 How It Works</h4>
        <div class="row g-3">
            <div class="col-md-6">
                <div class="card h-100 border-primary">
                    <div class="card-body">
                        <h6 class="card-title text-primary">
                            <i class="fa fa-download me-1"></i> Installation
                        </h6>
                        <ol class="small mb-0 ps-3">
                            <li>Add requirement to <code>composer.json</code></li>
                            <li>Run <code>composer update</code></li>
                            <li>Automatic post-installation (if configured)</li>
                            <li>Synchronize with the database registry</li>
                        </ol>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100 border-success">
                    <div class="card-body">
                        <h6 class="card-title text-success">
                            <i class="fa fa-trash me-1"></i> Removal
                        </h6>
                        <ol class="small mb-0 ps-3">
                            <li>Read package data from <code>installed.json</code></li>
                            <li>Remove service provider files</li>
                            <li>Remove from <code>composer.json</code> + run <code>composer update</code></li>
                            <li>Clean up the database registry record</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <hr class="my-4">

        <h4 class="mb-3">📦 Package Post-Installation</h4>
        <p>The module automatically executes post-installation steps if the package provides them in <code>composer.json</code>:</p>

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
            <strong>Supported:</strong> file publishing (<code>vendor:publish</code>), running migrations.
        </p>
        <p class="mt-2 small text-muted">
            <i class="fa fa-info-circle me-1"></i>
            <strong>Planned:</strong> seeding, executing custom commands.
        </p>

        <hr class="my-4">

        <h4 class="mb-3">🛠 Technical Details</h4>
        <div class="row g-3">
            <div class="col-md-4">
                <strong>Module Version</strong><br>
                <span class="text-muted">3.1.x</span>
            </div>
            <div class="col-md-4">
                <strong>Compatibility</strong><br>
                <span class="text-muted">EvolutionCMS CE 3.1+, PHP 8.3+</span>
            </div>
        </div>

        <p class="mb-0 mt-3"><small class="text-muted">
                <i class="fa fa-heart text-danger"></i>
                Built with ❤️ for the EvolutionCMS community.
                Source code: <a href="https://github.com/Ambrion/evocms-evo-package-manager" target="_blank">GitHub</a>.
            </small></p>
    </div>
</div>

<!-- Author contact block -->
<div class="card border-primary mt-4">
    <div class="card-header bg-primary text-white">
        <i class="fa fa-user-circle me-2"></i> Contact the module author
    </div>
    <div class="card-body">
        <p class="mb-3">
            <strong>Ambrion</strong> — module developer.<br>
            Questions, ideas, or found a bug? Write — I'll respond! 🤝
        </p>

        <div class="row g-3">
            <!-- Website -->
            <div class="col-md-4">
                <a href="https://ambrion.dev/?site=FeatureFlags" target="_blank"
                   class="d-flex align-items-center p-3 border rounded hover-shadow text-decoration-none h-100"
                   style="transition: all 0.2s;">
                    <i class="fa fa-globe fa-2x text-primary me-3"></i>
                    <div>
                        <div class="fw-bold">Website</div>
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
                        <small class="text-muted">Channel @ambrion_dev</small>
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
            <strong>Tip:</strong> Before asking, check <a href="https://github.com/Ambrion/evocms-evo-package-manager/issues" target="_blank">GitHub Issues</a> — maybe the answer is already there!
        </div>
    </div>
</div>
