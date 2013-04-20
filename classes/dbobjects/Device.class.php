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

	public function getReadings() {
		try {
			return  Reading::find(array('conditions'=>array('_device_id = ? GROUP BY sensorName ORDER BY id desc',$this->id)));
		} catch (RecordNotFoundException $e) { }
		return array();
	}

}