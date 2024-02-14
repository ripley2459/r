<?php

/**
 * Wrapper class that can be used to handle simple MySQL queries.
 * Feel free to use this file in your projects, but please be aware that it comes with no warranties or guarantees.
 * You are responsible for testing and using these functions at your own risk.
 * @author Cyril Neveu
 * @link https://github.com/ripley2459/r
 * @version 5
 */
class RDB
{
    const COMPARE = ['=', '!=', '<', '<>', '>', '<=', '>='];
    const ORDER_BY = ['ASC', 'DESC', 'RAND()'];
    private static array $_args;
    private static PDO $_pdo;
    private string $_operation;
    private string $_table;
    private array $_columns;
    private string $_statement = R::EMPTY;
    private array $_data;
    private array $_where = [];
    private array $_join = [];
    private array $_union = [];
    private int $_wherePointer = 0;
    private string $_orderBy;
    private ?int $_limit;
    private ?int $_offset;
    private int $_whereIndex = 0;

    private function __construct(string $operation, string $table, array $columns, array $data)
    {
        $this->_operation = $operation;
        $this->_table = $table;
        $this->_data = $data;
        $this->_columns = $columns;
    }

    /**
     * Start method initiates the database connection using the provided configuration.
     * This method sets up a connection to a MySQL database with the specified parameters:
     * - 'host': The hostname.
     * - 'dbname': The name of the database to connect to.
     * - 'charset': The character encoding used for the connection.
     * - 'user': The username for the database connection.
     * - 'password': The password for the database user.
     * - 'sqlPath': The path to SQL files (used for database setup and scripts execution).
     * If the connection is successfully established, it returns an instance of the RDB class,
     * which can be used to interact with the database.
     * If any errors occur during the connection attempt, it throws a PDOException with an error message.
     * @param array $args An associative array containing the necessary configuration parameters.
     * @param array $options The DSN options.
     * @throws PDOException If a connection error occurs, a PDOException is thrown.
     */
    public static function start(array $args, array $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => true]): void
    {
        R::checkArgument(R::allKeysExist($args, ['host', 'dbname', 'charset', 'user', 'password', 'sqlPath']));
        self::$_args = $args;
        try {
            $dsn = 'mysql:host=' . self::$_args['host'] . ';dbname=' . self::$_args['dbname'] . ';charset=' . self::$_args['charset'];
            $dsn_options = $options;
            self::$_pdo = new PDO($dsn, self::$_args['user'], self::$_args['password'], $dsn_options);
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage());
        }
    }

    /**
     * Inserts data into a table.
     * @param string $table The name of the table to insert data into.
     * @param array $columns An array of column names to specify the columns in the database table. In the shape of ['name', 'gender', 'age'].
     * @param array $data A 2D array containing the data to be inserted into the table. In the shape of [['john', 'marie', 'isac'], ['male', 'female', 'male'], ['31', '27', '45']].
     * @return RDB - When executed, it will return an array of int representing the id(s) of the inserted elements.
     */
    public static function insert(string $table, array $columns, array $data): RDB
    {
        R::checkArgument(!R::blank($columns) && !R::blank($data) && count($columns) == count($data));
        return new RDB('insert', $table, $columns, $data);
    }

    /**
     * Updates data of a table.
     * @param string $table The name of the table to update data from.
     * @param array $columns The array of column names that will be affected. In the shape of ['name', 'gender', 'age'].
     * @param array $data A 2D array containing the data to be used for the update process. In the shape of [['john', 'marie', 'isac'], ['male', 'female', 'male'], ['31', '27', '45']].
     * @return RDB - When executed, it will return a boolean. True means that the targeted table and rows were updated successfully.
     */
    public static function update(string $table, array $columns, array $data): RDB
    {
        R::checkArgument(!R::blank($columns) && !R::blank($data) && count($columns) == count($data));
        return new RDB('update', $table, $columns, $data);
    }

    /**
     * Selects data from a table.
     * @param string $table The name of the table to delete data from.
     * @return RDB - When executed, it will return a boolean. True means that the targeted data were removed successfully.
     */
    public static function select(string $table, string ...$columns): RDB
    {
        R::checkArgument(!R::blank($columns));
        return new RDB('select', $table, $columns, []);
    }

    /**
     * Delete data from a table.
     * @param string $table The name of the table to delete data from.
     * @return RDB - When executed, it will return a boolean. True means that the targeted data were removed successfully.
     */
    public static function delete(string $table): RDB
    {
        return new RDB('delete', $table, [], []);
    }

    /**
     * Checks and creates the associated table if necessary.
     * @param string $table The table to check the existence.
     * @param array $structure An array representing the structure of the table, used to create the table.
     * @return RDB - When executed, it will return a boolean. True means that a table with the provided name exists in the database of a table with the provided information have been successfully created.
     */
    public static function check(string $table, array $structure): RDB
    {
        return new RDB('check', $table, [], $structure);
    }

    /**
     * Drops a database table specified by the given table name.
     * @param string $table The name of the table to be dropped.
     * @return RDB - When executed, it will return a boolean. True means the table was successfully deleted from the database.
     */
    public static function drop(string $table): RDB
    {
        return new RDB('drop', $table, [], []);
    }

    /**
     * Truncate a database table specified by the given table name.
     * @param string $table The name of the table to be truncated.
     * @return RDB - When executed, it will return a boolean. True means the table was successfully deleted from the database.
     */
    public static function truncate(string $table): RDB
    {
        return new RDB('truncate', $table, [], []);
    }

    /**
     * Determine the PDO parameter type for a given value.
     * If the input type is not recognized, the default type returned is PDO::PARAM_STR.
     * @param mixed $param The parameter for which to determine the PDO parameter type.
     * @return int The PDO parameter type (PDO::PARAM_STR, PDO::PARAM_INT, or PDO::PARAM_BOOL).
     */
    public static function getType(mixed $param): int
    {
        if (is_string($param))
            return PDO::PARAM_STR;
        if (is_int($param))
            return PDO::PARAM_INT;
        if (is_bool($param))
            return PDO::PARAM_BOOL;
        return PDO::PARAM_STR;
    }

    /**
     * @return PDO
     */
    public static function &getPdo(): PDO
    {
        return self::$_pdo;
    }

    public function innerJoin(string $table, string $on): RDB
    {
        $this->join_IMPL('INNER JOIN', $table, $on);
        return $this;
    }

    private function join_IMPL(string $type, string $table, string $on): void
    {
        R::checkArgument($this->_operation == 'select');
        $this->_join[] = R::concat(R::SPACE, $type, $table, 'ON', $on);
    }

    public function leftJoin(string $table, string $on): RDB
    {
        $this->join_IMPL('LEFT JOIN', $on, $table);
        return $this;
    }

    public function rightJoin(string $table, string $on): RDB
    {
        $this->join_IMPL('RIGHT JOIN', $on, $table);
        return $this;
    }

    public function fullJoin(string $table, string $on): RDB
    {
        $this->join_IMPL('FULL JOIN', $table, $on);
        return $this;
    }

    public function or(): RDB
    {
        $this->_wherePointer++;
        return $this;
    }

    public function where(string $column, string $comparator = R::EMPTY, mixed $value = null): RDB_Where|RDB
    {
        $c = new RDB_Where($this, $this->_whereIndex++, $this->_table, $column, $comparator, $value);
        $this->_where[$this->_wherePointer][] = $c;
        return R::blank($comparator) ? $c : $this;
    }

    public function orderBy(string $column, string $order): RDB
    {
        R::whitelist($order, self::ORDER_BY);
        $this->_orderBy = $order == 'RAND()' ? 'RAND()' : $column . R::SPACE . $order;
        return $this;
    }

    public function limit(int $limit, int $offset = null): RDB
    {
        $this->_limit = $limit;
        $this->_offset = $offset;
        return $this;
    }

    public function union(RDB $sub): RDB
    {
        $this->union_IMPL('UNION', $sub);
        return $this;
    }

    private function union_IMPL(string $type, RDB $sub): void
    {
        R::checkArgument($this->_operation == 'select');
        $this->_union[] = [$type, $sub];
    }

    public function unionAll(RDB $sub): RDB
    {
        $this->union_IMPL('UNION ALL', $sub);
        return $this;
    }

    private function execute_SELECT(PDOStatement &$stmt, bool $ol = true): PDOStatement
    {
        $this->bindValues_SELECT($stmt, $ol);
        $stmt->execute();
        return $stmt;
    }

    public function bindValues_SELECT(PDOStatement &$stmt, bool $ol = true): void
    {
        if (!empty($this->_where)) {
            for ($i = 0; $i < count($this->_where); $i++) {
                foreach ($this->_where[$i] as $condition)
                    if (is_array($condition))
                        $condition[2]->bindValues($stmt);
                    else $condition->bindValues($stmt);
            }
        }

        foreach ($this->_union as $union)
            $union[1]->bindValues(false);

        if ($ol) {
            if (isset($this->_limit) && isset($this->_offset)) {
                $stmt->bindValue(':limit', $this->_limit, PDO::PARAM_INT);
                $stmt->bindValue(':offset', $this->_offset, PDO::PARAM_INT);
            } else if (isset($this->_limit))
                $stmt->bindValue(':limit', $this->_limit, PDO::PARAM_INT);
        }
    }

    /**
     * Prepares and executes the request.
     * @return PDOStatement|bool|array The return type depends on the request.
     */
    public function execute(): PDOStatement|bool|array
    {
        return $this->execute_IMPL(true);
    }

    /**
     * @param bool $ol Used in a recursive context. You shouldn't have to manually set it to false unless you want to remove the ORDER BY and LIMIT clauses from your request entirely.
     * @return PDOStatement|bool|array
     */
    private function execute_IMPL(bool $ol = true): PDOStatement|bool|array
    {
        if (R::blank($this->_statement))
            $this->_statement = $this->getStatement();
        $stmt = self::$_pdo->prepare($this->_statement);

        return match ($this->_operation) {
            'delete' => $this->execute_DELETE($stmt),
            'select' => $this->execute_SELECT($stmt, $ol),
            'update' => $this->execute_UPDATE($stmt),
            'insert' => $this->execute_INSERT($stmt),
            'show' => $this->execute_SHOW($stmt),
            'check' => $this->execute_CHECK($stmt),
            default => $stmt->execute() && $stmt->closeCursor(),
        };
    }

    /**
     * @return string The request with all markers, ready to be prepared.
     */
    public function getStatement(): string
    {
        return $this->getStatement_IMPL(true);
    }

    /**
     * @param bool $ol Used in a recursive context. You shouldn't have to manually set it to false unless you want to remove the ORDER BY and LIMIT clauses from your request entirely.
     * @return string
     * @see getStatement()
     */
    private function getStatement_IMPL(bool $ol = true): string
    {
        if (!R::blank($this->_statement))
            return $this->_statement;

        return match ($this->_operation) {
            'truncate' => 'TRUNCATE TABLE ' . $this->_table,
            'drop' => 'DROP TABLE ' . $this->_table,
            'show' => 'SHOW TABLES LIKE \'' . $this->_table . '\'',
            'check' => 'CREATE TABLE ' . $this->_table . ' (' . implode(', ', $this->_data) . ')',
            'delete' => $this->getStatement_DELETE(),
            'select' => $this->getStatement_SELECT($ol),
            'update' => $this->getStatement_UPDATE(),
            'insert' => $this->getStatement_INSERT(),
            default => R::EMPTY,
        };
    }

    private function getStatement_DELETE(): string
    {
        $stmt = R::concat(R::SPACE, 'DELETE', 'FROM', $this->_table);
        R::append($stmt, R::SPACE, $this->getStatement_WHERE());
        return $stmt;
    }

    private function getStatement_WHERE(): string
    {
        $stmt = R::EMPTY;

        if (!empty($this->_where)) {
            $stmt = 'WHERE';
            for ($i = 0; $i < count($this->_where); $i++) {
                $conditions = R::EMPTY;
                foreach ($this->_where[$i] as $condition)
                    R::append($conditions, ' AND ', $condition->getStatement());
                if (count($this->_where[$i]) > 1)
                    R::append($stmt, R::EMPTY, ' (', $conditions, ')');
                else R::append($stmt, R::SPACE, $conditions);

                if (isset($this->_where[$i + 1]))
                    R::append($stmt, R::SPACE, 'OR');
            }
        }

        return $stmt;
    }

    private function getStatement_SELECT(bool $ol = true): string
    {
        $stmt = R::concat(R::SPACE, 'SELECT', implode(', ', $this->_columns), 'FROM', $this->_table);

        foreach ($this->_join as $join)
            R::append($stmt, R::SPACE, $join);

        R::append($stmt, R::SPACE, $this->getStatement_WHERE());

        foreach ($this->_union as $union)
            R::append($stmt, R::SPACE, $union[0] . R::SPACE . $union[1]->getStatement_IMPL(false));

        if ($ol) {
            if (isset($this->_orderBy) && !R::blank($this->_orderBy))
                R::append($stmt, R::SPACE, 'ORDER BY', $this->_orderBy);
            if (isset($this->_limit) && isset($this->_offset))
                R::append($stmt, R::SPACE, 'LIMIT', ':limit', ',', ':offset');
            else if (isset($this->_limit))
                R::append($stmt, R::SPACE, 'LIMIT', ':limit');
        }

        return $stmt;
    }

    private function getStatement_UPDATE(): string
    {
        $vars = R::EMPTY;
        for ($i = 0; $i < count($this->_columns); $i++)
            R::append($vars, ', ', $this->_columns[$i] . ' = :' . $this->_columns[$i]);
        $stmt = R::concat(R::SPACE, R::SPACE, 'UPDATE', $this->_table, 'SET', $vars);

        R::append($stmt, R::SPACE, $this->getStatement_WHERE());

        return $stmt;
    }

    private function getStatement_INSERT(): string
    {
        $columns = $this->_columns;
        $s = 'INSERT INTO ' . $this->_table . ' (' . R::concat(', ', $columns) . ') VALUES ';
        R::prefix(':', $columns);
        return $s . '(' . R::concat(', ', $columns) . ')';
    }

    private function execute_DELETE(PDOStatement &$stmt): bool
    {
        if (!empty($this->_where)) {
            for ($i = 0; $i < count($this->_where); $i++) {
                foreach ($this->_where[$i] as $condition)
                    if (is_array($condition))
                        $condition[2]->bindValues($stmt);
                    else $condition->bindValues($stmt);
            }
        }

        return $stmt->execute() && $stmt->closeCursor();;
    }

    private function execute_UPDATE(PDOStatement &$stmt): bool // TODO Multiple update!
    {
        for ($i = 0; $i < count($this->_columns); $i++)
            $stmt->bindValue(':' . $this->_columns[$i], $this->_data[$i], PDO::PARAM_STR);

        if (!empty($this->_where)) {
            for ($i = 0; $i < count($this->_where); $i++) {
                foreach ($this->_where[$i] as $condition)
                    if (is_array($condition))
                        $condition[2]->bindValues($stmt);
                    else $condition->bindValues($stmt);
            }
        }

        return $stmt->execute() && $stmt->closeCursor();
    }

    private function execute_INSERT(PDOStatement $stmt): array
    {
        $amount = R::isSquareArray($this->_data);
        R::checkArgument($amount != false);
        $columns = $this->_columns;
        R::prefix(':', $columns);

        $inserted = [];
        for ($j = 0; $j < $amount; $j++) {
            for ($i = 0; $i < count($columns); $i++)
                $stmt->bindValue($columns[$i], $this->_data[$i][$j]);
            $stmt->execute();
            $inserted[] = self::$_pdo->lastInsertId();
        }

        $stmt->closeCursor();
        return $inserted;
    }

    private function execute_SHOW(PDOStatement $stmt): bool
    {
        $stmt->execute();
        $count = $stmt->rowCount() > 0;
        $stmt->closeCursor();
        return $count;
    }

    private function execute_CHECK(PDOStatement $stmt): bool
    {
        if (self::show($this->_table)->execute())
            return true;
        return $stmt->execute() && $stmt->closeCursor();
    }

    /**
     * Checks if a table exists in the database.
     * @param string $table The name of the table to check for existence.
     * @return RDB - When executed, it will return a boolean. True means that a table with the provided name exists in the database.
     */
    public static function show(string $table): RDB
    {
        return new RDB('show', $table, [], []);
    }
}