<?php

declare(strict_types=1);

final class UserRepository
{
    public function __construct(private JsonStore $store)
    {
    }

    public function findById(?string $id): ?array
    {
        if ($id === null || $id === '') {
            return null;
        }

        foreach ($this->store->read() as $user) {
            if (($user['id'] ?? '') === $id) {
                return $user;
            }
        }

        return null;
    }

    public function findByEmail(string $email): ?array
    {
        $normalizedEmail = mb_strtolower(trim($email));

        foreach ($this->store->read() as $user) {
            if (($user['email'] ?? '') === $normalizedEmail) {
                return $user;
            }
        }

        return null;
    }

    public function create(array $payload): array
    {
        $users = $this->store->read();

        $user = [
            'id' => 'user_' . bin2hex(random_bytes(8)),
            'last_name' => $payload['last_name'],
            'first_name' => $payload['first_name'],
            'birth_date' => $payload['birth_date'],
            'email' => mb_strtolower(trim($payload['email'])),
            'password_hash' => password_hash($payload['password'], PASSWORD_DEFAULT),
            'profile_description' => $payload['profile_description'],
            'created_at' => gmdate('c'),
        ];

        $users[] = $user;
        $this->store->write($users);

        return $user;
    }

    public function update(string $id, array $payload): ?array
    {
        $users = $this->store->read();

        foreach ($users as $index => $user) {
            if (($user['id'] ?? '') !== $id) {
                continue;
            }

            $users[$index]['last_name'] = $payload['last_name'];
            $users[$index]['first_name'] = $payload['first_name'];
            $users[$index]['birth_date'] = $payload['birth_date'];
            $users[$index]['profile_description'] = $payload['profile_description'];
            $users[$index]['updated_at'] = gmdate('c');

            $this->store->write($users);

            return $users[$index];
        }

        return null;
    }
}
