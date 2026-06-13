<?php

namespace App\Repositories;

use App\Abstracts\AbstractRepository;
use App\Models\User;
use Illuminate\Support\Collection;

class UserRepository extends AbstractRepository
{
    public function __construct(User $model)
    {
        $this->model = $model;
    }

    public function getTransferRecipients(int $excludeUserId): Collection
    {
        return $this->model->query()
            ->where('id', '!=', $excludeUserId)
            ->orderBy('name')
            ->get(['hash', 'name']);
    }
}
