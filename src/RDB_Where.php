<?php

class RDB_Where
{
    private RDB $_owner;
    private string $_chainedOperation = R::EMPTY;
    private ?RDB_Where $_chained = null;
    private int $_index = 0;
    private string $_table;
    private string $_column;
    private string $_operation = R::EMPTY;
    private array $_binding = array();

    public function __construct(RDB $owner, string $table, string $column, string $comparator = R::EMPTY, mixed $value = null, int $index = 0)
    {
        $this->_owner = $owner;
        $this->_table = $table;
        $this->_column = str_contains($column, '.') ? $column : $this->_table . '.' . $column;
        $this->_index = $index;
        if (!R::blank($comparator)) {
            R::whitelist($comparator, RDB::COMPARE);
            $this->_operation = $comparator . R::SPACE . '%s';
            $this->_binding = [$this->getDefaultBinding() => $value];
        }
    }

    private function getDefaultBinding(): string
    {
        return str_contains($this->_column, '.') ? ':' . R::replaceFirst('.', '_', $this->_column) . '_' . $this->_index : ':' . $this->_table . '_' . $this->_column . '_' . $this->_index;
    }

    public function chain(string $chainedOperation, RDB_Where $other): void
    {
        if ($this->isChained()) {
            $this->_chained->chain($chainedOperation, $other);
        } else {
            $this->_chainedOperation = $chainedOperation;
            $this->_chained = $other;
        }
    }

    public function isChained(): bool
    {
        return $this->_chained != null;
    }

    public function bindValues(PDOStatement &$request, int $index = 0): void
    {
        foreach ($this->_binding as $bind => $value) {
            $request->bindValue($bind, $value, RDB::getType($value));
        }

        $this->_chained?->bindValues($request, ++$index);
    }

    public function startWith(string $value): RDB
    {
        if (!R::blank($value)) {
            $this->_operation = 'LIKE %s';
            $this->_binding = [$this->getDefaultBinding() => $value . '%'];
        }

        return $this->_owner;
    }

    public function contains(string $value): RDB
    {
        if (!R::blank($value)) {
            $this->_operation = 'LIKE %s';
            $this->_binding = [$this->getDefaultBinding() => '%' . $value . '%'];
        } else array_pop($this->_owner->getWhere());

        return $this->_owner;
    }

    public function endWith(string $value): RDB
    {
        if (!R::blank($value)) {
            $this->_operation = 'LIKE %s';
            $this->_binding = [$this->getDefaultBinding() => '%' . $value];
        } else array_pop($this->_owner->getWhere());

        return $this->_owner;
    }

    public function in(array $values): RDB
    {
        if (!R::blank($values)) {
            $s = R::EMPTY;
            for ($i = 0; $i < count($values); $i++) {
                R::append($s, ', ', '%s');
                $this->_binding[$this->getDefaultBinding() . '_' . $i] = $values[$i];
            }

            $this->_operation = 'IN (' . $s . ')';
        } else array_pop($this->_owner->getWhere());

        return $this->_owner;
    }

    public function notIn(array $values): RDB
    {
        if (!R::blank($values)) {
            $s = R::EMPTY;
            for ($i = 0; $i < count($values); $i++) {
                R::append($s, ', ', '%s');
                $this->_binding[$this->getDefaultBinding() . '_' . $i] = $values[$i];
            }

            $this->_operation = 'NOT IN (' . $s . ')';
        } else array_pop($this->_owner->getWhere());

        return $this->_owner;
    }

    public function between(mixed $min, mixed $max): RDB
    {
        if (!R::blank($min) && !R::blank($max) && $max > $min) {
            $this->_operation = 'BETWEEN %s AND %s';
            $this->_binding = [$this->getDefaultBinding() . '_min' => $min, $this->getDefaultBinding() . '_max' => $max];
        }

        return $this->_owner;
    }

    public function notBetween(mixed $min, mixed $max): RDB
    {
        if (!R::blank($min) && !R::blank($max) && $max > $min) {
            $this->_operation = 'NOT BETWEEN %s AND %s';
            $this->_binding = [$this->getDefaultBinding() . '_min' => $min, $this->getDefaultBinding() . '_max' => $max];
        }

        return $this->_owner;
    }

    public function __debugInfo(): ?array
    {
        return [$this->getStatement()];
    }

    public function getStatement(): string
    {
        if ($this->isChained()) return R::concat(R::SPACE, $this->_column, vsprintf($this->_operation, array_keys($this->_binding)), $this->_chainedOperation, $this->_chained->getStatement());
        else return $this->_column . ' ' . vsprintf($this->_operation, array_keys($this->_binding));

        /*if ($this->isChained() || $chained)
            return R::concat(R::SPACE, !$chained ? '(' : R::EMPTY, $this->_column, vsprintf($this->_operation, array_keys($this->_binding)),
                $this->_chainedOperation, $this->_chained->getStatement(true));
        else return $this->_column . R::SPACE . vsprintf($this->_operation, array_keys($this->_binding)) . $chained ? ')' : R::EMPTY;*/
    }
}