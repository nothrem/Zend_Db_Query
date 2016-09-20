# Zend_Db_Query Standalone
Alternative for Zend_Db_Select that does not need Zend_Db_Adapter and connection into DB.

Usage
------

	require_once 'Zend/Db/Query/Mysql.php';
	
	$query = new Zend_Db_Query_Mysql();
	
	$query
		->from('articles')
		->columns(array('id', 'text'))
		->joinLeft('authors', 'articles.author = authors.id', 'name')
		->where('archived = ?', 0)
		->order('release_time DESC')
	;
	
	$sql = $query->assemble();
	
See [Zend Manual](https://framework.zend.com/manual/1.10/en/zend.db.select.html) for more details.
	
Method ```column()```
---------------------------

Method ```column()``` is new and not present in the original ```Zend_Db_Select```.

It can translate table and column names to their aliases.

	require_once 'Zend/Db/Query/Mysql.php';
	
	$query = new Zend_Db_Query_Mysql();
	
	$query
		->from(array('a' => 'articles'))
		->columns(array('id', 'text' => 'content_text'))
		->joinLeft('authors',
			$query->column('author', 'articles', $query->column('id', 'authors'))
		)
		->where($query->column('archived', 'articles', 0))
		->order($query->column('release_time', 'articles', null, 'desc'))
	;
	
	$sql = $query->assemble();
	
	/* returns
	 *   SELECT `a`.`id`, `a`.`content_text` AS `text` 
	 *   FROM `articles` AS `a` 
	 *   LEFT JOIN `authors` 
	 *     ON `a`.`author` = `authors`.`id`
	 *   WHERE (`a`.`archived` = 0) 
	 *   ORDER BY `a`.`release_time` DESC
	 */

Method ```column()``` returns ```Zend_Db_Expr``` which means its return value
can be safely used in any other ```Zend_Db_Query``` or ```Zend_Db_Select``` method.

When using the ```column()``` method inside the ```order()``` method,
you can set the value to null and then use 'asc' or 'desc' as the operator.
Alternatively, you can use ```Zend_Db_Query::SQL_ASC``` or ```Zend_Db_Query::SQL_DESC```.

Note that inside the ```join*()``` methods the method ```column()``` cannot
correctly translate the columns and table aliases for the table currently
being joined, because you call the ```column()``` method before the ```join*()```
method actually adds the table and its columns).

Method ```condition()```
---------------------------

Method ```condition()``` is new and not present in the original ```Zend_Db_Select```.

It can be used to create sub-conditions where needed.

	require_once 'Zend/Db/Query/Mysql.php';
	
	$query = new Zend_Db_Query_Mysql();
	
	$query
		->from(array('a' => 'articles'))
		->columns(array('id', 'text' => 'content_text'))
		->where($query->column('published', null, 1))
		->where($query->condition()
			->where($query->column('available', null, 'free'))
			->orWhere($query->column('available', null, 'preview'))
		)
	;
	
	$sql = $query->assemble();
	
	/* returns
	 *   SELECT `a`.`id`, `a`.`content_text` AS `text` 
	 *   FROM `articles` AS `a` 
	 *   WHERE 
	 *     (`a`.`published` = 1) 
	 *     AND
	 *     (
	 *       (`a`.`available` = 'free')
	 *       OR
	 *       (`a`.`available` = 'preview')
	 *     )
	 */
	 
JOIN condition as array
-----------------------

This is new and not present in the original ```Zend_Db_Select```.

When creating a join, instead of passing the condition as string, you can
use array and it will automatically convert the column and table names
to their aliases.

In the array, you can pass 2, 3 or 4 values which then will be translated
to columns and tables as follows:

	$query
		->from(array('a' => 'articles'))
		->columns(array('text', 'cat' => 'category'))
		
		//with 2 values, first one is from joined table,
		//the other one is searched in columns list
		->joinInner(array('c' => 'categories'), array('id', 'category'))
		// creates "ON `c`.`id` = `a`.`cat`"
		
		//with 3 values, first one is from joined table,
		//the other one is processed as table name and column name
		->joinLeft(array('au' => 'authors'), array('id', 'articles', 'author'))
		//creates "ON `au`.`id` = `a`.`author`"
		
		//4 values are processed as 2 combinations of table and column names
		//e.g. array('table1', 'column1', 'table2', 'column2');
		->joinLeft('comments'), array('comments', 'article', 'articles', 'id')
		//creates "ON `comments`.`article` = `a`.`id`"
	;
	
	 LEFT JOIN `authors`  LEFT JOIN `comments` ON 

INSERT and UPDATE support
-------------------------

This is new and not present in the original ```Zend_Db_Select```.

You can use methods ```insert()``` and ```update()``` to create simple
insert or update queries. Even insert with duplicate key option and update over
multiple tables are supported. Special cases of these queries (e.g. IGNORE,
DELAYED, etc.) are not supported (yet).

To switch to INSERT or UPDATE you simple use the methods ```insert()``` or ```update()```
instead of the method ```from()```. After using the method ```insert()``` you
can use the method```update()``` to create ON DUPLICATE KEY query. Other combinations
of the methods ```insert()```, ```update()``` and ```from()``` are not allowed
and will result in an exception. You must use the method ```reset()``` without any
parameters to clear the current state and allow to use the above methods again.

To define values for the insert or update you simple pass the column names and
their values into method ```columns()``` or into above methods as the second parameter.
You can use method ```column()``` to quote the column name and its value. In such
case the 4th parameter should stay undefined to create value assignment.

Please note that when creating ON DUPLICATE KEY query you must specify a table
name in the ```update()``` method since the parameter is required. Calling
the method ```update()``` after the ```insert()``` creates separate query which
must be handled that way (see below).

INSERT example:

    $query
        ->insert('marriage')
        ->columns(array(
                $query->column('spouse_1', null, 'John'),
                $query->column('spouse_2', null, 'Jane'),
                $query->column('date', null, new Zend_Db_Expr('NOW()')),
        ))
        ->update('marriage') //divorced and getting married again?!?
        ->columns(array(
            $query->column('date', null, new Zend_Db_Expr('NOW()')),
        ))
    ;
    $query->assemble();
    /* Creates:
     * INSERT INTO `marriage` SET
     *     `marriage`.`spouse_1` = 'John',
     *     `marriage`.`spouse_2` = 'Jane',
     *     `marriage`.`date` = NOW()
     * ON DUPLICATE KEY UPDATE
     *     `marriage`.`date` = NOW()
     */

UPDATE example:

    $query
        ->update(array('o' => 'online'))
        ->joinLeft(array('u' => 'user'), array('id', 'online', 'id'))
        ->columns(array(
                $query->column('page', 'online', 'account'),
                $query->column('time', 'online', new Zend_Db_Expr('NOW()')),
        ))
        ->where($query->column('name', 'user', 'john01'))
        ->limit(1)
        ->order($query->column('id', 'user')) //just to show it's possible
    ;
    $query->assemble();
    /* Creates:
     * UPDATE `online` AS `o`
     * LEFT JOIN `user` AS `u`
     *     ON `u`.`id` = `o`.`id`
     * SET
     *     `o`.`page` = 'account',
     *     `o`.`time` = NOW()
     * WHERE (`u`.`name` = 'john01')
     * ORDER BY `u`.`id`
     * LIMIT 1
     */
     
You must define the table names for joins and columns in case you update more table
since the method ```column()``` cannot detect column names from their definitions
and the column names would become ambiguous.

Note that you can use other methods for INSERT and UPDATE but their values will
not be included in the result, e.g.:

    $query
        ->insert('marriage')
        ->columns(array(
                $query->column('spouse_1', null, 'John'),
                $query->column('spouse_2', null, 'Jane'),
                $query->column('date', null, new Zend_Db_Expr('NOW()')),
        ))
        ->group('date')
        ->having('date < NOW()')
    ;
    $query->assemble();
    /* Creates:
     * INSERT INTO `marriage` 
     * SET
     *     `marriage`.`spouse_1` = 'John',
     *     `marriage`.`spouse_2` = 'Jane',
     *     `marriage`.`date` = NOW()
     */

Example for ON DUPLICATE KEY:

    $query->insert('marriage');
    $query->columns(array(
                $query->column('spouse_1', null, 'John'),
                $query->column('spouse_2', null, 'Jane'),
                $query->column('date', null, new Zend_Db_Expr('NOW()')),
    ));
    $query->update('marriage'); //WRONG
    $query->columns($query->column('date', null, new Zend_Db_Expr('NOW()')));
    $query->assemble();
    /* Creates WRONG query:
     * INSERT INTO `marriage`
     * SET
     *     `marriage`.`spouse_1` = 'John',
     *     `marriage`.`spouse_2` = 'Jane',
     *     `marriage`.`date` = NOW(),
     *     `marriage`.`date` = NOW()
     * ON DUPLICATE KEY
     */
     
To correctly add columns into the UPDATE part you need to store the returned
value of the ```update()``` method:

    $query->insert('marriage');
    $query->columns(array(
                $query->column('spouse_1', null, 'John'),
                $query->column('spouse_2', null, 'Jane'),
                $query->column('date', null, new Zend_Db_Expr('NOW()')),
        ))
    ;
    $update = $query->update('marriage'); //CORRECT
    $update->columns($query->column('date', null, new Zend_Db_Expr('NOW()')));
    $query->assemble();
    /* Creates CORRECT query:
     * INSERT INTO `marriage`
     * SET
     *     `marriage`.`spouse_1` = 'John',
     *     `marriage`.`spouse_2` = 'Jane',
     *     `marriage`.`date` = NOW()
     * ON DUPLICATE KEY UPDATE
     *     `marriage`.`date` = NOW()
     */

Usage with Zend Framework
-------------------------

To use this inside your Zend Framework you must
switch to ```master``` branch that contain only the new classed.

Instalation
-----------

Clone or download the ```master``` branch and copy folder ```Db```
into your instalation of the Zend Framework under your project.

If you don't have Zend Framework, clone or download the ```standalone``` branch
and copy all files into folder named ```Zend``` inside your ```include_path```.