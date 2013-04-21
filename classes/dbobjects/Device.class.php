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
			foreach (array('TEMP') as $sensorName) {
				$r  =Reading::find(array('conditions'=>array('_device_id = ? AND sensorName = ? ORDER BY `timestamp` desc LIMIT 1',$this->id,$sensorName)));
					$result[]=$r;
			}
			return $result;
		} catch (RecordNotFoundException $e) { }
		return array();
	}

	public function getReadingsForSensor($sensor,$period=86400,$offset=0) {
		try {

			$start = time() - $offset - $period;
			$end = time() - $offset;

			return Reading::find(array('conditions'=>array('_device_id = ? AND sensorName = ? AND timestamp > ? AND timestamp <= ?',$this->id,$sensor,$start,$end),'order'=>'timestamp desc'));
		} catch (RecordNotFoundException $e) { }
		return array();
	}

}