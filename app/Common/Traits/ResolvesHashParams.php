<?php

namespace App\Common\Traits;

use Illuminate\Support\Str;
use InvalidArgumentException;

trait ResolvesHashParams
{
    /**
     * Resolves `*_hash` params into `*_id` params using the given service map.
     *
     * @param  array<string, class-string>  $serviceMap  Maps each `*_hash` key to the service class that resolves it.
     */
    protected function resolveHashParams(array $params, array $serviceMap = []): array
    {
        foreach ($params as $key => $value) {
            if (Str::endsWith($key, '_hash')) {
                $service = $this->resolveService($key, $serviceMap);
                $params[Str::replaceLast('_hash', '_id', $key)] = $this->resolveHash($value, $service);

            }
        }

        return $params;
    }

    protected function resolveService(string $key, array $serviceMap)
    {
        if (! isset($serviceMap[$key])) {
            throw new InvalidArgumentException("No service mapped for {$key}");
        }

        return app($serviceMap[$key]);
    }

    protected function resolveHash($hash, $service)
    {
        if ($hash == null || $hash == '') {
            throw new InvalidArgumentException('Hash cannot be empty');
        }

        $id = $this->getIdFromHash($hash, $service);

        if ($id == null) {
            throw new InvalidArgumentException("Invalid hash provided: {$hash}");
        }

        return $id;
    }
}
