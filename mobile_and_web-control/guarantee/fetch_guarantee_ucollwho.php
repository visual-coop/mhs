<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'GuaranteeInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		
		$arrayGroupLoan = array();
		$getUcollwho = $conoracle->prepare("SELECT
											LCC.LOANCONTRACT_NO AS LOANCONTRACT_NO,
											LNTYPE.loantype_desc as TYPE_DESC,
											PRE.PRENAME_DESC,MEMB.MEMB_NAME,MEMB.MEMB_SURNAME,
											LCM.MEMBER_NO AS MEMBER_NO,
											NVL(LCM.principal_balance,0) as LOAN_BALANCE,
											LCM.LAST_PERIODPAY as LAST_PERIOD,
											LCM.period_payamt as PERIOD
											FROM
											LNCONTCOLL LCC LEFT JOIN LNCONTMASTER LCM ON  LCC.LOANCONTRACT_NO = LCM.LOANCONTRACT_NO
											LEFT JOIN MBMEMBMASTER MEMB ON LCM.MEMBER_NO = MEMB.MEMBER_NO
											LEFT JOIN MBUCFPRENAME PRE ON MEMB.PRENAME_CODE = PRE.PRENAME_CODE
											LEFT JOIN lnloantype LNTYPE  ON LCM.loantype_code = LNTYPE.loantype_code
											WHERE
											LCM.CONTRACT_STATUS > 0 AND LCM.CONTRACT_STATUS <> 8
											AND LCC.COLL_STATUS = '1'
											AND LCC.LOANCOLLTYPE_CODE = '01'
											AND LCC.REF_COLLNO = :member_no");
		$getUcollwho->execute([':member_no' => $member_no]);
		while($rowUcollwho = $getUcollwho->fetch(PDO::FETCH_ASSOC)){
			$arrayColl = array();
			$arrayColl["CONTRACT_NO"] = $rowUcollwho["LOANCONTRACT_NO"];
			$arrayColl["TYPE_DESC"] = $rowUcollwho["TYPE_DESC"];
			$arrayColl["MEMBER_NO"] = $rowUcollwho["MEMBER_NO"];
			$arrayAvarTar = $func->getPathpic($rowUcollwho["MEMBER_NO"]);
			$arrayColl["AVATAR_PATH"] = isset($arrayAvarTar["AVATAR_PATH"]) ? $config["URL_SERVICE"].$arrayAvarTar["AVATAR_PATH"] : null;
			$arrayColl["AVATAR_PATH_WEBP"] = isset($arrayAvarTar["AVATAR_PATH_WEBP"]) ? $config["URL_SERVICE"].$arrayAvarTar["AVATAR_PATH_WEBP"] : null;
			$arrayColl["LOAN_BALANCE"] = number_format($rowUcollwho["LOAN_BALANCE"],2);
			$arrayColl["LAST_PERIOD"] = $rowUcollwho["LAST_PERIOD"].' / '.$rowUcollwho["PERIOD"];
			$arrayColl["FULL_NAME"] = $rowUcollwho["PRENAME_DESC"].$rowUcollwho["MEMB_NAME"].' '.$rowUcollwho["MEMB_SURNAME"];
			$arrayGroupLoan[] = $arrayColl;
		}
		$arrayResult['CONTRACT_COLL'] = $arrayGroupLoan;
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