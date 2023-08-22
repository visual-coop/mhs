<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['member_no','api_token','password','unique_id'],$dataComing)){
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
	$checkLogin = $conmysql->prepare("SELECT password,user_type,pin,account_status,temppass,temppass_is_md5 FROM gcmemberaccount 
										WHERE member_no = :member_no");
	$checkLogin->execute([':member_no' => $member_no]);
	if($checkLogin->rowCount() > 0){
		if($arrPayload["PAYLOAD"]["channel"] == "mobile_app" && isset($dataComing["is_root"])){
			$checkBlackList = $conmysql->prepare("SELECT type_blacklist FROM gcdeviceblacklist WHERE unique_id = :unique_id and is_blacklist = '1' and type_blacklist = '1'");
			$checkBlackList->execute([':unique_id' => $dataComing["unique_id"]]);
			if($checkBlackList->rowCount() > 0){
				$arrayResult['RESPONSE_CODE'] = "WS0069";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
			if($dataComing["is_root"] == "1"){
				$insertBlackList = $conmysql->prepare("INSERT INTO gcdeviceblacklist(unique_id,member_no,type_blacklist)
													VALUES(:unique_id,:member_no,'1')");
				if($insertBlackList->execute([
					':unique_id' => $dataComing["unique_id"],
					':member_no' => $member_no
				])){
					$arrayResult['RESPONSE_CODE'] = "WS0069";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
					
				}
			}
		}
		$rowPassword = $checkLogin->fetch(PDO::FETCH_ASSOC);
		$checkResign = $conoracle->prepare("SELECT resign_status FROM mbmembmaster WHERE member_no = :member_no");
		$checkResign->execute([':member_no' => $member_no]);
		$rowResign = $checkResign->fetch(PDO::FETCH_ASSOC);
		if($rowResign["RESIGN_STATUS"] == '1'){
			$arrayResult['RESPONSE_CODE'] = "WS0051";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
		if($rowPassword['account_status'] == '-8'){
			$arrayResult['RESPONSE_CODE'] = "WS0048";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}else if($rowPassword['account_status'] == '-9'){
			if($rowPassword["temppass_is_md5"] == '1'){
				$valid_pass = password_verify(md5($dataComing["password"]), $rowPassword['temppass']);
			}else{
				$valid_pass = password_verify($dataComing["password"], $rowPassword['temppass']);
			}
		}else{
			$valid_pass = password_verify($dataComing["password"], $rowPassword['password']);
		}
		if ($valid_pass) {
			$refresh_token = $lib->generate_token();
			try{
				$conmysql->beginTransaction();
				$getMemberLogged = $conmysql->prepare("SELECT id_token FROM gcuserlogin WHERE member_no = :member_no and channel = :channel and is_login = '1'");
				$getMemberLogged->execute([
					':member_no' => $member_no,
					':channel' => $arrPayload["PAYLOAD"]["channel"]
				]);
				if($getMemberLogged->rowCount() > 0){
					$arrayIdToken = array();
					while($rowIdToken = $getMemberLogged->fetch(PDO::FETCH_ASSOC)){
						$arrayIdToken[] = $rowIdToken["id_token"];
					}
					$updateLoggedOneDevice = $conmysql->prepare("UPDATE gctoken gt,gcuserlogin gu SET gt.rt_is_revoke = '-6',
																gt.at_is_revoke = '-6',gt.rt_expire_date = NOW(),gt.at_expire_date = NOW(),
																gu.is_login = '-5',gu.logout_date = NOW()
																WHERE gt.id_token IN(".implode(',',$arrayIdToken).") and gu.id_token IN(".implode(',',$arrayIdToken).")");
					$updateLoggedOneDevice->execute();
				}
				$insertToken = $conmysql->prepare("INSERT INTO gctoken(refresh_token,unique_id,channel,device_name,ip_address) 
													VALUES(:refresh_token,:unique_id,:channel,:device_name,:ip_address)");
				if($insertToken->execute([
					':refresh_token' => $refresh_token,
					':unique_id' => $dataComing["unique_id"],
					':channel' => $arrPayload["PAYLOAD"]["channel"],
					':device_name' => $arrPayload["PAYLOAD"]["device_name"],
					':ip_address' => $arrPayload["PAYLOAD"]["ip_address"]
				])){
					$id_token = $conmysql->lastInsertId();
					if(isset($dataComing["firsttime"])){
						$firstapp = 0;
					}else{
						$firstapp = 1;
					}
					$insertLogin = $conmysql->prepare("INSERT INTO gcuserlogin(member_no,device_name,channel,unique_id,status_firstapp,id_token) 
												VALUES(:member_no,:device_name,:channel,:unique_id,:firstapp,:id_token)");
					if($insertLogin->execute([
						':member_no' => $member_no,
						':device_name' => $arrPayload["PAYLOAD"]["device_name"],
						':channel' => $arrPayload["PAYLOAD"]["channel"],
						':unique_id' => $dataComing["unique_id"],
						':firstapp' => $firstapp,
						':id_token' => $id_token
					])){
						$arrPayloadNew = array();
						$arrPayloadNew['id_userlogin'] = $conmysql->lastInsertId();
						$arrPayloadNew['user_type'] = $rowPassword['user_type'];
						$arrPayloadNew['id_token'] = $id_token;
						$arrPayloadNew['member_no'] = $member_no;
						$arrPayloadNew['exp'] = time() + intval($func->getConstant("limit_session_timeout"));
						$arrPayloadNew['refresh_amount'] = 0;
						$access_token = $jwt_token->customPayload($arrPayloadNew, $config["SECRET_KEY_JWT"]);
						if($arrPayload["PAYLOAD"]["channel"] == 'mobile_app'){
							if(isset($dataComing["fcm_token"]) && $dataComing["fcm_token"] != ""){
								$updateFCMToken = $conmysql->prepare("UPDATE gcmemberaccount SET fcm_token = :fcm_token  WHERE member_no = :member_no");
								$updateFCMToken->execute([
									':fcm_token' => $dataComing["fcm_token"] ?? null,
									':member_no' => $member_no
								]);
							}
							if(isset($dataComing["hms_token"]) && $dataComing["hms_token"] != ""){
								$updateFCMToken = $conmysql->prepare("UPDATE gcmemberaccount SET hms_token = :hms_token  WHERE member_no = :member_no");
								$updateFCMToken->execute([
									':hms_token' => $dataComing["hms_token"] ?? null,
									':member_no' => $member_no
								]);
							}
						}
						$updateAccessToken = $conmysql->prepare("UPDATE gctoken SET access_token = :access_token WHERE id_token = :id_token");
						if($updateAccessToken->execute([
							':access_token' => $access_token,
							':id_token' => $id_token
						])){
							$conmysql->commit();
							$arrayResult['REFRESH_TOKEN'] = $refresh_token;
							$arrayResult['ACCESS_TOKEN'] = $access_token;
							// Pin Status : 9 => DEV, 1 => TRUE, 0 => FALSE
							if($arrPayload["PAYLOAD"]["channel"] == 'mobile_app'){
								if($rowPassword['user_type'] == '9'){
									$arrayResult['PIN'] = (isset($rowPassword["pin"]) ? 9 : 0);
								}else{
									$arrayResult['PIN'] = (isset($rowPassword["pin"]) ? 1 : 0);
								}
							}else{
								if($rowPassword['account_status'] == '-9'){
									$arrayResult['TEMP_PASSWORD'] = TRUE;
								}else{
									$arrayResult['TEMP_PASSWORD'] = FALSE;
								}
							}
							$updateWrongPassCount = $conmysql->prepare("UPDATE gcmemberaccount SET counter_wrongpass = 0  WHERE member_no = :member_no");
							$updateWrongPassCount->execute([
								':member_no' => $member_no
							]);
							$arrayResult['RESULT'] = TRUE;							
							if ($forceNewSecurity == true) {
								$newArrayResult = array();
								$newArrayResult['ENC_TOKEN'] = $lib->generate_jwt_token($arrayResult, $jwt_token, $config["SECRET_KEY_JWT"]);
								$arrayResult = array();
								$arrayResult = $newArrayResult;
							}

							require_once('../../include/exit_footer.php');
						}else{
							$conmysql->rollback();
							$filename = basename(__FILE__, '.php');
							$logStruc = [
								":error_menu" => $filename,
								":error_code" => "WS1001",
								":error_desc" => "ไม่สามารถเข้าสู่ระบบได้ "."\n".json_encode($dataComing),
								":error_device" => $arrPayload["PAYLOAD"]["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
							];
							$log->writeLog('errorusage',$logStruc);
							$message_error = "ไม่สามารถเข้าสู่ระบบได้เพราะไม่สามารถ Update ลง gctoken"."\n"."Query => ".$updateAccessToken->queryString."\n"."Data => ".json_encode([
								':access_token' => $access_token,
								':id_token' => $id_token
							]);
							$lib->sendLineNotify($message_error);
							$arrayResult['RESPONSE_CODE'] = "WS1001";
							$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
							$arrayResult['RESULT'] = FALSE;
							require_once('../../include/exit_footer.php');
							
						}
					}else{
						$conmysql->rollback();
						$filename = basename(__FILE__, '.php');
						$logStruc = [
							":error_menu" => $filename,
							":error_code" => "WS1001",
							":error_desc" => "ไม่สามารถเข้าสู่ระบบได้ "."\n".json_encode($dataComing),
							":error_device" => $arrPayload["PAYLOAD"]["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
						];
						$log->writeLog('errorusage',$logStruc);
						$message_error = "ไม่สามารถเข้าสู่ระบบได้เพราะไม่สามารถ Insert ลง gcuserlogin"."\n"."Query => ".$insertLogin->queryString."\n"."Data => ".json_encode([
							':member_no' => $member_no,
							':device_name' => $arrPayload["PAYLOAD"]["device_name"],
							':channel' => $arrPayload["PAYLOAD"]["channel"],
							':unique_id' => $dataComing["unique_id"],
							':firstapp' => $firstapp,
							':id_token' => $id_token
						]);
						$lib->sendLineNotify($message_error);
						$arrayResult['RESPONSE_CODE'] = "WS1001";
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						$arrayResult['RESULT'] = FALSE;
						require_once('../../include/exit_footer.php');
						
					}
				}else{
					$conmysql->rollback();
					$filename = basename(__FILE__, '.php');
					$logStruc = [
						":error_menu" => $filename,
						":error_code" => "WS1001",
						":error_desc" => "ไม่สามารถเข้าสู่ระบบได้ "."\n".json_encode($dataComing),
						":error_device" => $arrPayload["PAYLOAD"]["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
					];
					$log->writeLog('errorusage',$logStruc);
					$message_error = "ไม่สามารถเข้าสู่ระบบได้เพราะไม่สามารถ Insert ลง gctoken"."\n"."Query => ".$insertToken->queryString."\n"."Data => ".json_encode([
						':refresh_token' => $refresh_token,
						':unique_id' => $dataComing["unique_id"],
						':channel' => $arrPayload["PAYLOAD"]["channel"],
						':device_name' => $arrPayload["PAYLOAD"]["device_name"],
						':ip_address' => $arrPayload["PAYLOAD"]["ip_address"]
					]);
					$lib->sendLineNotify($message_error);
					$arrayResult['RESPONSE_CODE'] = "WS1001";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
					
				}
			}catch (PDOExecption $e) {
				$conmysql->rollback();
				$filename = basename(__FILE__, '.php');
				$logStruc = [
					":error_menu" => $filename,
					":error_code" => "WS1001",
					":error_desc" => "ไม่สามารถเข้าสู่ระบบได้ "."\n".$e->getMessage(),
					":error_device" => $arrPayload["PAYLOAD"]["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
				];
				$log->writeLog('errorusage',$logStruc);
				$message_error = "ไม่สามารถเข้าสู่ระบบได้"."\n"."Error => ".$e->getMessage()."\n"."Data => ".json_encode($dataComing);
				$lib->sendLineNotify($message_error);
				$arrayResult['RESPONSE_CODE'] = "WS1001";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
		}else{
			$updateCounter = $conmysql->prepare("UPDATE gcmemberaccount SET counter_wrongpass = counter_wrongpass + 1 WHERE member_no = :member_no");
			$updateCounter->execute([':member_no' => $member_no]);
			$getCounter = $conmysql->prepare("SELECT counter_wrongpass FROM gcmemberaccount WHERE member_no = :member_no");
			$getCounter->execute([':member_no' => $member_no]);
			$rowCounter = $getCounter->fetch(PDO::FETCH_ASSOC);
			if($rowCounter["counter_wrongpass"] >= 5){
				$updateAccountStatus = $conmysql->prepare("UPDATE gcmemberaccount SET prev_acc_status = account_status,account_status = '-8',counter_wrongpass = 0 WHERE member_no = :member_no");
				$updateAccountStatus->execute([':member_no' => $member_no]);
				$struc = [
					':member_no' =>  $member_no,
					':device_name' =>  $arrPayload["PAYLOAD"]["device_name"],
					':unique_id' =>  $dataComing["unique_id"]
				];
				$log->writeLog("lockaccount",$struc);
				$arrayResult['RESPONSE_CODE'] = "WS0048";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
			$arrayResult['COUNTER_CAUTION'] = 5 - $rowCounter["counter_wrongpass"];
			$arrayResult['RESPONSE_CODE'] = "WS0002";
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