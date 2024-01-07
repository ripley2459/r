<?php


use PHPUnit\Framework\TestCase;

class RDBTests extends TestCase
{
    public const STRUCTURE = ['id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY', 'name VARCHAR(255)', 'gender TINYINT(1)', 'age TINYINT(255)', 'dateCreated DATETIME default CURRENT_TIMESTAMP', 'dateModified DATETIME default CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'];
    public const DATA = [['john', 'marie', 'isac', 'lisa', 'peter', 'sophie', 'michael', 'emily', 'alex', 'olivia', 'david', 'emma', 'kevin', 'claire', 'jason', 'amy', 'brian', 'jessica', 'eric', 'sara'], [false, true, false, true, false, true, false, true, false, true, false, true, false, true, false, true, false, true, false, true], [31, 27, 45, 22, 35, 28, 40, 24, 33, 30, 28, 26, 39, 29, 36, 25, 32, 34, 27, 38],];

    public function test_simple()
    {
        $show = RDB::show('users');
        $this->assertSame('SHOW TABLES LIKE \'users\'', $show->getStatement());

        $drop = RDB::drop('users');
        $this->assertSame('DROP TABLE users', $drop->getStatement());

        $truncate = RDB::truncate('users');
        $this->assertSame('TRUNCATE TABLE users', $truncate->getStatement());
    }

    public function test_check()
    {
        $check = RDB::check('users', self::STRUCTURE);
        $this->assertSame('CREATE TABLE users (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255), gender TINYINT(1), age TINYINT(255), dateCreated DATETIME default CURRENT_TIMESTAMP, dateModified DATETIME default CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)', $check->getStatement());
    }

    public function test_insert()
    {
        $check = RDB::insert('users', ['name', 'gender', 'age'], self::DATA);
        $this->assertSame('INSERT INTO users (name, gender, age) VALUES (:name, :gender, :age)', $check->getStatement());
    }

    public function test_select()
    {
        $select = RDB::select('users', 'name')->where('age', '>', 10)->where('gender', '=', 0)->orderBy('age', 'DESC');
        $this->assertSame('SELECT name FROM users WHERE (age > :age_0_0 AND gender = :gender_1_0) ORDER BY age DESC', $select->getStatement());

        $select = RDB::select('users', 'name')->where('age', '>', 10)->or()->where('name')->endWith('J');
        $this->assertSame('SELECT name FROM users WHERE age > :age_0_0 OR name LIKE :name_1_0', $select->getStatement());

        $select = RDB::select('users', 'name')->where('age', '>', 10)->or()->where('name')->endWith('J')->where('gender', '=', 0);
        $this->assertSame('SELECT name FROM users WHERE age > :age_0_0 OR (name LIKE :name_1_0 AND gender = :gender_2_0)', $select->getStatement());

        $select = RDB::select('users', '*')->where('name')->startWith('j');
        $this->assertSame('SELECT * FROM users WHERE name LIKE :name_0_0', $select->getStatement());

        $sub = RDB::select('users', 'users.id')->where('age')->between(10, 20);
        $select = RDB::select('posts', '*')->where('posts.id')->notIn($sub)->where('posts.name')->startWith('j');
        $this->assertSame('SELECT * FROM posts WHERE (posts.id NOT IN (SELECT users.id FROM users WHERE age BETWEEN :age_0_0 AND :age_0_1) AND posts.name LIKE :posts_name_1_0)', $select->getStatement());

        $select = RDB::select('users', '*')->innerJoin('posts', 'users.id = posts.author')->where('users.name')->startWith('j');
        $this->assertSame('SELECT * FROM users INNER JOIN posts ON users.id = posts.author WHERE users.name LIKE :users_name_0_0', $select->getStatement());

        $select = RDB::select('users A, users B', 'A.name AS nameA', 'B.name AS nameB')->where('gender', '=', 0)->where('A.name', '!=', 'B.name');
        $this->assertSame('SELECT A.name AS nameA, B.name AS nameB FROM users A, users B WHERE (gender = :gender_0_0 AND A.name != :A_name_1_0)', $select->getStatement());

        $sub = RDB::select('users', 'id')->where('age')->notBetween(10, 20)->orderBy('age', 'DESC');
        $select = RDB::select('users', 'id')->where('name')->contains('j')->union($sub)->orderBy('name', 'ASC');
        $this->assertSame('SELECT id FROM users WHERE name LIKE :name_0_0 UNION SELECT id FROM users WHERE age NOT BETWEEN :age_0_0 AND :age_0_1 ORDER BY name ASC', $select->getStatement());
    }

    public function test_update()
    {
        $data = ['john', 111];

        $update = RDB::update('users', ['name', 'age'], $data)->where('id', '=', 2);
        $this->assertSame('UPDATE users SET name = :name, age = :age WHERE id = :id_0_0', $update->getStatement());

        $update = RDB::update('users', ['name', 'age'], $data)->where('age')->between(10, 20)->where('name')->startWith('j');
        $this->assertSame('UPDATE users SET name = :name, age = :age WHERE (age BETWEEN :age_0_0 AND :age_0_1 AND name LIKE :name_1_0)', $update->getStatement());
    }

    public function test_delete()
    {
        $delete = RDB::delete('users')->where('id', '=', 2);
        $this->assertSame('DELETE FROM users WHERE id = :id_0_0', $delete->getStatement());

        $delete = RDB::delete('users')->where('age')->between(10, 20)->where('name')->startWith('j');
        $this->assertSame('DELETE FROM users WHERE (age BETWEEN :age_0_0 AND :age_0_1 AND name LIKE :name_1_0)', $delete->getStatement());
    }
}
