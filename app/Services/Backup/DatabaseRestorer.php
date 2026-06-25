<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\DB;
use RuntimeException;
use Symfony\Component\Process\Process;

class DatabaseRestorer
{
    public function restore(string $sqlPath): void
    {
        $connection = (string) config('database.default');

        if ($connection === 'sqlite') {
            $this->restoreSqlite($sqlPath);

            return;
        }

        if (! in_array($connection, ['mysql', 'mariadb'], true)) {
            throw new RuntimeException("Database driver [{$connection}] is not supported for restore.");
        }

        $this->restoreMysql($sqlPath, $connection);
    }

    private function restoreSqlite(string $sqlPath): void
    {
        $databasePath = (string) config('database.connections.sqlite.database');

        if (is_file($databasePath)) {
            unlink($databasePath);
        }

        touch($databasePath);

        $pdo = new \PDO('sqlite:'.$databasePath);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->exec((string) file_get_contents($sqlPath));
    }

    private function restoreMysql(string $sqlPath, string $connection): void
    {
        $mysql = (string) config('backup.mysql_path');

        if (! is_file($mysql)) {
            throw new RuntimeException("mysql client not found at [{$mysql}]. Set BACKUP_MYSQL_PATH in .env");
        }

        $host = (string) config("database.connections.{$connection}.host");
        $port = (string) config("database.connections.{$connection}.port");
        $database = (string) config("database.connections.{$connection}.database");
        $username = (string) config("database.connections.{$connection}.username");
        $password = (string) config("database.connections.{$connection}.password");

        DB::purge($connection);
        DB::disconnect($connection);

        $command = [
            $mysql,
            '--host='.$host,
            '--port='.$port,
            '--user='.$username,
            $database,
        ];

        if ($password !== '') {
            $command[] = '--password='.$password;
        }

        $process = new Process($command);
        $process->setInput((string) file_get_contents($sqlPath));
        $process->setTimeout(null);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('Database restore failed: '.trim($process->getErrorOutput() ?: $process->getOutput()));
        }
    }
}
