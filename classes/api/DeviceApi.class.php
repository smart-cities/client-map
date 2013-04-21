<?php

class DeviceApi {

	public function test() {
		$std = new stdClass;
		$std->reply = 'hello world!';
		return $std;
	}

	/**
	 * Returns a list of all devices in the database
	 *
	 *
	 */
	public function getlist($options = array()) {

		try {
			$devices = Device::find(array());

			return array('devices'=>$devices);
		} catch (RecordNotFoundException $e) {
			return array('devices'=>array());
		}
	}

	/**
	 * Add a device to our database
	 *
	 * This is not yet implemented
	 *
	 * @param string $device JSON encoded
	 */
	public function addDevice($device) {

	}

	/**
	 * Get the latest sensor data from the given device(s).
	 *
	 * Given a device ID (or a comma seperated list of IDs), return the device and the latest data readings
	 *
	 * @param string $devices
	 */
	public function getData($devices) {
		$arrDevices = explode(',',$devices);

		$result = array();

		foreach ($arrDevices as $deviceId) {

			try {
				$device = Device::find($deviceId);

				$o = new stdClass();
				$o->device = $device->__toApi();
				foreach ($device->getReadings() as $reading) {
					$o->device->readings []= $reading->__toApi();
				}

				$result[]=$o;
			} catch (RecordNotFoundException $e) { }
		}

		return array('deviceReadings'=>$result);
	}


	/**
	 * Get device sensor readings for the period specified.
	 *
	 * $period and $offset work combined to return data where (reading.timestamp > $offset-$period and reading.timestamp <= $offset)
	 *
	 * @param integer $device the device ID you're after
	 * @param string $sensorName the sensor name you're after, defaults to 'TEMP'
	 * @param integer $period the time period you want readings for. Defaults to one day.
	 * @param integer $offset The offset for when you want readings - 0 = today, -86400 = yesterday
	 */
	public function getReadings($deviceId,$sensorName="TEMP",$period=86400,$offset=0) {

		try {
			$device = Device::find($deviceId);

			$readings = array();

			foreach ($device->getReadingsForSensor($sensorName,$period,$offset) as $reading) {
				$readings[]=$reading->__toApi();
			}

		} catch (RecordNotFoundException $e) {
		}
		return array('device'=>$device->__toApi(),'readingCount'=>count($readings),'timeStart'=>time()-$offset-$period,'timeEnd'=>time()-$offset,  'deviceReadings'=>$readings);
	}

}
