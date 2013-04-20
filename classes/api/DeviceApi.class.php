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

}
