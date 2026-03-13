<?php

namespace AndersonAv\Firebird;

use AndersonAv\Firebird\Query\Builder as FirebirdQueryBuilder;
use AndersonAv\Firebird\Query\Grammars\FirebirdGrammar as FirebirdQueryGrammar;
use AndersonAv\Firebird\Query\Processors\FirebirdProcessor as FirebirdQueryProcessor;
use AndersonAv\Firebird\Schema\Builder as FirebirdSchemaBuilder;
use AndersonAv\Firebird\Schema\Grammars\FirebirdGrammar as FirebirdSchemaGrammar;
use Illuminate\Database\Connection as DatabaseConnection;
use Illuminate\Support\Str;
use PDO;

class FirebirdConnection extends DatabaseConnection
{

    /**
     * Bind values to their parameters in the given statement.
     *
     * @param  \PDOStatement  $statement
     * @param  array  $bindings
     * @return void
     */
    public function bindValues($statement, $bindings)
    {
        foreach ($bindings as $key => $value) {
            $param = is_string($key) ? $key : $key + 1;

            if ($value === null) {
                $statement->bindValue($param, null, PDO::PARAM_NULL);
                continue;
            }

            if (is_resource($value)) {
                $statement->bindValue($param, $value, PDO::PARAM_LOB);
                continue;
            }

            $statement->bindValue($param, $this->fbStringify($value), PDO::PARAM_STR);
        }
    }

    private function fbStringify($value): string {
        if (is_float($value)) {
            return number_format($value, 8, '.', '');
        }

        return (string) $value;
    }
    
    /**
     * Get the server version for the connection.
     *
     * @return string
     */
    public function getServerVersion(): string
    {
        $version = $this->getPdo()->getAttribute(PDO::ATTR_SERVER_VERSION);

        $matches = [];
        preg_match('/(?<=LI-V)\d+\.\d+\.\d+/', $version, $matches);

        return $matches[0] ?? $version;
    }

    /**
     * Get the default query grammar instance.
     *
     * @return \Illuminate\Database\Query\Grammars\Grammar
     */
    protected function getDefaultQueryGrammar()
    {
        $grammar = new FirebirdQueryGrammar($this);

        return $grammar;
    }

    /**
     * Get the default post processor instance.
     *
     * @return \Illuminate\Database\Query\Processors\Processor
     */
    protected function getDefaultPostProcessor()
    {
        return new FirebirdQueryProcessor;
    }

    /**
     * Get a schema builder instance for the connection.
     *
     * @return \Illuminate\Database\Schema\Builder
     */
    public function getSchemaBuilder()
    {
        if (is_null($this->schemaGrammar)) {
            $this->useDefaultSchemaGrammar();
        }

        return new FirebirdSchemaBuilder($this);
    }

    /**
     * Get the default schema grammar instance.
     *
     * @return \Illuminate\Database\Schema\Grammars\Grammar|null
     */
    protected function getDefaultSchemaGrammar()
    {
        $grammar = new FirebirdSchemaGrammar($this);

        return $grammar;
    }

    /**
     * Get a new query builder instance.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function query()
    {
        return new FirebirdQueryBuilder(
            $this,
            $this->getQueryGrammar(),
            $this->getPostProcessor()
        );
    }

    /**
     * Execute a stored procedure.
     *
     * @param  string  $procedure
     * @param  array  $bindings
     * @return \Illuminate\Support\Collection
     */
    public function executeProcedure(string $procedure, array $bindings = [])
    {
        return $this->query()->procedure($procedure, $bindings)->get();
    }
}
