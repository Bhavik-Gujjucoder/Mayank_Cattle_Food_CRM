<?php

namespace App\Services\Backup;

use RuntimeException;
use Symfony\Component\Process\Process;

class DatabaseDumper
{
    public function dump(string $outputPath): void
    {
        $connection = (string) config('database.default');

        if ($connection === 'sqlite') {
            $this->dumpSqlite($outputPath);

            return;
        }

        if (! in_array($connection, ['mysql', 'mariadb'], true)) {
            throw new RuntimeException("Database driver [{$connection}] is not supported for backup.");
        }

        $this->dumpMysql($outputPath, $connection);
    }

    private function dumpSqlite(string $outputPath): void
    {
        $databasePath = (string) config('database.connections.sqlite.database');

        if (! is_file($databasePath)) {
            throw new RuntimeException("SQLite database file not found: {$databasePath}");
        }

        $sql = "-- SQLite backup generated at ".now()->toDateTimeString().PHP_EOL;
        $sql .= '.dump'.PHP_EOL;

        $pdo = new \PDO('sqlite:'.$databasePath);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        foreach ($pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'") as $row) {
            $table = $row['name'];

            if ($this->isExcludedTable($table)) {
                continue;
            }
            $create = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name=".$pdo->quote($table))->fetchColumn();
            $sql .= $create.';'.PHP_EOL;

            $rows = $pdo->query("SELECT * FROM `{$table}`");
            foreach ($rows as $record) {
                $columns = array_map(fn ($col) => '`'.$col.'`', array_keys($record));
                $values = array_map(fn ($value) => $value === null ? 'NULL' : $pdo->quote((string) $value), array_values($record));
                $sql .= 'INSERT INTO `'.$table.'` ('.implode(', ', $columns).') VALUES ('.implode(', ', $values).');'.PHP_EOL;
            }
        }

        file_put_contents($outputPath, $sql);
    }

    private function dumpMysql(string $outputPath, string $connection): void
    {
        $mysqldump = (string) config('backup.mysqldump_path');

        if (! is_file($mysqldump)) {
            throw new RuntimeException("mysqldump not found at [{$mysqldump}]. Set BACKUP_MYSQLDUMP_PATH in .env");
        }

        $host = (string) config("database.connections.{$connection}.host");
        $port = (string) config("database.connections.{$connection}.port");
        $database = (string) config("database.connections.{$connection}.database");
        $username = (string) config("database.connections.{$connection}.username");
        $password = (string) config("database.connections.{$connection}.password");

        $command = [
            $mysqldump,
            '--host='.$host,
            '--port='.$port,
            '--user='.$username,
            '--single-transaction',
            '--routines',
            '--triggers',
            '--add-drop-table',
            '--result-file='.$outputPath,
        ];

        foreach ($this->excludedTables() as $table) {
            $command[] = '--ignore-table='.$database.'.'.$table;
        }

        $command[] = $database;

        if ($password !== '') {
            $command[] = '--password='.$password;
        }

        $process = new Process($command);
        $process->setTimeout(null);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('Database dump failed: '.trim($process->getErrorOutput() ?: $process->getOutput()));
        }

        if (! is_file($outputPath) || filesize($outputPath) === 0) {
            throw new RuntimeException('Database dump file is empty.');
        }
    }

    /**
     * @return list<string>
     */
    private function excludedTables(): array
    {
        return array_values(array_filter((array) config('backup.excluded_tables', [])));
    }

    private function isExcludedTable(string $table): bool
    {
        return in_array($table, $this->excludedTables(), true);
    }
}
