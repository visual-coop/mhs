<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'AssistInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrayGrpYear = array();
		$yearAss = 0;
		$fetchAssGrpYear = $conoracle->prepare("SELECT assist_year as ASSIST_YEAR,sum(ASSIST_AMT) as ASS_RECEIVED FROM assreqmaster 
												WHERE member_no = :member_no and req_status = 1 GROUP BY assist_year ORDER BY assist_year DESC");
		$fetchAssGrpYear->execute([':member_no' => $member_no]);
		while($rowAssYear = $fetchAssGrpYear->fetch(PDO::FETCH_ASSOC)){
			$arrayYear = array();
			$arrayYear["ASSIST_YEAR"] = $rowAssYear["ASSIST_YEAR"];
			$arrayYear["ASS_RECEIVED"] = number_format($rowAssYear["ASS_RECEIVED"],2);
			if($yearAss < $rowAssYear["ASSIST_YEAR"]){
				$yearAss = $rowAssYear["ASSIST_YEAR"];
			}
			$arrayGrpYear[] = $arrayYear;
		}
		if(isset($dataComing["ass_year"]) && $dataComing["ass_year"] != ""){
			$yearAss = $dataComing["ass_year"];
		}
		$fetchAssType = $conoracle->prepare("SELECT ast.ASSISTTYPE_DESC,ast.ASSISTTYPE_CODE,asm.ASSIST_DOCNO as ASSCONTRACT_NO,asm.ASSIST_AMT,asm.PAY_DATE
												FROM assreqmaster asm LEFT JOIN 
												assucfassisttype ast ON asm.ASSISTTYPE_CODE = ast.ASSISTTYPE_CODE and asm.coop_id = ast.coop_id 
												WHERE asm.member_no = :member_no 
												and asm.req_status = 1 and asm.assist_year = :year and asm.ref_slipno IS NOT NULL");
		$fetchAssType->execute([
			':member_no' => $member_no,
			':year' => $yearAss
		]);
		$arrGroupAss = array();
		while($rowAssType = $fetchAssType->fetch(PDO::FETCH_ASSOC)){
			$arrAss = array();
			$arrAss["ASSIST_RECVAMT"] = number_format($rowAssType["ASSIST_AMT"],2);
			$arrAss["PAY_DATE"] = $lib->convertdate($rowAssType["PAY_DATE"],'d m Y');
			$arrAss["ASSISTTYPE_CODE"] = $rowAssType["ASSISTTYPE_CODE"];
			$arrAss["ASSISTTYPE_DESC"] = $rowAssType["ASSISTTYPE_DESC"];
			$arrAss["ASSCONTRACT_NO"] = $rowAssType["ASSCONTRACT_NO"];
			$arrGroupAss[] = $arrAss;
		}
		$arrayResult["IS_STM"] = FALSE;
		$arrayResult["YEAR"] = $arrayGrpYear;
		$arrayResult["ASSIST"] = $arrGroupAss;
		$arrayResult["RESULT"] = TRUE;
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