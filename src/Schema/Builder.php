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
        return new Blueprint($table, $callback);
    }
}
