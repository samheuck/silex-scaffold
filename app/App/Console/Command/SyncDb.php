<?php

namespace App\Console\Command;

use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Table;
use App\Console\Command\BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Yaml\Parser;

class SyncDB extends BaseCommand
{
    private $schemas;
    private $tables;

    protected function configure()
    {
        $this
            ->setName("db-sync")
            ->setDescription("Install or update the database schema");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $db = $this->app['db']->getSchemaManager();

        // Get schema definitions.
        $yaml = new Parser();
        if ($dir = opendir($this->app['path']['base'] . '/schema')) {
            while (false !== ($entry = readdir($dir))) {
                if ($entry != "." && $entry != "..") {
                    $schema = $yaml->parse(file_get_contents($this->app['path']['base'] . "/schema/$entry"));
                    $this->schemas[key($schema)] = $schema[key($schema)];
                }
            }
        } else {
            throw new \Exception('Could not open schema directory.');
        }

        // Setup table definitions.
        foreach ($this->schemas as $tableName => $definition) {
            $this->tables[$tableName] = $this->createTable($tableName, $definition);
        }

        // Create any tables that do not already exist.
        foreach ($this->tables as $tableName => $table) {
            if (!$db->tablesExist($tableName)) {
                $db->createTable($table);
            }
        }

        // Create foreign keys.
        foreach ($this->tables as $tableName => $table) {
            if (isset($this->schemas[$tableName]['foreign_keys'])) {
                foreach ($this->schemas[$tableName]['foreign_keys'] as $fk) {
                    $table->addForeignKeyConstraint(
                        $this->tables[$fk['foreign_table']],
                        $fk['local_columns'],
                        $fk['foreign_columns'],
                        $fk['options']
                    );
                }
            }
        }

        // Commit to database.
        foreach ($this->tables as $tableName => $table) {
            if ($db->tablesExist($tableName)) {
                $fromDb = $db->listTableDetails($tableName);
                $comparator = new Comparator();
                if ($tableDiff = $comparator->diffTable($fromDb, $table)) {
                    $db->alterTable($tableDiff);
                }
            }
        }
    }

    private function createTable($tableName, $definition)
    {
        $table = new Table($tableName);

        // Add columns.
        foreach ($definition['fields'] as $field) {
            $table->addColumn($field['name'], $field['type'], $field['attributes']);
        }

        // Set primary key.
        if (isset($definition['primary_key'])) {
            $table->setPrimaryKey($definition['primary_key']);
        }

        // Create unique indexes.
        if (isset($definition['unique_index'])) {
            foreach ($definition['unique_index'] as $index) {
                $table->addUniqueIndex($index['fields']);
            }
        }

        // Create indexes.
        if (isset($definition['index'])) {
            foreach ($definition['unique_index'] as $index) {
                $table->addIndex($index['fields']);
            }
        }

        return $table;
    }
}
