<?php

declare(strict_types=1);

namespace EvolutionCMS\EvoPackageManager\Infrastructure\Database\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent-модель для таблицы evo_packages.
 * Только инфраструктурный слой — не знает про доменные сущности.
 *
 * @property int $id
 * @property string $name
 * @property string $version
 * @property string $status
 * @property string $type
 * @property string $source
 * @property array|null $requirements
 * @property array|null $autoload
 * @property array|null $extra
 * @property array|null $metadata
 * @property string|null $installed_at
 * @property string $created_at
 * @property string $updated_at
 */
class EvoPackage extends Model
{
    protected $table = 'evo_packages';

    // Автоматическое кастование JSON-полей в массивы
    protected $casts = [
        'requirements' => 'array',
        'autoload' => 'array',
        'extra' => 'array',
        'metadata' => 'array',
        'installed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $fillable = [
        'name', 'version', 'status', 'type', 'source',
        'requirements', 'autoload', 'extra', 'metadata', 'installed_at',
    ];

    /**
     * Name — уникальное поле, используем его для upsert
     */
    public function getRouteKeyName(): string
    {
        return 'name';
    }
}
