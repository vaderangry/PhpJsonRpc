<?php

namespace PhpJsonRpc\Tests;

class UserRepository
{
    public function getOne(int $id)
    {
        return new User($id, 'unknown@empire.com', 'unknown');
    }

    public function getList(array $where = [], int $limit = 0, int $offset = 10)
    {
        return [
            new User(1, 'vader@@empire.com', 'vader'),
            new User(2, 'yoda@empire.com', 'yoda'),
            new User(3, 'obiwan@empire.com', 'obiwan'),
            new User(4, 'bobafett@empire.com', 'bobafett'),
            new User(5, 'amidala@empire.com', 'amidala')
        ];
    }

    public function create(User $user)
    {
        $user->id = 8;
        return $user;
    }

    public function error()
    {
        throw new \RuntimeException('Internal server error');
    }
}
