<?php

declare(strict_types=1);

class Analytics
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
    | Spending By Category
    |--------------------------------------------------------------------------
    */

    public function getSpendingByCategory(
        int $userId
    ): array {

        $sql = "
            SELECT
                COALESCE(
                    c.name,
                    'Uncategorized'
                ) AS category_name,

                COALESCE(
                    SUM(e.amount),
                    0
                ) AS total

            FROM expenses e

            LEFT JOIN categories c
                ON e.category_id = c.id

            WHERE
                e.user_id = :user_id

            GROUP BY
                e.category_id,
                c.name

            ORDER BY
                total DESC
        ";

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute([
            ':user_id' =>
                $userId
        ]);

        return $stmt->fetchAll();
    }


    /*
    |--------------------------------------------------------------------------
    | Monthly Spending
    |--------------------------------------------------------------------------
    */

    public function getMonthlySpending(
        int $userId,
        int $months = 6
    ): array {

        /*
        |--------------------------------------------------------------------------
        | Limit Month Range
        |--------------------------------------------------------------------------
        */

        if ($months < 1) {
            $months = 1;
        }

        if ($months > 24) {
            $months = 24;
        }


        /*
        |--------------------------------------------------------------------------
        | Query
        |--------------------------------------------------------------------------
        */

        $sql = "
            SELECT

                DATE_FORMAT(
                    expense_date,
                    '%Y-%m'
                ) AS month,

                COALESCE(
                    SUM(amount),
                    0
                ) AS total

            FROM expenses

            WHERE
                user_id = :user_id

                AND expense_date >= DATE_SUB(
                    CURDATE(),
                    INTERVAL {$months} MONTH
                )

            GROUP BY
                DATE_FORMAT(
                    expense_date,
                    '%Y-%m'
                )

            ORDER BY
                month ASC
        ";

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute([
            ':user_id' =>
                $userId
        ]);

        return $stmt->fetchAll();
    }


    /*
    |--------------------------------------------------------------------------
    | Daily Spending For Current Month
    |--------------------------------------------------------------------------
    */

    public function getDailySpending(
        int $userId
    ): array {

        $sql = "
            SELECT

                expense_date AS date,

                COALESCE(
                    SUM(amount),
                    0
                ) AS total

            FROM expenses

            WHERE
                user_id = :user_id

                AND YEAR(expense_date)
                    = YEAR(CURDATE())

                AND MONTH(expense_date)
                    = MONTH(CURDATE())

            GROUP BY
                expense_date

            ORDER BY
                expense_date ASC
        ";

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute([
            ':user_id' =>
                $userId
        ]);

        return $stmt->fetchAll();
    }


    /*
    |--------------------------------------------------------------------------
    | Expense Count
    |--------------------------------------------------------------------------
    */

    public function getExpenseCount(
        int $userId
    ): int {

        $sql = "
            SELECT COUNT(*)

            FROM expenses

            WHERE
                user_id = :user_id
        ";

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute([
            ':user_id' =>
                $userId
        ]);

        return (int) $stmt->fetchColumn();
    }


    /*
    |--------------------------------------------------------------------------
    | Average Expense
    |--------------------------------------------------------------------------
    */

    public function getAverageExpense(
        int $userId
    ): float {

        $sql = "
            SELECT COALESCE(
                AVG(amount),
                0
            )

            FROM expenses

            WHERE
                user_id = :user_id
        ";

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute([
            ':user_id' =>
                $userId
        ]);

        return (float) $stmt->fetchColumn();
    }


    /*
    |--------------------------------------------------------------------------
    | Highest Expense
    |--------------------------------------------------------------------------
    */

    public function getHighestExpense(
        int $userId
    ): float {

        $sql = "
            SELECT COALESCE(
                MAX(amount),
                0
            )

            FROM expenses

            WHERE
                user_id = :user_id
        ";

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute([
            ':user_id' =>
                $userId
        ]);

        return (float) $stmt->fetchColumn();
    }


    /*
    |--------------------------------------------------------------------------
    | Current Month Expense Count
    |--------------------------------------------------------------------------
    */

    public function getCurrentMonthCount(
        int $userId
    ): int {

        $sql = "
            SELECT COUNT(*)

            FROM expenses

            WHERE
                user_id = :user_id

                AND YEAR(expense_date)
                    = YEAR(CURDATE())

                AND MONTH(expense_date)
                    = MONTH(CURDATE())
        ";

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute([
            ':user_id' =>
                $userId
        ]);

        return (int) $stmt->fetchColumn();
    }


    /*
    |--------------------------------------------------------------------------
    | Current Month Average
    |--------------------------------------------------------------------------
    */

    public function getCurrentMonthAverage(
        int $userId
    ): float {

        $sql = "
            SELECT COALESCE(
                AVG(amount),
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

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute([
            ':user_id' =>
                $userId
        ]);

        return (float) $stmt->fetchColumn();
    }
}