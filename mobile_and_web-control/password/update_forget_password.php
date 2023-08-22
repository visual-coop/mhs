<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['api_token','unique_id','member_no','email','device_name'],$dataComing)){
	$arrPayload = $auth->check_apitoken($dataComing["api_token"],$config["SECRET_KEY_JWT"]);
	if(!$arrPayload["VALIDATE"]){
		$filename = basename(__FILE__, '.php');
		$logStruc = [
			":error_menu" => $filename,
			":error_code" => "WS0001",
			":error_desc" => "ไม่สามารถยืนยันข้อมูลได้"."\n".json_encode($dataComing),
			":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
		];
		$log->writeLog('errorusage',$logStruc);
		$arrayResult['RESPONSE_CODE'] = "WS0001";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(401);
		require_once('../../include/exit_footer.php');
		
	}
	$member_no = strtoupper($lib->mb_str_pad($dataComing["member_no"]));
	$checkMember = $conmysql->prepare("SELECT account_status,email FROM gcmemberaccount 
										WHERE member_no = :member_no");
	$checkMember->execute([
		':member_no' => $member_no
	]);
	if($checkMember->rowCount() > 0){
		$rowChkMemb = $checkMember->fetch(PDO::FETCH_ASSOC);
		if($rowChkMemb["account_status"] == '-8'){
			$arrayResult['RESPONSE_CODE'] = "WS0048";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
		if(empty($rowChkMemb["email"])){
			$arrayResult['RESPONSE_CODE'] = "WS0049";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
		if(strtolower($dataComing["email"]) != strtolower($rowChkMemb["email"])){
			$arrayResult['RESPONSE_CODE'] = "WS0050";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
		$getNameMember = $conoracle->prepare("SELECT memb_name,memb_surname FROM mbmembmaster WHERE member_no = :member_no");
		$getNameMember->execute([':member_no' => $member_no]);
		$rowName = $getNameMember->fetch(PDO::FETCH_ASSOC);
		$template = $func->getTemplateSystem('ForgetPassword');
		$arrayDataTemplate = array();
		$temp_pass = $lib->randomText('number',6);
		$arrayDataTemplate["FULL_NAME"] = (isset($rowName["MEMB_NAME"]) ? $rowName["MEMB_NAME"].' '.$rowName["MEMB_SURNAME"] : $member_no);
		$arrayDataTemplate["TEMP_PASSWORD"] = $temp_pass;
		$arrayDataTemplate["DEVICE_NAME"] = $arrPayload["PAYLOAD"]["device_name"];
		$arrayDataTemplate["REQUEST_DATE"] = $lib->convertdate(date('Y-m-d H:i'),'D m Y',true);
		$conmysql->beginTransaction();
		$updateTemppass = $conmysql->prepare("UPDATE gcmemberaccount SET prev_acc_status = account_status,temppass = :temp_pass,account_status = '-9',counter_wrongpass = 0,temppass_is_md5 = '0'
											WHERE member_no = :member_no");
		if($updateTemppass->execute([
			':temp_pass' => password_hash($temp_pass,PASSWORD_DEFAULT),
			':member_no' => $member_no
		])){
			$arrResponse = $lib->mergeTemplate($template["SUBJECT"],$template["BODY"],$arrayDataTemplate);
			$arrMailStatus = $lib->sendMail($dataComing["email"],$arrResponse["SUBJECT"],$arrResponse["BODY"],$mailFunction);
			if($arrMailStatus["RESULT"]){
				$conmysql->commit();
				if($func->logoutAll(null,$member_no,'-9')){
					$arrayResult['RESULT'] = TRUE;
					require_once('../../include/exit_footer.php');
				}else{
					$filename = basename(__FILE__, '.php');
					$logStruc = [
						":error_menu" => $filename,
						":error_code" => "WS1014",
						":error_desc" => "ลืมรหัสผ่านไม่ได้เพราะไม่สามารถบังคับอุปกรณ์อื่นออกจากระบบได้ "."\n".json_encode($dataComing),
						":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
					];
					$log->writeLog('errorusage',$logStruc);
					$message_error = "ไม่สามารถลืมรหัสผ่านได้เพราะบังคับคนออกจากระบบไม่ได้"."\n"."Data => ".json_encode($dataComing)."\n"."Payload => ".json_encode($payload);
					$lib->sendLineNotify($message_error);
					$arrayResult['RESPONSE_CODE'] = "WS1014";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
					
				}
			}else{
				$filename = basename(__FILE__, '.php');
				$logStruc = [
					":error_menu" => $filename,
					":error_code" => "WS0019",
					":error_desc" => "ส่งเมลไม่ได้ ".$dataComing["email"]."\n"."Error => ".$arrMailStatus["MESSAGE_ERROR"],
					":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
				];
				$log->writeLog('errorusage',$logStruc);
				$conmysql->rollback();
				$arrayResult['RESPONSE_CODE'] = "WS0019";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
		}else{
			$conmysql->rollback();
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS1014",
				":error_desc" => "บันทึกรหัสผ่านชั่วคราวไม่ได้ "."\n".json_encode($dataComing),
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "ไม่สามารถลืมรหัสผ่านได้เพราะ Update ลง gcmemberaccount ไม่ได้"."\n"."Query => ".$updateTemppass->queryString."\n"."Param => ". json_encode([
				':temp_pass' => password_hash($temp_pass,PASSWORD_DEFAULT),
				':member_no' => $member_no
			]);
			$lib->sendLineNotify($message_error);
			$arrayResult['RESPONSE_CODE'] = "WS1014";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0003";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
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