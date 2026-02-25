<?php

declare(strict_types=1);

namespace Modules\Shared\Repositories;

use App\Repositories\EloquentRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Shared\Models\Currency;
use Modules\Shared\Repositories\Contracts\CurrencyRepositoryInterface;

/** @extends EloquentRepository<Currency> */
class CurrencyRepository extends EloquentRepository implements CurrencyRepositoryInterface
{
    private const string CACHE_KEY_ALL = 'currencies:all';

    private const int CACHE_TTL = 86400;

    public function __construct(Currency $model)
    {
        parent::__construct($model);
    }

    /** @return Collection<int, Currency> */
    public function all(array $columns = ['*']): Collection
    {
        return Cache::remember(self::CACHE_KEY_ALL, self::CACHE_TTL, fn () => parent::all($columns));
    }

    public function invalidateCache(): void
    {
        Cache::forget(self::CACHE_KEY_ALL);
    }
}
