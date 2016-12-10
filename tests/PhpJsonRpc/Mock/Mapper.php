<?php

namespace PhpJsonRpc\Tests\Mock;

require_once __DIR__ . '/../Domain/UserRepository.php';
require_once __DIR__ . '/../Domain/Math.php';

use PhpJsonRpc\Server\MapperInterface;
use PhpJsonRpc\Tests\Math;
use PhpJsonRpc\Tests\UserRepository;

/**
 * Custom mapping
 */
class Mapper implements MapperInterface
{
    public function getClassAndMethod(string $requestedMethod): array
    {
        $map = [
            'User.getOne'  => [UserRepository::class, 'getOne'],
            'User.getList' => [UserRepository::class, 'getList'],
            'User.create'  => [UserRepository::class, 'create'],
            'Math.pow'     => [Math::class, 'pow']
        ];

        if (array_key_exists($requestedMethod, $map)) {
            return $map[$requestedMethod];
        }

        return ['', ''];
    }
}
