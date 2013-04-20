<?php
class ReadingApi {

	protected static $getData_defaultOptions = array(
		'sensors' => array('TEMP','RH','LIGHT'),
		// bounding box for display
		'startLng' => 0,
		'endLng' => 0,
		'startLat' => 0,
		'endLat' => 0,
		'mode' => 'test',
	);

	/**
	 * Get data readings for the given bounding box.
	 *
	 * Currently doesn't obey lat/lng. Or even sensors.
	 * Example $options string: {"sensors":["TEMP","RH","LIGHT"],"startLng":1,"endLng":1,"startLat":10,"endLat":10,"mode":"test"}
	 *
	 * @param string $options Json encoded options array
	 * @return array
	 * @throws RestException
	 */

	public function getdata($options='') {

		if ($options!='') { $options = json_decode($options,true); }
		$options = array_merge(self::$getData_defaultOptions,$options);

		if ($options['startLng']==0 &&  $options['endLng']==0 &&  $options['startLat']==0 && $options['endLat']==0) {
			throw new RestException(400,'Please provide latitude and longitude values');
		}

		$readings = array();

		if ($options['mode']=='test') {

			for ($x=1;$x<=50;$x++) {

				$obj = new DeviceReadingApiObject();

				// workout lat/lng to return based on given bounding box.
				// will have to deal with crossing thresholds in a later version...

				$lengthLng = ($options['endLng'] - $options['startLng']) ;
				$lengthLat = ($options['endLat'] - $options['startLat']) ;

				$obj->device_lat = $options['startLat'] + (rand(0,100)/100 * $lengthLat);
				$obj->device_lng = $options['startLng'] + (rand(0,100)/100 * $lengthLng);

				$obj->_device_id = rand(1,255);
				$obj->timestamp = time();
				$obj->sensorName = 'TEMP';
				$obj->sensorValue = rand(5,35);

				$readings[]=$obj;

			}

		} else {

			// grab latest data for all devices in range

			// @todo - range check lat/lng

			$sql = "SELECT
				_device_id, timestamp, sensorName, dataFloat,
				lat,lng
			FROM
				Readings
			LEFT JOIN
				Devices on Devices.id = _device_id

			GROUP BY _device_id,sensorName
			ORDER BY timestamp DESC
			";

			$db =  Dbo::getConnection();
			$stm = $db->prepare($sql);
			$db->executeStatement($stm,array());

			while ($data=$stm->fetch(Dbo::FETCH_ASSOC)) {

				$obj = new DeviceReadingApiObject();
				$obj->_device_id = $data['_device_id'];
				$obj->timestamp = $data['timestamp'];
				$obj->sensorName = $data['sensorName'] != '' ? $data['sensorName'] : 'TEMP';
				$obj->sensorValue = $data['dataFloat'];

				$readings[]=$obj;

			}

		}

		return array(
				'count'=>count($readings),
				'readings'=>$readings
				);
	}

}