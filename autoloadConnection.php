<?php
date_default_timezone_set("Asia/Bangkok");

require_once(__DIR__.'/include/connection.php');
require_once(__DIR__.'/include/validate_input.php');
use Connection\connection;

$con = new connection();
$conmysql = $con->connecttomysql();
$checkSystem = $conmysql->prepare("SELECT menu_status FROM gcmenu									
									WHERE menu_parent = '-1'
									and (menu_channel = :channel OR menu_channel = 'both')");
$checkSystem->execute([':channel' => $dataComing["channel"]]);
if($checkSystem->rowCount() > 0){
	$rowSystem = $checkSystem->fetch(PDO::FETCH_ASSOC);
	if($rowSystem["menu_status"] == '1'){
		$conoracle = $con->connecttooracle();
		if(is_array($conoracle)){
			$conoracle["IS_OPEN"] = '1';
		}
	}else{
		$conoracle = $con->connecttooracle();
		$conoracle->IS_OPEN = '0';
		if(!is_array($conoracle)){
			$updateMenu = $conmysql->prepare("UPDATE gcmenu SET menu_status = '1',menu_permission = '0' WHERE menu_parent = '-1' and menu_permission = '3'");
			$updateMenu->execute();
		}
	}
}else{
	$conoracle = $con->connecttooracle();
	$conoracle->IS_OPEN = '0';
	if(!is_array($conoracle)){
		$updateMenu = $conmysql->prepare("UPDATE gcmenu SET menu_status = '1',menu_permission = '0' WHERE menu_parent = '-1' and menu_permission = '3'");
		$updateMenu->execute();
	}
}
?>