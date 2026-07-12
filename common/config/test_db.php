<?php

$db = require __DIR__ . '/db.php';
// test database! Important not to run tests on production or development databases
$db['dsn'] = env('TEST_DB_DSN', 'mysql:host=localhost;dbname=yii2_api_adminpanel_test');

return $db;
