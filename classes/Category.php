<?php

declare(strict_types=1);

class Category
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
    | Get All Categories For User
    |--------------------------------------------------------------------------
    */

    public function getAllByUser(
        int $userId
    ): array {

        $sql = "
            SELECT
                id,
                name,
                created_at

            FROM categories

            WHERE user_id = :user_id

            ORDER BY name ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':user_id' => $userId
        ]);

        return $stmt->fetchAll();
    }


    /*
    |--------------------------------------------------------------------------
    | Find Category By ID
    |--------------------------------------------------------------------------
    */

    public function findById(
        int $categoryId,
        int $userId
    ): ?array {

        $sql = "
            SELECT
                id,
                name,
                created_at

            FROM categories

            WHERE
                id = :category_id
                AND user_id = :user_id

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':category_id' => $categoryId,
            ':user_id' => $userId
        ]);

        $category = $stmt->fetch();

        return $category ?: null;
    }


    /*
    |--------------------------------------------------------------------------
    | Check Duplicate Category Name
    |--------------------------------------------------------------------------
    */

    public function existsByName(
        string $name,
        int $userId,
        ?int $excludeId = null
    ): bool {

        $sql = "
            SELECT COUNT(*)

            FROM categories

            WHERE
                user_id = :user_id
                AND LOWER(name) = LOWER(:name)
        ";

        if ($excludeId !== null) {

            $sql .= "
                AND id != :exclude_id
            ";
        }

        $stmt = $this->db->prepare($sql);

        $params = [
            ':user_id' => $userId,
            ':name' => $name
        ];

        if ($excludeId !== null) {

            $params[':exclude_id'] = $excludeId;
        }

        $stmt->execute($params);

        return (int) $stmt->fetchColumn() > 0;
    }


    /*
    |--------------------------------------------------------------------------
    | Create Category
    |--------------------------------------------------------------------------
    */

    public function create(
        int $userId,
        string $name
    ): int {

        $sql = "
            INSERT INTO categories
            (
                user_id,
                name
            )
            VALUES
            (
                :user_id,
                :name
            )
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':user_id' => $userId,
            ':name' => $name
        ]);

        return (int) $this->db->lastInsertId();
    }


    /*
    |--------------------------------------------------------------------------
    | Update Category
    |--------------------------------------------------------------------------
    */

    public function update(
        int $categoryId,
        int $userId,
        string $name
    ): bool {

        $sql = "
            UPDATE categories

            SET
                name = :name

            WHERE
                id = :category_id
                AND user_id = :user_id
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':name' => $name,
            ':category_id' => $categoryId,
            ':user_id' => $userId
        ]);

        return $stmt->rowCount() > 0;
    }


    /*
    |--------------------------------------------------------------------------
    | Check Whether Category Is Used
    |--------------------------------------------------------------------------
    |
    | A category should not be deleted if it is used by:
    |
    | 1. Expenses
    | 2. Budgets
    |
    */

    public function isUsed(
        int $categoryId,
        int $userId
    ): bool {

        $sql = "
            SELECT
                (
                    SELECT COUNT(*)
                    FROM expenses
                    WHERE
                        category_id = :category_id_expense
                        AND user_id = :user_id_expense
                )
                +
                (
                    SELECT COUNT(*)
                    FROM budgets
                    WHERE
                        category_id = :category_id_budget
                        AND user_id = :user_id_budget
                )
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':category_id_expense' => $categoryId,
            ':user_id_expense' => $userId,
            ':category_id_budget' => $categoryId,
            ':user_id_budget' => $userId
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }


    /*
    |--------------------------------------------------------------------------
    | Delete Category
    |--------------------------------------------------------------------------
    */

    public function delete(
        int $categoryId,
        int $userId
    ): bool {

        /*
        |--------------------------------------------------------------------------
        | Do Not Delete Used Categories
        |--------------------------------------------------------------------------
        */

        if ($this->isUsed($categoryId, $userId)) {

            return false;
        }


        $sql = "
            DELETE FROM categories

            WHERE
                id = :category_id
                AND user_id = :user_id
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':category_id' => $categoryId,
            ':user_id' => $userId
        ]);

        return $stmt->rowCount() > 0;
    }
}