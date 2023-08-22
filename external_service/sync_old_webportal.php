<?php
require_once(__DIR__.'/../extension/vendor/autoload.php');
require_once('../autoloadConnection.php');
require_once(__DIR__.'/../include/lib_util.php');
require_once(__DIR__.'/../include/function_util.php');

use Utility\Library;
use Component\functions;
use WebPConvert\WebPConvert;

$lib = new library();
$func = new functions();
$webP = new WebPConvert();

$arrGrp = array();

$dbhost = "127.0.0.1";
$dbuser = "root";
$dbpass = "@TAK2020";
$dbname = "mobile_tak";
try{
	$conmysql = new PDO("mysql:dbname={$dbname};host={$dbhost}", $dbuser, $dbpass);
	$conmysql->exec("set names utf8mb4");
}catch(Throwable $e){
	echo json_encode($e);
}
$dbhost2 = "192.168.0.213";
$dbuser2 = "root";
$dbpass2 = "WebServer";
$dbname2 = "mobile_tak";
try{
	$conmysql2 = new PDO("mysql:dbname={$dbname2};host={$dbhost2}", $dbuser2, $dbpass2);
	$conmysql2->exec("set names utf8mb4");
}catch(Throwable $e){
	echo json_encode($e);
}
	$bulkIns = array();
	$arrayMember = array();
	$getData_New = $conmysql->prepare("SELECT member_no FROM gcmemberaccount ");
	$getData_New->execute();
	$member = array();
	$mmn = array();
	while($row = $getData_New->fetch(PDO::FETCH_ASSOC)){
		$member[] = "'".$row["member_no"]."'";
	}
	$getMe = $conmysql2->prepare("SELECT * FROM mdbmembmaster WHERE member_no NOT IN(".implode(',',$member).") GROUP BY member_no");
	$getMe->execute();
	$insertTOSRN = array();
	$i = 0;
	while($rowMe = $getMe->fetch()){
		$pass = password_hash($rowMe["password"], PASSWORD_DEFAULT);
		$pin = password_hash($rowMe["pin"], PASSWORD_DEFAULT);
		if(isset($rowMe["path_pic"]) && $rowMe["path_pic"] != null){
			$pathInsert = '/resource/avatar/'.$rowMe["member_no"].'/'.$rowMe["member_no"].'.jpg';
			$insertTOSRN[] = "('".$rowMe["member_no"]."','".$pass."','".$pin."','".
			$rowMe["ADDR_PHONE"]."','".$rowMe["ADDR_EMAIL"]."','-9','".$pass."','".
			$pathInsert."','1')";
		}else{
			$insertTOSRN[] = "('".$rowMe["member_no"]."','".$pass."','".$pin."','".
			$rowMe["ADDR_PHONE"]."','".$rowMe["ADDR_EMAIL"]."','-9','".$pass."',null,'1')";

		}
		if(sizeof($insertTOSRN) == 1000){
			$insert = $conmysql->prepare("INSERT INTO gcmemberaccount(member_no,password,pin,phone_number,email,account_status,temppass,path_avatar,temppass_is_md5)
											VALUES".implode(',',$insertTOSRN));
			if($insert->execute()){
				echo 'done insert 1000';
			}else{
				echo json_encode($insert);
			}
			unset($insertTOSRN);
			$insertTOSRN = array();
		}
		$i++;
		echo $i.'/';
	}
		
	if(sizeof($insertTOSRN) > 0){
		$insert = $conmysql->prepare("INSERT INTO gcmemberaccount(member_no,password,pin,phone_number,email,account_status,temppass,path_avatar,temppass_is_md5)
												VALUES".implode(',',$insertTOSRN));
		$insert->execute();
	}
	echo 'done !!';

?>