<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DepositInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrAllAccount = array();
		$getSumAllAccount = $conoracle->prepare("SELECT SUM(prncbal) as SUM_BALANCE FROM dpdeptmaster WHERE member_no = :member_no and deptclose_status <> 1");
		$getSumAllAccount->execute([':member_no' => $member_no]);
		$rowSumbalance = $getSumAllAccount->fetch(PDO::FETCH_ASSOC);
		$arrayResult['SUM_BALANCE'] = number_format($rowSumbalance["SUM_BALANCE"],2);
		$getAccount = $conoracle->prepare("SELECT dp.depttype_code,dt.depttype_desc,dp.deptaccount_no,dp.deptaccount_name,dp.prncbal as BALANCE,
											(SELECT max(OPERATE_DATE) FROM dpdeptstatement WHERE deptaccount_no = dp.deptaccount_no) as LAST_OPERATE_DATE
											FROM dpdeptmaster dp LEFT JOIN DPDEPTTYPE dt ON dp.depttype_code = dt.depttype_code
											WHERE dp.member_no = :member_no and dp.deptclose_status <> 1 ORDER BY dp.deptaccount_no ASC");
		$getAccount->execute([':member_no' => $member_no]);
		while($rowAccount = $getAccount->fetch(PDO::FETCH_ASSOC)){
			$arrAccount = array();
			$arrGroupAccount = array();
			$account_no = $lib->formataccount($rowAccount["DEPTACCOUNT_NO"],$func->getConstant('dep_format'));
			$arrayHeaderAcc = array();
			if($dataComing["channel"] == 'web'){
				if(file_exists(__DIR__.'/../../resource/cover-dept/'.$rowAccount["DEPTTYPE_CODE"].'.jpg')){
					$arrGroupAccount["COVER_IMG"] = $config["URL_SERVICE"].'resource/cover-dept/'.$rowAccount["DEPTTYPE_CODE"].'.jpg?v='.date('Ym');
				}else{
					$arrGroupAccount["COVER_IMG"] = null;
				}
			}
			$fetchAlias = $conmysql->prepare("SELECT alias_name,path_alias_img,date_format(update_date,'%Y%m%d%H%i%s') as update_date FROM gcdeptalias WHERE deptaccount_no = :account_no");
			$fetchAlias->execute([
				':account_no' => $rowAccount["DEPTACCOUNT_NO"]
			]);
			$rowAlias = $fetchAlias->fetch(PDO::FETCH_ASSOC);
			$arrAccount["ALIAS_NAME"] = $rowAlias["alias_name"] ?? null;
			if(isset($rowAlias["path_alias_img"])){
				$explodePathAliasImg = explode('.',$rowAlias["path_alias_img"]);
				$arrAccount["ALIAS_PATH_IMG_WEBP"] = $config["URL_SERVICE"].$explodePathAliasImg[0].'.webp?v='.$rowAlias["update_date"];
				$arrAccount["ALIAS_PATH_IMG"] = $config["URL_SERVICE"].$rowAlias["path_alias_img"].'?v='.$rowAlias["update_date"];
			}else{
				$arrAccount["ALIAS_PATH_IMG"] = null;
				$arrAccount["ALIAS_PATH_IMG_WEBP"]  = null;
			}
			$arrAccount["DEPTACCOUNT_NO"] = $account_no;
			$arrAccount["DEPTACCOUNT_NO_HIDDEN"] = $lib->formataccount_hidden($account_no,$func->getConstant('hidden_dep'));
			$arrAccount["DEPTACCOUNT_NAME"] = preg_replace('/\"/','',TRIM($rowAccount["DEPTACCOUNT_NAME"]));
			$arrAccount["BALANCE"] = number_format($rowAccount["BALANCE"],2);
			$arrAccount["LAST_OPERATE_DATE"] = $lib->convertdate($rowAccount["LAST_OPERATE_DATE"],'y-n-d');
			$arrAccount["LAST_OPERATE_DATE_FORMAT"] = $lib->convertdate($rowAccount["LAST_OPERATE_DATE"],'D m Y');
			$arrGroupAccount['TYPE_ACCOUNT'] = $rowAccount["DEPTTYPE_DESC"];
			$arrGroupAccount['DEPT_TYPE_CODE'] = $rowAccount["DEPTTYPE_CODE"];
			if(array_search($rowAccount["DEPTTYPE_DESC"],array_column($arrAllAccount,'TYPE_ACCOUNT')) === False){
				($arrGroupAccount['ACCOUNT'])[] = $arrAccount;
				$arrAllAccount[] = $arrGroupAccount;
			}else{
				($arrAllAccount[array_search($rowAccount["DEPTTYPE_DESC"],array_column($arrAllAccount,'TYPE_ACCOUNT'))]["ACCOUNT"])[] = $arrAccount;
			}
		}
		
		$arrayResult['DETAIL_DEPOSIT'] = $arrAllAccount;
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