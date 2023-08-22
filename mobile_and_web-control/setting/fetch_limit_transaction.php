<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'SettingLimitTrans')){
		$arrayLimitGrp = array();
		foreach($config["LIMIT_TRANSACTION"] as $limit_trans){
			if($limit_trans["IS_USE"] === "true"){
				$getLimitWithdraw = $conmysql->prepare("SELECT ".$limit_trans["LIMIT_NAME"]." FROM gcmemberaccount WHERE member_no = :member_no");
				$getLimitWithdraw->execute([':member_no' => $payload["member_no"]]);
				$rowLimitTransaction = $getLimitWithdraw->fetch(PDO::FETCH_ASSOC);
				$limit_coop = $func->getConstant($limit_trans["LIMIT_NAME"]);
				$limit_amt = 0;
				if($limit_coop >= $rowLimitTransaction[$limit_trans["LIMIT_NAME"]]){
					$limit_amt = (int)$rowLimitTransaction[$limit_trans["LIMIT_NAME"]];
				}else{
					$limit_amt = (int)$limit_coop;
				}
				$arrayLimit = array();
				$arrayLimit["LIMIT_NAME"] = $limit_trans["LIMIT_NAME"];
				$arrayLimit["TYPE_TRANS"] = $limit_trans["LIMIT_TYPE_".strtoupper($lang_locale)];
				$arrayLimit["LIMIT_AMOUNT"] = $limit_amt;
				$arrayLimit["LIMIT_AMOUNT_COOP"] = $limit_coop;
				$arrayLimitGrp[] = $arrayLimit;
			}
		}
		$arrayResult['LIMIT_GROUP'] = $arrayLimitGrp;
		$arrayResult['RESULT'] = TRUE;
		require_once('../../include/exit_footer.php');
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../include/exit_footer.php');
		
	}
}else{
	$filename = basename(__FILE__, '.php');
	$logStruc = [
		":error_menu" => $filename,
		":error_code" => "WS4004",
		":error_desc" => "ส่ง Argument มาไม่ครบ "."\n".json_encode($dataComing),
		":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
	];
	$log->writeLog('errorusage',$logStruc);
	$message_error = "ไฟล์ ".$filename." ส่ง Argument มาไม่ครบมาแค่ "."\n".json_encode($dataComing);
	$lib->sendLineNotify($message_error);
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../include/exit_footer.php');
	
}
?>