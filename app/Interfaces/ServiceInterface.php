<?php

namespace App\Interfaces;

interface ServiceInterface
{
    public function all();

    public function find($id);

    public function save(array $data);

    public function firstOrCreate(array $params);

    public function update($id, array $data);

    public function updateOrCreate(array $paramsValidate, array $params);

    public function delete($id);
}
