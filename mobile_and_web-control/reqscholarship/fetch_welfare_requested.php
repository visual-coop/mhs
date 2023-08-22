<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ScholarshipRequest')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrChildGrp = array();
		$checkChildHave = $conoracle->prepare("SELECT asch.childcard_id as CHILDCARD_ID, mp.prename_desc||asch.child_name||'   '||asch.child_surname as CHILD_NAME
															FROM ASNREQSCHOLARSHIP asch LEFT JOIN mbucfprename mp ON  asch.childprename_code = mp.prename_code
															WHERE  asch.approve_status = 1 and asch.scholarship_year = (EXTRACT(year from sysdate) +542) and asch.member_no = :member_no");
		$checkChildHave->execute([':member_no' => $member_no]);
		while($rowChild = $checkChildHave->fetch(PDO::FETCH_ASSOC)){
			$arrChild = array();
			$arrChild["CHILDCARD_ID"] = $rowChild["CHILDCARD_ID"];
			$arrChild["CHILDCARD_ID_FORMAT"] = $lib->formatcitizen($rowChild["CHILDCARD_ID"]);
			$arrChild["CHILD_NAME"] = $rowChild["CHILD_NAME"];
			$arrChildGrp[] = $arrChild;
		}
		$arrayResult['CHILD'] = $arrChildGrp;
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