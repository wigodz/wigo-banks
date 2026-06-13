<?php

namespace App\Abstracts;

use App\Common\Traits\ResolvesHashParams;
use App\Interfaces\RepositoryInterface;
use App\Interfaces\ServiceInterface;
use Exception;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TRepository of RepositoryInterface
 */
class AbstractService implements ServiceInterface
{
    use ResolvesHashParams;

    protected $with = [];

    /** @var TRepository */
    protected RepositoryInterface $repository;

    public function all(array $params = [], array $with = [])
    {
        return $this->repository->all($params, $with);
    }

    /**
     * @throws Exception
     */
    public function find($id, $with = [])
    {
        $result = $this->repository->find($id, $with);

        if ($result == null) {
            $classPath = get_class($this->repository->getModel());
            $classPathExploded = explode('\\', $classPath);
            $className = end($classPathExploded);

            throw new Exception("$className not found.");
        }

        return $result;
    }

    public function findOneWhere(array $where, array $with = []): ?Model
    {
        return $this->repository->findOneWhere($where, $with);
    }

    public function findWhereIn(string $column, array $values, array $with = [])
    {
        return $this->repository->findWhereIn($column, $values, $with);
    }

    public function create(array $data)
    {
        return $this->repository->create($data);
    }

    public function beforeUpdate($id, array $data): array
    {
        return $data;
    }

    public function validateOnUpdate($id, array $params) {}

    public function afterUpdate($entity, array $params) {}

    public function update($id, array $data)
    {
        $data = $this->beforeUpdate($id, $data);
        $this->validateOnUpdate($id, $data);

        $updated = $this->repository->update($id, $data);

        if ($updated) {
            $this->afterUpdate($updated, $data);
        }

        return $updated;
    }

    public function delete($id)
    {
        return $this->repository->delete($id);
    }

    /**
     * @return array
     */
    public function beforeSave(array $data)
    {
        return $data;
    }

    /**
     * @return bool
     */
    public function validateOnInsert(array $params)
    {
        return true;
    }

    /**
     * @return mixed
     */
    public function save(array $data)
    {
        $data = $this->beforeSave($data);
        if ($this->validateOnInsert($data) !== false) {
            $entity = $this->repository->create($data);
            $this->afterSave($entity, $data);

            return $entity;
        }
    }

    public function afterSave($entity, array $params)
    {
        return $entity;
    }

    public function exists(array $params = [])
    {
        return $this->repository->exists($params);
    }

    /**
     * Update or create.
     */
    public function updateOrCreate(array $paramsValidate, array $params): Model
    {
        return $this->repository->updateOrCreate($paramsValidate, $params);
    }

    /**
     * First or create.
     */
    public function firstOrCreate($paramsValidation, $params = [])
    {
        return $this->repository->firstOrCreate($paramsValidation, $params);
    }

    public function getIdFromHash(string $hash, $service): ?int
    {
        return optional($service->find($hash))->id;
    }
}
