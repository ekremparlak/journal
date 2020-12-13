<?php

// This is a configuration file for the doctrine cli

use App\Database\Database;
use Doctrine\ORM\Tools\Console\ConsoleRunner;

require_once __DIR__ . '/private/init.php';

$entityManager = Database::getInstance();

return ConsoleRunner::createHelperSet($entityManager);