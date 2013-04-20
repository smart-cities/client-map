<?php
class Device extends dbobject {

	public $pkey = 'id';

	protected $fields = array(
		'GUID',
		'manufacturer',
		'model',
		'identifier',
		'name',
		'description',
		'lat',
		'lng',
		'lastConnection',
		'timezone',
		'email',
	);

}