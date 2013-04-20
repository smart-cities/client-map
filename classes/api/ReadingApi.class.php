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
	 * Currently doesn't obey lat/lng. Or even sensors.<br/>
	 *<br/>
	 * Example $options string: {"sensors":["TEMP","RH","LIGHT"],"startLng":1,"endLng":1,"startLat":10,"endLat":10,"mode":"real"}<br/>
	 *<br/>
	 * Set mode to 'test' to return 50 randomly generated data points within your bounding box.
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

	/**
	 * API call for inserting data into the database
	 *
	 * $data is a JSON array which needs to contain the following fields:<br/>
	 *
	 * deviceId - our database device ID<br/>
	 * OR<br/>
	 * deviceGUID - our generated GUID for the device<br/>
	 *<br/>
	 * sensorName, which should be a string matching our sensor list (currently TEMP,RH,LIGHT)<br/>
	 * dataFloat - the value if it's a floating point/integer number<br/>
	 * dataString - the value if it's a string<br/>
	 *<br/>
	 * example string: {"deviceId":0, "sensorName":"TEMP", "dataFloat":100}<br/>
	 *
	 * @param string $data Json Object
	 */
	public function send($data) {
		if ($data!='') {
			$data = json_decode($data);
		}

		if (!is_object($data)) throw new RestException('400', 'Unable to parse JSON data');

		try {
			if (isset($data->deviceId)) {
				$device = Device::find($data->deviceId);
			} elseif (isset($data->deviceGUID)) {
				$device = Device::find(array('conditions'=>array('GUID = ?',$data->deviceGUID),'limit'=>1));
			}
		} catch (RecordNotFoundException $e) {
			throw new RestException('400', 'Unable to find device with the ID/GUID you specified');
		}

		try {
			if (isset($device)) {
				$reading = new Reading();

				$reading->_device_id = $device->id;
				$reading->sensorName = $data->sensorName;
				$reading->dataFloat = isset($data->dataFloat) ? $data->dataFloat : null;
				$reading->dataString = isset($data->dataString) ? $data->dataString : null;
				$reading->save();
			}
		} catch (Exception $e) {
			var_dump($e);
			var_dump($e->getTrace());
			exit;
		}

		$result = new stdClass();
		$result->result = 'success';
		$result->readingId = $reading->id;

		return $result;

	}

	/**
	 * Retrieve the last 10 readings from the database
	 *
	 * Returns the last 10 readings out of the database, plain and simple.
	 *
	 */
	public function getLast10Readings() {

		$sql = "SELECT
		Readings.*,Devices.id as deviceId, devices.lat as deviceLat, devices.lng as deviceLng, devices.name as deviceName, devices.GUID as deviceGUID
		FROM
		Readings
		LEFT JOIN
		Devices on Devices.id = _device_id

		ORDER BY Readings.id DESC
		LIMIT 10
		";

		$db =  Dbo::getConnection();
		$stm = $db->prepare($sql);
		$db->executeStatement($stm,array());

		$result = array();

		while ($data=$stm->fetch(Dbo::FETCH_ASSOC)) {

			$obj = new stdClass();
			foreach ($data as $k=>$v) {
				$obj->$k=$v;
			}
			$obj->sensorName='TEMP';

			$result[]=$obj;

		}

		return array('Readings'=>$result);
	}

}