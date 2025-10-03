<?php

namespace AndersonAv\Firebird\Query\Grammars;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Support\Str;
use RuntimeException;

class FirebirdGrammar extends Grammar
{

    /**
     * wrapValue.
     *
     * @var array
     */
	protected function wrapValue($value)
	{
		
		if ($value !== '*') {
			return '"'.strtoupper($value).'"';
		}
		
		return $value;
	}

	/**
     * Wrap an array of values.
     *
     * @param  array  $values
     * @return array
     */
    public function wrapArray(array $values)
    {
        return array_map([$this, 'wrap'], $values);
    }

	/**
     * Wrap a table in keyword identifiers.
     *
     * @param  mixed  $table
     * @return string
     */
    public function wrapTable($table)
    {
        return $this->wrapTableFB(
            $table instanceof Blueprint ? $table->getTable() : $table
        );
    }
	
    /**
     * Wrap a table in keyword identifiers.
     *
     * @param  \Illuminate\Contracts\Database\Query\Expression|string  $table
     * @return string
     */
    public function wrapTableFB($table)
    {
		
        if ($this->isExpression($table)) {
            return $this->getValue($table);
        }
		
        // If the table being wrapped has an alias we'll need to separate the pieces
        // so we can prefix the table and then wrap each of the segments on their
        // own and then join these both back together using the "as" connector.
        if (stripos($table, ' as ') !== false) {
            return $this->wrapAliasedTable($table);
        }

        // If the table being wrapped has a custom schema name specified, we need to
        // prefix the last segment as the table name then wrap each segment alone
        // and eventually join them both back together using the dot connector.
        if (str_contains($table, '.')) {
            $table = substr_replace($table, '.'.$this->tablePrefix, strrpos($table, '.'), 1);

            return collect(explode('.', $table))
                ->map($this->wrapValue(...))
                ->implode('.');
        }

        return $this->wrapValue($this->tablePrefix.$table);
    }

	/**
     * Wrap a value in keyword identifiers.
     *
     * @param  \Illuminate\Support\Fluent|\Illuminate\Contracts\Database\Query\Expression|string  $value
     * @return string
     */
    public function wrap($value)
    {
        return $this->wrapFB(
            $value instanceof Fluent ? $value->name : $value,
        );
    }
	
    /**
     * Wrap a value in keyword identifiers.
     *
     * @param  \Illuminate\Contracts\Database\Query\Expression|string  $value
     * @return string
     */
    public function wrapFB($value)
    {
        if ($this->isExpression($value)) {
            return $this->getValue($value);
        }

        // If the value being wrapped has a column alias we will need to separate out
        // the pieces so we can wrap each of the segments of the expression on its
        // own, and then join these both back together using the "as" connector.
        if (stripos($value, ' as ') !== false) {
            return $this->wrapAliasedValue($value);
        }

        // If the given value is a JSON selector we will wrap it differently than a
        // traditional value. We will need to split this path and wrap each part
        // wrapped, etc. Otherwise, we will simply wrap the value as a string.
        if ($this->isJsonSelector($value)) {
            return $this->wrapJsonSelector($value);
        }

        return $this->wrapSegments(explode('.', $value));
    }

	/**
     * Wrap a value that has an alias.
     *
     * @param  string  $value
     * @return string
     */
    protected function wrapAliasedValue($value)
    {
        $segments = preg_split('/\s+as\s+/i', $value);

        return $this->wrap($segments[0]).' as '.$this->wrapValue($segments[1]);
    }

    /**
     * Wrap a table that has an alias.
     *
     * @param  string  $value
     * @return string
     */
    protected function wrapAliasedTable($value)
    {
        $segments = preg_split('/\s+as\s+/i', $value);

        return $this->wrapTable($segments[0]).' as '.$this->wrapValue($this->tablePrefix.$segments[1]);
    }

    /**
     * Wrap the given value segments.
     *
     * @param  array  $segments
     * @return string
     */
    protected function wrapSegments($segments)
    {
        return collect($segments)->map(function ($segment, $key) use ($segments) {
            return $key == 0 && count($segments) > 1
                            ? $this->wrapTable($segment)
                            : $this->wrapValue($segment);
        })->implode('.');
    }
	
	/**
     * Format a value so that it can be used in "default" clauses.
     *
     * @param  mixed  $value
     * @return string
     */
    protected function getDefaultValue($value)
    {
        if ($value instanceof Expression) {
            return $this->getValue($value);
        }

        if ($value instanceof BackedEnum) {
            return "'{$value->value}'";
        }

        return is_bool($value)
                    ? "'".(int) $value."'"
                    : "'".(string) $value."'";
    }

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
        // Se não houver conexão, assume Firebird >= 3
        if (!$this->connection) {
            return false;
        }

        $version = $this->connection->getServerVersion() ?: '3.0.0';

        return version_compare($version, '3.0.0', '<');
    }
}