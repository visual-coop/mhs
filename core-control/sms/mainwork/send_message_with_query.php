<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','message_emoji_','type_send','channel_send','id_query'],$dataComing)){
	if($func->check_permission_core($payload,'sms','sendmessageall') || $func->check_permission_core($payload,'sms','sendmessageperson')){
		$id_template = isset($dataComing["id_smstemplate"]) && $dataComing["id_smstemplate"] != "" ? $dataComing["id_smstemplate"] : null;
		if($dataComing["channel_send"] == "mobile_app"){
			$getQuery = $conmysql->prepare("SELECT sms_query,column_selected,is_bind_param,is_stampflag,stamp_table,where_stamp,target_field,condition_target,set_column
											FROM smsquery WHERE id_smsquery = :id_query");
			$getQuery->execute([':id_query' => $dataComing["id_query"]]);
			if($getQuery->rowCount() > 0){
				if(isset($dataComing["send_image"]) && $dataComing["send_image"] != null){
					$destination = __DIR__.'/../../../resource/image_wait_to_be_sent';
					$file_name = $lib->randomText('all',6);
					if(!file_exists($destination)){
						mkdir($destination, 0777, true);
					}
					$createImage = $lib->base64_to_img($dataComing["send_image"],$file_name,$destination,null);
					if($createImage == 'oversize'){
						$arrayResult['RESPONSE_MESSAGE'] = "รูปภาพที่ต้องการส่งมีขนาดใหญ่เกินไป";
						$arrayResult['RESULT'] = FALSE;
						require_once('../../../include/exit_footer.php');
					}else{
						if($createImage){
							$pathImg = $config["URL_SERVICE"]."resource/image_wait_to_be_sent/".$createImage["normal_path"];
						}else{
							$arrayResult['RESPONSE_MESSAGE'] = "นามสกุลไฟล์ไม่ถูกต้อง";
							$arrayResult['RESULT'] = FALSE;
							require_once('../../../include/exit_footer.php');
						}
					}
				}
				$blukInsert = array();
				$blukInsertNot = array();
				$rowQuery = $getQuery->fetch(PDO::FETCH_ASSOC);
				$arrColumn = explode(',',$rowQuery["column_selected"]);
				if($rowQuery["is_bind_param"] == '0'){
					$queryTarget = $conoracle->prepare($rowQuery['sms_query']);
					$queryTarget->execute();
					while($rowTarget = $queryTarget->fetch(PDO::FETCH_ASSOC)){
						$arrGroupMessage = array();
						$arrDestination = array();
						$arrMemberNoDestination = array();
						$arrTarget = array();
						foreach($arrColumn as $column){
							$arrTarget[$column] = $rowTarget[strtoupper($column)] ?? null;
						}
						$arrMessageMerge = $lib->mergeTemplate($dataComing["topic_emoji_"],$dataComing["message_emoji_"],$arrTarget);
						if(!in_array($rowTarget[$rowQuery["target_field"]]."_".$arrMessageMerge["BODY"],$dataComing["destination_revoke"])){
							$arrToken = $func->getFCMToken('person',$rowTarget[$rowQuery["target_field"]]);
							if(isset($arrToken["LIST_SEND"][0]["TOKEN"]) && $arrToken["LIST_SEND"][0]["TOKEN"] != ""){
								if($arrToken["LIST_SEND"][0]["RECEIVE_NOTIFY_TRANSACTION"] == "1"){
									$arrPayloadNotify["TO"] = array($arrToken["LIST_SEND"][0]["TOKEN"]);
									$arrPayloadNotify["MEMBER_NO"] = $arrToken["LIST_SEND"][0]["MEMBER_NO"];
									$arrMessage["SUBJECT"] = $arrMessageMerge["SUBJECT"];
									$arrMessage["BODY"] = $arrMessageMerge["BODY"];
									$arrMessage["PATH_IMAGE"] = $pathImg ?? null;
									$arrPayloadNotify["PAYLOAD"] = $arrMessage;
									$arrPayloadNotify["SEND_BY"] = $payload["username"];
									$arrPayloadNotify["ID_TEMPLATE"] = $id_template;
									$arrPayloadNotify["TYPE_NOTIFY"] = "2";
									if($lib->sendNotify($arrPayloadNotify,$dataComing["type_send"])){
										if($rowQuery["is_stampflag"] == '1'){
											$arrayExecute = array();
											preg_match_all('/\\:(.*?)\\s/',$rowQuery["where_stamp"],$arrayRawExecute);
											foreach($arrayRawExecute[1] as $execute){
												$arrayExecute[$execute] = $rowTarget[$execute];
											}
											$updateFlagStamp = $conoracle->prepare("UPDATE ".$rowQuery["stamp_table"]." SET ".$rowQuery["set_column"]." WHERE ".$rowQuery["where_stamp"]);
											$updateFlagStamp->execute($arrayExecute);
										}
										$blukInsert[] = "('1','".$arrMessageMerge["SUBJECT"]."','".$arrMessageMerge["BODY"]."','".($pathImg ?? null)."','".$arrToken["LIST_SEND"][0]["MEMBER_NO"]."','".$payload["username"]."'".(isset($id_template) ? ",".$id_template : ",null").")";
										if(sizeof($blukInsert) == 1000){
											$arrPayloadHistory["TYPE_SEND_HISTORY"] = "manymessage";
											$arrPayloadHistory["bulkInsert"] = $blukInsert;
											$func->insertHistory($arrPayloadHistory,'2');
											unset($blukInsert);
											$blukInsert = array();
										}
									}else{
										$blukInsertNot[] = "('".$arrMessageMerge["BODY"]."','".$arrToken["LIST_SEND"][0]["MEMBER_NO"]."','".$dataComing["channel_send"]."',null,'".$arrToken["LIST_SEND"][0]["TOKEN"]."','ไม่สามารถส่งได้ให้ดู LOG','".$payload["username"]."'".(isset($id_template) ? ",".$id_template : ",null").")";
										if(sizeof($blukInsertNot) == 1000){
											$func->logSMSWasNotSent($blukInsertNot);
											unset($blukInsertNot);
											$blukInsertNot = array();
										}
									}
								}else{
									$blukInsertNot[] = "('".$arrMessageMerge["BODY"]."','".$arrToken["LIST_SEND"][0]["MEMBER_NO"]."','".$dataComing["channel_send"]."',null,'".$arrToken["LIST_SEND"][0]["TOKEN"]."','บัญชีปลายทางไม่ประสงค์เปิดรับการแจ้งเตือน','".$payload["username"]."'".(isset($id_template) ? ",".$id_template : ",null").")";
									if(sizeof($blukInsertNot) == 1000){
										$func->logSMSWasNotSent($blukInsertNot);
										unset($blukInsertNot);
										$blukInsertNot = array();
									}
								}
							}else{
								if(isset($arrToken["LIST_SEND_HW"][0]["TOKEN"]) && $arrToken["LIST_SEND_HW"][0]["TOKEN"] != ""){
									if($arrToken["LIST_SEND_HW"][0]["RECEIVE_NOTIFY_TRANSACTION"] == "1"){
										$arrPayloadNotify["TO"] = array($arrToken["LIST_SEND_HW"][0]["TOKEN"]);
										$arrPayloadNotify["MEMBER_NO"] = $arrToken["LIST_SEND_HW"][0]["MEMBER_NO"];
										$arrMessage["SUBJECT"] = $arrMessageMerge["SUBJECT"];
										$arrMessage["BODY"] = $arrMessageMerge["BODY"];
										$arrMessage["PATH_IMAGE"] = $pathImg ?? null;
										$arrPayloadNotify["PAYLOAD"] = $arrMessage;
										$arrPayloadNotify["SEND_BY"] = $payload["username"];
										$arrPayloadNotify["ID_TEMPLATE"] = $id_template;
										$arrPayloadNotify["TYPE_NOTIFY"] = "2";
										if($lib->sendNotifyHW($arrPayloadNotify,$dataComing["type_send"])){
											if($rowQuery["is_stampflag"] == '1'){
												$arrayExecute = array();
												preg_match_all('/\\:(.*?)\\s/',$rowQuery["where_stamp"],$arrayRawExecute);
												foreach($arrayRawExecute[1] as $execute){
													$arrayExecute[$execute] = $rowTarget[$execute];
												}
												$updateFlagStamp = $conoracle->prepare("UPDATE ".$rowQuery["stamp_table"]." SET ".$rowQuery["set_column"]." WHERE ".$rowQuery["where_stamp"]);
												$updateFlagStamp->execute($arrayExecute);
											}
											$blukInsert[] = "('1','".$arrMessageMerge["SUBJECT"]."','".$arrMessageMerge["BODY"]."','".($pathImg ?? null)."','".$arrToken["LIST_SEND_HW"][0]["MEMBER_NO"]."','".$payload["username"]."'".(isset($id_template) ? ",".$id_template : ",null").")";
											if(sizeof($blukInsert) == 1000){
												$arrPayloadHistory["TYPE_SEND_HISTORY"] = "manymessage";
												$arrPayloadHistory["bulkInsert"] = $blukInsert;
												$func->insertHistory($arrPayloadHistory,'2');
												unset($blukInsert);
												$blukInsert = array();
											}
										}else{
											$blukInsertNot[] = "('".$arrMessageMerge["BODY"]."','".$arrToken["LIST_SEND_HW"][0]["MEMBER_NO"]."','".$dataComing["channel_send"]."',null,'".$arrToken["LIST_SEND_HW"][0]["TOKEN"]."','ไม่สามารถส่งได้ให้ดู LOG','".$payload["username"]."'".(isset($id_template) ? ",".$id_template : ",null").")";
											if(sizeof($blukInsertNot) == 1000){
												$func->logSMSWasNotSent($blukInsertNot);
												unset($blukInsertNot);
												$blukInsertNot = array();
											}
										}
									}else{
										$blukInsertNot[] = "('".$arrMessageMerge["BODY"]."','".$arrToken["LIST_SEND_HW"][0]["MEMBER_NO"]."','".$dataComing["channel_send"]."',null,'".$arrToken["LIST_SEND_HW"][0]["TOKEN"]."','บัญชีปลายทางไม่ประสงค์เปิดรับการแจ้งเตือน','".$payload["username"]."'".(isset($id_template) ? ",".$id_template : ",null").")";
										if(sizeof($blukInsertNot) == 1000){
											$func->logSMSWasNotSent($blukInsertNot);
											unset($blukInsertNot);
											$blukInsertNot = array();
										}
									}
								}else{
									$blukInsertNot[] = "('".$arrMessageMerge["BODY"]."','".$rowTarget[$rowQuery["target_field"]]."','".$dataComing["channel_send"]."',null,null,'หา Token ในการส่งไม่เจออาจจะเพราะไม่อนุญาตให้ส่งแจ้งเตือนเข้าเครื่อง','".$payload["username"]."'".(isset($id_template) ? ",".$id_template : ",null").")";
									if(sizeof($blukInsertNot) == 1000){
										$func->logSMSWasNotSent($blukInsertNot);
										unset($blukInsertNot);
										$blukInsertNot = array();
									}
								}
							}
						}
					}
					if(sizeof($blukInsertNot) > 0){
						$func->logSMSWasNotSent($blukInsertNot);
						unset($blukInsertNot);
						$blukInsertNot = array();
					}
					if(sizeof($blukInsert) > 0){
						$arrPayloadHistory["TYPE_SEND_HISTORY"] = "manymessage";
						$arrPayloadHistory["bulkInsert"] = $blukInsert;
						$func->insertHistory($arrPayloadHistory,'2');
						unset($blukInsert);
						$blukInsert = array();
					}
					$arrayResult["RESULT"] = TRUE;
					require_once('../../../include/exit_footer.php');
				}else{
					$query = $rowQuery['sms_query'];
					if(stripos($query,'WHERE') === FALSE){
						if(stripos($query,'GROUP BY') !== FALSE){
							$arrQuery = explode('GROUP BY',$query);
							$query = $arrQuery[0]." WHERE ".$rowQuery["condition_target"]." GROUP BY ".$arrQuery[1];
						}else{
							$query .= " WHERE ".$rowQuery["condition_target"];
						}
					}else{
						if(stripos($query,'GROUP BY') !== FALSE){
							$arrQuery = explode('GROUP BY',$query);
							$query = $arrQuery[0]." and ".$rowQuery["condition_target"]." GROUP BY ".$arrQuery[1];
						}else{
							$query .= " and ".$rowQuery["condition_target"];
						}
					}
					$condition = explode(':',$rowQuery["condition_target"]);
					foreach($dataComing["destination"] as $target){
						if($condition[1] == $rowQuery["target_field"]){
							if(strlen($target) <= 8){
								$target = strtolower($lib->mb_str_pad($target));
							}else{
								$target = $target;
							}
						}else{
							$target = $target;
						}
						
						$queryTarget = $conoracle->prepare($query);
						$queryTarget->execute([':'.$condition[1] => $target]);
						$rowTarget = $queryTarget->fetch(PDO::FETCH_ASSOC);
						if(isset($rowTarget[$rowQuery["target_field"]])){
							$arrGroupMessage = array();
							$arrDestination = array();
							$arrMemberNoDestination = array();
							$arrTarget = array();
							foreach($arrColumn as $column){
								$arrTarget[$column] = $rowTarget[strtoupper($column)] ?? null;
							}
							$arrMessageMerge = $lib->mergeTemplate($dataComing["topic_emoji_"],$dataComing["message_emoji_"],$arrTarget);
							if(!in_array($target.'_'.$arrMessageMerge["BODY"],$dataComing["destination_revoke"])){
								if($condition[1] == $rowQuery["target_field"]){
									$arrToken = $func->getFCMToken('person',$target);
								}else{
									$arrToken = $func->getFCMToken('person',$rowTarget[$rowQuery["target_field"]]);
								}
								if(sizeof($arrToken["MEMBER_NO"]) > 0){
									if(isset($arrToken["LIST_SEND"][0]["TOKEN"]) && $arrToken["LIST_SEND"][0]["TOKEN"] != ""){
										if($arrToken["LIST_SEND"][0]["RECEIVE_NOTIFY_TRANSACTION"] == "1"){
											$arrPayloadNotify["TO"] = array($arrToken["LIST_SEND"][0]["TOKEN"]);
											$arrPayloadNotify["MEMBER_NO"] = $arrToken["LIST_SEND"][0]["MEMBER_NO"];
											$arrMessage["SUBJECT"] = $arrMessageMerge["SUBJECT"];
											$arrMessage["BODY"] = $arrMessageMerge["BODY"];
											$arrMessage["PATH_IMAGE"] = $pathImg ?? null;
											$arrPayloadNotify["PAYLOAD"] = $arrMessage;
											$arrPayloadNotify["SEND_BY"] = $payload["username"];
											$arrPayloadNotify["ID_TEMPLATE"] = $id_template;
											$arrPayloadNotify["TYPE_NOTIFY"] = "2";
											if($lib->sendNotify($arrPayloadNotify,$dataComing["type_send"])){
												if($rowQuery["is_stampflag"] == '1'){
													$arrayExecute = array();
													preg_match_all('/\\:(.*?)\\s/',$rowQuery["where_stamp"],$arrayRawExecute);
													foreach($arrayRawExecute[1] as $execute){
														$arrayExecute[$execute] = $rowTarget[$execute];
													}
													$updateFlagStamp = $conoracle->prepare("UPDATE ".$rowQuery["stamp_table"]." SET ".$rowQuery["set_column"]." WHERE ".$rowQuery["where_stamp"]);
													$updateFlagStamp->execute($arrayExecute);
												}
												$blukInsert[] = "('1','".$arrMessageMerge["SUBJECT"]."','".$arrMessageMerge["BODY"]."','".($pathImg ?? null)."','".$arrToken["LIST_SEND"][0]["MEMBER_NO"]."','".$payload["username"]."'".(isset($id_template) ? ",".$id_template : ",null").")";
												if(sizeof($blukInsert) == 1000){
													$arrPayloadHistory["TYPE_SEND_HISTORY"] = "manymessage";
													$arrPayloadHistory["bulkInsert"] = $blukInsert;
													$func->insertHistory($arrPayloadHistory,'2');
													unset($blukInsert);
													$blukInsert = array();
												}
											}else{
												$blukInsertNot[] = "('".$arrMessageMerge["BODY"]."','".$arrToken["LIST_SEND"][0]["MEMBER_NO"]."','".$dataComing["channel_send"]."',null,'".$arrToken["LIST_SEND"][0]["TOKEN"]."','ไม่สามารถส่งได้ให้ดู LOG','".$payload["username"]."'".(isset($id_template) ? ",".$id_template : ",null").")";
												if(sizeof($blukInsertNot) == 1000){
													$func->logSMSWasNotSent($blukInsertNot);
													unset($blukInsertNot);
													$blukInsertNot = array();
												}
											}
										}else{
											$blukInsertNot[] = "('".$arrMessageMerge["BODY"]."','".$arrToken["LIST_SEND"][0]["MEMBER_NO"]."','".$dataComing["channel_send"]."',null,'".$arrToken["LIST_SEND"][0]["TOKEN"]."','บัญชีปลายทางไม่ประสงค์เปิดรับการแจ้งเตือน','".$payload["username"]."'".(isset($id_template) ? ",".$id_template : ",null").")";
											if(sizeof($blukInsertNot) == 1000){
												$func->logSMSWasNotSent($blukInsertNot);
												unset($blukInsertNot);
												$blukInsertNot = array();
											}
										}
									}else{
										if(isset($arrToken["LIST_SEND_HW"][0]["TOKEN"]) && $arrToken["LIST_SEND_HW"][0]["TOKEN"] != ""){
											if($arrToken["LIST_SEND_HW"][0]["RECEIVE_NOTIFY_TRANSACTION"] == "1"){
												$arrPayloadNotify["TO"] = array($arrToken["LIST_SEND_HW"][0]["TOKEN"]);
												$arrPayloadNotify["MEMBER_NO"] = $arrToken["LIST_SEND_HW"][0]["MEMBER_NO"];
												$arrMessage["SUBJECT"] = $arrMessageMerge["SUBJECT"];
												$arrMessage["BODY"] = $arrMessageMerge["BODY"];
												$arrMessage["PATH_IMAGE"] = $pathImg ?? null;
												$arrPayloadNotify["PAYLOAD"] = $arrMessage;
												$arrPayloadNotify["SEND_BY"] = $payload["username"];
												$arrPayloadNotify["ID_TEMPLATE"] = $id_template;
												$arrPayloadNotify["TYPE_NOTIFY"] = "2";
												if($lib->sendNotifyHW($arrPayloadNotify,$dataComing["type_send"])){
													if($rowQuery["is_stampflag"] == '1'){
														$arrayExecute = array();
														preg_match_all('/\\:(.*?)\\s/',$rowQuery["where_stamp"],$arrayRawExecute);
														foreach($arrayRawExecute[1] as $execute){
															$arrayExecute[$execute] = $rowTarget[$execute];
														}
														$updateFlagStamp = $conoracle->prepare("UPDATE ".$rowQuery["stamp_table"]." SET ".$rowQuery["set_column"]." WHERE ".$rowQuery["where_stamp"]);
														$updateFlagStamp->execute($arrayExecute);
													}
													$blukInsert[] = "('1','".$arrMessageMerge["SUBJECT"]."','".$arrMessageMerge["BODY"]."','".($pathImg ?? null)."','".$arrToken["LIST_SEND_HW"][0]["MEMBER_NO"]."','".$payload["username"]."'".(isset($id_template) ? ",".$id_template : ",null").")";
													if(sizeof($blukInsert) == 1000){
														$arrPayloadHistory["TYPE_SEND_HISTORY"] = "manymessage";
														$arrPayloadHistory["bulkInsert"] = $blukInsert;
														$func->insertHistory($arrPayloadHistory,'2');
														unset($blukInsert);
														$blukInsert = array();
													}
												}else{
													$blukInsertNot[] = "('".$arrMessageMerge["BODY"]."','".$arrToken["LIST_SEND_HW"][0]["MEMBER_NO"]."','".$dataComing["channel_send"]."',null,'".$arrToken["LIST_SEND_HW"][0]["TOKEN"]."','ไม่สามารถส่งได้ให้ดู LOG','".$payload["username"]."'".(isset($id_template) ? ",".$id_template : ",null").")";
													if(sizeof($blukInsertNot) == 1000){
														$func->logSMSWasNotSent($blukInsertNot);
														unset($blukInsertNot);
														$blukInsertNot = array();
													}
												}
											}else{
												$blukInsertNot[] = "('".$arrMessageMerge["BODY"]."','".$arrToken["LIST_SEND_HW"][0]["MEMBER_NO"]."','".$dataComing["channel_send"]."',null,'".$arrToken["LIST_SEND_HW"][0]["TOKEN"]."','บัญชีปลายทางไม่ประสงค์เปิดรับการแจ้งเตือน','".$payload["username"]."'".(isset($id_template) ? ",".$id_template : ",null").")";
												if(sizeof($blukInsertNot) == 1000){
													$func->logSMSWasNotSent($blukInsertNot);
													unset($blukInsertNot);
													$blukInsertNot = array();
												}
											}
										}else{
											$blukInsertNot[] = "('".$arrMessageMerge["BODY"]."','".$target."','".$dataComing["channel_send"]."',null,null,'หา Token ในการส่งไม่เจออาจจะเพราะไม่อนุญาตให้ส่งแจ้งเตือนเข้าเครื่อง','".$payload["username"]."'".(isset($id_template) ? ",".$id_template : ",null").")";
											if(sizeof($blukInsertNot) == 1000){
												$func->logSMSWasNotSent($blukInsertNot);
												unset($blukInsertNot);
												$blukInsertNot = array();
											}
										}
									}
								}else{
									$blukInsertNot[] = "('".$arrMessageMerge["BODY"]."','".$target."','".$dataComing["channel_send"]."',null,null,'สมาชิกยังไม่ได้ใช้งานแอปพลิเคชั่น','".$payload["username"]."'".(isset($id_template) ? ",".$id_template : ",null").")";
									if(sizeof($blukInsertNot) == 1000){
										$func->logSMSWasNotSent($blukInsertNot);
										unset($blukInsertNot);
										$blukInsertNot = array();
									}
								}
							}
						}
					}
					if(sizeof($blukInsertNot) > 0){
						$func->logSMSWasNotSent($blukInsertNot);
						unset($blukInsertNot);
						$blukInsertNot = array();
					}
					if(sizeof($blukInsert) > 0){
						$arrPayloadHistory["TYPE_SEND_HISTORY"] = "manymessage";
						$arrPayloadHistory["bulkInsert"] = $blukInsert;
						$func->insertHistory($arrPayloadHistory,'2');
						unset($blukInsert);
						$blukInsert = array();
					}
					$arrayResult["RESULT"] = TRUE;
					require_once('../../../include/exit_footer.php');
				}
			}else{
				$arrayResult['RESPONSE'] = "ไม่พบชุดคิวรี่ข้อมูล กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../include/exit_footer.php');
			}
		}else{
			$getQuery = $conmysql->prepare("SELECT sms_query,column_selected,is_bind_param,target_field,is_stampflag,stamp_table,where_stamp,set_column,condition_target 
											FROM smsquery WHERE id_smsquery = :id_query");
			$getQuery->execute([':id_query' => $dataComing["id_query"]]);
			if($getQuery->rowCount() > 0){
				$arrGRPAll = array();
				$arrayMerge = array();
				$bulkInsert = array();
				$rowQuery = $getQuery->fetch(PDO::FETCH_ASSOC);
				$arrColumn = explode(',',$rowQuery["column_selected"]);
				if($rowQuery["is_bind_param"] == '0'){
					$queryTarget = $conoracle->prepare($rowQuery['sms_query']);
					$queryTarget->execute();
					while($rowTarget = $queryTarget->fetch(PDO::FETCH_ASSOC)){
						$arrTarget = array();
						foreach($arrColumn as $column){
							$arrTarget[$column] = $rowTarget[strtoupper($column)] ?? null;
						}
						$arrMessage = $lib->mergeTemplate(null,$dataComing["message_emoji_"],$arrTarget);
						if(!in_array($rowTarget[$rowQuery["target_field"]]."_".$arrMessage["BODY"],$dataComing["destination_revoke"])){
							$arrayTel = $func->getSMSPerson('person',$rowTarget[$rowQuery["target_field"]]);
							if(isset($arrayTel[0]["TEL"]) && $arrayTel[0]["TEL"] != ""){
								$arrayDest["cmd_sms"] = "CMD=".$config["CMD_SMS"]."&FROM=".$config["FROM_SERVICES_SMS"]."&TO=66".(substr($arrayTel[0]["TEL"],1,9))."&REPORT=Y&CHARGE=".$config["CHARGE_SMS"]."&CODE=".$config["CODE_SMS"]."&CTYPE=UNICODE&CONTENT=".$lib->unicodeMessageEncode($arrMessage["BODY"]);
								$arraySendSMS = $lib->sendSMS($arrayDest);
								if($arraySendSMS["RESULT"]){
									if($rowQuery["is_stampflag"] == '1'){
										$arrayExecute = array();
										preg_match_all('/\\:(.*?)\\s/',$rowQuery["where_stamp"],$arrayRawExecute);
										foreach($arrayRawExecute[1] as $execute){
											$arrayExecute[$execute] = $rowTarget[$execute];
										}
										$updateFlagStamp = $conoracle->prepare("UPDATE ".$rowQuery["stamp_table"]." SET ".$rowQuery["set_column"]." WHERE ".$rowQuery["where_stamp"]);
										$updateFlagStamp->execute($arrayExecute);
									}
									$arrayMerge[] = $arrayTel[0];
									$arrGRPAll[$arrayTel[0]["MEMBER_NO"]] = $arrMessage["BODY"];
								}else{
									$bulkInsert[] = "('".$arrMessage["BODY"]."','".$arrayTel[0]["MEMBER_NO"]."',
											'sms','".$arrayTel[0]["TEL"]."',null,'".$arraySendSMS["MESSAGE"]."','".$payload["username"]."'".(isset($id_template) ? ",".$id_template : ",null").")";
									if(sizeof($bulkInsert) == 1000){
										$func->logSMSWasNotSent($bulkInsert);
										unset($bulkInsert);
										$bulkInsert = array();
									}
								}
							}else{
								$bulkInsert[] = "('".$arrMessage["BODY"]."','".$arrayTel[0]["MEMBER_NO"]."',
								'sms',null,null,'ไม่พบเบอร์โทรศัพท์','".$payload["username"]."'".(isset($id_template) ? ",".$id_template : ",null").")";
								if(sizeof($bulkInsert) == 1000){
									$func->logSMSWasNotSent($bulkInsert);
									unset($bulkInsert);
									$bulkInsert = array();
								}
							}
						}
					}
					if(sizeof($bulkInsert) > 0){
						$func->logSMSWasNotSent($bulkInsert);
						unset($bulkInsert);
						$bulkInsert = array();
					}
					if(sizeof($arrGRPAll) > 0){
						$arrayLogSMS = $func->logSMSWasSent($id_template,$arrGRPAll,$arrayMerge,$payload["username"],true);
						$arrayResult['RESULT'] = $arrayLogSMS;
					}else{
						$arrayResult['RESULT'] = TRUE;
					}
					require_once('../../../include/exit_footer.php');
				}else{
					$query = $rowQuery['sms_query'];
					if(stripos($query,'WHERE') === FALSE){
						if(stripos($query,'GROUP BY') !== FALSE){
							$arrQuery = explode('GROUP BY',$query);
							$query = $arrQuery[0]." WHERE ".$rowQuery["condition_target"]." GROUP BY ".$arrQuery[1];
						}else{
							$query .= " WHERE ".$rowQuery["condition_target"];
						}
					}else{
						if(stripos($query,'GROUP BY') !== FALSE){
							$arrQuery = explode('GROUP BY',$query);
							$query = $arrQuery[0]." and ".$rowQuery["condition_target"]." GROUP BY ".$arrQuery[1];
						}else{
							$query .= " and ".$rowQuery["condition_target"];
						}
					}
					$condition = explode(':',$rowQuery["condition_target"]);
					foreach($dataComing["destination"] as $target){
						if($condition[1] == $rowQuery["target_field"]){
							if(strlen($target) <= 8){
								$destination = strtolower($lib->mb_str_pad($target));
							}else{
								$destination = $target;
							}
						}else{
							$destination = $target;
						}
						
						$queryTarget = $conoracle->prepare($query);
						$queryTarget->execute([':'.$condition[1] => $destination]);
						$rowTarget = $queryTarget->fetch(PDO::FETCH_ASSOC);
						if(isset($rowTarget[$rowQuery["target_field"]])){
							$arrGroupCheckSend = array();
							$arrGroupMessage = array();
							$arrTarget = array();
							foreach($arrColumn as $column){
								$arrTarget[$column] = $rowTarget[strtoupper($column)] ?? null;
							}
							$arrMessage = $lib->mergeTemplate(null,$dataComing["message_emoji_"],$arrTarget);
							if(!in_array($destination.'_'.$arrMessage["BODY"],$dataComing["destination_revoke"])){
								if($condition[1] == $rowQuery["target_field"]){
									$arrayTel = $func->getSMSPerson('person',$destination);
								}else{
									$arrayTel = $func->getSMSPerson('person',$rowTarget[$rowQuery["target_field"]]);
								}
								if(isset($arrayTel[0]["TEL"]) && $arrayTel[0]["TEL"] != ""){
									$arrayDest["cmd_sms"] = "CMD=".$config["CMD_SMS"]."&FROM=".$config["FROM_SERVICES_SMS"]."&TO=66".(substr($arrayTel[0]["TEL"],1,9))."&REPORT=Y&CHARGE=".$config["CHARGE_SMS"]."&CODE=".$config["CODE_SMS"]."&CTYPE=UNICODE&CONTENT=".$lib->unicodeMessageEncode($arrMessage["BODY"]);
									$arraySendSMS = $lib->sendSMS($arrayDest);
									if($arraySendSMS["RESULT"]){
										if($rowQuery["is_stampflag"] == '1'){
											$arrayExecute = array();
											preg_match_all('/\\:(.*?)\\s/',$rowQuery["where_stamp"],$arrayRawExecute);
											foreach($arrayRawExecute[1] as $execute){
												$arrayExecute[$execute] = $rowTarget[$execute];
											}
											$updateFlagStamp = $conoracle->prepare("UPDATE ".$rowQuery["stamp_table"]." SET ".$rowQuery["set_column"]." WHERE ".$rowQuery["where_stamp"]);
											$updateFlagStamp->execute($arrayExecute);
										}
										$arrayMerge[] = $arrayTel[0];
										$arrGRPAll[$arrayTel[0]["MEMBER_NO"]] = $arrMessage["BODY"];
									}else{
										$bulkInsert[] = "('".$arrMessage["BODY"]."','".$arrayTel[0]["MEMBER_NO"]."',
												'sms','".$arrayTel[0]["TEL"]."',null,'".$arraySendSMS["MESSAGE"]."','".$payload["username"]."'".(isset($id_template) ? ",".$id_template : ",null").")";
										if(sizeof($bulkInsert) == 1000){
											$func->logSMSWasNotSent($bulkInsert);
											unset($bulkInsert);
											$bulkInsert = array();
										}
									}
								}else{
									$bulkInsert[] = "('".$arrMessage["BODY"]."','".$arrayTel[0]["MEMBER_NO"]."',
									'sms',null,null,'ไม่พบเบอร์โทรศัพท์','".$payload["username"]."'".(isset($id_template) ? ",".$id_template : ",null").")";
									if(sizeof($bulkInsert) == 1000){
										$func->logSMSWasNotSent($bulkInsert);
										unset($bulkInsert);
										$bulkInsert = array();
									}
								}
							}
						}
					}
					if(sizeof($bulkInsert) > 0){
						$func->logSMSWasNotSent($bulkInsert);
						unset($bulkInsert);
						$bulkInsert = array();
					}
					if(sizeof($arrGRPAll) > 0){
						$arrayLogSMS = $func->logSMSWasSent($id_template,$arrGRPAll,$arrayMerge,$payload["username"],true);
						$arrayResult['RESULT'] = $arrayLogSMS;
					}else{
						$arrayResult['RESULT'] = TRUE;
					}
					require_once('../../../include/exit_footer.php');
				}
			}else{
				$arrayResult['RESPONSE'] = "ไม่พบชุดคิวรี่ข้อมูล กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../include/exit_footer.php');
			}
		}
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../../include/exit_footer.php');
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../include/exit_footer.php');
}
?>