<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'BindAccountConsent')){
		$bankaccount_no = preg_replace('/-/','',$dataComing["bank_account_no"]);
		$sigma_key = $lib->generate_token();
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$fetchMemberName = $conoracle->prepare("SELECT MP.PRENAME_DESC,MB.MEMB_NAME,MB.MEMB_SURNAME
												FROM MBMEMBMASTER MB LEFT JOIN MBUCFPRENAME MP ON MB.PRENAME_CODE = MP.PRENAME_CODE
												WHERE MB.member_no = :member_no");
		$fetchMemberName->execute([
			':member_no' => $member_no
		]);
		$rowMember = $fetchMemberName->fetch(PDO::FETCH_ASSOC);
		$account_name_th = $rowMember["PRENAME_DESC"].$rowMember["MEMB_NAME"].' '.$rowMember["MEMB_SURNAME"];
		$conmysql->beginTransaction();
		$insertBindAcc = $conmysql->prepare("INSERT INTO gcbindaccount(sigma_key,member_no,deptaccount_no_coop,deptaccount_no_bank,
											citizen_id,bank_account_name,bank_account_name_en,bank_code,bind_date,bindaccount_status,id_token)
											VALUES(:sigma_key,:member_no,:deptaccount_no_coop,:deptaccount_no_bank,
											:citizen_id,:bank_account_name,:bank_account_name_en,:bank_code,NOW(),'1',:id_token)");
		if($insertBindAcc->execute([
			':sigma_key' => $sigma_key,
			':member_no' => $payload["member_no"],
			':deptaccount_no_coop' => $payload["member_no"],
			':deptaccount_no_bank' => $bankaccount_no,
			':citizen_id' => $dataComing["citizen_id"],
			':bank_account_name' => $account_name_th,
			':bank_account_name_en' => $account_name_th,
			':bank_code' => $dataComing["bank_code"],
			':id_token' => $payload["id_token"]
		])){
			$coop_account_no = $payload["member_no"];
			$arrPayloadverify = array();
			$arrPayloadverify['member_no'] = $payload["member_no"];
			$arrPayloadverify['coop_account_no'] = $coop_account_no;
			$arrPayloadverify['bank_account_no'] = $bankaccount_no;
			$arrPayloadverify['citizen_id'] = $dataComing["citizen_id"];
			$arrPayloadverify["coop_key"] = $config["COOP_KEY"];
			$arrPayloadverify['exp'] = time() + 300;
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
			$responseAPI = $lib->posting_data($config["URL_API_COOPDIRECT"].'/ktb/request_reg_id_for_consent_non_direct',$arrSendData);
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
				$message_error = "ผูกบัญชีไม่ได้เพราะต่อ Service ไปที่ ".$config["URL_API_COOPDIRECT"]."/ktb/request_reg_id_for_consent_non_direct ไม่ได้ ตอนเวลา ".date('Y-m-d H:i:s');
				$lib->sendLineNotify($message_error);
				$func->MaintenanceMenu($dataComing["menu_component"]);
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
			$arrResponse = json_decode($responseAPI);
			if($arrResponse->RESULT){
				if(isset($dataComing["upload_bankbook"]) && $dataComing["upload_bankbook"] != ""){
					$subpath = $bankaccount_no;
					$destination = __DIR__.'/../../resource/book_bank';
					$data_Img = explode(',',$dataComing["upload_bankbook"]);
					$info_img = explode('/',$data_Img[0]);
					$ext_img = str_replace('base64','',$info_img[1]);
					if(!file_exists($destination)){
						mkdir($destination, 0777, true);
					}
					if($ext_img == 'png' || $ext_img == 'jpg' || $ext_img == 'jpeg'){
						$createImage = $lib->base64_to_img($dataComing["upload_bankbook"],$subpath,$destination,null);
					}else if($ext_img == 'pdf'){
						$createImage = $lib->base64_to_pdf($dataComing["upload_bankbook"],$subpath,$destination);
					}
					if($createImage == 'oversize'){
						$arrayResult['RESPONSE_CODE'] = "WS0008";
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						$arrayResult['RESULT'] = FALSE;
						require_once('../../include/exit_footer.php');
						
					}else{
						if($createImage){
							
						}
					}
				}
				$conmysql->commit();
				$arrayStruc = [
					':member_no' => $payload["member_no"],
					':id_userlogin' => $payload["id_userlogin"],
					':bind_status' => '1',
					':coop_account_no' => $coop_account_no
				];
				$log->writeLog('bindaccount',$arrayStruc);
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
					':deptaccount_no_bank' => $bankaccount_no,
					':citizen_id' => $dataComing["citizen_id"],
					':bank_account_name' => $account_name_th,
					':bank_account_name_en' => $account_name_th,
					':bank_code' => $dataComing["bank_code"],
					':id_token' => $payload["id_token"]
				]),
				':query_error' => $insertBindAcc->queryString,
				':query_flag' => '-9'
			];
			$log->writeLog('bindaccount',$arrayStruc);
			$message_error = "ผูกบัญชีไม่ได้เพราะ Insert ลง gcbindaccount ไม่ได้ "."\n"."Query => ".$insertBindAcc->queryString."\n"."Param =>". json_encode([
				':sigma_key' => $sigma_key,
				':member_no' => $payload["member_no"],
				':deptaccount_no_bank' => $bankaccount_no,
				':citizen_id' => $dataComing["citizen_id"],
				':bank_account_name' => $account_name_th,
				':bank_account_name_en' => $account_name_th,
				':bank_code' => $dataComing["bank_code"],
				':id_token' => $payload["id_token"]
			]);
			$lib->sendLineNotify($message_error);
			$func->MaintenanceMenu($dataComing["menu_component"]);
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
