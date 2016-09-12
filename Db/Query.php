<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Db
 * @subpackage Select
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */


/**
 * @see Zend_Db_Expr
 */
require_once 'Zend/Db/Expr.php';


/**
 * Class for SQL SELECT generation without direct connection to the DB.
 *
 * Extend this class and replace any method to adapt it to other SQL server,
 * especially methods quote*() and real_string_escape() may need to be changed.
 *
 * @category   Zend
 * @package    Zend_Db
 * @subpackage Select
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
abstract class Zend_Db_Query
{

    const DISTINCT       = 'distinct';
    const COLUMNS        = 'columns';
    const FROM           = 'from';
    const UNION          = 'union';
    const WHERE          = 'where';
    const GROUP          = 'group';
    const HAVING         = 'having';
    const ORDER          = 'order';
    const LIMIT_COUNT    = 'limitcount';
    const LIMIT_OFFSET   = 'limitoffset';
    const FOR_UPDATE     = 'forupdate';

    const INNER_JOIN     = 'inner join';
    const LEFT_JOIN      = 'left join';
    const RIGHT_JOIN     = 'right join';
    const FULL_JOIN      = 'full join';
    const CROSS_JOIN     = 'cross join';
    const NATURAL_JOIN   = 'natural join';

    const SQL_WILDCARD   = '*';
    const SQL_SELECT     = 'SELECT';
    const SQL_UNION      = 'UNION';
    const SQL_UNION_ALL  = 'UNION ALL';
    const SQL_FROM       = 'FROM';
    const SQL_WHERE      = 'WHERE';
    const SQL_DISTINCT   = 'DISTINCT';
    const SQL_GROUP_BY   = 'GROUP BY';
    const SQL_ORDER_BY   = 'ORDER BY';
    const SQL_HAVING     = 'HAVING';
    const SQL_FOR_UPDATE = 'FOR UPDATE';
    const SQL_AND        = 'AND';
    const SQL_AS         = 'AS';
    const SQL_OR         = 'OR';
    const SQL_ON         = 'ON';
    const SQL_ASC        = 'ASC';
    const SQL_DESC       = 'DESC';

    /**
     * Bind variables for query
     *
     * @var array
     */
    protected $_bind = array();

    /**
     * The initial values for the $_parts array.
     * NOTE: It is important for the 'FOR_UPDATE' part to be last to ensure
     * meximum compatibility with database adapters.
     *
     * @var array
     */
    protected static $_partsInit = array(
        self::DISTINCT     => false,
        self::COLUMNS      => array(),
        self::UNION        => array(),
        self::FROM         => array(),
        self::WHERE        => array(),
        self::GROUP        => array(),
        self::HAVING       => array(),
        self::ORDER        => array(),
        self::LIMIT_COUNT  => null,
        self::LIMIT_OFFSET => null,
        self::FOR_UPDATE   => false
    );

    /**
     * Specify legal join types.
     *
     * @var array
     */
    protected static $_joinTypes = array(
        self::INNER_JOIN,
        self::LEFT_JOIN,
        self::RIGHT_JOIN,
        self::FULL_JOIN,
        self::CROSS_JOIN,
        self::NATURAL_JOIN,
    );

    /**
     * Specify legal union types.
     *
     * @var array
     */
    protected static $_unionTypes = array(
        self::SQL_UNION,
        self::SQL_UNION_ALL
    );

    /**
     * The component parts of a SELECT statement.
     * Initialized to the $_partsInit array in the constructor.
     *
     * @var array
     */
    protected $_parts = array();

    /**
     * Tracks which columns are being select from each table and join.
     *
     * @var array
     */
    protected $_tableCols = array();

    /**
     * Class constructor
     *
     * @param Zend_Db_Adapter_Abstract $adapter
     */
    public function __construct()
    {
        $this->_parts = self::$_partsInit;
    }

    /**
     * Get bind variables
     *
     * @return array
     */
    public function getBind()
    {
        return $this->_bind;
    }

    /**
     * Set bind variables
     *
     * @param mixed $bind
     * @return Zend_Db_Query
     */
    public function bind($bind)
    {
        $this->_bind = $bind;

        return $this;
    }

    /**
     * Makes the query SELECT DISTINCT.
     *
     * @param bool $flag Whether or not the SELECT is DISTINCT (default true).
     * @return Zend_Db_Query
     */
    public function distinct($flag = true)
    {
        $this->_parts[self::DISTINCT] = (bool) $flag;
        return $this;
    }

    /**
     * Adds a FROM table and optional columns to the query.
     *
     * The first parameter $name can be a simple string, in which case the
     * correlation name is generated automatically.  If you want to specify
     * the correlation name, the first parameter must be an associative
     * array in which the key is the correlation name, and the value is
     * the physical table name.  For example, array('alias' => 'table').
     * The correlation name is prepended to all columns fetched for this
     * table.
     *
     * The second parameter can be a single string or Zend_Db_Expr object,
     * or else an array of strings or Zend_Db_Expr objects.
     *
     * The first parameter can be null or an empty string, in which case
     * no correlation name is generated or prepended to the columns named
     * in the second parameter.
     *
     * @param  array|string|Zend_Db_Expr $name The table name or an associative array
     *                                         relating correlation name to table name.
     * @param  array|string|Zend_Db_Expr $cols The columns to select from this table.
     * @param  string $schema The schema name to specify, if any.
     * @return Zend_Db_Query
     */
    public function from($name, $cols = null, $schema = null)
    {
        return $this->_join(self::FROM, $name, null, $cols, $schema);
    }

    /**
     * Specifies the columns used in the FROM clause.
     *
     * The parameter can be a single string or Zend_Db_Expr object,
     * or else an array of strings or Zend_Db_Expr objects.
     *
     * @param  array|string|Zend_Db_Expr $cols The columns to select from this table.
     * @param  string $correlationName Correlation name of target table. OPTIONAL
     * @return Zend_Db_Query
     */
    public function columns($cols = self::SQL_WILDCARD, $correlationName = null)
    {
        if ($correlationName === null && count($this->_parts[self::FROM])) {
            $correlationNameKeys = array_keys($this->_parts[self::FROM]);
            $correlationName = current($correlationNameKeys);
        }

        if (!array_key_exists($correlationName, $this->_parts[self::FROM])) {
            /**
             * @see Zend_Db_Select_Exception
             */
            require_once 'Zend/Db/Select/Exception.php';
            throw new Zend_Db_Select_Exception("No table has been specified for the FROM clause");
        }

        $this->_tableCols($correlationName, $cols);

        return $this;
    }

    /**
     * Adds a UNION clause to the query.
     *
     * The first parameter has to be an array of Zend_Db_Query or
     * sql query strings.
     *
     * <code>
     * $sql1 = $db->select();
     * $sql2 = "SELECT ...";
     * $select = $db->select()
     *      ->union(array($sql1, $sql2))
     *      ->order("id");
     * </code>
     *
     * @param  array $select Array of select clauses for the union.
     * @return Zend_Db_Query
     */
    public function union($select = array(), $type = self::SQL_UNION)
    {
        if (!is_array($select)) {
            require_once 'Zend/Db/Select/Exception.php';
            throw new Zend_Db_Select_Exception(
                "union() only accepts an array of Zend_Db_Query instances of sql query strings."
            );
        }

        if (!in_array($type, self::$_unionTypes)) {
            require_once 'Zend/Db/Select/Exception.php';
            throw new Zend_Db_Select_Exception("Invalid union type '{$type}'");
        }

        foreach ($select as $target) {
            $this->_parts[self::UNION][] = array($target, $type);
        }

        return $this;
    }

    /**
     * Adds a JOIN table and columns to the query.
     *
     * The $name and $cols parameters follow the same logic
     * as described in the from() method.
     *
     * @param  array|string|Zend_Db_Expr $name The table name.
     * @param  string $cond Join on this condition.
     * @param  array|string $cols The columns to select from the joined table.
     * @param  string $schema The database name to specify, if any.
     * @return Zend_Db_Query
     */
    public function join($name, $cond, $cols = null, $schema = null)
    {
        return $this->joinInner($name, $cond, $cols, $schema);
    }

    /**
     * Add an INNER JOIN table and colums to the query
     * Rows in both tables are matched according to the expression
     * in the $cond argument.  The result set is comprised
     * of all cases where rows from the left table match
     * rows from the right table.
     *
     * The $name and $cols parameters follow the same logic
     * as described in the from() method.
     *
     * @param  array|string|Zend_Db_Expr $name The table name.
     * @param  string $cond Join on this condition.
     * @param  array|string $cols The columns to select from the joined table.
     * @param  string $schema The database name to specify, if any.
     * @return Zend_Db_Query
     */
    public function joinInner($name, $cond, $cols = null, $schema = null)
    {
        return $this->_join(self::INNER_JOIN, $name, $cond, $cols, $schema);
    }

    /**
     * Add a LEFT OUTER JOIN table and colums to the query
     * All rows from the left operand table are included,
     * matching rows from the right operand table included,
     * and the columns from the right operand table are filled
     * with NULLs if no row exists matching the left table.
     *
     * The $name and $cols parameters follow the same logic
     * as described in the from() method.
     *
     * @param  array|string|Zend_Db_Expr $name The table name.
     * @param  string $cond Join on this condition.
     * @param  array|string $cols The columns to select from the joined table.
     * @param  string $schema The database name to specify, if any.
     * @return Zend_Db_Query
     */
    public function joinLeft($name, $cond, $cols = null, $schema = null)
    {
        return $this->_join(self::LEFT_JOIN, $name, $cond, $cols, $schema);
    }

    /**
     * Add a RIGHT OUTER JOIN table and colums to the query.
     * Right outer join is the complement of left outer join.
     * All rows from the right operand table are included,
     * matching rows from the left operand table included,
     * and the columns from the left operand table are filled
     * with NULLs if no row exists matching the right table.
     *
     * The $name and $cols parameters follow the same logic
     * as described in the from() method.
     *
     * @param  array|string|Zend_Db_Expr $name The table name.
     * @param  string $cond Join on this condition.
     * @param  array|string $cols The columns to select from the joined table.
     * @param  string $schema The database name to specify, if any.
     * @return Zend_Db_Query
     */
    public function joinRight($name, $cond, $cols = null, $schema = null)
    {
        return $this->_join(self::RIGHT_JOIN, $name, $cond, $cols, $schema);
    }

    /**
     * Add a FULL OUTER JOIN table and colums to the query.
     * A full outer join is like combining a left outer join
     * and a right outer join.  All rows from both tables are
     * included, paired with each other on the same row of the
     * result set if they satisfy the join condition, and otherwise
     * paired with NULLs in place of columns from the other table.
     *
     * The $name and $cols parameters follow the same logic
     * as described in the from() method.
     *
     * @param  array|string|Zend_Db_Expr $name The table name.
     * @param  string $cond Join on this condition.
     * @param  array|string $cols The columns to select from the joined table.
     * @param  string $schema The database name to specify, if any.
     * @return Zend_Db_Query
     */
    public function joinFull($name, $cond, $cols = null, $schema = null)
    {
        return $this->_join(self::FULL_JOIN, $name, $cond, $cols, $schema);
    }

    /**
     * Add a CROSS JOIN table and colums to the query.
     * A cross join is a cartesian product; there is no join condition.
     *
     * The $name and $cols parameters follow the same logic
     * as described in the from() method.
     *
     * @param  array|string|Zend_Db_Expr $name The table name.
     * @param  array|string $cols The columns to select from the joined table.
     * @param  string $schema The database name to specify, if any.
     * @return Zend_Db_Query
     */
    public function joinCross($name, $cols = null, $schema = null)
    {
        return $this->_join(self::CROSS_JOIN, $name, null, $cols, $schema);
    }

    /**
     * Add a NATURAL JOIN table and colums to the query.
     * A natural join assumes an equi-join across any column(s)
     * that appear with the same name in both tables.
     * Only natural inner joins are supported by this API,
     * even though SQL permits natural outer joins as well.
     *
     * The $name and $cols parameters follow the same logic
     * as described in the from() method.
     *
     * @param  array|string|Zend_Db_Expr $name The table name.
     * @param  array|string $cols The columns to select from the joined table.
     * @param  string $schema The database name to specify, if any.
     * @return Zend_Db_Query
     */
    public function joinNatural($name, $cols = null, $schema = null)
    {
        return $this->_join(self::NATURAL_JOIN, $name, null, $cols, $schema);
    }

    /**
     * Adds a WHERE condition to the query by AND.
     *
     * If a value is passed as the second param, it will be quoted
     * and replaced into the condition wherever a question-mark
     * appears. Array values are quoted and comma-separated.
     *
     * <code>
     * // simplest but non-secure
     * $select->where("id = $id");
     *
     * // secure (ID is quoted but matched anyway)
     * $select->where('id = ?', $id);
     *
     * // alternatively, with named binding
     * $select->where('id = :id');
     * </code>
     *
     * Note that it is more correct to use named bindings in your
     * queries for values other than strings. When you use named
     * bindings, don't forget to pass the values when actually
     * making a query:
     *
     * <code>
     * $db->fetchAll($select, array('id' => 5));
     * </code>
     *
     * @param string   $cond  The WHERE condition.
     * @param mixed    $value OPTIONAL The value to quote into the condition.
     * @param int      $type  OPTIONAL The type of the given value
     * @return Zend_Db_Query
     */
    public function where($cond, $value = null, $type = null)
    {
        $this->_parts[self::WHERE][] = $this->_where($cond, $value, $type, true);

        return $this;
    }

    /**
     * Adds a WHERE condition to the query by OR.
     *
     * Otherwise identical to where().
     *
     * @param string   $cond  The WHERE condition.
     * @param mixed    $value OPTIONAL The value to quote into the condition.
     * @param int      $type  OPTIONAL The type of the given value
     * @return Zend_Db_Query
     *
     * @see where()
     */
    public function orWhere($cond, $value = null, $type = null)
    {
        $this->_parts[self::WHERE][] = $this->_where($cond, $value, $type, false);

        return $this;
    }

    /**
     * Adds grouping to the query.
     *
     * @param  array|string $spec The column(s) to group by.
     * @return Zend_Db_Query
     */
    public function group($spec)
    {
        if (!is_array($spec)) {
            $spec = array($spec);
        }

        foreach ($spec as $val) {
            if (preg_match('/^([\w]*\(([^\)]|(?1))*\))$/', (string) $val)) {
                $val = new Zend_Db_Expr($val);
            }
            $this->_parts[self::GROUP][] = $val;
        }

        return $this;
    }

    /**
     * Adds a HAVING condition to the query by AND.
     *
     * If a value is passed as the second param, it will be quoted
     * and replaced into the condition wherever a question-mark
     * appears. See {@link where()} for an example
     *
     * @param string $cond The HAVING condition.
     * @param mixed    $value OPTIONAL The value to quote into the condition.
     * @param int      $type  OPTIONAL The type of the given value
     * @return Zend_Db_Query
     */
    public function having($cond, $value = null, $type = null)
    {
        if ($value !== null) {
            $cond = $this->quoteInto($cond, $value, $type);
        }

        if ($this->_parts[self::HAVING]) {
            $this->_parts[self::HAVING][] = self::SQL_AND . " ($cond)";
        } else {
            $this->_parts[self::HAVING][] = "($cond)";
        }

        return $this;
    }

    /**
     * Adds a HAVING condition to the query by OR.
     *
     * Otherwise identical to orHaving().
     *
     * @param string $cond The HAVING condition.
     * @param mixed    $value OPTIONAL The value to quote into the condition.
     * @param int      $type  OPTIONAL The type of the given value
     * @return Zend_Db_Query
     *
     * @see having()
     */
    public function orHaving($cond, $value = null, $type = null)
    {
        if ($value !== null) {
            $cond = $this->quoteInto($cond, $value, $type);
        }

        if ($this->_parts[self::HAVING]) {
            $this->_parts[self::HAVING][] = self::SQL_OR . " ($cond)";
        } else {
            $this->_parts[self::HAVING][] = "($cond)";
        }

        return $this;
    }

    /**
     * Adds a row order to the query.
     *
     * @param mixed $spec The column(s) and direction to order by.
     * @return Zend_Db_Query
     */
    public function order($spec)
    {
        if (!is_array($spec)) {
            $spec = array($spec);
        }

        // force 'ASC' or 'DESC' on each order spec, default is ASC.
        foreach ($spec as $val) {
            if ($val instanceof Zend_Db_Expr) {
                $expr = $val->__toString();
                if (empty($expr)) {
                    continue;
                }
                $this->_parts[self::ORDER][] = $val;
            } else {
                if (empty($val)) {
                    continue;
                }
                $direction = self::SQL_ASC;
                if (preg_match('/(.*\W)(' . self::SQL_ASC . '|' . self::SQL_DESC . ')\b/si', $val, $matches)) {
                    $val = trim($matches[1]);
                    $direction = $matches[2];
                }
                if (preg_match('/^([\w]*\(([^\)]|(?1))*\))$/', (string) $val)) {
                    $val = new Zend_Db_Expr($val);
                }
                $this->_parts[self::ORDER][] = array($val, $direction);
            }
        }

        return $this;
    }

    /**
     * Sets a limit count and offset to the query.
     *
     * @param int $count OPTIONAL The number of rows to return.
     * @param int $offset OPTIONAL Start returning after this many rows.
     * @return Zend_Db_Query
     */
    public function limit($count = null, $offset = null)
    {
        $this->_parts[self::LIMIT_COUNT]  = (int) $count;
        $this->_parts[self::LIMIT_OFFSET] = (int) $offset;
        return $this;
    }

    /**
     * Sets the limit and count by page number.
     *
     * @param int $page Limit results to this page number.
     * @param int $rowCount Use this many rows per page.
     * @return Zend_Db_Query
     */
    public function limitPage($page, $rowCount)
    {
        $page     = ($page > 0)     ? $page     : 1;
        $rowCount = ($rowCount > 0) ? $rowCount : 1;
        $this->_parts[self::LIMIT_COUNT]  = (int) $rowCount;
        $this->_parts[self::LIMIT_OFFSET] = (int) $rowCount * ($page - 1);
        return $this;
    }

    /**
     * Makes the query SELECT FOR UPDATE.
     *
     * @param bool $flag Whether or not the SELECT is FOR UPDATE (default true).
     * @return Zend_Db_Query
     */
    public function forUpdate($flag = true)
    {
        $this->_parts[self::FOR_UPDATE] = (bool) $flag;
        return $this;
    }

    /**
     * Get part of the structured information for the current query.
     *
     * @param string $part
     * @return mixed
     * @throws Zend_Db_Select_Exception
     */
    public function getPart($part)
    {
        $part = strtolower($part);
        if (!array_key_exists($part, $this->_parts)) {
            require_once 'Zend/Db/Select/Exception.php';
            throw new Zend_Db_Select_Exception("Invalid Select part '$part'");
        }
        return $this->_parts[$part];
    }

    /**
     * Converts this object to an SQL SELECT string.
     *
     * @return string|null This object as a SELECT string. (or null if a string cannot be produced.)
     */
    public function assemble()
    {
        $sql = self::SQL_SELECT;
        foreach (array_keys(self::$_partsInit) as $part) {
            $method = '_render' . ucfirst($part);
            if (method_exists($this, $method)) {
                $sql = $this->$method($sql);
            }
        }
        return $sql;
    }

    /**
     * Clear parts of the Select object, or an individual part.
     *
     * @param string $part OPTIONAL
     * @return Zend_Db_Query
     */
    public function reset($part = null)
    {
        if ($part == null) {
            $this->_parts = self::$_partsInit;
        } elseif (array_key_exists($part, self::$_partsInit)) {
            $this->_parts[$part] = self::$_partsInit[$part];
        }
        return $this;
    }

    /**
     * Populate the {@link $_parts} 'join' key
     *
     * Does the dirty work of populating the join key.
     *
     * The $name and $cols parameters follow the same logic
     * as described in the from() method.
     *
     * @param  null|string $type Type of join; inner, left, and null are currently supported
     * @param  array|string|Zend_Db_Expr $name Table name
     * @param  string $cond Join on this condition
     * @param  array|string $cols The columns to select from the joined table
     * @param  string $schema The database name to specify, if any.
     * @return Zend_Db_Query
     * @throws Zend_Db_Select_Exception
     */
    protected function _join($type, $name, $cond, $cols = null, $schema = null)
    {
        if (!in_array($type, self::$_joinTypes) && $type != self::FROM) {
            /**
             * @see Zend_Db_Select_Exception
             */
            require_once 'Zend/Db/Select/Exception.php';
            throw new Zend_Db_Select_Exception("Invalid join type '$type'");
        }

        if (count($this->_parts[self::UNION])) {
            require_once 'Zend/Db/Select/Exception.php';
            throw new Zend_Db_Select_Exception("Invalid use of table with " . self::SQL_UNION);
        }

        if (empty($name)) {
            $correlationName = $tableName = '';
        } elseif (is_array($name)) {
            // Must be array($correlationName => $tableName) or array($ident, ...)
            foreach ($name as $_correlationName => $_tableName) {
                if (is_string($_correlationName)) {
                    // We assume the key is the correlation name and value is the table name
                    $tableName = $_tableName;
                    $correlationName = $_correlationName;
                } else {
                    // We assume just an array of identifiers, with no correlation name
                    $tableName = $_tableName;
                    $correlationName = $this->_uniqueCorrelation($tableName);
                }
                break;
            }
        } elseif ($name instanceof Zend_Db_Expr || $name instanceof Zend_Db_Select || $name instanceof Zend_Db_Query) {
            $tableName = $name;
            $correlationName = $this->_uniqueCorrelation('t');
        } elseif (preg_match('/^(.+)\s+AS\s+(.+)$/i', $name, $m)) {
            $tableName = $m[1];
            $correlationName = $m[2];
        } else {
            $tableName = $name;
            $correlationName = $this->_uniqueCorrelation($tableName);
        }

        // Schema from table name overrides schema argument
        if (!is_object($tableName) && false !== strpos($tableName, '.')) {
            list($schema, $tableName) = explode('.', $tableName);
        }

        $lastFromCorrelationName = null;
        if (!empty($correlationName)) {
            if (array_key_exists($correlationName, $this->_parts[self::FROM])) {
                /**
                 * @see Zend_Db_Select_Exception
                 */
                require_once 'Zend/Db/Select/Exception.php';
                throw new Zend_Db_Select_Exception("You cannot define a correlation name '$correlationName' more than once");
            }

            if ($type == self::FROM) {
                // append this from after the last from joinType
                $tmpFromParts = $this->_parts[self::FROM];
                $this->_parts[self::FROM] = array();
                // move all the froms onto the stack
                while ($tmpFromParts) {
                    $currentCorrelationName = key($tmpFromParts);
                    if ($tmpFromParts[$currentCorrelationName]['joinType'] != self::FROM) {
                        break;
                    }
                    $lastFromCorrelationName = $currentCorrelationName;
                    $this->_parts[self::FROM][$currentCorrelationName] = array_shift($tmpFromParts);
                }
            } else {
                $tmpFromParts = array();
            }
            $this->_parts[self::FROM][$correlationName] = array(
                'joinType'      => $type,
                'schema'        => $schema,
                'tableName'     => $tableName,
                'joinCondition' => $cond
                );
            while ($tmpFromParts) {
                $currentCorrelationName = key($tmpFromParts);
                $this->_parts[self::FROM][$currentCorrelationName] = array_shift($tmpFromParts);
            }
        }

        // add to the columns from this joined table
        if ($type == self::FROM && $lastFromCorrelationName == null) {
            $lastFromCorrelationName = true;
        }

        if (isset($cols)) {
        	$this->_tableCols($correlationName, $cols, $lastFromCorrelationName);
        }

        return $this;
    }

    /**
     * Handle JOIN... USING... syntax
     *
     * This is functionality identical to the existing JOIN methods, however
     * the join condition can be passed as a single column name. This method
     * then completes the ON condition by using the same field for the FROM
     * table and the JOIN table.
     *
     * <code>
     * $select = $db->select()->from('table1')
     *                        ->joinUsing('table2', 'column1');
     *
     * // SELECT * FROM table1 JOIN table2 ON table1.column1 = table2.column2
     * </code>
     *
     * These joins are called by the developer simply by adding 'Using' to the
     * method name. E.g.
     * * joinUsing
     * * joinInnerUsing
     * * joinFullUsing
     * * joinRightUsing
     * * joinLeftUsing
     *
     * @return Zend_Db_Query
     */
    public function _joinUsing($type, $name, $cond, $cols = null, $schema = null)
    {
        if (empty($this->_parts[self::FROM])) {
            require_once 'Zend/Db/Select/Exception.php';
            throw new Zend_Db_Select_Exception("You can only perform a joinUsing after specifying a FROM table");
        }

        $join  = $this->quoteIdentifier(key($this->_parts[self::FROM]), true);
        $from  = $this->quoteIdentifier($this->_uniqueCorrelation($name), true);

        $joinCond = array();
        foreach ((array)$cond as $fieldName) {
            $cond1 = $from . '.' . $fieldName;
            $cond2 = $join . '.' . $fieldName;
            $joinCond[]  = $cond1 . ' = ' . $cond2;
        }
        $cond = implode(' '.self::SQL_AND.' ', $joinCond);

        return $this->_join($type, $name, $cond, $cols, $schema);
    }

    /**
     * Generate a unique correlation name
     *
     * @param string|array $name A qualified identifier.
     * @return string A unique correlation name.
     */
    private function _uniqueCorrelation($name)
    {
        if (is_array($name)) {
            $k = key($name);
            $c = is_string($k) ? $k : end($name);
        } else {
            // Extract just the last name of a qualified table name
            $dot = strrpos($name,'.');
            $c = ($dot === false) ? $name : substr($name, $dot+1);
        }
        for ($i = 2; array_key_exists($c, $this->_parts[self::FROM]); ++$i) {
            $c = $name . '_' . (string) $i;
        }
        return $c;
    }

    /**
     * Adds to the internal table-to-column mapping array.
     *
     * @param  string $tbl The table/join the columns come from.
     * @param  array|string $cols The list of columns; preferably as
     * an array, but possibly as a string containing one column.
     * @param  bool|string True if it should be prepended, a correlation name if it should be inserted
     * @return void
     */
    protected function _tableCols($correlationName, $cols, $afterCorrelationName = null)
    {
        if (!is_array($cols)) {
            $cols = array($cols);
        }

        if ($correlationName == null) {
            $correlationName = '';
        }

        $columnValues = array();

        foreach (array_filter($cols) as $alias => $col) {
            $currentCorrelationName = $correlationName;
            if (is_string($col)) {
                // Check for a column matching "<column> AS <alias>" and extract the alias name
                if (preg_match('/^(.+)\s+' . self::SQL_AS . '\s+(.+)$/i', $col, $m)) {
                    $col = $m[1];
                    $alias = $m[2];
                }
                // Check for columns that look like functions and convert to Zend_Db_Expr
                if (preg_match('/^([\w]*\(([^\)]|(?1))*\))$/', (string) $col)) {
                    $col = new Zend_Db_Expr($col);
                } elseif (preg_match('/(.+)\.(.+)/', $col, $m)) {
                    $currentCorrelationName = $m[1];
                    $col = $m[2];
                }
            }
            $columnValues[] = array($currentCorrelationName, $col, is_string($alias) ? $alias : null);
        }

        if ($columnValues) {

            // should we attempt to prepend or insert these values?
            if ($afterCorrelationName === true || is_string($afterCorrelationName)) {
                $tmpColumns = $this->_parts[self::COLUMNS];
                $this->_parts[self::COLUMNS] = array();
            } else {
                $tmpColumns = array();
            }

            // find the correlation name to insert after
            if (is_string($afterCorrelationName)) {
                while ($tmpColumns) {
                    $this->_parts[self::COLUMNS][] = $currentColumn = array_shift($tmpColumns);
                    if ($currentColumn[0] == $afterCorrelationName) {
                        break;
                    }
                }
            }

            // apply current values to current stack
            foreach ($columnValues as $columnValue) {
                array_push($this->_parts[self::COLUMNS], $columnValue);
            }

            // finish ensuring that all previous values are applied (if they exist)
            while ($tmpColumns) {
                array_push($this->_parts[self::COLUMNS], array_shift($tmpColumns));
            }
        }
    }

    /**
     * Internal function for creating the where clause
     *
     * @param string   $condition
     * @param mixed    $value  optional
     * @param string   $type   optional
     * @param boolean  $bool  true = AND, false = OR
     * @return string  clause
     */
    protected function _where($condition, $value = null, $type = null, $bool = true)
    {
        if (count($this->_parts[self::UNION])) {
            require_once 'Zend/Db/Select/Exception.php';
            throw new Zend_Db_Select_Exception("Invalid use of where clause with " . self::SQL_UNION);
        }

        if ($value !== null) {
            $condition = $this->quoteInto($condition, $value, $type);
        }

        $cond = "";
        if ($this->_parts[self::WHERE]) {
            if ($bool === true) {
                $cond = self::SQL_AND . ' ';
            } else {
                $cond = self::SQL_OR . ' ';
            }
        }

        return $cond . "($condition)";
    }

    /**
     * @return array
     */
    protected function _getDummyTable()
    {
        return array();
    }

    /**
     * Return a quoted schema name
     *
     * @param string   $schema  The schema name OPTIONAL
     * @return string|null
     */
    protected function _getQuotedSchema($schema = null)
    {
        if ($schema === null) {
            return null;
        }
        return $this->quoteIdentifier($schema, true) . '.';
    }

    /**
     * Return a quoted table name
     *
     * @param string   $tableName        The table name
     * @param string   $correlationName  The correlation name OPTIONAL
     * @return string
     */
    protected function _getQuotedTable($tableName, $correlationName = null)
    {
        return $this->quoteTableAs($tableName, $correlationName, true);
    }

    /**
     * Render DISTINCT clause
     *
     * @param string   $sql SQL query
     * @return string
     */
    protected function _renderDistinct($sql)
    {
        if ($this->_parts[self::DISTINCT]) {
            $sql .= ' ' . self::SQL_DISTINCT;
        }

        return $sql;
    }

    /**
     * Render DISTINCT clause
     *
     * @param string   $sql SQL query
     * @return string|null
     */
    protected function _renderColumns($sql)
    {
        if (!count($this->_parts[self::COLUMNS])) {
            return null;
        }

        $columns = array();
        foreach ($this->_parts[self::COLUMNS] as $columnEntry) {
            list($correlationName, $column, $alias) = $columnEntry;
            if ($column instanceof Zend_Db_Expr) {
                $columns[] = $this->quoteColumnAs($column, $alias, true);
            } else {
                if ($column == self::SQL_WILDCARD) {
                    $column = new Zend_Db_Expr(self::SQL_WILDCARD);
                    $alias = null;
                }
                if (empty($correlationName)) {
                    $columns[] = $this->quoteColumnAs($column, $alias, true);
                } else {
                    $columns[] = $this->quoteColumnAs(array($correlationName, $column), $alias, true);
                }
            }
        }

        return $sql . "\n\t" . implode(",\n\t", $columns);
    }

    /**
     * Render FROM clause
     *
     * @param string   $sql SQL query
     * @return string
     */
    protected function _renderFrom($sql)
    {
        /*
         * If no table specified, use RDBMS-dependent solution
         * for table-less query.  e.g. DUAL in Oracle.
         */
        if (empty($this->_parts[self::FROM])) {
            $this->_parts[self::FROM] = $this->_getDummyTable();
        }

        $from = array();

        foreach ($this->_parts[self::FROM] as $correlationName => $table) {
            $tmp = '';

            $joinType = ($table['joinType'] == self::FROM) ? self::INNER_JOIN : $table['joinType'];

            // Add join clause (if applicable)
            if (! empty($from)) {
                $tmp .= ' ' . strtoupper($joinType) . ' ';
            }

            $tmp .= $this->_getQuotedSchema($table['schema']);
            $tmp .= $this->_getQuotedTable($table['tableName'], $correlationName);

            // Add join conditions (if applicable)
            if (!empty($from) && ! empty($table['joinCondition'])) {
                $tmp .= ' ' . self::SQL_ON . ' ' . $this->_renderJoinCondition($table['joinCondition'], $correlationName);
            }

            // Add the table name and condition add to the list
            $from[] = $tmp;
        }

        // Add the list of all joins
        if (!empty($from)) {
            $sql .= "\n " . self::SQL_FROM . ' ' . implode("\n", $from);
        }

        return $sql;
    }

    /**
     * Convert JOIN condition saved as array into string using column() method
     *
     * @param array|string|Zend_Db_Expr $condition
     * @param string $correlationName
     * @throws Zend_Db_Select_Exception
     * @return string
     */
    protected function _renderJoinCondition($condition, $correlationName) {
    	if (!is_array($condition)) {
    		return $condition;
    	}

		switch (count($condition)) {
			case 0:
			case 1:
				throw new Zend_Db_Select_Exception('JOIN condition requires two columns.');
			case 2:
				return $this->column($condition[0], $correlationName) . ' = ' . $this->column($condition[1]);
			case 3:
				return $this->column($condition[0], $correlationName) . ' = ' . $this->column($condition[2], $condition[1]);
			case 4:
				return $this->column($condition[1], $condition[0]) . ' = ' . $this->column($condition[3], $condition[2]);
		}
    }

    /**
     * Render UNION query
     *
     * @param string   $sql SQL query
     * @return string
     */
    protected function _renderUnion($sql)
    {
        if ($this->_parts[self::UNION]) {
            $parts = count($this->_parts[self::UNION]);
            foreach ($this->_parts[self::UNION] as $cnt => $union) {
                list($target, $type) = $union;
                if ($target instanceof Zend_Db_Select || $target instanceof Zend_Db_Query) {
                    $target = $target->assemble();
                }
                $sql .= $target;
                if ($cnt < $parts - 1) {
                    $sql .= ' ' . $type . ' ';
                }
            }
        }

        return $sql;
    }

    /**
     * Render WHERE clause
     *
     * @param string   $sql SQL query
     * @return string
     */
    protected function _renderWhere($sql)
    {
        if ($this->_parts[self::FROM] && $this->_parts[self::WHERE]) {
            $sql .= "\n " . self::SQL_WHERE . ' ' .  implode("\n\t", $this->_parts[self::WHERE]);
        }

        return $sql;
    }

    /**
     * Render GROUP clause
     *
     * @param string   $sql SQL query
     * @return string
     */
    protected function _renderGroup($sql)
    {
        if ($this->_parts[self::FROM] && $this->_parts[self::GROUP]) {
            $group = array();
            foreach ($this->_parts[self::GROUP] as $term) {
                $group[] = $this->quoteIdentifier($term, true);
            }
            $sql .= "\n " . self::SQL_GROUP_BY . ' ' . implode(",\n\t", $group);
        }

        return $sql;
    }

    /**
     * Render HAVING clause
     *
     * @param string   $sql SQL query
     * @return string
     */
    protected function _renderHaving($sql)
    {
        if ($this->_parts[self::FROM] && $this->_parts[self::HAVING]) {
            $sql .= "\n " . self::SQL_HAVING . ' ' . implode(' ', $this->_parts[self::HAVING]);
        }

        return $sql;
    }

    /**
     * Render ORDER clause
     *
     * @param string   $sql SQL query
     * @return string
     */
    protected function _renderOrder($sql)
    {
        if ($this->_parts[self::ORDER]) {
            $order = array();
            foreach ($this->_parts[self::ORDER] as $term) {
                if (is_array($term)) {
                    if(is_numeric($term[0]) && strval(intval($term[0])) == $term[0]) {
                        $order[] = (int)trim($term[0]) . ' ' . $term[1];
                    } else {
                        $order[] = $this->quoteIdentifier($term[0], true) . ' ' . $term[1];
                    }
                } elseif (is_numeric($term) && strval(intval($term)) == $term) {
                    $order[] = (int)trim($term);
                } else {
                    $order[] = $this->quoteIdentifier($term, true);
                }
            }
            $sql .= "\n " . self::SQL_ORDER_BY . ' ' . implode(', ', $order);
        }

        return $sql;
    }

    /**
     * Render LIMIT OFFSET clause
     *
     * @param string   $sql SQL query
     * @return string
     */
    protected function _renderLimitoffset($sql)
    {
        $count = 0;
        $offset = 0;

        if (!empty($this->_parts[self::LIMIT_OFFSET])) {
            $offset = (int) $this->_parts[self::LIMIT_OFFSET];
            $count = PHP_INT_MAX;
        }

        if (!empty($this->_parts[self::LIMIT_COUNT])) {
            $count = (int) $this->_parts[self::LIMIT_COUNT];
        }

        /*
         * Add limits clause
         */
        if ($count > 0) {
            $sql = trim($this->_renderLimit($sql, $count, $offset));
        }

        return $sql;
    }

    /**
     * Render FOR UPDATE clause
     *
     * @param string   $sql SQL query
     * @return string
     */
    protected function _renderForupdate($sql)
    {
        if ($this->_parts[self::FOR_UPDATE]) {
            $sql .= ' ' . self::SQL_FOR_UPDATE;
        }

        return $sql;
    }

    /**
     * Turn magic function calls into non-magic function calls
     * for joinUsing syntax
     *
     * @param string $method
     * @param array $args OPTIONAL Zend_Db_Table_Select query modifier
     * @return Zend_Db_Query
     * @throws Zend_Db_Select_Exception If an invalid method is called.
     */
    public function __call($method, array $args)
    {
        $matches = array();

        /**
         * Recognize methods for Has-Many cases:
         * findParent<Class>()
         * findParent<Class>By<Rule>()
         * Use the non-greedy pattern repeat modifier e.g. \w+?
         */
        if (preg_match('/^join([a-zA-Z]*?)Using$/', $method, $matches)) {
            $type = strtolower($matches[1]);
            if ($type) {
                $type .= ' join';
                if (!in_array($type, self::$_joinTypes)) {
                    require_once 'Zend/Db/Select/Exception.php';
                    throw new Zend_Db_Select_Exception("Unrecognized method '$method()'");
                }
                if (in_array($type, array(self::CROSS_JOIN, self::NATURAL_JOIN))) {
                    require_once 'Zend/Db/Select/Exception.php';
                    throw new Zend_Db_Select_Exception("Cannot perform a joinUsing with method '$method()'");
                }
            } else {
                $type = self::INNER_JOIN;
            }
            array_unshift($args, $type);
            return call_user_func_array(array($this, '_joinUsing'), $args);
        }

        require_once 'Zend/Db/Select/Exception.php';
        throw new Zend_Db_Select_Exception("Unrecognized method '$method()'");
    }

    /**
     * Implements magic method.
     *
     * @return string This object as a SELECT string.
     */
    public function __toString()
    {
        try {
        	if ($this->_isCondition) {
        		return implode(' ', $this->_parts[self::WHERE]);
        	}
            $sql = $this->assemble();
        } catch (Exception $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            $sql = '';
        }
        return (string)$sql;
    }

    /* Code from Zend_Db_Adapter_Abstract */

    /**
     * Specifies whether the adapter automatically quotes identifiers.
     * If true, most SQL generated by Zend_Db classes applies
     * identifier quoting automatically.
     * If false, developer must quote identifiers themselves
     * by calling quoteIdentifier().
     *
     * @var bool
     */
    protected $_autoQuoteIdentifiers = true;

    /**
     * Keys are UPPERCASE SQL datatypes or the constants
     * Zend_Db::INT_TYPE, Zend_Db::BIGINT_TYPE, or Zend_Db::FLOAT_TYPE.
     *
     * Values are:
     * 0 = 32-bit integer
     * 1 = 64-bit integer
     * 2 = float or decimal
     *
     * @var array Associative array of datatypes to values 0, 1, or 2.
     */
    protected $_numericDataTypes = array(
    		Zend_Db::INT_TYPE    => Zend_Db::INT_TYPE,
    		Zend_Db::BIGINT_TYPE => Zend_Db::BIGINT_TYPE,
    		Zend_Db::FLOAT_TYPE  => Zend_Db::FLOAT_TYPE
    );

    /**
     * Quote a raw string.
     *
     * @param string $value     Raw string
     * @return string           Quoted string
     */
    protected function _quote($value)
    {
    	if (is_int($value)) {
    		return $value;
    	} elseif (is_float($value)) {
    		return sprintf('%F', $value);
    	}
    	return "'" . addcslashes($value, "\000\n\r\\'\"\032") . "'";
    }

    /**
     * Safely quotes a value for an SQL statement.
     *
     * If an array is passed as the value, the array values are quoted
     * and then returned as a comma-separated string.
     *
     * @param mixed $value The value to quote.
     * @param mixed $type  OPTIONAL the SQL datatype name, or constant, or null.
     * @return mixed An SQL-safe quoted value (or string of separated values).
     */
    public function quote($value, $type = null)
    {
    	if ($value instanceof Zend_Db_Select || $value instanceof Zend_Db_Query) {
    		return '(' . $value->assemble() . ')';
    	}

    	if ($value instanceof Zend_Db_Expr) {
    		return $value->__toString();
    	}

    	if (is_array($value)) {
    		foreach ($value as &$val) {
    			$val = $this->quote($val, $type);
    		}
    		return implode(', ', $value);
    	}

    	if ($type !== null && array_key_exists($type = strtoupper($type), $this->_numericDataTypes)) {
    		$quotedValue = '0';
    		switch ($this->_numericDataTypes[$type]) {
    			case Zend_Db::INT_TYPE: // 32-bit integer
    				$quotedValue = (string) intval($value);
    				break;
    			case Zend_Db::BIGINT_TYPE: // 64-bit integer
    				// ANSI SQL-style hex literals (e.g. x'[\dA-F]+')
    				// are not supported here, because these are string
    				// literals, not numeric literals.
    				if (preg_match('/^(
                          [+-]?                  # optional sign
                          (?:
                            0[Xx][\da-fA-F]+     # ODBC-style hexadecimal
                            |\d+                 # decimal or octal, or MySQL ZEROFILL decimal
                            (?:[eE][+-]?\d+)?    # optional exponent on decimals or octals
                          )
                        )/x',
                            (string) $value, $matches)) {
                            	$quotedValue = $matches[1];
                            }
                            break;
    			case Zend_Db::FLOAT_TYPE: // float or decimal
    				$quotedValue = sprintf('%F', $value);
    		}
    		return $quotedValue;
    	}

    	return $this->_quote($value);
    }

    /**
     * Quotes a value and places into a piece of text at a placeholder.
     *
     * The placeholder is a question-mark; all placeholders will be replaced
     * with the quoted value.   For example:
     *
     * <code>
     * $text = "WHERE date < ?";
     * $date = "2005-01-02";
     * $safe = $sql->quoteInto($text, $date);
     * // $safe = "WHERE date < '2005-01-02'"
     * </code>
     *
     * @param string  $text  The text with a placeholder.
     * @param mixed   $value The value to quote.
     * @param string  $type  OPTIONAL SQL datatype
     * @param integer $count OPTIONAL count of placeholders to replace
     * @return string An SQL-safe quoted value placed into the original text.
     */
    public function quoteInto($text, $value, $type = null, $count = null)
    {
    	if ($count === null) {
    		return str_replace('?', $this->quote($value, $type), $text);
    	} else {
    		return implode($this->quote($value, $type), explode('?', $text, $count + 1));
    	}
    }

    /**
     * Quotes an identifier.
     *
     * Accepts a string representing a qualified indentifier. For Example:
     * <code>
     * $adapter->quoteIdentifier('myschema.mytable')
     * </code>
     * Returns: "myschema"."mytable"
     *
     * Or, an array of one or more identifiers that may form a qualified identifier:
     * <code>
     * $adapter->quoteIdentifier(array('myschema','my.table'))
     * </code>
     * Returns: "myschema"."my.table"
     *
     * The actual quote character surrounding the identifiers may vary depending on
     * the adapter.
     *
     * @param string|array|Zend_Db_Expr $ident The identifier.
     * @param boolean $auto If true, heed the AUTO_QUOTE_IDENTIFIERS config option.
     * @return string The quoted identifier.
     */
    public function quoteIdentifier($ident, $auto=false)
    {
    	return $this->_quoteIdentifierAs($ident, null, $auto);
    }

    /**
     * Quote a column identifier and alias.
     *
     * @param string|array|Zend_Db_Expr $ident The identifier or expression.
     * @param string $alias An alias for the column.
     * @param boolean $auto If true, heed the AUTO_QUOTE_IDENTIFIERS config option.
     * @return string The quoted identifier and alias.
     */
    public function quoteColumnAs($ident, $alias, $auto=false)
    {
    	return $this->_quoteIdentifierAs($ident, $alias, $auto);
    }

    /**
     * Quote a table identifier and alias.
     *
     * @param string|array|Zend_Db_Expr $ident The identifier or expression.
     * @param string $alias An alias for the table.
     * @param boolean $auto If true, heed the AUTO_QUOTE_IDENTIFIERS config option.
     * @return string The quoted identifier and alias.
     */
    public function quoteTableAs($ident, $alias = null, $auto = false)
    {
    	return $this->_quoteIdentifierAs($ident, $alias, $auto);
    }

    /**
     * Quote an identifier and an optional alias.
     *
     * @param string|array|Zend_Db_Expr $ident The identifier or expression.
     * @param string $alias An optional alias.
     * @param boolean $auto If true, heed the AUTO_QUOTE_IDENTIFIERS config option.
     * @param string $as The string to add between the identifier/expression and the alias.
     * @return string The quoted identifier and alias.
     */
    protected function _quoteIdentifierAs($ident, $alias = null, $auto = false, $as = ' AS ')
    {
    	if ($ident instanceof Zend_Db_Expr) {
    		$quoted = $ident->__toString();
    	} elseif ($ident instanceof Zend_Db_Select || $ident instanceof Zend_Db_Query) {
    		$quoted = '(' . $ident->assemble() . ')';
    	} else {
    		if (is_string($ident)) {
    			$ident = explode('.', $ident);
    		}
    		if (is_array($ident)) {
    			$segments = array();
    			foreach ($ident as $segment) {
    				if ($segment instanceof Zend_Db_Expr) {
    					$segments[] = $segment->__toString();
    				} else {
    					$segments[] = $this->_quoteIdentifier($segment, $auto);
    				}
    			}
    			if ($alias !== null && end($ident) == $alias) {
    				$alias = null;
    			}
    			$quoted = implode('.', $segments);
    		} else {
    			$quoted = $this->_quoteIdentifier($ident, $auto);
    		}
    	}
    	if ($alias !== null) {
    		$quoted .= $as . $this->_quoteIdentifier($alias, $auto);
    	}
    	return $quoted;
    }

    /**
     * Quote an identifier.
     *
     * @param  string $value The identifier or expression.
     * @param boolean $auto If true, heed the AUTO_QUOTE_IDENTIFIERS config option.
     * @return string        The quoted identifier and alias.
     */
    protected function _quoteIdentifier($value, $auto=false)
    {
    	if ($auto === false || $this->_autoQuoteIdentifiers === true) {
    		$q = $this->getQuoteIdentifierSymbol();
    		return ($q . str_replace("$q", "$q$q", $value) . $q);
    	}
    	return $value;
    }

    /**
     * Returns the symbol the adapter uses for delimited identifiers.
     *
     * @return string
     */
    public function getQuoteIdentifierSymbol()
    {
    	return '"';
    }

    protected abstract function _renderLimit($sql, $count, $offset = 0);

    /* Added code (c) 2016 Nothrem Sinsky */

    /**
     * Look for a column in the column list and return its quoted name
     * or condition with its name.
     *
     * Works on both Full name (e.g. 'users.index') and alias (e.g. 'u.i'),
     * always returns aliased names as defined in from(), columns() or join*().
     *
     * @param  string $name Full name or alias of a column.
     * @param  string $tableName (optional, default: null) Full name or alias of a table the column belong to. Must be defined if $name is not defined in column list or the $name is ambiguous.
     * @param  mixed $value (optional, default: null) If defined, will return full condition with the $operator and quoted $value.
     * @param  string $operator (optional, default: '=') Operator for the condition.
     * @throws Zend_Db_Select_Exception
     * @return string Table and column alias (or full name if not defined) or whole condition incl. the given $value.
     *
     * @example <code>
     *     $query->from(array('u' => 'users'), array('i' => 'id', 'name' => 'FullName'));
     *
     *     $query->column('id');                            //returns "`u`.`i`"
     *     $query->column('id', 'user');                    //returns "`u`.`i`"
     *     $query->column('gender');                        //throws exception because column `gender` was not defined in from()
     *     $query->column('gender', 'user_data');           //returns "`user_data`.`gender`" because it knows both column and table name
     *
     *     $query->column('id', 'users', 123);              //returns "`u`.`i` = 123"
     *     $query->column('gender', 'users', 'male', '!='); //returns "`u`.`gender` != 'male'"
     *     $query->column('name', null, '%Doe', 'like');    //returns "`u`.`name` LIKE '%Doe'"
     *     $query->column('name', null, 'Doe', 'like');     //returns "`u`.`name` LIKE '%Doe%'" (wraps value in % if $operator is LIKE and there are no % in the $value)
     *     $query->column('id', null, array(1,2,'-'), 'in');//returns "`u`.`name` IN (1, 2, '-')" (automatically convert and quote array for IN operator)
     *     $query->column('id', null, $subquery, 'in');     //returns "`u`.`name` IN (SELECT ...)" (accepts another Zend_Db_Query as the value for IN operator)
     *
     *     $query->where($query->column('id', null, 123));  //adds condition with correctly aliased, prefixed and quoted name and value: " (`u`.`i` = 123) "
     * </code>
     */
    public function column($name, $tableName = null, $value = null, $operator = '=') {
    	if (!is_string($name)) {
    		throw new Zend_Db_Select_Exception('Invalid column name. String expected, ' . gettype($name) . ' given.');
    	}

    	$match = array();
    	$index = array();

    	foreach ($this->getPart(self::COLUMNS) as $col) {
    		if ($col[1] === $name || $col[2] === $name) {
    			$match[] = $col;
    			$index[$col[0]] = $col;
    		}
    	}

    	$operator = strtoupper($operator);
    	if (isset($value)) {
    		if ('LIKE' === $operator && false === strpos($value, '%')) {
    			$value = ' LIKE ' . $this->quote('%' . $value . '%');
    		}
    		elseif ('IN' === $operator) {
    			if (is_array($value)) {
    				foreach ($value as $i => $val) {
    					$value[$i] = $this->quote($val);
    				}
    				$value = ' IN (' . implode(', ', $value) . ')';
    			}
    			else {
    				$value = ' IN (' . $value . ')';
    			}
    		}
    		else {
    			$value = ' ' . $operator . ' ' . $this->quote($value);
    		}
    	}
    	elseif (in_array($operator, array(self::SQL_ASC, self::SQL_DESC, 'IS NULL', 'IS NOT NULL'))) {
    		$value = ' ' . $operator;
    	}
    	else {
    		$value = '';
    	}

    	switch (count($match)) {
    		case 1: //only one column found, return it with table alias stored in its data
    			if (!empty($match[0][2])) {
    				return new Zend_Db_Expr($this->quoteIdentifier($match[0][2]) . $value);
    			}
    			$name = $match[0][1];
    			$tableName = $match[0][0];
    			//NO BREAK HERE
    		case 0: //no column found, but if table is defined, we can still use it
    			if (is_null($tableName)) {
    				if (1 === count($this->getPart(self::FROM))) {
    					$tables = $this->getPart(self::FROM);
    					reset($tables);
    					return new Zend_Db_Expr($this->quoteIdentifier(key($tables)) . '.' . ($this->quoteIdentifier($name)) . $value);
    				}
    				throw new Zend_Db_Select_Exception("Column '$name' not found in column list. Please specify table name.");
    			}
    			foreach ($this->getPart(self::FROM) as $alias => $table) {
    				if ($tableName === $alias || $tableName === $table['tableName']) {
    					return new Zend_Db_Expr($this->quoteIdentifier($alias) . '.' . ($this->quoteIdentifier($name)) . $value);
    				}
    			}
    			return new Zend_Db_Expr($this->quoteIdentifier($tableName) . '.' . ($this->quoteIdentifier($name)) . $value);

    		default: //more columns found, must check from which table the column is
    			if (is_null($tableName)) {
    				throw new Zend_Db_Select_Exception("Ambiguous column '$name'. Please specify table name.");
    			}
    			foreach ($this->getPart(self::FROM) as $alias => $table) {
    				$col = null;
    				if ($tableName === $alias || $tableName === $table['tableName']) {
    					$col = $index[$alias];
    					return new Zend_Db_Expr($col[2] ? $this->quoteIdentifier($col[2]) : ($this->quoteIdentifier($col[0]) . '.' . $this->quoteIdentifier($col[1])) . $value);
    				}
    			}
    			return new Zend_Db_Expr($this->quoteIdentifier($tableName) . '.' . ($this->quoteIdentifier($name)) . $value);
    	}
    }

    /**
     * @var boolean Marks that this Query is a subcondition and should return only WHERE part when converted to string
     */
    protected $_isCondition = false;

    /**
     * Returns all WHERE conditions to be used as a sub-condition
     *
     * @return string
     * @example <code>
     *    $cond = new Zend_Db_Query_Mysql();
     *    $cond
     *    	->where($query->column('id', null, 1))
     *    	->orwhere($query->column('id', null, 5))
     *    $query->where($cond->getWhere());
     * </code>
     */
    public function condition() {
    	$self = get_class($this);
    	$condition = new $self();
    	$condition->_isCondition = true;
    	return $condition;
    }

    /**
     * @var $_states Store custom states
     */
    protected $_states = array();

    /**
     * Add custom state with optional value
     *
     * @param string $name
     * @param mixed $value (optional, default: true) Any value, use NULL to delete state
     * @return Zend_Db_Query
     */
    public function setState($name, $value = true) {
    	if (is_null($value)) {
    		unset($this->_states[$name]);
    	}
    	else {
    		$this->_states[$name] = $value;
    	}

    	return $this;
    }

    /**
     * Check if the query as given custom state
     *
     * @param  string $name
     * @return boolean TRUE when state has value other than NULL
     */
    public function hasState($name) {
    	return array_key_exists($name, $this->_states);
    }

    /**
     * Return TRUE if given name was not yet set, then add the state
     *
     * @param  string $name
     * @return boolean TRUE when state was not yet set, FALSE if state was already set using setState() or uniqueState()
     */
    public function uniqueState($name) {
    	if ($this->hasState($name)) {
    		return false;
    	}
    	$this->setState($name);
    	return true;
    }

    /**
     * Return value of a state
     *
     * @param  string $name
     * @return NULL|Mixed
     */
    public function getState($name) {
    	return $this->hasState($name) ? $this->_states[$name] : null;
    }

}
