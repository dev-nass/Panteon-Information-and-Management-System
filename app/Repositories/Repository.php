<?php

namespace App\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

abstract class Repository
{
    /**
     * param Model $model injecting an instance of a model class (new User())
     * */
    public function __construct(protected Model $model) {}

    public function model(): Model
    {
        return $this->model;
    }

    public function query(): Builder
    {
        return $this->model->query();
    }

    public function paginate(int $perPage = 15, array $columns = ['*'], array|string $relations = []): LengthAwarePaginator
    {
        return $this->query()->with($relations)->paginate($perPage, $columns);
    }

    public function find(int|string $id, array $columns = ['*'], array|string $relations = []): ?Model
    {
        return $this->query()->with($relations)->find($id, $columns);
    }

    /**
     * Why can we use the injected model instance from the __construct() here
     * but not on the update() below,
     * because create() just needs the table + fillable fields
     *
     * @param  array  $data  contain (['name' => 'John'])
     * */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * Description: can be use as $this->update($user, ['name' => 'Jonathan'])
     *
     * @param  Model  $model  expects an already fetched record contaning id,
     *                        which then be use for update
     * @param  array  $data  contain (['name' => 'John'])
     * */
    public function update(Model $model, array $data): bool
    {
        return $model->update($data);
    }

    /**
     * @param  Model  $model  expects an already fetched record cotaning id
     * */
    public function delete(Model $model): ?bool
    {
        return $model->delete();
    }

    public function updateOrCreate(array $attributes, array $values): Model
    {
        return $this->model->updateOrCreate($attributes, $values);
    }

    public function transaction(\Closure $callback): mixed
    {
        return DB::transaction($callback);
    }

    /**
     * Sample Usage:
     * $userRepository->createWithRelations(
     * // main model data
     * data: [
     * 'name'     => 'John Doe',
     * 'email'    => 'john@example.com',
     * 'password' => bcrypt('secret'),
     * ],
     *
     * // relations and their data
     * relations: [
     * 'profile' => [
     * 'bio'    => 'Software Developer',
     * 'avatar' => 'avatar.png',
     * ],
     * 'address' => [
     * 'street' => '123 Main St',
     * 'city'   => 'Manila',
     * ],
     * ]
     * );
     */
    public function createWithRelations(array $data, array $relations = []): Model
    {
        return DB::transaction(function () use ($data, $relations) {
            $model = $this->create($data);
            foreach ($relations as $relation => $relationData) {
                if (method_exists($model, $relation)) {
                    $model->$relation()->create($relationData);
                }
            }

            return $model;
        });
    }

    /**
     * Sample Usage:
     * $userRepository->updateOrCreateWithRelations(
     * attributes: ['email' => 'john@example.com'],   // find user by
     * values:     ['name'  => 'John Doe'],            // update user with
     * relations:  [
     * 'profile' => [
     * 'attributes' => ['bio'    => 'Developer'], // find profile by
     * 'values'     => ['avatar' => 'avatar.png'] // update profile with
     * ]
     * ]
     * );
     *
     * param array $attributes for finding the record by; ['col' => 'value']
     * param array $values for filling the record with; ['col' => 'value']
     * param array $relations for [relationshipName => ['colName' => 'value']]
     * */
    public function updateOrCreateWithRelations(array $attributes, array $values, array $relations = []): Model
    {
        return DB::transaction(function () use ($attributes, $values, $relations) {
            $model = $this->updateOrCreate($attributes, $values);
            foreach ($relations as $relation => $relationData) {
                if (method_exists($model, $relation)) {
                    $model->$relation()->updateOrCreate(
                        $relationData['attributes'],
                        $relationData['values']
                    );
                }
            }

            return $model;
        });
    }
}
