<?php

class RDB
{
    const COMPARE = ['=', '!=', '<', '<>', '>', '<=', '>='];
    const JOIN = ['INNER', 'LEFT', 'RIGHT', 'LEFT OUTER', 'RIGHT OUTER'];
    const ORDER_BY = ['ASC', 'DESC'/*, 'RAND()'*/];
    const AND_OR = ['AND', 'OR'];
    const LOGIC = ['ALL', 'AND', 'ANY', 'BETWEEN', 'EXISTS', 'IN', 'LIKE', 'NOT', 'OR', 'SOME'];
    private static array $_args;
    private static PDO $_pdo;
    private string $_operation;
    private string $_table;
    private array $_columns;
    private int $_limit = 0;
    private int $_offset = 0;
    private string $_orderBy = R::EMPTY;
    private array $_where = array();
    private ?PDOStatement $_request = null;

    private function __construct(string $operation, string $table, array $columns)
    {
        $this->_operation = $operation;
        $this->_table = self::$_args['prefix'] . $table;
        $this->_columns = $columns;
    }

    /**
     * Start method initiates the database connection using the provided configuration.
     *
     * This method sets up a connection to a MySQL database with the specified parameters:
     * - 'host': The hostname or IP address of the database server.
     * - 'dbname': The name of the database to connect to.
     * - 'charset': The character encoding used for the connection.
     * - 'user': The username for the database connection.
     * - 'password': The password for the database user.
     * - 'prefix': A database table prefix (if applicable).
     * - 'sqlPath': The path to SQL files (used for database setup and scripts execution).
     *
     * If the connection is successfully established, it returns an instance of the RDB class, which can be used to interact with the database.
     * If any errors occur during the connection attempt, it throws a PDOException with an error message.
     * @param array $args An associative array containing the necessary configuration parameters.
     * @throws PDOException If a connection error occurs, a PDOException is thrown.
     */
    public static function start(array $args): void
    {
        R::checkArgument(R::allKeysExist($args, ['host', 'dbname', 'charset', 'user', 'password', 'prefix', 'sqlPath']));
        self::$_args = $args;

        try {
            $dsn = 'mysql:host=' . self::$_args['host'] . ';dbname=' . self::$_args['dbname'] . ';charset=' . self::$_args['charset'];
            $dsn_options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => true];
            self::$_pdo = new PDO($dsn, self::$_args['user'], self::$_args['password'], $dsn_options);
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage());
        }
    }

    /**
     * Inserts data into a database table.
     * @param string $table The name of the database table to insert data into.
     * @param array $columns An array of column names to specify the columns in the database table. In the shape of ['name', 'gender', 'age'].
     * @param array $data A 2D array containing the data to be inserted into the table. In the shape of [['john', 'marie', 'isac'], ['male', 'female', 'male'], ['31', '27', '45']].
     * @return bool Returns true if the insertion is successful; otherwise, returns false.
     * @throws PDOException If any errors occur during the insertion process, they are caught and rethrown.
     */
    public static function insert(string $table, array $columns, array $data): bool
    {

        R::checkArgument(isset(self::$_pdo) && count($columns) == count($data) && !empty($data));
        $amount = R::isSquareArray($data);
        R::checkArgument($amount != false);

        $table = self::$_args['prefix'] . $table;
        $s = 'INSERT INTO ' . $table . ' (' . R::concat(', ', $columns) . ') VALUES ';
        R::prefix(':', $columns);
        $s .= '(' . R::concat(', ', $columns) . ')';
        $r = self::$_pdo->prepare($s);

        try {
            for ($j = 0; $j < $amount; $j++) {
                for ($i = 0; $i < count($columns); $i++) {
                    $r->bindValue($columns[$i], $data[$i][$j]);
                }

                $r->execute();
            }

            return $r->closeCursor();
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage());
        }
    }

    /**
     * @return PDOStatement The ready to fetch data.
     */
    public function execute(): PDOStatement
    {
        if ($this->_request == null) $this->_request = self::$_pdo->prepare($this->getStatement());
        $this->bindValues($this->_request);
        R::checkArgument($this->_request->execute());
        return $this->_request;
    }

    private function getStatement(): string
    {
        $statement = R::concat(R::SPACE, $this->_operation, implode(', ', $this->_columns), 'FROM', $this->_table);

        if (!empty($this->_where)) {
            $statement .= ' WHERE ';
            $statement .= $this->_where[0]->getStatement($this->_table);
            for ($i = 1; $i < count($this->_where); $i++) {
                R::append($statement, R::SPACE, 'AND', $this->_where[$i]->getStatement($this->_table));
            }
        }

        if (!R::blank($this->_orderBy)) R::append($statement, R::SPACE, 'ORDER BY', $this->_orderBy);

        if ($this->_limit > 0 && $this->_offset > 0) {
            R::append($statement, R::SPACE, 'LIMIT', ':limit', ',', ':offset');
        } else if ($this->_limit > 0) R::append($statement, R::SPACE, 'LIMIT', ':limit');

        return $statement;
    }

    private function bindValues(PDOStatement &$request): void
    {
        foreach ($this->_where as $where) {
            $where->bindValues($request);
        }

        if ($this->_limit > 0 && $this->_offset > 0) {
            $request->bindValue(':limit', $this->_limit, PDO::PARAM_INT);
            $request->bindValue(':offset', $this->_offset, PDO::PARAM_INT);
        } else if ($this->_limit > 0) $request->bindValue(':limit', $this->_limit, PDO::PARAM_INT);
    }

    /**
     * Drops a database table specified by the given table name.
     * @param string $table The name of the table to be dropped.
     * @return bool True if the table was successfully dropped, false otherwise.
     */
    public static function drop(string $table): bool
    {
        return self::command('DROP TABLE ' . self::$_args['prefix'] . $table)->closeCursor();
    }

    /**
     * Convenient way to run a custom query.
     * @param string $query The query to prepare and then execute.
     * @return PDOStatement
     * @throws PDOException
     */
    public static function command(string $query): PDOStatement
    {
        try {
            $r = self::$_pdo->prepare($query);
            $r->execute();
            return $r;
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage());
        }
    }

    /**
     * Checks if a table exists in the database.
     * @param string $table The name of the table to check for existence.
     * @return bool Returns true if the table exists; otherwise, returns false.
     */
    public static function show(string $table): bool
    {
        $r = self::command('SHOW TABLES LIKE \'' . self::$_args['prefix'] . $table . '\'');
        $p = $r->rowCount() > 0;
        $r->closecursor();
        return $p;
    }

    /**
     * @param string $table
     * @param string ...$columns
     * @return RDB
     */
    public static function select(string $table, string ...$columns): RDB
    {
        return new RDB('SELECT', $table, $columns);
    }

    public function orderBy(string $column, string $order): RDB
    {
        R::whitelist($order, self::ORDER_BY);
        $this->_orderBy = $this->_table . '.' . $column . R::SPACE . $order;
        return $this;
    }

    public function limit(int $limit, int $offset = 0): RDB
    {
        $this->_limit = $limit;
        $this->_offset = $offset;
        return $this;
    }

    public function where(string $column, string $comparator = R::EMPTY, mixed $value = null): object
    {
        $c = new RRequest($this, $this->_table, $column, $comparator, $value);
        $this->_where[] = $c;
        return R::blank($comparator) ? $c : $this;
    }
}