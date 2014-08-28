<?php

namespace App\Console\Command;

use App\Console\Command\BaseCommand;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Parser;

class SchemaSync extends BaseCommand
{
    private $schemas;
    private $tables;

    protected function configure()
    {
        $this
            ->setName("db:schema-sync")
            ->setDescription("Install or update the database schema");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->parseSchema();
        $this->createTables();
        $this->commitNewTables();
        $this->createForeignKeys();
        $this->commitChanges();
    }

    private function parseSchema()
    {
        $yaml = new Parser();

        if ($dir = opendir($this->app['paths']['base'] . '/schema')) {
            while (false !== ($fileName = readdir($dir))) {
                if ('.' !== $fileName && '..' !== $fileName) {
                    $schema = $yaml->parse(file_get_contents($this->app['paths']['base'] . "/schema/$fileName"));
                    $this->schemas[key($schema)] = $schema[key($schema)];
                }
            }
        } else {
            throw new \Exception('Could not open schema directory.');
        }
    }

    private function createTables()
    {
        foreach ($this->schemas as $tableName => $definition) {
            $this->tables[$tableName] = $this->createTable($tableName, $definition);
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

    private function commitNewTables()
    {
        $db = $this->app['db']->getSchemaManager();

        // Create any tables that do not already exist.
        foreach ($this->tables as $tableName => $table) {
            if (!$db->tablesExist($tableName)) {
                $db->createTable($table);
            }
        }
    }

    private function createForeignKeys()
    {
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
    }

    private function commitChanges()
    {
        $db = $this->app['db']->getSchemaManager();
        $comparator = new Comparator();

        foreach ($this->tables as $tableName => $table) {
            if ($db->tablesExist($tableName)) {
                $fromDb = $db->listTableDetails($tableName);

                if ($tableDiff = $comparator->diffTable($fromDb, $table)) {
                    $db->alterTable($tableDiff);
                }
            }
        }
    }
}