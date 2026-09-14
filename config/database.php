<?php
require_once __DIR__ . '/config.php';

class SafePDOStatement
{
    public function execute(array $params = []): bool
    {
        return false;
    }

    public function fetch(int $mode = PDO::FETCH_ASSOC)
    {
        return false;
    }

    public function fetchAll(int $mode = PDO::FETCH_ASSOC): array
    {
        return [];
    }

    public function fetchColumn(): bool|string|int|float|null
    {
        return false;
    }

    public function rowCount(): int
    {
        return 0;
    }
}

class SafePDO
{
    public function prepare(string $statement, array $driverOptions = []): SafePDOStatement
    {
        return new SafePDOStatement();
    }

    public function query(string $statement): SafePDOStatement
    {
        return new SafePDOStatement();
    }

    public function setAttribute(int $attribute, mixed $value): bool
    {
        return true;
    }
}

$pdo = null;
$primaryDsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;

try {
    $pdo = new PDO($primaryDsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $primaryException) {
    $fallbackDsn = "pgsql:host=" . DB_FALLBACK_HOST . ";port=" . DB_FALLBACK_PORT . ";dbname=" . DB_FALLBACK_NAME;
    try {
        $pdo = new PDO($fallbackDsn, DB_FALLBACK_USER, DB_FALLBACK_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $fallbackException) {
        error_log('Primary database connection failed: ' . $primaryException->getMessage());
        error_log('Fallback database connection failed: ' . $fallbackException->getMessage());

        $_SESSION['db_error'] = 'Database unavailable. Set DATABASE_URL or DB_* environment variables and ensure PostgreSQL accepts the configured host, port, database, user, and password.';

        $pdo = new SafePDO();
    }
}
?>