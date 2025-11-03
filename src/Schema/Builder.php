<?php

namespace AndersonAv\Firebird\Schema;

use Illuminate\Database\Schema\Builder as SchemaBuilder;
use AndersonAv\Firebird\Schema\Blueprint;

class Builder extends SchemaBuilder
{
    /**
     * Create a new Blueprint instance.
     *
     * @param  string  $table
     * @param  \Closure|null  $callback
     * @return \AndersonAv\Firebird\Schema\Blueprint
     */
    protected function createBlueprint($table, \Closure $callback = null)
    {
        return new Blueprint($this->connection, $table, $callback);
    }

    /**
     * Determine if a table exists in the database.
     *
     * @param  string  $table
     * @return bool
     */
    public function hasTable($table)
    {
        $table = strtoupper(trim($this->connection->getTablePrefix() . $table));
        
        $result = $this->connection->select(
            'SELECT RDB$RELATION_NAME FROM RDB$RELATIONS WHERE RDB$RELATION_NAME = ?', [$table]
        );

        return !empty($result);
    }

}
