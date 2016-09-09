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
	
Using method ```column()```
---------------------------

Method ```column()``` is new and not present in the original ```Zend_Db_Select```.

It can translate table and column names to their aliases.

	require_once 'Zend/Db/Query/Mysql.php';
	
	$query = new Zend_Db_Query_Mysql();
	
	$query
		->from(array('a' => 'articles'))
		->columns(array('id', 'text' => 'content_text'))
		->joinLeft('authors',
			$query->column('author', 'articles', new Zend_Db_Expr('authors.id'))
		)
		->where($query->column('archived', 'articles', 0))
		->order(new Zend_Db_Expr($query->column('release_time', 'articles') . ' DESC'))
	;
	
	$sql = $query->assemble();
	
	/* returns
	 *   SELECT `a`.`id`, `a`.`content_text` AS `text` 
	 *   FROM `articles` AS `a` 
	 *   LEFT JOIN `authors` 
	 *     ON `a`.`author` = authors.id 
	 *   WHERE (`a`.`archived` = 0) 
	 *   ORDER BY `a`.`release_time` DESC
	 */

Note that currently there is a problem inside the join condition, because it
cannot correctly translate the columns from the table currently being joined
(because you call the ```column()``` method before the ```join*()``` method
actually adds the table and its columns).

As a workaround you must define these columns as a ```string``` or ```Zend_Db_Expr```.

Other methods (e.g. ```group()``` or ```order()```) may not fully support
the ```column()``` method result and may mess up the quoting.
This is a bug in the Zend library.
As a workaround you must define these columns as ```Zend_Db_Expr```.

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