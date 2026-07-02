<?php

function database_backup_dir()
{
    $dir = STORAGE_PATH . '/backups';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    return $dir;
}

function database_backup_create()
{
    $dir = database_backup_dir();
    $stamp = date('Ymd-His');
    $filename = 'db-' . DB_NAME . '-' . $stamp . '.sql';
    $path = $dir . '/' . $filename;
    $gz_path = $path . '.gz';

    $cmd = sprintf(
        'pg_dump -h %s -p %s -U %s -d %s --no-owner --no-acl -f %s',
        escapeshellarg(DB_HOST),
        escapeshellarg(DB_PORT),
        escapeshellarg(DB_USER),
        escapeshellarg(DB_NAME),
        escapeshellarg($path)
    );

    putenv('PGPASSWORD=' . DB_PASS);
    $output = array();
    $exit_code = 1;
    exec($cmd . ' 2>&1', $output, $exit_code);
    putenv('PGPASSWORD');

    if ($exit_code !== 0 || !is_file($path)) {
        return array(
            'ok' => false,
            'error' => 'pg_dump failed: ' . implode("\n", $output),
        );
    }

    $sql = file_get_contents($path);
    if ($sql === false || $sql === '') {
        if (is_file($path)) {
            unlink($path);
        }
        return array('ok' => false, 'error' => 'Backup was empty.');
    }

    $gz = gzencode($sql, 9);
    unlink($path);
    if ($gz === false) {
        return array('ok' => false, 'error' => 'Could not compress backup.');
    }

    if (file_put_contents($gz_path, $gz) === false) {
        return array('ok' => false, 'error' => 'Could not write backup file.');
    }

    database_backup_prune($dir, 14);

    return array(
        'ok' => true,
        'file' => basename($gz_path),
        'path' => $gz_path,
        'size' => filesize($gz_path),
    );
}

function database_backup_prune($dir, $keep = 14)
{
    $files = glob($dir . '/db-*.sql.gz');
    if (!is_array($files)) {
        return;
    }

    usort($files, function ($a, $b) {
        return filemtime($b) - filemtime($a);
    });

    foreach (array_slice($files, $keep) as $old) {
        if (is_file($old)) {
            unlink($old);
        }
    }
}

function database_backup_list($limit = 10)
{
    $dir = database_backup_dir();
    $files = glob($dir . '/db-*.sql.gz');
    if (!is_array($files)) {
        return array();
    }

    usort($files, function ($a, $b) {
        return filemtime($b) - filemtime($a);
    });

    $rows = array();
    foreach (array_slice($files, 0, (int) $limit) as $file) {
        $rows[] = array(
            'file' => basename($file),
            'size' => (int) filesize($file),
            'created_at' => date('Y-m-d H:i', filemtime($file)),
        );
    }

    return $rows;
}

function database_backup_resolve_file($filename)
{
    $filename = basename((string) $filename);
    if (!preg_match('/^db-[a-zA-Z0-9_\-]+\-[0-9]{8}-[0-9]{6}\.sql\.gz$/', $filename)) {
        return '';
    }

    $path = database_backup_dir() . '/' . $filename;
    if (!is_file($path)) {
        return '';
    }

    return $path;
}
