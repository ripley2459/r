<?php

require_once 'params.php';
require_once 'R.php';
require_once 'RDB.php';
require_once 'RRequest.php';

RDB::start(PARAMS); // Start the connection

$table = 'test';
$columns = ['name', 'gender', 'age'];

if (RDB::show($table)) RDB::drop($table); // Reset

$a = R::EMPTY;
R::append($a, ', ', 'id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY');
R::append($a, ', ', 'name VARCHAR(255)');
R::append($a, ', ', 'gender TINYINT(1)');
R::append($a, ', ', 'age TINYINT(255)');
R::append($a, ', ', 'dateCreated DATETIME default CURRENT_TIMESTAMP');
R::append($a, ', ', 'dateModified DATETIME default CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
$s = 'CREATE TABLE ' . PARAMS['prefix'] . $table . ' (' . $a . ')';
RDB::command($s)->closeCursor();

$data = [
    ['john', 'marie', 'isac', 'lisa', 'peter', 'sophie', 'michael', 'emily', 'alex', 'olivia', 'david', 'emma', 'kevin', 'claire', 'jason', 'amy', 'brian', 'jessica', 'eric', 'sara'],
    [false, true, false, true, false, true, false, true, false, true, false, true, false, true, false, true, false, true, false, true],
    [31, 27, 45, 22, 35, 28, 40, 24, 33, 30, 28, 26, 39, 29, 36, 25, 32, 34, 27, 38],
];
RDB::insert('test', $columns, $data); // Fill

$request = RDB::select('test', 'id', 'name', 'gender', 'age')
    ->where('name')->contains('a')
    ->where('age')->between(30, 40)
    ->where('gender', '=', 0)
    ->orderBy('age', 'DESC')
    ->execute();

var_dump($request);

while ($d = $request->fetch(PDO::FETCH_ASSOC)) {
    var_dump($d);
}