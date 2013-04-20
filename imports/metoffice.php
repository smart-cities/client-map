<?php
require('../common.php');

$row = 1;
if (($handle = fopen("metoffice.txt", "r")) !== FALSE) {
		while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
			$deviceId = $data[1];
			$timestamp = $data[2] / 1000;
			$tempFaren = $data[4];
			$tempCelcius = ($tempFaren - 32) / 1.8;
			echo $deviceId.' '.date('Y-m-d H:i:s',$timestamp).' '.$tempCelcius."\r\n";

			$r = new Reading();
			$r->_device_id = $deviceId;
			$r->timestsamp = $timestamp;
			$r->sensorName = 'TEMP';
			$r->dataFloat = $tempCelcius;
			$r->save();

		}
		fclose($handle);
}

