<?php
set_time_limit(150);
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','citizen_id'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'BindAccountConsent')){
		try {
			$coop_account_no = $payload["member_no"];
			$arrPayloadverify = array();
			$arrPayloadverify['member_no'] = $payload["member_no"];
			$arrPayloadverify['coop_account_no'] = $coop_account_no;
			$arrPayloadverify['citizen_id'] = $dataComing["citizen_id"];
			$arrPayloadverify["coop_key"] = $config["COOP_KEY"];
			$arrPayloadverify['exp'] = time() + 300;
			$sigma_key = $lib->generate_token();
			$arrPayloadverify['sigma_key'] = $sigma_key;
			$verify_token = $jwt_token->customPayload($arrPayloadverify, $config["SIGNATURE_KEY_VERIFY_API"]);
			$arrSendData = array();
			$arrSendData["verify_token"] = $verify_token;
			$arrSendData["app_id"] = $config["APP_ID"];
			$checkBeenBindForPending = $conmysql->prepare("SELECT id_bindaccount FROM gcbindaccount WHERE member_no = :member_no 
														and bindaccount_status = '8' and bank_code = '006'");
			$checkBeenBindForPending->execute([
				':member_no' => $payload["member_no"]
			]);
			if($checkBeenBindForPending->rowCount() > 0){
				$arrayAccPending = array();
				while($rowAccPending = $checkBeenBindForPending->fetch(PDO::FETCH_ASSOC)){
					$arrayAccPending[] = $rowAccPending["id_bindaccount"];
				}
				$deleteAccForPending = $conmysql->prepare("DELETE FROM gcbindaccount WHERE id_bindaccount IN(".implode(',',$arrayAccPending).")");
				$deleteAccForPending->execute();
			}
			$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
			$fetchMemberName = $conoracle->prepare("SELECT MP.PRENAME_DESC,MB.MEMB_NAME,MB.MEMB_SURNAME 
													FROM MBMEMBMASTER MB LEFT JOIN MBUCFPRENAME MP ON MB.PRENAME_CODE = MP.PRENAME_CODE
													WHERE MB.member_no = :member_no");
			$fetchMemberName->execute([
				':member_no' => $member_no
			]);
			$rowMember = $fetchMemberName->fetch(PDO::FETCH_ASSOC);
			$account_name_th = $rowMember["PRENAME_DESC"].$rowMember["MEMB_NAME"].' '.$rowMember["MEMB_SURNAME"];
			//$account_name_en = $arrResponseVerify->ACCOUNT_NAME_EN;
			$conmysql->beginTransaction();
			
			
			$insertPendingBindAccount = $conmysql->prepare("INSERT INTO gcbindaccount(sigma_key,member_no,deptaccount_no_coop,citizen_id,bank_account_name,bank_account_name_en,bank_code,id_token,account_payfee) 
															VALUES(:sigma_key,:member_no,:coop_account_no,:citizen_id,:bank_account_name,:bank_account_name_en,'006',:id_token,:acc_payfee)");
			if($insertPendingBindAccount->execute([
				':sigma_key' => $sigma_key,
				':member_no' => $payload["member_no"],
				':coop_account_no' => $coop_account_no,
				':citizen_id' => $dataComing["citizen_id"],
				':bank_account_name' => $account_name_th,
				':bank_account_name_en' => $account_name_th,
				':id_token' => $payload["id_token"],
				':acc_payfee' => preg_replace('/-/','',$dataComing["account_payfee"])
			])){
				$responseAPI = $lib->posting_data($config["URL_API_COOPDIRECT"].'/ktb/request_reg_id_for_consent',$arrSendData);
				if(!$responseAPI["RESULT"]){
					$arrayResult['RESPONSE_CODE'] = "WS0022";
					$arrayStruc = [
						':member_no' => $payload["member_no"],
						':id_userlogin' => $payload["id_userlogin"],
						':bind_status' => '-9',
						':response_code' => $arrayResult['RESPONSE_CODE'],
						':response_message' => $responseAPI["RESPONSE_MESSAGE"],
						':coop_account_no' => $coop_account_no,
						':query_flag' => '1'
					];
					$log->writeLog('bindaccount',$arrayStruc);
					$message_error = "ผูกบัญชีไม่ได้เพราะต่อ Service ไปที่ ".$config["URL_API_COOPDIRECT"]."/ktb/request_reg_id_for_consent ไม่ได้ ตอนเวลา ".date('Y-m-d H:i:s');
					$lib->sendLineNotify($message_error);
					$func->MaintenanceMenu($dataComing["menu_component"]);
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
					
				}
				$arrResponse = json_decode($responseAPI);
				if($arrResponse->RESULT){
					$conmysql->commit();
					$arrayStruc = [
						':member_no' => $payload["member_no"],
						':id_userlogin' => $payload["id_userlogin"],
						':bind_status' => '1',
						':coop_account_no' => $coop_account_no
					];
					$log->writeLog('bindaccount',$arrayStruc);
					$arrayResult["URL_CONSENT"] = $arrResponse->URL_CONSENT;
					$arrayResult['RESULT'] = TRUE;
					require_once('../../include/exit_footer.php');
				}else{
					$conmysql->rollback();
					$arrayResult['RESPONSE_CODE'] = "WS0039";
					$arrayStruc = [
						':member_no' => $payload["member_no"],
						':id_userlogin' => $payload["id_userlogin"],
						':bind_status' => '-9',
						':response_code' => $arrayResult['RESPONSE_CODE'],
						':response_message' => $arrResponse->RESPONSE_MESSAGE,
						':coop_account_no' => $coop_account_no,
						':query_flag' => '1'
					];
					$log->writeLog('bindaccount',$arrayStruc);
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
					
				}
			}else{
				$conmysql->rollback();
				$arrayResult['RESPONSE_CODE'] = "WS1022";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayStruc = [
					':member_no' => $payload["member_no"],
					':id_userlogin' => $payload["id_userlogin"],
					':bind_status' => '-9',
					':response_code' => $arrayResult['RESPONSE_CODE'],
					':response_message' => $arrayResult['RESPONSE_MESSAGE'],
					':coop_account_no' => $coop_account_no,
					':data_bind_error' => json_encode([
						':sigma_key' => $sigma_key,
						':member_no' => $payload["member_no"],
						':coop_account_no' => $coop_account_no,
						':citizen_id' => $dataComing["citizen_id"],
						':bank_account_name' => $account_name_th,
						':bank_account_name_en' => $account_name_th,
						':id_token' => $payload["id_token"],
						':acc_payfee' => preg_replace('/-/','',$dataComing["account_payfee"])
					]),
					':query_error' => $insertPendingBindAccount->queryString,
					':query_flag' => '-9'
				];
				$log->writeLog('bindaccount',$arrayStruc);
				$message_error = "ผูกบัญชีไม่ได้เพราะ Insert ลง gcbindaccount ไม่ได้ "."\n"."Query => ".$insertPendingBindAccount->queryString."\n"."Param =>". json_encode([
					':sigma_key' => $sigma_key,
					':member_no' => $payload["member_no"],
					':coop_account_no' => $coop_account_no,
					':citizen_id' => $dataComing["citizen_id"],
					':bank_account_name' => $account_name_th,
					':bank_account_name_en' => $account_name_th,
					':id_token' => $payload["id_token"],
					':acc_payfee' => preg_replace('/-/','',$dataComing["account_payfee"])
				]);
				$lib->sendLineNotify($message_error);
				$func->MaintenanceMenu($dataComing["menu_component"]);
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
		}catch(Throwable $e) {
			$arrayResult['RESPONSE_CODE'] = "WS0039";
			$arrayStruc = [
				':member_no' => $payload["member_no"],
				':id_userlogin' => $payload["id_userlogin"],
				':bind_status' => '-9',
				':response_code' => $arrayResult['RESPONSE_CODE'],
				':response_message' => $e->getMessage(),
				':query_flag' => '1'
			];
			$log->writeLog('bindaccount',$arrayStruc,true);
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
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