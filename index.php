<?php

require_once 'params.php';
require_once 'vendor/autoload.php';

RDB::start(PARAMS);

$table = 'users';
$columns = ['name', 'gender', 'age'];
$structure = [
    'id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
    'name VARCHAR(255)',
    'gender TINYINT(1)',
    'age TINYINT(255)',
    'dateCreated DATETIME default CURRENT_TIMESTAMP',
    'dateModified DATETIME default CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
];
$data = [
    ['john', 'marie', 'isac', 'lisa', 'peter', 'sophie', 'michael', 'emily', 'alex', 'olivia', 'david', 'emma', 'kevin', 'claire', 'jason', 'amy', 'brian', 'jessica', 'eric', 'sara'],
    [false, true, false, true, false, true, false, true, false, true, false, true, false, true, false, true, false, true, false, true],
    [31, 27, 45, 22, 35, 28, 40, 24, 33, 30, 28, 26, 39, 29, 36, 25, 32, 34, 27, 38],
];

if (RDB::show($table)->execute())
    RDB::drop($table)->execute();

RDB::check($table, $structure)->execute();

$insert = RDB::insert($table, $columns, $data)->execute();

var_dump($insert);

$posts = 'posts';
$postsColumns = ['title', 'author'];
$structure = [
    'id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
    'title VARCHAR(255)',
    'author BIGINT',
    'dateCreated DATETIME default CURRENT_TIMESTAMP',
    'dateModified DATETIME default CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
];
$data = [
    ['whimsical widget', 'giggly gadget', 'quirky quill', 'silly sprocket', 'zany zipline', 'funky flamingo', 'merry muffin maker', 'bouncy banana', 'dizzy doodle', 'jolly jigsaw', 'playful pogo stick', 'cheerful chatterbox', 'wacky watermelon', 'spirited snorkel', 'lively lollipop', 'joyful jamboree', 'crazy crayon', 'amusing astronaut', 'mirthful moonwalk', 'chuckling chinchilla'],
    [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20]
];

if (RDB::show($posts)->execute())
    RDB::drop($posts)->execute();

RDB::check($posts, $structure)->execute();

RDB::insert($posts, $postsColumns, $data)->execute();


$select = RDB::select('users', 'users.id')
    ->where('name')->contains('a')
    ->orderBy('age', 'DESC')
    ->execute();

while ($data = $select->fetch(PDO::FETCH_ASSOC)) {
     var_dump($data);
}

$select->closeCursor();

$update = RDB::update($table, $columns, ['john', false, 111])->where('id', '=', 2)->execute();

var_dump($update);

$sub = RDB::select('users', 'users.id')->where('users.age')->between(10, 20);
$select = RDB::select('posts', '*')->where('posts.id')->notIn($sub)->where('posts.title')->startWith('j')->execute();

while ($data = $select->fetch(PDO::FETCH_ASSOC)) {
    var_dump($data);
}