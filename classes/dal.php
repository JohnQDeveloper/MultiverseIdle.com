<?php

declare(strict_types=1);

class DAL
{
    public object $dbh;
    private object|false $statement;

    /**
     * @param object $db PDO database connection object
     */
    public function __construct(object $db)
    {
        $this->dbh = $db;
        $this->statement = false;
    }

    /**
     * Alias for write method
     *
     * @param string $query SQL query with placeholders
     * @param array<string, mixed>|null $preparedArray Parameters for prepared statement
     */
    public function w(string $query, ?array $preparedArray = null): bool
    {
        return $this->write($query, $preparedArray);
    }

    /**
     * Alias for read method
     *
     * @param string $query SQL query with placeholders
     * @param array<string, mixed>|null $preparedArray Parameters for prepared statement
     * @param int $fetchMode PDO fetch mode constant
     * @return array<int, array<string, mixed>>|false
     */
    public function r(string $query, ?array $preparedArray = null, int $fetchMode = PDO::FETCH_ASSOC): array|false
    {
        return $this->read($query, $preparedArray, $fetchMode);
    }

    /**
     * Get number of rows affected by last statement
     */
    public function rows_affected(): int
    {
        if ($this->statement === false) {
            return 0;
        }
        return $this->statement->rowCount();
    }

    /**
     * Get last inserted ID
     */
    public function last_insert_id(): string|false
    {
        return $this->dbh->lastInsertId();
    }

    /**
     * Execute a write query (INSERT, UPDATE, DELETE)
     *
     * @param string $query SQL query with placeholders
     * @param array<string, mixed>|null $preparedArray Parameters for prepared statement
     */
    public function write(string $query, ?array $preparedArray = null): bool
    {
        if ($this->dbh === false) {
            return false;
        }

        try {
            $this->statement = $this->dbh->prepare($query);

            if ($preparedArray === null) {
                $this->statement->execute();
            } else {
                $this->statement->execute($preparedArray);
            }

            return true;
        } catch (PDOException $e) {
            if (DEBUG) {
                echo $e . "<BR />";
            }
            return false;
        }
    }

    /**
     * Execute a read query (SELECT)
     *
     * @param string $query SQL query with placeholders
     * @param array<string, mixed>|null $preparedArray Parameters for prepared statement
     * @param int $fetchMode PDO fetch mode constant
     * @return array<int, array<string, mixed>>|false
     */
    public function read(string $query, ?array $preparedArray = null, int $fetchMode = PDO::FETCH_ASSOC): array|false
    {
        if ($this->dbh === false) {
            return false;
        }

        try {
            $this->statement = $this->dbh->prepare($query);

            if ($preparedArray === null) {
                $this->statement->execute();
            } else {
                $this->statement->execute($preparedArray);
            }

            return $this->statement->fetchAll($fetchMode);
        } catch (PDOException $e) {
            if (DEBUG) {
                echo $e . "<BR />";
            }
            return false;
        }
    }
}
