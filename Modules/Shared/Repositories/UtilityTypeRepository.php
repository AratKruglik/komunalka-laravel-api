<?php

declare(strict_types=1);

namespace Modules\Shared\Repositories;

use App\Repositories\EloquentRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Shared\Models\UtilityType;
use Modules\Shared\Repositories\Contracts\UtilityTypeRepositoryInterface;

/** @extends EloquentRepository<UtilityType> */
class UtilityTypeRepository extends EloquentRepository implements UtilityTypeRepositoryInterface
{
    private const string CACHE_KEY_ALL = 'utility_types:all';

    private const string CACHE_KEY_ACTIVE = 'utility_types:active';

    private const int CACHE_TTL = 86400;

    public function __construct(UtilityType $model)
    {
        parent::__construct($model);
    }

    public function findBySlug(string $slug): ?UtilityType
    {
        return $this->newQuery()->where('slug', $slug)->first();
    }

    public function getActive(): Collection
    {
        $value = Cache::get(self::CACHE_KEY_ACTIVE);

        if ($value instanceof Collection) {
            return $value;
        }

        $value = $this->newQuery()->active()->get();
        Cache::put(self::CACHE_KEY_ACTIVE, $value, self::CACHE_TTL);

        return $value;
    }

    /** @return Collection<int, UtilityType> */
    public function all(array $columns = ['*']): Collection
    {
        $value = Cache::get(self::CACHE_KEY_ALL);

        if ($value instanceof Collection) {
            return $value;
        }

        $value = parent::all($columns);
        Cache::put(self::CACHE_KEY_ALL, $value, self::CACHE_TTL);

        return $value;
    }

    public function invalidateCache(): void
    {
        Cache::forget(self::CACHE_KEY_ALL);
        Cache::forget(self::CACHE_KEY_ACTIVE);
    }
}
