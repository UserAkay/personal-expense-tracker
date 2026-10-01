<?php

declare(strict_types=1);

class Budget
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
    | Create Budget
    |--------------------------------------------------------------------------
    */

    public function create(
        int $userId,
        int $categoryId,
        float $amount,
        string $budgetMonth
    ): int {

        $sql = "
            INSERT INTO budgets
            (
                user_id,
                category_id,
                amount,
                month_year
            )
            VALUES
            (
                :user_id,
                :category_id,
                :amount,
                :month_year
            )
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':user_id' => $userId,
            ':category_id' => $categoryId,
            ':amount' => $amount,
            ':month_year' => $budgetMonth
        ]);

        return (int) $this->db->lastInsertId();
    }


    /*
    |--------------------------------------------------------------------------
    | Find Budget By ID
    |--------------------------------------------------------------------------
    */

    public function findById(
        int $budgetId,
        int $userId
    ): ?array {

        $sql = "
            SELECT
                b.*,
                c.name AS category_name

            FROM budgets b

            INNER JOIN categories c
                ON b.category_id = c.id

            WHERE
                b.id = :budget_id
                AND b.user_id = :user_id

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':budget_id' => $budgetId,
            ':user_id' => $userId
        ]);

        $budget = $stmt->fetch();

        return $budget ?: null;
    }


    /*
    |--------------------------------------------------------------------------
    | Check Duplicate Budget
    |--------------------------------------------------------------------------
    */

    public function exists(
        int $userId,
        int $categoryId,
        string $budgetMonth,
        ?int $excludeId = null
    ): bool {

        $sql = "
            SELECT COUNT(*)

            FROM budgets

            WHERE
                user_id = :user_id
                AND category_id = :category_id
                AND month_year = :month_year
        ";

        if ($excludeId !== null) {

            $sql .= "
                AND id != :exclude_id
            ";
        }

        $stmt = $this->db->prepare($sql);

        $params = [
            ':user_id' => $userId,
            ':category_id' => $categoryId,
            ':month_year' => $budgetMonth
        ];

        if ($excludeId !== null) {

            $params[':exclude_id'] = $excludeId;
        }

        $stmt->execute($params);

        return (int) $stmt->fetchColumn() > 0;
    }


    /*
    |--------------------------------------------------------------------------
    | Get All Budgets For User
    |--------------------------------------------------------------------------
    */

    public function getAllByUser(
        int $userId,
        ?string $budgetMonth = null
    ): array {

        if ($budgetMonth === null) {

            $budgetMonth = date('Y-m');
        }


        $sql = "
            SELECT

                b.id,
                b.user_id,
                b.category_id,
                b.amount,
                b.month_year,
                b.created_at,

                c.name AS category_name,

                COALESCE(
                    (
                        SELECT SUM(e.amount)

                        FROM expenses e

                        WHERE
                            e.user_id = b.user_id

                            AND e.category_id =
                                b.category_id

                            AND DATE_FORMAT(
                                e.expense_date,
                                '%Y-%m'
                            ) = b.month_year
                    ),
                    0
                ) AS spent

            FROM budgets b

            INNER JOIN categories c
                ON b.category_id = c.id

            WHERE
                b.user_id = :user_id
                AND b.month_year = :month_year

            ORDER BY
                c.name ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':user_id' => $userId,
            ':month_year' => $budgetMonth
        ]);

        return $stmt->fetchAll();
    }


    /*
    |--------------------------------------------------------------------------
    | Update Budget
    |--------------------------------------------------------------------------
    */

    public function update(
        int $budgetId,
        int $userId,
        int $categoryId,
        float $amount,
        string $budgetMonth
    ): bool {

        $sql = "
            UPDATE budgets

            SET
                category_id = :category_id,
                amount = :amount,
                month_year = :month_year

            WHERE
                id = :budget_id
                AND user_id = :user_id
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':category_id' => $categoryId,
            ':amount' => $amount,
            ':month_year' => $budgetMonth,
            ':budget_id' => $budgetId,
            ':user_id' => $userId
        ]);

        /*
        |--------------------------------------------------------------------------
        | execute() only means SQL ran successfully.
        |
        | An unchanged record gives rowCount() = 0, but the update
        | itself is still valid. Therefore we return true if the
        | budget still belongs to the user.
        |--------------------------------------------------------------------------
        */

        return true;
    }


    /*
    |--------------------------------------------------------------------------
    | Delete Budget
    |--------------------------------------------------------------------------
    */

    public function delete(
        int $budgetId,
        int $userId
    ): bool {

        $sql = "
            DELETE FROM budgets

            WHERE
                id = :budget_id
                AND user_id = :user_id
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':budget_id' => $budgetId,
            ':user_id' => $userId
        ]);

        return $stmt->rowCount() > 0;
    }
}