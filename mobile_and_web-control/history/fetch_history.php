<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','type_history'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'Notification')){
		$arrGroupHis = array();
		$executeData = [
			':member_no' => $payload["member_no"],
			':his_type' => $dataComing["type_history"]
		];
		$extraQuery = "";
		if(isset($dataComing["fetch_type"])){
			switch($dataComing["fetch_type"]){
				case "refresh":
					$executeData[':id_history'] = $dataComing["id_history"] ?? 0; 
					$extraQuery = "and id_history > :id_history";
					break;
				case "more":
					$executeData[':id_history'] = $dataComing["id_history"] ?? 16777215; // max number mediumint(8) of id_history
					$extraQuery = "and id_history < :id_history";
					break;
			}
		}
		$getHistory = $conmysql->prepare("SELECT id_history,his_title,his_detail,receive_date,his_read_status,his_path_image  FROM gchistory 
											WHERE member_no = :member_no and his_type = :his_type $extraQuery and his_del_status = '0' 
											ORDER BY id_history DESC LIMIT 10");
		$getHistory->execute($executeData);
		while($rowHistory = $getHistory->fetch(PDO::FETCH_ASSOC)){
			$arrHistory = array();
			$arrHistory["TITLE"] = $rowHistory["his_title"];
			$arrHistory["DETAIL"] = $rowHistory["his_detail"];
			$arrHistory["READ_STATUS"] = $rowHistory["his_read_status"];
			$arrHistory["IMG"] = $rowHistory["his_path_image"];
			$arrHistory["ID_HISTORY"] = $rowHistory["id_history"];
			$arrHistory["RECEIVE_DATE"] = $lib->convertdate($rowHistory["receive_date"],'D m Y',true);
			$arrGroupHis[] = $arrHistory;
		}
		$arrayResult['HISTORY'] = $arrGroupHis;
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
		":error_desc" => "??? Argument ???????? "."\n".json_encode($dataComing),
		":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
	];
	$log->writeLog('errorusage',$logStruc);
	$message_error = "???? ".$filename." ??? Argument ????????????? "."\n".json_encode($dataComing);
	$lib->sendLineNotify($message_error);
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../include/exit_footer.php');
	
}
?>
