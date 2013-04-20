<?php
class Reading extends dbobject {

	public $pkey = 'id';

	protected $fields = array(
		'_device_id',
		'timestamp',
		'sensorName',
		'dataFloat',
		'dataString',
	);

	public function __construct()  {
		$this->timestamp = time();
	}

	public function __toApi() {
		$result = parent::__toApi();

		$result->dateTimeString = date('Y-m-d H:i:s',$result->timestamp);
		return $result;

	}

}