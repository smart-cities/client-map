<?php

class SmartCitiesAutoloader
{
	public static function Register() {
		return spl_autoload_register(array('SmartCitiesAutoloader', 'Load'));
	}

	public static function Load($pObjectName){

		// array of directories to check
		$arrDirs=array(
				dirname(__FILE__).'/',
				dirname(__FILE__).'/db/',
				dirname(__FILE__).'/dbobjects/',
				dirname(__FILE__).'/helpers/'
		);
		// replace _ with ., e.g. DB_PDO becomes db.pdo
		$fileName=str_replace('_','.',$pObjectName);

		// API files
		if (substr($fileName,-3)=='Api') {
			$fileName = 'api/'.$fileName;
		}

		foreach ($arrDirs as $dir) {
			if (file_exists($dir.$fileName.'.class.php')) {
				require_once($dir.$fileName.'.class.php');
				break;
			}elseif (file_exists($dir.$pObjectName.'.class.php')){
				require_once ($dir.$pObjectName.'.class.php');
				break;
			}
		}

	}	//	function Load()

}

SmartCitiesAutoloader::Register();