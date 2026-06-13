<?php

namespace App\Services;

use App\Abstracts\AbstractService;
use App\Events\UserCreated;
use App\Models\User;
use App\Repositories\UserRepository;

/**
 * @extends AbstractService<UserRepository>
 */
class UserService extends AbstractService
{
    public function __construct(UserRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAuthenticatedUser(User $user, array $with = []): array
    {
        $user = $this->findOneWhere(['email' => $user->email], $with);

        return [
            'hash' => $user->hash,
            'name' => $user->name,
            'email' => $user->email,
        ];
    }

    public function getTransferRecipients(User $user): array
    {
        return [
            'recipients' => $this->repository->getTransferRecipients($user->id)
                ->map(fn (User $recipient) => [
                    'hash' => $recipient->hash,
                    'name' => $recipient->name,
                ])
                ->all(),
        ];
    }

    public function afterSave($entity, array $params)
    {
        event(new UserCreated($entity));

        return $entity;
    }
}
