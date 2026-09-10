<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * The default rollback journal takes an exclusive lock on the whole database
 * for every write, which the three queue workers collide on immediately.
 * Write-ahead logging lets readers carry on while one worker writes.
 *
 * The setting is stored in the database file rather than on the connection, so
 * running it once here covers every later connection. It gets its own
 * migration because SQLite refuses to change journal mode inside a
 * transaction, and the schema migration should stay atomic.
 */
final class Version20260910090100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Switch SQLite to write-ahead logging so readers and a writer can work at once.';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('PRAGMA journal_mode = WAL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('PRAGMA journal_mode = DELETE');
    }
}
