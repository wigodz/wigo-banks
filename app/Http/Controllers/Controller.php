<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected string $messageSuccessDefault = 'Operação realizada com sucesso';

    protected string $messageErrorDefault = 'Ops';

    const TYPE_SUCCESS = 'success';

    const TYPE_ERROR = 'error';

    public function ok(array $items = [], int $status = Response::HTTP_OK): JsonResponse
    {
        $data = [
            'type' => self::TYPE_SUCCESS,
            'status' => $status,
            'data' => $items,
            'show' => false,
        ];

        return response()->json($data, $status);
    }

    public function error(
        string $message = '',
        array $items = [],
        int $status = Response::HTTP_UNPROCESSABLE_ENTITY
    ): JsonResponse {
        if (is_null($message)) {
            $message = $this->messageErrorDefault;
        }

        $data = [
            'type' => self::TYPE_ERROR,
            'status' => $status,
            'message' => $message,
            'show' => true,
        ];

        if ($items) {
            foreach ($items as $key => $item) {
                data_set($data, "errors.$key", $item);
            }
        }

        return response()->json($data, $status);
    }

    public function success(
        string $message,
        array $items = [],
        int $status = Response::HTTP_OK
    ): JsonResponse {
        if (is_null($message)) {
            $message = $this->messageSuccessDefault;
        }

        $data = [
            'type' => self::TYPE_SUCCESS,
            'status' => $status,
            'message' => $message,
            'show' => true,
        ];

        if ($items instanceof Arrayable) {
            $items = $items->toArray();
        }

        if ($items) {
            foreach ($items as $key => $item) {
                data_set($data, $key, $item);
            }
        }

        return response()->json($data, $status);
    }

    public function getUserAuth(): ?Authenticatable
    {
        return Auth::user();
    }

    public function hasPermissionTo($permission): void
    {
        if (! Auth::user()->hasPermissionTo($permission)) {
            throw new UnauthorizedException(
                403,
                'Você não tem permissão suficiente para executar essa ação'
            );
        }
    }
}
