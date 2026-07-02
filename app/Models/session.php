<?php

class PgsqlSessionHandler implements SessionHandlerInterface, SessionUpdateTimestampHandlerInterface
{
    private $pdo;
    private $table = 'php_sessions';

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function open($path, $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read($id): string|false
    {
        try {
            $sql = 'SELECT data FROM ' . $this->table . ' WHERE id = :id LIMIT 1';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(array('id' => (string) $id));
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row || !isset($row['data'])) {
                return '';
            }

            return (string) $row['data'];
        } catch (PDOException $e) {
            app_log_error('Session read failed: ' . $e->getMessage());

            return '';
        }
    }

    public function write($id, $data): bool
    {
        try {
            $sql = 'INSERT INTO ' . $this->table . ' (id, data, last_activity)
                VALUES (:id, :data, CURRENT_TIMESTAMP)
                ON CONFLICT (id) DO UPDATE SET
                    data = EXCLUDED.data,
                    last_activity = CURRENT_TIMESTAMP';
            $stmt = $this->pdo->prepare($sql);

            return $stmt->execute(array(
                'id' => (string) $id,
                'data' => (string) $data,
            ));
        } catch (PDOException $e) {
            app_log_error('Session write failed: ' . $e->getMessage());

            return false;
        }
    }

    public function destroy($id): bool
    {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM ' . $this->table . ' WHERE id = :id');

            return $stmt->execute(array('id' => (string) $id));
        } catch (PDOException $e) {
            app_log_error('Session destroy failed: ' . $e->getMessage());

            return false;
        }
    }

    public function gc($maxlifetime): int|false
    {
        try {
            $stmt = $this->pdo->prepare(
                'DELETE FROM ' . $this->table . '
                WHERE last_activity < (CURRENT_TIMESTAMP - (:seconds || \' seconds\')::interval)'
            );
            $stmt->execute(array('seconds' => (int) $maxlifetime));

            return $stmt->rowCount();
        } catch (PDOException $e) {
            app_log_error('Session gc failed: ' . $e->getMessage());

            return false;
        }
    }

    public function validateId(string $id): bool
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT 1 FROM ' . $this->table . ' WHERE id = :id LIMIT 1'
            );
            $stmt->execute(array('id' => $id));

            return (bool) $stmt->fetchColumn();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function updateTimestamp(string $id, string $data): bool
    {
        try {
            $stmt = $this->pdo->prepare(
                'UPDATE ' . $this->table . ' SET last_activity = CURRENT_TIMESTAMP WHERE id = :id'
            );

            return $stmt->execute(array('id' => $id));
        } catch (PDOException $e) {
            return false;
        }
    }
}

function session_driver_name()
{
    $driver = getenv('SESSION_DRIVER');
    if ($driver !== false && $driver !== '') {
        return strtolower(trim((string) $driver));
    }

    if (getenv('VERCEL') === '1' || getenv('VERCEL_ENV') !== false) {
        return 'database';
    }

    if (defined('APP_ENV') && APP_ENV === 'production') {
        return 'database';
    }

    return 'file';
}

function create_session_pdo()
{
    $dsn = 'pgsql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME;
    if (defined('DB_SSLMODE') && DB_SSLMODE !== '') {
        $dsn .= ';sslmode=' . DB_SSLMODE;
    }

    $options = array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    );

    return new PDO($dsn, DB_USER, DB_PASS, $options);
}

function ensure_php_sessions_table(PDO $pdo)
{
    static $ready = false;
    if ($ready) {
        return true;
    }

    $cached = app_cache_get('php_sessions_table_verified', 86400);
    if (is_array($cached) && !empty($cached['verified'])) {
        $ready = true;
        return true;
    }

    if (php_sessions_table_exists($pdo)) {
        app_cache_put('php_sessions_table_verified', array('verified' => true));
        $ready = true;
        return true;
    }

    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS php_sessions (
            id VARCHAR(128) PRIMARY KEY,
            data TEXT NOT NULL DEFAULT '',
            last_activity TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_php_sessions_last_activity ON php_sessions (last_activity)');
        app_cache_put('php_sessions_table_verified', array('verified' => true));
        $ready = true;

        return true;
    } catch (PDOException $e) {
        app_log_error('Could not ensure php_sessions table: ' . $e->getMessage());

        return false;
    }
}

function php_sessions_table_exists(PDO $pdo)
{
    static $exists = null;
    if ($exists !== null) {
        return $exists;
    }

    try {
        $stmt = $pdo->query("SELECT to_regclass('public.php_sessions')");
        $exists = $stmt && $stmt->fetchColumn() !== null;
    } catch (PDOException $e) {
        $exists = false;
    }

    return $exists;
}

function register_database_session_handler(PDO $pdo)
{
    if (!ensure_php_sessions_table($pdo)) {
        return false;
    }

    $handler = new PgsqlSessionHandler($pdo);
    session_set_save_handler($handler, true);

    return true;
}

function app_session_cookie_params()
{
    return array(
        'lifetime' => 0,
        'path' => '/',
        'secure' => request_is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    );
}

function app_start_session($pdo = null)
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }

    session_set_cookie_params(app_session_cookie_params());
    ini_set('session.use_strict_mode', '1');
    ini_set('session.lazy_write', '0');

    if ($pdo instanceof PDO && session_driver_name() === 'database') {
        register_database_session_handler($pdo);
    }

    session_start();

    static $shutdown_registered = false;
    if (!$shutdown_registered) {
        $shutdown_registered = true;
        register_shutdown_function(static function () {
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }
        });
    }
}

function app_commit_session()
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
}
