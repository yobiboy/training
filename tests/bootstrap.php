<?php

require __DIR__.'/../vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Force the testing environment
|--------------------------------------------------------------------------
|
| docker-compose.yml sets DB_CONNECTION, DB_HOST, etc. as real container
| environment variables so the app talks to Postgres in dev. Those land in
| $_SERVER, and Laravel's env() resolution reads $_SERVER before it reads
| $_ENV or getenv() — so phpunit.xml's <env ... force="true"/> overrides
| (which only touch $_ENV/getenv()) are silently outranked, and tests using
| RefreshDatabase end up migrating the real dev database instead of the
| in-memory SQLite one. Setting $_SERVER directly here, before the app ever
| boots, is what actually wins.
|
*/
foreach ([
    'APP_ENV' => 'testing',
    'DB_CONNECTION' => 'sqlite',
    'DB_DATABASE' => ':memory:',
    'DB_URL' => '',
] as $key => $value) {
    putenv("{$key}={$value}");
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}
