<?php

namespace App\Http\Controllers\Api\V1;

use App\Abstracts\AbstractController;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends AbstractController
{
    public function __construct(private readonly UserService $userService)
    {
        $this->service = $userService;
    }

    public function me(Request $request): JsonResponse
    {
        $data = $this->userService->getAuthenticatedUser($request->user());

        return $this->ok($data);
    }
}
