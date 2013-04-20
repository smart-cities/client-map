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

}
