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
		->where('archived = ?', false)
		->order('release_time DESC')
	;
	
	$sql = $query->assemble();
	
	see [Zend Manual](https://framework.zend.com/manual/1.10/en/zend.db.select.html) for more details.

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