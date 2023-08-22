<?php

namespace Connection;

class connection {
	public $conmysql;
	public $conoldmysql;
	public $conoracle;
	
	public function connecttooldmysql() {
		$json = file_get_contents(__DIR__.'/../config/config_connection.json');
		$json_data = json_decode($json,true);
		$dbhost = $json_data["DBMOBILE_HOST_OLD"];
		$dbuser = $json_data["DBMOBILE_USERNAME_OLD"];
		$dbpass = $json_data["DBMOBILE_PASSWORD_OLD"];
		$dbname = $json_data["DBMOBILE_DATABASENAME_OLD"];
		try{
			$this->conoldmysql = new \PDO("mysql:dbname={$dbname};host={$dbhost}", $dbuser, $dbpass);
			$this->conoldmysql->exec("set names utf8mb4");
			return $this->conoldmysql;
		}catch(\Throwable $e){
			$arrayError = array();
			$arrayError["ERROR"] = $e->getMessage();
			$arrayError["RESULT"] = FALSE;
			$arrayError["MESSAGE"] = "Can't connect To MySQL Old Server";
			return $arrayError;
			http_response_code(200);
			exit();
		}
	}
	public function connecttomysql() {
		$json = file_get_contents(__DIR__.'/../config/config_connection.json');
		$json_data = json_decode($json,true);
		$dbhost = $json_data["DBMOBILE_HOST"];
		$dbuser = $json_data["DBMOBILE_USERNAME"];
		$dbpass = $json_data["DBMOBILE_PASSWORD"];
		$dbname = $json_data["DBMOBILE_DATABASENAME"];
		try{
			$this->conmysql = new \PDO("mysql:dbname={$dbname};host={$dbhost}", $dbuser, $dbpass);
			$this->conmysql->exec("set names utf8mb4");
			return $this->conmysql;
		}catch(\Throwable $e){
			$arrayError = array();
			$arrayError["ERROR"] = $e->getMessage();
			$arrayError["RESULT"] = FALSE;
			$arrayError["MESSAGE"] = "Can't connect To MySQL";
			return $arrayError;
			http_response_code(200);
			exit();
		}
	}
	public function connecttooracle() {
		$json = file_get_contents(__DIR__.'/../config/config_connection.json');
		$json_data = json_decode($json,true);
		try{
			$dbuser = $json_data["DBORACLE_USERNAME"];
			$dbpass = $json_data["DBORACLE_PASSWORD"];
			$dbname = "(DESCRIPTION =
						(ADDRESS_LIST =
						  (ADDRESS = (PROTOCOL = TCP)(HOST = ".$json_data["DBORACLE_HOST"].")(PORT = ".$json_data["DBORACLE_PORT"]."))
						)
						(CONNECT_DATA =
						  (".$json_data["DBORACLE_TYPESERVICE"]." = ".$json_data["DBORACLE_SERVICE"].")
						)
					  )";
			$this->conoracle = new \PDO("oci:dbname=".$dbname.";charset=utf8", $dbuser, $dbpass);
			$this->conoracle->query("ALTER SESSION SET NLS_DATE_FORMAT = 'DD-MM-YYYY HH24:MI:SS'");
			$this->conoracle->query("ALTER SESSION SET NLS_DATE_LANGUAGE = 'AMERICAN'");
			return $this->conoracle;
		}catch(\Throwable $e){
			$arrayError = array();
			$arrayError["ERROR"] = $e->getMessage();
			$arrayError["RESULT"] = FALSE;
			$arrayError["MESSAGE"] = "Can't connect To Oracle";
			return $arrayError;
			http_response_code(200);
			exit();
		}
	}
}
?>