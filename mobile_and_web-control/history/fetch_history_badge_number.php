<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'Notification')){
		$getBadge = $conmysql->prepare("SELECT IFNULL(COUNT(id_history),0) as badge,his_type FROM gchistory 
										WHERE member_no = :member_no AND his_read_status = '0' and his_del_status = '0' 
										GROUP BY his_type");
		$getBadge->execute([
			':member_no' => $payload["member_no"]
		]);
		if($getBadge->rowCount() > 0){
			while($badgeData = $getBadge->fetch(PDO::FETCH_ASSOC)){
				$arrayResult['BADGE_'.$badgeData["his_type"]] = isset($badgeData["badge"]) ? $badgeData["badge"] : 0;
			}
			if(isset($arrayResult['BADGE_1'])){
				$arrayResult['BADGE_1'] = $arrayResult['BADGE_1'];
			}else{
				$arrayResult['BADGE_1'] = 0;
			}
			if(isset($arrayResult['BADGE_2'])){
				$arrayResult['BADGE_2'] = $arrayResult['BADGE_2'];
			}else{
				$arrayResult['BADGE_2'] = 0;
			}
			$arrayResult['BADGE_SUMMARY'] = $arrayResult['BADGE_1'] + $arrayResult['BADGE_2'];
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			http_response_code(204);
			
		}
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
