<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Database\MigrationRunner;
use Config\Database;
use Config\Migrations;

class PrepareTestDatabase extends BaseCommand
{
    protected $group = 'Tests';
    protected $name = 'tests:prepare-db';
    protected $description = 'Drop all tables in the tests database and rerun the App migrations.';
    protected $usage = 'tests:prepare-db';

    /**
     * @param array<int, string> $params
     */
    public function run(array $params): int
    {
        CLI::write('Preparing test database (group "tests").');

        $db = $this->connectToTestsDatabase();
        if ($db === null) {
            return 1;
        }

        $isSqlite = strtolower($db->DBDriver) === 'sqlite3';
        $this->dropAllTables($db);

        if ($isSqlite) {
            $db->close();
            $db = $this->connectToTestsDatabase();
            if ($db === null) {
                return 1;
            }
        }
        if (! $isSqlite) {
            $this->resetMigrationHistory($db);
        }
        $this->migrateAppSchema($db);
        $ready = $this->ensureExpectedTablesPresent($db);

        if (! $ready) {
            CLI::error('Post-migration verification failed. Inspect the database and rerun the command.');
            return 1;
        }

        CLI::write('Test database prepared.', 'green');
        return 0;
    }

    /**
     * @return BaseConnection<mixed, mixed>|null
     */
    private function connectToTestsDatabase(): ?BaseConnection
    {
        try {
            $connection = Database::connect('tests');
            $connection->initialize();
            return $connection;
        } catch (DatabaseException $e) {
            CLI::error('Unable to connect to the tests database: ' . $e->getMessage());
            CLI::write('Ensure the `ci4_test` schema exists and the credentials in phpunit.xml/.env match.', 'yellow');
            return null;
        }
    }

    /**
     * @param BaseConnection<mixed, mixed> $db
     */
    private function dropAllTables(BaseConnection $db): void
    {
        $driver = strtolower($db->DBDriver);
        if ($driver === 'sqlite3') {
            $path = $db->database;
            if (is_file($path)) {
                unlink($path);
                CLI::write('Deleted existing SQLite test database file.');
            }
            return;
        }

        $allTables = $db->listTables();
        $tables = is_array($allTables)
            ? array_values(array_filter($allTables, static fn (string $table): bool => $table !== 'migrations'))
            : [];
        if (empty($tables)) {
            CLI::write('No tables found to drop.');
            return;
        }

        $this->disableForeignKeys($db);

        foreach ($tables as $table) {
            $db->query("DROP TABLE IF EXISTS `$table`");
        }

        $this->enableForeignKeys($db);
        CLI::write('Dropped all existing tables.');
    }

    /**
     * @param BaseConnection<mixed, mixed> $db
     */
    private function disableForeignKeys(BaseConnection $db): void
    {
        $driver = strtolower($db->DBDriver);
        if ($driver === 'mysqli' || $driver === 'mysql') {
            $db->query('SET FOREIGN_KEY_CHECKS=0');
        }
    }

    /**
     * @param BaseConnection<mixed, mixed> $db
     */
    private function enableForeignKeys(BaseConnection $db): void
    {
        $driver = strtolower($db->DBDriver);
        if ($driver === 'mysqli' || $driver === 'mysql') {
            $db->query('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    /**
     * @param BaseConnection<mixed, mixed> $db
     */
    private function migrateAppSchema(BaseConnection $db): void
    {
        $config = new Migrations();
        $config->enabled = true;

        /** @var MigrationRunner $runner */
        $runner = service('migrations', $config, $db, false);
        $runner->setSilent(false);
        $runner->setNamespace('App');
        $runner->latest('tests');
    }

    /**
     * @param BaseConnection<mixed, mixed> $db
     */
    private function resetMigrationHistory(BaseConnection $db): void
    {
        if (! $db->tableExists('migrations')) {
            return;
        }

        $db->table('migrations')
            ->where('group', 'tests')
            ->delete();
    }

    /**
     * @param BaseConnection<mixed, mixed> $db
     */
    private function ensureExpectedTablesPresent(BaseConnection $db): bool
    {
        $tables = $db->listTables();
        if (! is_array($tables)) {
            CLI::error('Unable to list tables after migrations.');
            return false;
        }

        $required = ['migrations'];
        foreach ($required as $table) {
            if (! in_array($table, $tables, true)) {
                CLI::error("Required table `{$table}` is missing after migrations.");
                return false;
            }
        }

        $count = 0;
        if ($db->tableExists('migrations')) {
            $count = $db->table('migrations')->countAllResults(false);
        }

        if ($count === 0) {
            CLI::error('`migrations` table does not contain entries.');
            return false;
        }

        return true;
    }
}
