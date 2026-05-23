<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evo_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->comment('Composer-style: vendor/package-name');
            $table->string('version', 50)->comment('Resolved SemVer 2.0 string');
            $table->enum('status', ['active', 'installed', 'disabled', 'pending_update', 'failed'])->default('active');
            $table->string('type', 50)->default('library');
            $table->string('source', 50)->default('packagist');
            $table->json('requirements')->nullable();
            $table->json('autoload')->nullable();
            $table->json('extra')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('installed_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evo_packages');
    }
};
