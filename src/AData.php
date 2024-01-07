<?php

/**
 * Procedural abstract class representing an object that can be stored in a database.
 * This class provides a procedural approach to interact with a database, including methods for retrieving, inserting, updating, and deleting data. Subclasses must implement specific methods to define the table name, columns, and other database-related details.
 * @property int $id The unique identifier for the data
 * @author Cyril Neveu
 * @link https://github.com/ripley2459/r
 * @version 2
 */
abstract class AData
{
    /**
     * @var int The unique identifier for this data.
     */
    protected int $id;

    /**
     * Checks and creates the associated table for this instance if necessary.
     * @return bool True if the table exists or was created successfully.
     */
    public abstract static function checkTable(): bool;

    /**
     * Saves new instance(s) to the database.
     * @param array $data Data used for saving. Under this form [['john', 'marie'], [false, true], [31, 27]] (in column order).
     * @return array Array of int representing the inserted id(s).
     */
    public static function register(array $data): array
    {
        $static = new static(); // PHP 8.0 killed the static::abstractStaticMethod() so we have to be cunning.
        return RDB::insert($static::getTableName(), $static::getInsertableColumns(), $data)->execute();
    }

    /**
     * Retrieves the table name without the eventual prefix.
     * @return string The name of the table without the eventual prefix.
     */
    public abstract static function getTableName(): string;

    /**
     * Retrieves the columns present in the database that can receive data during insertion.
     * @return array An array of column names.
     */
    public abstract static function getInsertableColumns(): array;

    /**
     * Deletes this instance from the database.
     * @return bool True if the instance was deleted successfully.
     */
    public function unregister(): bool
    {
        return RDB::delete(static::getTableName())->where('id', '=', $this->id)->execute() != false;
    }

    /**
     * Saves changes made to this instance.
     * @param array $data Additional data for updating. Under this form ['john', false, 111] (in column order).
     * @return bool True if the instance was modified successfully.
     */
    public function update(array $data): bool
    {
        $update = RDB::update(static::getTableName(), static::getUpdatableColumns(), $data)->where('id', '=', $this->id)->execute() != false;
        $this->__construct($this->id);
        return $update;
    }

    /**
     * Retrieves the columns present in the database that can be updated.
     * @return array An array of column names.
     */
    public abstract static function getUpdatableColumns(): array;

    public function __construct(int $id = null)
    {
        if (R::blank($id))
            return;

        $r = RDB::select(static::getTableName(), '*')->limit(1)->where('id', '=', $id)->execute();

        try {
            $d = $r->fetch();
            foreach (static::getColumns() as $column)
                $this->$column = $d[$column];
            $r->closeCursor();
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage());
        }
    }

    /**
     * Retrieves the columns present in the database.
     * @return array An array of column names.
     */
    public abstract static function getColumns(): array;

    /**
     * @return int The unique identifier for this data.
     */
    public function getId(): int
    {
        return $this->id;
    }
}