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
 * @see Zend_Db_Query
 */
require_once 'Zend/Db/Query.php';


/**
 * Class for MySQL SELECT generation without direct connection to the DB.
 *
 * @category   Zend
 * @package    Zend_Db
 * @subpackage Select
 * @copyright  Copyright (c) 2016 Nothrem Sinsky
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Db_Query_Mysql extends Zend_Db_Query
{

    /* Code from Zend_Db_Adapter_Mysqli */


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
			Zend_Db::FLOAT_TYPE  => Zend_Db::FLOAT_TYPE,
			'INT'                => Zend_Db::INT_TYPE,
			'INTEGER'            => Zend_Db::INT_TYPE,
			'MEDIUMINT'          => Zend_Db::INT_TYPE,
			'SMALLINT'           => Zend_Db::INT_TYPE,
			'TINYINT'            => Zend_Db::INT_TYPE,
			'BIGINT'             => Zend_Db::BIGINT_TYPE,
			'SERIAL'             => Zend_Db::BIGINT_TYPE,
			'DEC'                => Zend_Db::FLOAT_TYPE,
			'DECIMAL'            => Zend_Db::FLOAT_TYPE,
			'DOUBLE'             => Zend_Db::FLOAT_TYPE,
			'DOUBLE PRECISION'   => Zend_Db::FLOAT_TYPE,
			'FIXED'              => Zend_Db::FLOAT_TYPE,
			'FLOAT'              => Zend_Db::FLOAT_TYPE
	);

    /**
     * Returns the symbol the adapter uses for delimiting identifiers.
     *
     * @return string
     */
    public function getQuoteIdentifierSymbol()
    {
    	return "`";
    }

    /**
     * Adds an adapter-specific LIMIT clause to the SELECT statement.
     *
     * @param string $sql
     * @param int $count
     * @param int $offset OPTIONAL
     * @return string
     */
    public function _renderLimit($sql, $count, $offset = 0)
    //renames from Adapter::limit() which would collide with Query::limit()
    {
    	$count = intval($count);
    	if ($count <= 0) {
    		/**
    		 * @see Zend_Db_Adapter_Mysqli_Exception
    		 */
    		require_once 'Zend/Db/Adapter/Mysqli/Exception.php';
    		throw new Zend_Db_Adapter_Mysqli_Exception("LIMIT argument count=$count is not valid");
    	}

    	$offset = intval($offset);
    	if ($offset < 0) {
    		/**
    		 * @see Zend_Db_Adapter_Mysqli_Exception
    		 */
    		require_once 'Zend/Db/Adapter/Mysqli/Exception.php';
    		throw new Zend_Db_Adapter_Mysqli_Exception("LIMIT argument offset=$offset is not valid");
    	}

    	$sql .= "\n LIMIT $count";
    	if ($offset > 0) {
    		$sql .= " OFFSET $offset";
    	}

    	return $sql;
    }

}
