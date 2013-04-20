<?php
class Reading extends dbobject {

	public $pkey = 'id';

	protected $fields = array(
		'id',
		'_device_id',
		'timestamp',
		'sensorName',
		'dataFloat',
		'dataString',
	);

	public function __construct()  {
		$this->timestamp = time();
	}

}