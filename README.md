# RLib

RLib is my internet development library that provides useful PHP and Javascript functions and a very basic CSS boilerplate.

The library provides:

- Solutions to redundant issues/tasks: event system, string operations, management of user inputs, utilities...
- Foundation for any structure that can be managed by a MySQL database.
- Database management: like a fancy connexion methode and simple yet complete interaction methods with a MySQL database like inserting, deleting, selecting...

## Installation

Require [PHPUnit](https://phpunit.de/index.html).

```bash
composer require --dev phpunit/phpunit ^10
```

**Running tests**

```bash
vendor/bin/phpunit test/RTests.php
vendor/bin/phpunit test/RDBTests.php
```

## Credits

- core_styling.css is heavily based on [Skeleton](getskeleton.com) and [Normalize](git.io/normalize) both under MIT licence.