<?php
require_once('../autoload.php');

if(isset($dataComing["fcm_token"]) && $dataComing["fcm_token"] != ""){
	$updateNewToken = $conmysql->prepare("UPDATE gcmemberaccount SET fcm_token = :fcm_token WHERE member_no = :member_no");
	$updateNewToken->execute([
		':fcm_token' => $dataComing["fcm_token"],
		':member_no' => $payload["member_no"]
	]);
}
if(isset($dataComing["hms_token"]) && $dataComing["hms_token"] != ""){
	$updateNewToken = $conmysql->prepare("UPDATE gcmemberaccount SET hms_token = :hms_token WHERE member_no = :member_no");
	$updateNewToken->execute([
		':hms_token' => $dataComing["hms_token"],
		':member_no' => $payload["member_no"]
	]);
}
$arrayResult['RESULT'] = TRUE;
require_once('../../include/exit_footer.php');
?>