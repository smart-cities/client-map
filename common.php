<?php

// define autoloader

require __DIR__.'/classes/autoloader.php';
require __DIR__.'/vendor/autoload.php';

// database connection parameters required:


$config['db']['host']='127.0.0.1';
$config['db']['db']='smartcities';
$config['db']['username']='root';
$config['db']['password']='';

if( file_exists(__DIR__.'/local.php')) {
	include (__DIR__.'/local.php');
}

// connect to the database (so we instantiate the singleton)
$db = Dbo::getConnection($config['db']);
