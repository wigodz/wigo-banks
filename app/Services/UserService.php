<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;

class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {}

    public function getAuthenticatedUser(User $user): User
    {
        return $this->users->findByEmail($user->email);
    }
}
