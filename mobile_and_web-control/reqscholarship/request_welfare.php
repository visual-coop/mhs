<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','childcard_id'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ScholarshipRequest')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$checkRightsReq = $conoracle->prepare("SELECT child_name as CHILD_NAME FROM ASNREQSCHOLARSHIP 
																WHERE CHILDCARD_ID = :child_id and SCHOLARSHIP_YEAR = (EXTRACT(year from sysdate) +543) and APPROVE_STATUS <> -9");
		$checkRightsReq->execute([':child_id' => $dataComing["childcard_id"]]);
		$rowRights = $checkRightsReq->fetch(PDO::FETCH_ASSOC);
		if(empty($rowRights["CHILD_NAME"])){
			$checkReqStatus = $conoracle->prepare("SELECT CHILDCARD_ID,REQUEST_STATUS, CANCEL_REMARK FROM asnreqschshiponline 
															WHERE SCHOLARSHIP_YEAR = (EXTRACT(year from sysdate) +543) and CHILDCARD_ID = :child_id and REQUEST_STATUS <> 8");
			$checkReqStatus->execute([':child_id' => $dataComing["childcard_id"]]);
			$rowReqStatus = $checkReqStatus->fetch(PDO::FETCH_ASSOC);
			if(isset($rowReqStatus["CHILDCARD_ID"])){
				if($rowReqStatus["REQUEST_STATUS"] == 1){
					$arrayResult['RESPONSE_CODE'] = "WS0077";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
					
				}else if($rowReqStatus["REQUEST_STATUS"] == 11){
					$arrayResult['CAN_CLEAR'] = TRUE;
					$arrayResult['RESPONSE_CODE'] = "WS0077";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
					
				}else if($rowReqStatus["REQUEST_STATUS"] == -1){
					$arrayResult['RESPONSE_CODE'] = "WS0078";
					$arrayResult['RESPONSE_MESSAGE'] = str_replace('${REMARK}',($rowReqStatus["CANCEL_REMARK"] ?? "เหตุผลบางประการ"),$configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale]);
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
					
				}else{
					$arrayResult['RESPONSE_CODE'] = "WS0079";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
					
				}
			}else{
				$delOldSchShip = $conoracle->prepare("DELETE FROM asnreqschshiponline WHERE scholarship_year = (EXTRACT(year from sysdate) +543) and member_no = :member_no and childcard_id = :child_id and request_status = 8");
				$delOldSchShip->execute([
					':member_no' => $member_no,
					':child_id' => $dataComing["childcard_id"]
				]);
				$insertSchShipOnline = $conoracle->prepare("INSERT INTO asnreqschshiponline(scholarship_year, member_no, childcard_id, request_status)
																				VALUES((EXTRACT(year from sysdate) +543),:member_no,:child_id,8)");
				if($insertSchShipOnline->execute([
					':member_no' => $member_no,
					':child_id' => $dataComing["childcard_id"]
				])){
					$conoracle->beginTransaction();
					foreach($dataComing["upload_list"] as $list){
						$subpath = $dataComing["childcard_id"].date('Ym');
						$destination = __DIR__.'/../../resource/reqwelfare/'.$subpath;
						$data_Img = explode(',',$list["upload_base64"]);
						$info_img = explode('/',$data_Img[0]);
						$ext_img = str_replace('base64','',$info_img[1]);
						$full_file_name = $list["upload_name"].$ext_img;
						if(!file_exists($destination)){
							mkdir($destination, 0777, true);
						}
						if($ext_img == 'png' || $ext_img == 'jpg' || $ext_img == 'jpeg'){
							$createImage = $lib->base64_to_img($list["upload_base64"],$list["upload_name"],$destination,null);
							if($createImage == 'oversize'){
							}else{
								if($createImage){
									$pathImgShowClient = $config["URL_SERVICE"]."resource/reqwelfare/".$subpath."/".$createImage["normal_path"];
									$deleteDocSch = $conoracle->prepare("DELETE FROM asnreqschshiponlinedet WHERE scholarship_year = (EXTRACT(year from sysdate) +543) and member_no = :member_no and childcard_id = :child_id and seq_no = :seq_no");
									$deleteDocSch->execute([
										':member_no' => $member_no,
										':child_id' => $dataComing["childcard_id"],
										':seq_no' => $list["upload_seq"]
									]);
									$insertSchShipOnlineDoc = $conoracle->prepare("INSERT INTO asnreqschshiponlinedet(scholarship_year, member_no, childcard_id, seq_no, document_desc, upload_status,filename)
																									VALUES((EXTRACT(year from sysdate) +543),:member_no,:child_id,:seq_no,:document_desc,1,:filename)");
									if($insertSchShipOnlineDoc->execute([
										':member_no' => $member_no,
										':child_id' => $dataComing["childcard_id"],
										':seq_no' => $list["upload_seq"],
										':document_desc' => $list["upload_label"],
										':filename' => $pathImgShowClient
									])){
										
									}else{
										$filename = basename(__FILE__, '.php');
										$logStruc = [
											":error_menu" => $filename,
											":error_code" => "WS1032",
											":error_desc" => "ไม่สามารถ Insert ลง insertSchShipOnlineDoc ได้ "."\n".$insertSchShipOnlineDoc->queryString."\n".json_encode([
												':member_no' => $member_no,
												':child_id' => $dataComing["childcard_id"],
												':seq_no' => $list["upload_seq"],
												':document_desc' => $list["upload_label"],
												':filename' => $pathImgShowClient
											]),
											":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
										];
										$log->writeLog('errorusage',$logStruc);
										$message_error = "ไม่สามารถ Insert ลง insertSchShipOnlineDoc ได้ "."\n".$insertSchShipOnlineDoc->queryString."\n".json_encode([
											':member_no' => $member_no,
											':child_id' => $dataComing["childcard_id"],
											':seq_no' => $list["upload_seq"],
											':document_desc' => $list["upload_label"],
											':filename' => $pathImgShowClient
										]);
										$lib->sendLineNotify($message_error);
										$arrayResult['RESPONSE_CODE'] = "WS1032";
										$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
										$arrayResult['RESULT'] = FALSE;
										require_once('../../include/exit_footer.php');
										
									}
								}
							}
						}else if($ext_img == 'pdf'){
							$createImage = $lib->base64_to_pdf($list["upload_base64"],$list["upload_name"],$destination);
							if($createImage){
								$pathImgShowClient = $config["URL_SERVICE"]."resource/reqwelfare/".$subpath."/".$createImage["normal_path"];
								$deleteDocSch = $conoracle->prepare("DELETE FROM asnreqschshiponlinedet WHERE scholarship_year = (EXTRACT(year from sysdate) +543) and member_no = :member_no and childcard_id = :child_id and seq_no = :seq_no");
								$deleteDocSch->execute([
									':member_no' => $member_no,
									':child_id' => $dataComing["childcard_id"],
									':seq_no' => $list["upload_seq"]
								]);
								$insertSchShipOnlineDoc = $conoracle->prepare("INSERT INTO asnreqschshiponlinedet(scholarship_year, member_no, childcard_id, seq_no, document_desc, upload_status,filename)
																								VALUES((EXTRACT(year from sysdate) +543),:member_no,:child_id,:seq_no,:document_desc,1,:filename)");
								if($insertSchShipOnlineDoc->execute([
									':member_no' => $member_no,
									':child_id' => $dataComing["childcard_id"],
									':seq_no' => $list["upload_seq"],
									':document_desc' => $list["upload_label"],
									':filename' => $pathImgShowClient
								])){
								}else{
									$filename = basename(__FILE__, '.php');
									$logStruc = [
										":error_menu" => $filename,
										":error_code" => "WS1032",
										":error_desc" => "ไม่สามารถ Insert ลง insertSchShipOnlineDoc ได้ "."\n".$insertSchShipOnlineDoc->queryString."\n".json_encode([
											':member_no' => $member_no,
											':child_id' => $dataComing["childcard_id"],
											':seq_no' => $list["upload_seq"],
											':document_desc' => $list["upload_label"],
											':filename' => $pathImgShowClient
										]),
										":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
									];
									$log->writeLog('errorusage',$logStruc);
									$message_error = "ไม่สามารถ Insert ลง insertSchShipOnlineDoc ได้ "."\n".$insertSchShipOnlineDoc->queryString."\n".json_encode([
										':member_no' => $member_no,
										':child_id' => $dataComing["childcard_id"],
										':seq_no' => $list["upload_seq"],
										':document_desc' => $list["upload_label"],
										':filename' => $pathImgShowClient
									]);
									$lib->sendLineNotify($message_error);
									$arrayResult['RESPONSE_CODE'] = "WS1032";
									$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
									$arrayResult['RESULT'] = FALSE;
									require_once('../../include/exit_footer.php');
									
								}
							}
						}
					}
					$conoracle->commit();
					$updateFlagUpload = $conoracle->prepare("UPDATE asnreqschshiponline SET request_status = 1, lastupload_date = sysdate 
																			WHERE scholarship_year = (EXTRACT(year from sysdate) +543) and member_no = :member_no and childcard_id = :child_id");
					$updateFlagUpload->execute([
						':member_no' => $member_no,
						':child_id' => $dataComing["childcard_id"]
					]);
					$arrayResult['RESULT'] = TRUE;
					require_once('../../include/exit_footer.php');
				}else{
					$filename = basename(__FILE__, '.php');
					$logStruc = [
						":error_menu" => $filename,
						":error_code" => "WS1032",
						":error_desc" => "ไม่สามารถ Insert ลง asnreqschshiponline ได้ "."\n".$insertSchShipOnline->queryString."\n".json_encode([
							':member_no' => $member_no,
							':child_id' => $dataComing["childcard_id"]
						]),
						":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
					];
					$log->writeLog('errorusage',$logStruc);
					$message_error = "ไม่สามารถ Insert ลง asnreqschshiponline ได้ "."\n".$insertSchShipOnline->queryString."\n".json_encode([
						':member_no' => $member_no,
						':child_id' => $dataComing["childcard_id"]
					]);
					$lib->sendLineNotify($message_error);
					$arrayResult['RESPONSE_CODE'] = "WS1032";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
					
				}
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0076";
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