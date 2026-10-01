<?php

declare(strict_types=1);

class Expense
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
    | Create Expense
    |--------------------------------------------------------------------------
    */

    public function create(
        int $userId,
        int $categoryId,
        float $amount,
        string $description,
        string $expenseDate
    ): int {

        $sql = "
            INSERT INTO expenses
            (
                user_id,
                category_id,
                amount,
                description,
                expense_date
            )
            VALUES
            (
                :user_id,
                :category_id,
                :amount,
                :description,
                :expense_date
            )
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':user_id' => $userId,
            ':category_id' => $categoryId,
            ':amount' => $amount,
            ':description' => $description,
            ':expense_date' => $expenseDate
        ]);

        return (int) $this->db->lastInsertId();
    }


    /*
    |--------------------------------------------------------------------------
    | Find Expense By ID
    |--------------------------------------------------------------------------
    */

    public function findById(
        int $expenseId,
        int $userId
    ): ?array {

        $sql = "
            SELECT
                e.*,
                c.name AS category_name

            FROM expenses e

            LEFT JOIN categories c
                ON e.category_id = c.id

            WHERE
                e.id = :expense_id
                AND e.user_id = :user_id

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':expense_id' => $expenseId,
            ':user_id' => $userId
        ]);

        $expense = $stmt->fetch();

        return $expense ?: null;
    }


    /*
    |--------------------------------------------------------------------------
    | Get All Expenses For User
    |--------------------------------------------------------------------------
    */

    public function getAllByUser(
        int $userId
    ): array {

        $sql = "
            SELECT
                e.*,
                c.name AS category_name

            FROM expenses e

            LEFT JOIN categories c
                ON e.category_id = c.id

            WHERE
                e.user_id = :user_id

            ORDER BY
                e.expense_date DESC,
                e.id DESC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':user_id' => $userId
        ]);

        return $stmt->fetchAll();
    }


    /*
    |--------------------------------------------------------------------------
    | Get Filtered Expenses
    |--------------------------------------------------------------------------
    |
    | Supports:
    | - Category
    | - Month
    | - Search
    | - Sorting
    |
    */

    public function getFilteredByUser(
        int $userId,
        ?int $categoryId = null,
        ?string $month = null,
        ?string $search = null,
        string $sort = 'newest'
    ): array {

        $sql = "
            SELECT
                e.*,
                c.name AS category_name

            FROM expenses e

            LEFT JOIN categories c
                ON e.category_id = c.id

            WHERE
                e.user_id = :user_id
        ";

        $params = [
            ':user_id' => $userId
        ];


        /*
        |--------------------------------------------------------------------------
        | Category Filter
        |--------------------------------------------------------------------------
        */

        if (
            $categoryId !== null
            && $categoryId > 0
        ) {

            $sql .= "
                AND e.category_id = :category_id
            ";

            $params[':category_id'] = $categoryId;
        }


        /*
        |--------------------------------------------------------------------------
        | Month Filter
        |--------------------------------------------------------------------------
        */

        if (
            $month !== null
            && $month !== ''
        ) {

            $sql .= "
                AND DATE_FORMAT(
                    e.expense_date,
                    '%Y-%m'
                ) = :month
            ";

            $params[':month'] = $month;
        }


        /*
        |--------------------------------------------------------------------------
        | Search Filter
        |--------------------------------------------------------------------------
        |
        | Search description and category name.
        |
        */

        if (
            $search !== null
            && $search !== ''
        ) {

            $sql .= "
                AND (
                    e.description LIKE :search
                    OR c.name LIKE :search
                )
            ";

            $params[':search'] =
                '%' . $search . '%';
        }


        /*
        |--------------------------------------------------------------------------
        | Sorting
        |--------------------------------------------------------------------------
        |
        | Never directly insert user-provided sorting
        | values into SQL.
        |
        */

        $orderBy = match ($sort) {

            'oldest' => "
                e.expense_date ASC,
                e.id ASC
            ",

            'highest' => "
                e.amount DESC,
                e.expense_date DESC,
                e.id DESC
            ",

            'lowest' => "
                e.amount ASC,
                e.expense_date DESC,
                e.id DESC
            ",

            default => "
                e.expense_date DESC,
                e.id DESC
            "
        };


        $sql .= "
            ORDER BY
                {$orderBy}
        ";


        /*
        |--------------------------------------------------------------------------
        | Execute Query
        |--------------------------------------------------------------------------
        */

        $stmt = $this->db->prepare($sql);

        $stmt->execute($params);

        return $stmt->fetchAll();
    }


    /*
    |--------------------------------------------------------------------------
    | Update Expense
    |--------------------------------------------------------------------------
    */

    public function update(
        int $expenseId,
        int $userId,
        int $categoryId,
        float $amount,
        string $description,
        string $expenseDate
    ): bool {

        $sql = "
            UPDATE expenses

            SET
                category_id = :category_id,
                amount = :amount,
                description = :description,
                expense_date = :expense_date

            WHERE
                id = :expense_id
                AND user_id = :user_id
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':category_id' => $categoryId,
            ':amount' => $amount,
            ':description' => $description,
            ':expense_date' => $expenseDate,
            ':expense_id' => $expenseId,
            ':user_id' => $userId
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Delete Expense
    |--------------------------------------------------------------------------
    */

    public function delete(
        int $expenseId,
        int $userId
    ): bool {

        $sql = "
            DELETE FROM expenses

            WHERE
                id = :expense_id
                AND user_id = :user_id
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':expense_id' => $expenseId,
            ':user_id' => $userId
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Get Total Expenses
    |--------------------------------------------------------------------------
    */

    public function getTotalByUser(
        int $userId
    ): float {

        $sql = "
            SELECT COALESCE(
                SUM(amount),
                0
            )

            FROM expenses

            WHERE
                user_id = :user_id
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':user_id' => $userId
        ]);

        return (float) $stmt->fetchColumn();
    }


    /*
    |--------------------------------------------------------------------------
    | Get Current Month Total
    |--------------------------------------------------------------------------
    */

    public function getCurrentMonthTotal(
        int $userId
    ): float {

        $sql = "
            SELECT COALESCE(
                SUM(amount),
                0
            )

            FROM expenses

            WHERE
                user_id = :user_id

                AND YEAR(expense_date)
                    = YEAR(CURDATE())

                AND MONTH(expense_date)
                    = MONTH(CURDATE())
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':user_id' => $userId
        ]);

        return (float) $stmt->fetchColumn();
    }
}