<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 */
interface RepositoryInterface
{
    /** @return Collection<int, TModel> */
    public function all(array $columns = ['*']): Collection;

    /** @return TModel|null */
    public function find(int|string $id, array $columns = ['*']): ?Model;

    /** @return TModel */
    public function findOrFail(int|string $id, array $columns = ['*']): Model;

    /** @return TModel */
    public function create(array $attributes): Model;

    /** @return TModel */
    public function update(Model $model, array $attributes): Model;

    public function delete(Model $model): bool;

    /** @return LengthAwarePaginator<int, TModel> */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator;
}
