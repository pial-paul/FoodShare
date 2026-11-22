<?php
declare(strict_types=1);

function db(): mysqli
{
    static $connection;
    if (!$connection) {
        $user = getenv('FOODSHARE_DB_USER');
        $password = getenv('FOODSHARE_DB_PASSWORD');
        if (!$user || $password === false || $password === '') {
            throw new RuntimeException('Missing database configuration.');
        }
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $connection = new mysqli(getenv('FOODSHARE_DB_HOST') ?: '127.0.0.1', $user, $password,
            getenv('FOODSHARE_DB_NAME') ?: 'foodshare_db', (int)(getenv('FOODSHARE_DB_PORT') ?: 3306));
        $connection->set_charset('utf8mb4');
        $connection->query("SET time_zone = '+00:00'");
    }
    return $connection;
}

function rows(string $sql, array $params = []): array
{
    return db()->execute_query($sql, $params)->fetch_all(MYSQLI_ASSOC);
}

function today(): string
{
    return (new DateTimeImmutable('now', new DateTimeZone(getenv('FOODSHARE_TIMEZONE') ?: 'UTC')))->format('Y-m-d');
}
