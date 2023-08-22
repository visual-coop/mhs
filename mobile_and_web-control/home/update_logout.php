<?php
require_once('../autoload.php');

if($func->logout($payload["id_token"],'0')){
	$arrayResult['RESULT'] = TRUE;
	require_once('../../include/exit_footer.php');
}else{
	$filename = basename(__FILE__, '.php');
	$logStruc = [
		":error_menu" => $filename,
		":error_code" => "WS1007",
		":error_desc" => "ออกจากระบบไม่ได้ "."\n".json_encode($payload),
		":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
	];
	$log->writeLog('errorusage',$logStruc);
	$arrayResult['RESPONSE_CODE'] = "WS1007";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	require_once('../../include/exit_footer.php');
	
}
?>