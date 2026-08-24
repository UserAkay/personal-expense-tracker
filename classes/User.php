<?php

declare(strict_types=1);

class User
{
    private PDO $db;


    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }


    /*
    |--------------------------------------------------------------------------
    | Create User
    |--------------------------------------------------------------------------
    */

    public function create(
        string $name,
        string $email,
        string $password
    ): int {

        $name = trim($name);
        $email = strtolower(trim($email));


        /*
        |--------------------------------------------------------------------------
        | Hash Password
        |--------------------------------------------------------------------------
        */

        $hashedPassword = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        if ($hashedPassword === false) {
            throw new RuntimeException(
                'Unable to secure the password.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Insert User
        |--------------------------------------------------------------------------
        */

        $sql = "
            INSERT INTO users
            (
                name,
                email,
                password
            )
            VALUES
            (
                :name,
                :email,
                :password
            )
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':password' => $hashedPassword
        ]);


        return (int) $this->db->lastInsertId();
    }


    /*
    |--------------------------------------------------------------------------
    | Find User By Email
    |--------------------------------------------------------------------------
    */

    public function findByEmail(
        string $email
    ): ?array {

        $email = strtolower(trim($email));

        $sql = "
            SELECT
                id,
                name,
                email,
                password,
                created_at
            FROM users
            WHERE email = :email
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':email' => $email
        ]);

        $user = $stmt->fetch();

        return $user ?: null;
    }


    /*
    |--------------------------------------------------------------------------
    | Find User By ID
    |--------------------------------------------------------------------------
    */

    public function findById(
        int $id
    ): ?array {

        if ($id <= 0) {
            return null;
        }

        $sql = "
            SELECT
                id,
                name,
                email,
                password,
                created_at
            FROM users
            WHERE id = :id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id' => $id
        ]);

        $user = $stmt->fetch();

        return $user ?: null;
    }


    /*
    |--------------------------------------------------------------------------
    | Check Email Exists
    |--------------------------------------------------------------------------
    */

    public function emailExists(
        string $email,
        ?int $excludeId = null
    ): bool {

        $email = strtolower(trim($email));

        $sql = "
            SELECT COUNT(*)
            FROM users
            WHERE email = :email
        ";

        $params = [
            ':email' => $email
        ];


        if ($excludeId !== null) {

            $sql .= "
                AND id != :exclude_id
            ";

            $params[':exclude_id'] =
                $excludeId;
        }


        $stmt = $this->db->prepare($sql);

        $stmt->execute($params);

        return (int) $stmt->fetchColumn() > 0;
    }


    /*
    |--------------------------------------------------------------------------
    | Authenticate User
    |--------------------------------------------------------------------------
    */

    public function authenticate(
        string $email,
        string $password
    ): ?array {

        $user = $this->findByEmail($email);

        if ($user === null) {
            return null;
        }


        /*
        |--------------------------------------------------------------------------
        | Verify Password
        |--------------------------------------------------------------------------
        */

        if (
            !isset($user['password'])
            || !is_string($user['password'])
            || !password_verify(
                $password,
                $user['password']
            )
        ) {
            return null;
        }


        /*
        |--------------------------------------------------------------------------
        | Upgrade Older Password Hash
        |--------------------------------------------------------------------------
        */

        if (
            password_needs_rehash(
                $user['password'],
                PASSWORD_DEFAULT
            )
        ) {

            $newHash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            if ($newHash !== false) {

                $stmt = $this->db->prepare("
                    UPDATE users
                    SET password = :password
                    WHERE id = :id
                ");

                $stmt->execute([
                    ':password' => $newHash,
                    ':id' => (int) $user['id']
                ]);
            }
        }


        return $user;
    }
}