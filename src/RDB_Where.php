<?php

/**
 * @author Cyril Neveu
 * @link https://github.com/ripley2459/r
 * @version 4
 */
class RDB_Where
{
    private RDB $_owner;
    private int $_index;
    private string $_table;
    private string $_column;
    private string $_operation;
    private array $_values = array();

    public function __construct(RDB $owner, int $index, string $table, string $column, string $comparator = R::EMPTY, mixed $value = null)
    {
        $this->_owner = $owner;
        $this->_index = $index;
        $this->_table = $table;
        $this->_column = $column;
        if (!R::blank($comparator)) {
            R::whitelist($comparator, RDB::COMPARE);
            $this->_operation = $comparator . R::SPACE . '%s';
            $this->_values[] = $value;
        }
    }

    public function startWith(string $value): RDB
    {
        if (!R::blank($value)) {
            $this->_operation = 'LIKE %s';
            $this->_values[] = $value . '%';
        }

        return $this->_owner;
    }

    public function contains(string $value): RDB
    {
        if (!R::blank($value)) {
            $this->_operation = 'LIKE %s';
            $this->_values[] = '%' . $value . '%';
        }

        return $this->_owner;
    }

    public function endWith(string $value): RDB
    {
        if (!R::blank($value)) {
            $this->_operation = 'LIKE %s';
            $this->_values[] = '%' . $value;
        }

        return $this->_owner;
    }

    public function in(mixed $values): RDB
    {
        if (!R::blank($values)) {
            if (is_array($values)) {
                $s = R::EMPTY;
                for ($i = 0; $i < count($values); $i++) {
                    R::append($s, ', ', '%s');
                    $this->_values[] = $values[$i];
                }

                $this->_operation = 'IN (' . $s . ')';
            } else if ($values instanceof RDB) { // Special case
                $this->_values[] = $values;
                $this->_operation = 'IN (%s)';
            }
        }

        return $this->_owner;
    }

    public function notIn(mixed $values): RDB
    {
        if (!R::blank($values)) {
            if (is_array($values)) {
                $s = R::EMPTY;
                for ($i = 0; $i < count($values); $i++) {
                    R::append($s, ', ', '%s');
                    $this->_values[] = $values[$i];
                }

                $this->_operation = 'NOT IN (' . $s . ')';
            } else if ($values instanceof RDB) { // Special case
                $this->_values[] = $values;
                $this->_operation = 'NOT IN (%s)';
            }
        }

        return $this->_owner;
    }

    public function between(mixed $min, mixed $max): RDB
    {
        if (!R::blank($min) && !R::blank($max) && $max > $min) {
            $this->_operation = 'BETWEEN %s AND %s';
            $this->_values[] = $min;
            $this->_values[] = $max;
        }

        return $this->_owner;
    }

    public function notBetween(mixed $min, mixed $max): RDB
    {
        if (!R::blank($min) && !R::blank($max) && $max > $min) {
            $this->_operation = 'NOT BETWEEN %s AND %s';
            $this->_values[] = $min;
            $this->_values[] = $max;
        }

        return $this->_owner;
    }

    public function getStatement(): string
    {
        $placeholders = [];
        if ($this->_values[0] instanceof RDB)  // Special case
            $placeholders[] = $this->_values[0]->getStatement();
        else for ($i = 0; $i < count($this->_values); $i++)
            $placeholders[] = ':' . str_replace('.', '_', $this->_column) . '_' . $this->_index . '_' . $i;
        return $this->_column . ' ' . vsprintf($this->_operation, $placeholders);
    }

    public function bindValues(PDOStatement &$stmt): void
    {
        for ($i = 0; $i < count($this->_values); $i++)
            if ($this->_values[0] instanceof RDB) // Special case
                $this->_values[0]->bindValues_SELECT($stmt, false);
            else $stmt->bindValue(':' . str_replace('.', '_', $this->_column) . '_' . $this->_index . '_' . $i, $this->_values[$i], RDB::getType($this->_values[$i]));
    }


}