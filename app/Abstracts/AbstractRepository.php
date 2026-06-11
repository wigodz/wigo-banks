<?php

namespace App\Abstracts;

use App\Interfaces\RepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AbstractRepository implements RepositoryInterface
{
    protected Model $model;

    public function getModel(): Model
    {
        return $this->model;
    }

    public function all(?array $params = [], array $with = [])
    {
        return $this->model->query()
            ->with($with)
            ->orderBy('created_at', $params['order_by'] ?? 'desc')
            ->paginate($params['per_page'] ?? 20);
    }

    public function find($id, $with = [])
    {
        if (is_numeric($id)) {
            return $this->model->with($with)->find($id);
        }

        return $this->findOneWhere(['hash' => $id], $with);
    }

    public function findOrFail($id): Model|Builder
    {
        return $this->model->findOrFail($id);
    }

    /**
     * Retorna o primeiro registro encontrado
     *
     * @return mixed
     */
    public function findOneWhere(array $where, $with = [])
    {
        $object = $this->where($where, $with);

        return $object->first();
    }

    public function findWhereIn(string $column, array $values, array $with = [])
    {
        return $this->getModel()->whereIn($column, $values)->with($with)->get();
    }

    /**
     * @param  array  $with
     * @return mixed
     */
    public function where(array $where, $with = [])
    {
        return $this->getModel()->where($where)->with($with)->get();
    }

    public function create(array $data): Model|Builder
    {
        return $this->model->query()->create($data);
    }

    public function update($id, array $data)
    {
        $instanceOfModel = $this->find($id);

        if ($instanceOfModel) {
            $instanceOfModel->update($data);

            return $instanceOfModel;
        }

        return false;
    }

    public function delete($id): bool
    {
        $instanceOfModel = $this->find($id);

        if ($instanceOfModel) {
            $instanceOfModel->delete();

            return true;
        }

        return false;
    }

    public function exists(array $params): bool
    {
        return $this->where($params)->isNotEmpty();
    }

    /**
     * Update or create.
     */
    public function updateOrCreate(array $paramsValidation, array $params): Model
    {
        return $this->getModel()->updateOrCreate($paramsValidation, $params);
    }

    /**
     * First or create.
     */
    public function firstOrCreate(array $searchParams, array $params): Model
    {
        return $this->getModel()->firstOrCreate($searchParams, $params);
    }

    public function getFillable(): array
    {
        return $this->model->getFillable();
    }
}
