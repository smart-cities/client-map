<?php

class DBOjbectResponse implements \Luracast\Restler\iRespond {
	function formatResponse($data) {

		if (is_callable(array($data,'__toApi'),false)) {
			// when we're using PHP 5.4 , and we have objects that implement JsonSerializable, this hack won't be needed anymore...
			return $data->__toApi();
		} else {

			if (is_array($data) && count($data) == 1) {
				// we've been given an array with a single key, e.g.
				// array('arrayOfBar'=>array($obj1,$obj2,$obj3));
				$tmp = array_keys($data);
				$arrayKey = $tmp[0];
			}

			if (isset($arrayKey) && isset($data[$arrayKey][0])) {
				foreach ($data[$arrayKey] as $val) {
					if (is_object($val) && !($val instanceof stdClass)) {
						$result[get_class($val)][] = $this->formatResponse($val);
					} else {
						$result[$arrayKey][] = $this->formatResponse($val);
					}
				}
				return $result;
			}
		}

		return $data;

	}
	function formatError($statusCode, $message) {
		return array('error' => array('code' => $statusCode,
				'message' => $message));
	}
}