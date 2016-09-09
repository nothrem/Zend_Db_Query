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

Usage with Zend Framework
-----------------

To use this inside your Zend Framework you must
switch to ```master``` branch that contain only the new classed.

Instalation
-----------

Clone or download the ```master``` branch and copy folder ```Db```
into your instalation of the Zend Framework under your project.

If you don't have Zend Framework, clone or download the ```standalone``` branch
and copy all files into folder named ```Zend``` inside your ```include_path```.