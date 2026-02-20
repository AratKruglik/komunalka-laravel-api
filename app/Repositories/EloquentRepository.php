<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 *
 * @implements RepositoryInterface<TModel>
 */
abstract class EloquentRepository implements RepositoryInterface
{
    /** @param TModel $model */
    public function __construct(protected Model $model) {}

    /** @return Builder<TModel> */
    protected function newQuery(): Builder
    {
        return $this->model->newQuery();
    }

    public function all(array $columns = ['*']): Collection
    {
        return $this->newQuery()->get($columns);
    }

    public function find(int|string $id, array $columns = ['*']): ?Model
    {
        return $this->newQuery()->find($id, $columns);
    }

    public function findOrFail(int|string $id, array $columns = ['*']): Model
    {
        return $this->newQuery()->findOrFail($id, $columns);
    }

    public function create(array $attributes): Model
    {
        return $this->newQuery()->create($attributes);
    }

    public function update(Model $model, array $attributes): Model
    {
        $model->update($attributes);

        return $model->refresh();
    }

    public function delete(Model $model): bool
    {
        return (bool) $model->delete();
    }

    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return $this->newQuery()->paginate($perPage, $columns);
    }
}
