<?php

namespace Kentron\Template\Phinx;

use Phinx\Migration\AbstractMigration;

abstract class AMigration extends AbstractMigration
{
    /**
     * Returns the length of the column provided
     *
     * @param string $table  The table in which the column belongs
     * @param string $column The column in question
     *
     * @return null|int Null if the table/column does not exist
     */
    protected function getColumnLength (string $table, string $column): ?int
    {
        if (!$this->hasTable($table)) {
            return null;
        }

        if (!$this->table($table)->hasColumn($column)) {
            return null;
        }

        $response = $this->fetchRow(
            "SELECT
                CHARACTER_MAXIMUM_LENGTH
            FROM
                `information_schema`.columns
            WHERE
                table_name = '$table'
            AND
                COLUMN_NAME = '$column'"
        );

        return $response["CHARACTER_MAXIMUM_LENGTH"] ?? null;
    }
}
