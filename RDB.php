<?php

class RDB
{
    private static array $_compare = ['=', '!=', '<', '<>', '>', '<=', '>='];
    private static array $_join = ['INNER', 'LEFT', 'RIGHT', 'LEFT OUTER', 'RIGHT OUTER'];
    private static array $_orderBy = ['ASC', 'DESC'];
    private static array $_andOr = ['AND', 'OR'];
    private static array $_args;
    private static PDO $_pdo;

    public static function start(array $args): RDB
    {
        R::checkArgument(R::allKeysExist($args, ['host', 'dbname', 'charset', 'user', 'password', 'prefix', 'sqlPath']));
        self::$_args = $args;

        try {
            $dsn = 'mysql:host=' . self::$_args['host'] . ';dbname=' . self::$_args['dbname'] . ';charset=' . self::$_args['charset'];
            $dsn_options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => true];
            self::$_pdo = new PDO($dsn, self::$_args['user'], self::$_args['password'], $dsn_options);
            return new RDB();
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
     * Convenient way to run a custom query.
     * @param string $query The query to prepare and then execute.
     * @return PDOStatement
     * @throws PDOException
     */
    public static function execute(string $query): PDOStatement
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
     * Drops a database table specified by the given table name.
     * @param string $table The name of the table to be dropped.
     * @return bool True if the table was successfully dropped, false otherwise.
     */
    public static function drop(string $table): bool
    {
        return self::execute('DROP TABLE ' . self::$_args['prefix'] . $table)->closeCursor();
    }

    /**
     * Checks if a table exists in the database.
     * @param string $table The name of the table to check for existence.
     * @return bool Returns true if the table exists; otherwise, returns false.
     */
    public static function show(string $table): bool
    {
        $r = self::execute('SHOW TABLES LIKE \'' . self::$_args['prefix'] . $table . '\'');
        $p = $r->rowCount() > 0;
        $r->closecursor();
        return $p;
    }
}