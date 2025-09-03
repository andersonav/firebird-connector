<?php

namespace AndersonAv\Firebird\Query\Grammars;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Support\Str;
use RuntimeException;

class FirebirdGrammar extends Grammar
{
    protected $selectComponents = [
        'aggregate',
        'columns',
        'from',
        'joins',
        'wheres',
        'groups',
        'havings',
        'orders',
        'offset',
        'limit',
        'lock',
    ];

    protected $operators = [
        '=',
        '<',
        '>',
        '<=',
        '>=',
        '<>',
        '!=',
        '!<',
        '!>',
        '~<',
        '~>',
        '^<',
        '^>',
        '~=',
        '^=',
        'like',
        'not like',
        'between',
        'not between',
        'containing',
        'not containing',
        'starting with',
        'not starting with',
        'similar to',
        'not similar to',
        'is distinct from',
        'is not distinct from',
    ];

    /**
     * Compile the "select *" portion of the query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $columns
     * @return string|null
     */
    protected function compileColumns(Builder $query, $columns)
    {
        if (!is_null($query->aggregate)) {
            return;
        }

        $select = 'select ';

        $usesLegacy = $this->usesLegacyLimitAndOffset();

        if (isset($query->limit) && $usesLegacy) {
            $select .= $this->compileLegacyLimit($query, $query->limit) . ' ';
        }

        if (isset($query->offset) && $usesLegacy) {
            $select .= $this->compileLegacyOffset($query, $query->offset) . ' ';
        }

        if ($query->distinct) {
            if (is_array($query->distinct)) {
                throw new RuntimeException('This database engine does not support distinct on specific columns.');
            }

            $select .= 'distinct ';
        }

        return $select . $this->columnize($columns);
    }

    /**
     * Compile the "limit" portions of the query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  int  $limit
     * @return string
     */
    protected function compileLimit(Builder $query, $limit)
    {
        if ($this->usesLegacyLimitAndOffset()) {
            return;
        }

        return 'fetch first ' . (int) $limit . ' rows only';
    }

    /**
     * Compile the "limit" portions of the query for legacy versions of Firebird.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  int  $limit
     * @return string
     */
    protected function compileLegacyLimit(Builder $query, $limit)
    {
        return 'first ' . (int) $limit;
    }

    /**
     * Compile the "offset" portions of the query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  int  $offset
     * @return string
     */
    protected function compileOffset(Builder $query, $offset)
    {
        if ($this->usesLegacyLimitAndOffset()) {
            return;
        }

        return 'offset ' . (int) $offset . ' rows';
    }

    /**
     * Compile the "offset" portions of the query for legacy versions of Firebird.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  int  $offset
     * @return string
     */
    protected function compileLegacyOffset(Builder $query, $offset)
    {
        return 'skip ' . (int) $offset;
    }

    /**
     * Compile the random statement into SQL.
     *
     * @param  string  $seed
     * @return string
     */
    public function compileRandom($seed = null)
    {
        return 'rand()';
    }

    /**
     * Wrap a union subquery in parentheses.
     *
     * @param  string  $sql
     * @return string
     */

    protected function wrapUnion($sql)
    {
        return 'select * from (' . $sql . ')';
    }

    /**
     * Compile the "union" queries attached to the main query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return string
     */
    protected function compileUnions(Builder $query)
    {
        $sql = '';

        foreach ($query->unions as $union) {
            $sql .= $this->compileUnion($union);
        }

        if (!empty($query->unionOrders)) {
            $sql .= ' ' . $this->compileOrders($query, $query->unionOrders);
        }

        $usesLegacy = $this->usesLegacyLimitAndOffset();

        if (isset($query->unionOffset) && $usesLegacy) {
            throw new RuntimeException('This database engine does not support offset on union queries.');
        } elseif (isset($query->unionOffset)) {
            $sql .= ' ' . $this->compileOffset($query, $query->unionOffset);
        }

        if (isset($query->unionLimit) && $usesLegacy) {
            throw new RuntimeException('This database engine does not support limit on union queries.');
        } elseif (isset($query->unionLimit)) {
            $sql .= ' ' . $this->compileLimit($query, $query->unionLimit);
        }

        return ltrim($sql);
    }

    /**
     * Compile a date based where clause.
     *
     * @param  string  $type
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function dateBasedWhere($type, Builder $query, $where)
    {
        $condition = ($type === 'date' || $type === 'time')
            ? 'cast(' . $this->wrap($where['column']) . ' as ' . $type . ') '
            : 'extract(' . $type . ' from ' . $this->wrap($where['column']) . ') ';

        $condition .= $where['operator'] . ' ' . $this->parameter($where['value']);

        return $condition;
    }

    /**
     * Compile the select clause for a stored procedure.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string  $procedure
     * @param  array  $values
     * @return string
     */
    public function compileProcedure(Builder $query, $procedure, array $values = [])
    {
        $procedure = $this->wrap($procedure);
        return $procedure . ' (' . $this->parameterize($values) . ')';
    }

    /**
     * Compile an aggregated select clause.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $aggregate
     * @return string
     */
    protected function compileAggregate(Builder $query, $aggregate)
    {
        return Str::replaceLast(
            'as aggregate',
            'as "aggregate"',
            parent::compileAggregate($query, $aggregate)
        );
    }

    /**
     * Determine if the database uses the legacy limit and offset syntax.
     *
     * @return bool
     */
    protected function usesLegacyLimitAndOffset(): bool
    {
        return version_compare($this->connection->getServerVersion(), '3.0.0', '<');
    }
}
