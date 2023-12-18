<?php

require_once 'params.php';
require_once 'vendor/autoload.php';

RDB::start(PARAMS); // Start the connection


$users = 'users';
$usersColumns = ['name', 'gender', 'age'];

if (RDB::show($users)) RDB::drop($users);

$args = [
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

RDB::check($users, $args);
RDB::insert($users, $usersColumns, $data);


$posts = 'posts';
$postsColumns = ['title', 'author'];

if (RDB::show($posts)) RDB::drop($posts);

$args = [
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

RDB::check($posts, $args);
RDB::insert($posts, $postsColumns, $data);


$select = RDB::select($users, 'id', 'name', 'gender', 'age')
    ->where('name')->contains('a')
    ->where('age')->between(30, 40)
    ->where('gender', '=', 0)
    ->orderBy('age', 'DESC')
    ->execute();

var_dump($select);

while ($d = $select->fetch(PDO::FETCH_ASSOC)) {
    var_dump($d);
}

/*
$delete = RDB::delete($users)->where('gender', '=', false)->execute();

var_dump($delete);
*/

/*
$data = [
    'john', false, 111
];

$update = RDB::update($users, $usersColumns, $data)->where('id', '=', 2)->execute();

var_dump($update);
*/

/*
$select = RDB::select($posts, 'id', 'title')
    ->innerJoin('id', $users, 'id')
    ->where('at_users.id', '=', 1)
    ->execute();

var_dump($select);

while ($d = $select->fetch(PDO::FETCH_ASSOC)) {
    var_dump($d);
}
*/

$select = RDB::select($users, 'id', 'name', 'gender', 'age')
    ->where('name')->contains('john')
    ->or('name')->contains('marie')
    ->where('gender', '=', 0)
    ->orderBy('age', 'DESC')
    ->execute();

var_dump($select);

while ($d = $select->fetch(PDO::FETCH_ASSOC)) {
    var_dump($d);
}