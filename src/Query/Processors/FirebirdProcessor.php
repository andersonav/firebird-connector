<?php

namespace AndersonAv\Firebird\Query\Processors;

use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Database\Query\Builder as LaravelQueryBuilder;

class FirebirdProcessor extends Processor
{
    /**
     * Process the results of a column listing query.
     *
     * @param  array  $results
     * @return array
     */
    public function processColumnListing($results)
    {
        return array_map(function ($result) {
            return ((object) $result)->column_name;
        }, $results);
    }

    /**
     * Process an  "insert get ID" query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string  $sql
     * @param  array  $values
     * @param  string|null  $sequence
     * @return int
     */
    public function processInsertGetId(LaravelQueryBuilder $query, $sql, $values, $sequence = null)
    {
        $query->getConnection()->insert($sql, $values);

        // Executa uma consulta para pegar o próximo valor do generator (sequência) no Firebird
        $id = $query->getConnection()->selectOne("SELECT GEN_ID($sequence, 0) AS ID FROM RDB\$DATABASE");

        return is_numeric($id) ? (int) $id : $id;
    }
}
