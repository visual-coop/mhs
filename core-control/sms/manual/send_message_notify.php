<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','type_send','channel_send'],$dataComing)){
	if($func->check_permission_core($payload,'sms','sendmessageall') || $func->check_permission_core($payload,'sms','sendmessageperson')){
		$id_template = isset($dataComing["id_smstemplate"]) && $dataComing["id_smstemplate"] != "" ? $dataComing["id_smstemplate"] : null;
		if($dataComing["channel_send"] == "mobile_app"){
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
			if(isset($dataComing["message_importData"]) && $dataComing["message_importData"] != "" && sizeof($dataComing["message_importData"]) > 0){
				$blukInsert = array();
				$blukInsertNot = array();
				$destination = array();
				foreach($dataComing["message_importData"] as $key => $target){
					if(!in_array($key,$dataComing["destination_revoke"])){
						$destination[] = strtolower($lib->mb_str_pad($target["DESTINATION"]));
						$destinationFull[] = $target;
					}
				}
				$arrToken = $func->getFCMToken('person',$destination);
				foreach($destinationFull as $dest){
					$indexFound = array_search($dest["DESTINATION"], $arrToken["MEMBER_NO"]);
					if($indexFound !== false){
						if(isset($arrToken["LIST_SEND"][$indexFound]["MEMBER_NO"]) && $arrToken["LIST_SEND"][$indexFound]["MEMBER_NO"] != ""){
							$member_no = $arrToken["LIST_SEND"][$indexFound]["MEMBER_NO"];
							$token = $arrToken["LIST_SEND"][$indexFound]["TOKEN"];
							$recv_noti_news = $arrToken["LIST_SEND"][$indexFound]["RECEIVE_NOTIFY_NEWS"] ?? null;
							$recv_noti_trans = $arrToken["LIST_SEND"][$indexFound]["RECEIVE_NOTIFY_TRANSACTION"] ?? null;
						}else{
							$member_no = $arrToken["LIST_SEND_HW"][$indexFound]["MEMBER_NO"];
							$token = $arrToken["LIST_SEND_HW"][$indexFound]["TOKEN"];
							$recv_noti_news = $arrToken["LIST_SEND_HW"][$indexFound]["RECEIVE_NOTIFY_NEWS"] ?? null;
							$recv_noti_trans = $arrToken["LIST_SEND_HW"][$indexFound]["RECEIVE_NOTIFY_TRANSACTION"] ?? null;
						}
						if(isset($token) && $token != ""){
							if($recv_noti_news == "1"){
								$arrPayloadNotify["TO"] = array($token);
								$arrPayloadNotify["MEMBER_NO"] = $member_no;
								$arrMessage["SUBJECT"] = $dataComing["topic_emoji_"];
								$arrMessage["BODY"] = $dest["MESSAGE"] ?? "-";
								$arrMessage["PATH_IMAGE"] = $pathImg ?? null;
								$arrPayloadNotify["PAYLOAD"] = $arrMessage;
								$arrPayloadNotify["SEND_BY"] = $payload["username"];
								$arrPayloadNotify["ID_TEMPLATE"] = $id_template;
								if($lib->sendNotify($arrPayloadNotify,$dataComing["type_send"]) || $lib->sendNotifyHW($arrPayloadNotify,$dataComing["type_send"])){
									$blukInsert[] = "('1','".$dataComing["topic_emoji_"]."','".$dest["MESSAGE"]."','".($pathImg ?? null)."','".$member_no."','".$payload["username"]."'".(isset($id_template) ? ",".$id_template : ",null").")";
									if(sizeof($blukInsert) == 1000){
										$arrPayloadHistory["TYPE_SEND_HISTORY"] = "manymessage";
										$arrPayloadHistory["bulkInsert"] = $blukInsert;
										$func->insertHistory($arrPayloadHistory);
										unset($blukInsert);
										$blukInsert = array();
									}
								}else{
									$blukInsertNot[] = "('".$dest["MESSAGE"]."','".$member_no."','".$dataComing["channel_send"]."',null,'".$token."','ไม่สามารถส่งได้ให้ดู LOG','".$payload["username"]."'".(isset($id_template) ? ",".$id_template : ",null").")";
									if(sizeof($blukInsertNot) == 1000){
										$func->logSMSWasNotSent($blukInsertNot);
										unset($blukInsertNot);
										$blukInsertNot = array();
									}
								}
							}
						}
					}
				}
				if(sizeof($blukInsert) > 0){
					$arrPayloadHistory["TYPE_SEND_HISTORY"] = "manymessage";
					$arrPayloadHistory["bulkInsert"] = $blukInsert;
					$func->insertHistory($arrPayloadHistory);
					unset($blukInsert);
					$blukInsert = array();
				}
				if(sizeof($blukInsertNot) > 0){
					$func->logSMSWasNotSent($blukInsertNot);
					unset($blukInsertNot);
					$blukInsertNot = array();
				}
				$arrayResult['RESULT'] = TRUE;
				require_once('../../../include/exit_footer.php');
			}else{
				if($dataComing["type_send"] == "person"){
					$blukInsert = array();
					$blukInsertNot = array();
					$destination = array();
					foreach($dataComing["destination"] as $key => $target){
						if(!in_array($key,$dataComing["destination_revoke"])){
							$destination[] = strtolower($lib->mb_str_pad($target));
						}
					}
					$arrToken = $func->getFCMToken('person',$destination);
					foreach($arrToken["LIST_SEND"] as $dest){
						if(isset($dest["TOKEN"]) && $dest["TOKEN"] != ""){
							$arrPayloadNotify["TO"] = array($dest["TOKEN"]);
							$arrPayloadNotify["MEMBER_NO"] = $dest["MEMBER_NO"];
							$arrMessage["SUBJECT"] = $dataComing["topic_emoji_"];
							$message = ($dataComing["message_emoji_"] ?? "-");
							$arrMessage["BODY"] = $message;
							$arrMessage["PATH_IMAGE"] = $pathImg ?? null;
							$arrPayloadNotify["PAYLOAD"] = $arrMessage;
							$arrPayloadNotify["SEND_BY"] = $payload["username"];
							$arrPayloadNotify["ID_TEMPLATE"] = $id_template;
							if($lib->sendNotify($arrPayloadNotify,$dataComing["type_send"])){
								$blukInsert[] = "('1','".$dataComing["topic_emoji_"]."','".$message."','".($pathImg ?? null)."','".$dest["MEMBER_NO"]."','".$payload["username"]."'".(isset($id_template) ? ",".$id_template : ",null").")";
								if(sizeof($blukInsert) == 1000){
									$arrPayloadHistory["TYPE_SEND_HISTORY"] = "manymessage";
									$arrPayloadHistory["bulkInsert"] = $blukInsert;
									$func->insertHistory($arrPayloadHistory);
									unset($blukInsert);
									$blukInsert = array();
								}
							}else{
								$blukInsertNot[] = "('".$message."','".$dest["MEMBER_NO"]."','".$dataComing["channel_send"]."',null,'".$dest["TOKEN"]."','ไม่สามารถส่งได้ให้ดู LOG','".$payload["username"]."'".(isset($id_template) ? ",".$id_template : ",null").")";
								if(sizeof($blukInsertNot) == 1000){
									$func->logSMSWasNotSent($blukInsertNot);
									unset($blukInsertNot);
									$blukInsertNot = array();
								}
							}
						}
					}
					foreach($arrToken["LIST_SEND_HW"] as $dest){
						if(isset($dest["TOKEN"]) && $dest["TOKEN"] != ""){
							$arrPayloadNotify["TO"] = array($dest["TOKEN"]);
							$arrPayloadNotify["MEMBER_NO"] = $dest["MEMBER_NO"];
							$arrMessage["SUBJECT"] = $dataComing["topic_emoji_"];
							$message = ($dataComing["message_emoji_"] ?? "-");
							$arrMessage["BODY"] = $message;
							$arrMessage["PATH_IMAGE"] = $pathImg ?? null;
							$arrPayloadNotify["PAYLOAD"] = $arrMessage;
							$arrPayloadNotify["SEND_BY"] = $payload["username"];
							$arrPayloadNotify["ID_TEMPLATE"] = $id_template;
							if($lib->sendNotifyHW($arrPayloadNotify,$dataComing["type_send"])){
								$blukInsert[] = "('1','".$dataComing["topic_emoji_"]."','".$message."','".($pathImg ?? null)."','".$dest["MEMBER_NO"]."','".$payload["username"]."'".(isset($id_template) ? ",".$id_template : ",null").")";
								if(sizeof($blukInsert) == 1000){
									$arrPayloadHistory["TYPE_SEND_HISTORY"] = "manymessage";
									$arrPayloadHistory["bulkInsert"] = $blukInsert;
									$func->insertHistory($arrPayloadHistory);
									unset($blukInsert);
									$blukInsert = array();
								}
							}else{
								$blukInsertNot[] = "('".$message."','".$dest["MEMBER_NO"]."','".$dataComing["channel_send"]."',null,'".$dest["TOKEN"]."','ไม่สามารถส่งได้ให้ดู LOG','".$payload["username"]."'".(isset($id_template) ? ",".$id_template : ",null").")";
								if(sizeof($blukInsertNot) == 1000){
									$func->logSMSWasNotSent($blukInsertNot);
									unset($blukInsertNot);
									$blukInsertNot = array();
								}
							}
						}
					}
					if(sizeof($blukInsert) > 0){
						$arrPayloadHistory["TYPE_SEND_HISTORY"] = "manymessage";
						$arrPayloadHistory["bulkInsert"] = $blukInsert;
						$func->insertHistory($arrPayloadHistory);
						unset($blukInsert);
						$blukInsert = array();
					}
					if(sizeof($blukInsertNot) > 0){
						$func->logSMSWasNotSent($blukInsertNot);
						unset($blukInsertNot);
						$blukInsertNot = array();
					}
					$arrayResult['RESULT'] = TRUE;
					require_once('../../../include/exit_footer.php');
				}else{
					$bulkInsert = array();
					$arrToken = $func->getFCMToken('all');
					$arrAllToken = array();
					$arrAllMember_no = array();
					foreach($arrToken["LIST_SEND"] as $dest){
						if(isset($dest["TOKEN"]) && $dest["TOKEN"] != ""){
							if($dest["RECEIVE_NOTIFY_NEWS"] == "1"){
								$arrAllMember_no[] = $dest["MEMBER_NO"];
								$arrAllToken[] = $dest["TOKEN"];
							}else{
								$bulkInsert[] = "('".$dataComing["message_emoji_"]."','".$dest["MEMBER_NO"]."',
								'mobile_app',null,'".$dest["TOKEN"]."','บัญชีปลายทางไม่ประสงค์เปิดรับการแจ้งเตือน','".$payload["username"]."'".(isset($id_template) ? ",".$id_template : ",null").")";
							}
							if(sizeof($bulkInsert) == 1000){
								$func->logSMSWasNotSent($bulkInsert);
								unset($bulkInsert);
								$bulkInsert = array();
							}
						}else{
							$bulkInsert[] = "('".$dataComing["message_emoji_"]."','".$dest["MEMBER_NO"]."',
							'mobile_app',null,null,'หา Token ในการส่งไม่เจออาจจะเพราะไม่อนุญาตให้ส่งแจ้งเตือนเข้าเครื่อง','".$payload["username"]."'".(isset($id_template) ? ",".$id_template : ",null").")";
							if(sizeof($bulkInsert) == 1000){
								$func->logSMSWasNotSent($bulkInsert);
								unset($bulkInsert);
								$bulkInsert = array();
							}
						}
					}
					foreach($arrToken["LIST_SEND_HW"] as $dest){
						if(isset($dest["TOKEN"]) && $dest["TOKEN"] != ""){
							if($dest["RECEIVE_NOTIFY_NEWS"] == "1"){
								$arrAllMember_no[] = $dest["MEMBER_NO"];
								$arrAllToken[] = $dest["TOKEN"];
							}else{
								$bulkInsert[] = "('".$dataComing["message_emoji_"]."','".$dest["MEMBER_NO"]."',
								'mobile_app',null,'".$dest["TOKEN"]."','บัญชีปลายทางไม่ประสงค์เปิดรับการแจ้งเตือน','".$payload["username"]."'".(isset($id_template) ? ",".$id_template : ",null").")";
							}
							if(sizeof($bulkInsert) == 1000){
								$func->logSMSWasNotSent($bulkInsert);
								unset($bulkInsert);
								$bulkInsert = array();
							}
						}else{
							$bulkInsert[] = "('".$dataComing["message_emoji_"]."','".$dest["MEMBER_NO"]."',
							'mobile_app',null,null,'หา Token ในการส่งไม่เจออาจจะเพราะไม่อนุญาตให้ส่งแจ้งเตือนเข้าเครื่อง','".$payload["username"]."'".(isset($id_template) ? ",".$id_template : ",null").")";
							if(sizeof($bulkInsert) == 1000){
								$func->logSMSWasNotSent($bulkInsert);
								unset($bulkInsert);
								$bulkInsert = array();
							}
						}
					}
					if(sizeof($arrAllToken) > 0){
						if(sizeof($bulkInsert) > 0){
							$func->logSMSWasNotSent($bulkInsert);
							unset($bulkInsert);
							$bulkInsert = array();
						}
						$arrPayloadNotify["TO"] = $config["SUBSCRIPT_ROOM_NOTIFY"];
						$arrPayloadNotify["MEMBER_NO"] = $arrAllMember_no;
						$arrMessage["SUBJECT"] = $dataComing["topic_emoji_"];
						$arrMessage["BODY"] = $dataComing["message_emoji_"];
						$arrMessage["PATH_IMAGE"] = $pathImg ?? null;
						$arrPayloadNotify["PAYLOAD"] = $arrMessage;
						$arrPayloadNotify["TYPE_SEND_HISTORY"] = "onemessage";
						$arrPayloadNotify["SEND_BY"] = $payload["username"];
						$arrPayloadNotify["ID_TEMPLATE"] = $id_template;
						if($lib->sendNotify($arrPayloadNotify,'all') || $lib->sendNotifyHW($arrPayloadNotify,'all')){
							if($func->insertHistory($arrPayloadNotify,'1')){ //รอแก้ไขส่งทุกคน Subscribe ตามห้อง
								$arrayResult['RESULT'] = TRUE;
								require_once('../../../include/exit_footer.php');
							}else{
								$arrayResult['RESPONSE'] = "ไม่สามารถส่งข้อความได้เนื่องจากไม่สามารถบันทึกประวัติการส่งแจ้งเตือนได้";
								$arrayResult['RESULT'] = FALSE;
								require_once('../../../include/exit_footer.php');
							}
						}else{
							$arrayResult['RESPONSE'] = "ส่งข้อความล้มเหลว กรุณาติดต่อผู้พัฒนา";
							$arrayResult['RESULT'] = FALSE;
							require_once('../../../include/exit_footer.php');
						}
					}else{
						if(sizeof($bulkInsert) > 0){
							$func->logSMSWasNotSent($bulkInsert);
							unset($bulkInsert);
							$bulkInsert = array();
						}
						$arrayResult['RESPONSE'] = "ไม่พบบัญชีที่สามารถส่งได้กรุณาลองใหม่อีกครั้ง";
						$arrayResult['RESULT'] = FALSE;
						require_once('../../../include/exit_footer.php');
					}
				}
			}
		}else if($dataComing["channel_send"] == "sms"){
			if(isset($dataComing["message_importData"]) && $dataComing["message_importData"] != "" && sizeof($dataComing["message_importData"]) > 0){
				$arrGRPAll = array();
				$destination = array();
				$arrDestGRP = array();
				$arrDestSend = array();
				foreach($dataComing["message_importData"] as $key => $target){
					$destination_temp = array();
					if(mb_strlen($target["DESTINATION"]) <= 8){
						if(!in_array($key,$dataComing["destination_revoke"])){
							$destination[] = strtolower($lib->mb_str_pad($target["DESTINATION"]));
							$arrDestSend[] = $target;
						}
					}else if(mb_strlen($target["DESTINATION"]) == 10){
						if(!in_array($key,$dataComing["destination_revoke"])){
							$destination_temp["MEMBER_NO"] = null;
							$destination_temp["TEL"] = $target["DESTINATION"];
							$arrDestGRP[] = $destination_temp;
							$arrDestSend[] = $target;
						}
					}
				}
				$arrayTel = $func->getSMSPerson('person',$destination,false,true);
				if(isset($arrDestGRP)){
					$arrayMerge = array_merge($arrayTel,$arrDestGRP);
				}else{
					$arrayMerge = $arrayTel;
				}
				$arrSend = array();
				foreach($arrDestSend as $dest){
					$indexFound = array_search($dest["DESTINATION"], array_column($arrayMerge, 'MEMBER_NO')) !== false ? 
					array_search($dest["DESTINATION"], array_column($arrayMerge, 'MEMBER_NO')) : array_search($dest["DESTINATION"], array_column($arrayMerge, 'TEL'));
					if($indexFound !== false){
						$member_no = $arrayMerge[$indexFound]["MEMBER_NO"];
						$telMember = $arrayMerge[$indexFound]["TEL"];
						if(isset($telMember) && $telMember != ""){
							$message_body = $dest["MESSAGE"];
							$arrayDest["cmd_sms"] = "CMD=".$config["CMD_SMS"]."&FROM=".$config["FROM_SERVICES_SMS"]."&TO=66".(substr($telMember,1,9))."&REPORT=Y&CHARGE=".$config["CHARGE_SMS"]."&CODE=".$config["CODE_SMS"]."&CTYPE=UNICODE&CONTENT=".$lib->unicodeMessageEncode($message_body);
							$arraySendSMS = $lib->sendSMS($arrayDest);
							if($arraySendSMS["RESULT"]){
								$arrSendTemp = array();
								$arrGRPAll[$member_no] = $message_body;
								$arrSendTemp["TEL"] = $telMember;
								$arrSendTemp["MEMBER_NO"] = $member_no;
								$arrSend[] = $arrSendTemp;
							}else{
								$bulkInsert[] = "('".$message_body."','".$member_no."',
										'sms','".$telMember."',null,'".$arraySendSMS["MESSAGE"]."','".$payload["username"]."'".(isset($id_template) ? ",".$id_template : ",null").")";
								if(sizeof($bulkInsert) == 1000){
									$func->logSMSWasNotSent($bulkInsert);
									unset($bulkInsert);
								}
							}
						}
					}
				}
				if(sizeof($arrGRPAll) > 0){
					$func->logSMSWasSent($id_template,$arrGRPAll,$arrSend,$payload["username"],true);
				}
				if(sizeof($bulkInsert) > 0){
					$func->logSMSWasNotSent($bulkInsert);
					unset($bulkInsert);
					$bulkInsert = array();
				}
				$arrayResult['RESULT'] = TRUE;
				require_once('../../../include/exit_footer.php');
			}else{
				if($dataComing["type_send"] == "person"){
					$arrGRPAll = array();
					$destination = array();
					$arrDestGRP = array();
					foreach($dataComing["destination"] as $key => $target){
						$destination_temp = array();
						if(mb_strlen($target) <= 8){
							if(!in_array($key,$dataComing["destination_revoke"])){
								$destination[] = strtolower($lib->mb_str_pad($target));
							}
						}else if(mb_strlen($target) == 10){
							if(!in_array($target,$dataComing["destination_revoke"])){
								$destination_temp["MEMBER_NO"] = null;
								$destination_temp["TEL"] = $target;
								$arrDestGRP[] = $destination_temp;
							}
						}
					}
					$arrayTel = $func->getSMSPerson('person',$destination,false,true);
					if(isset($arrDestGRP)){
						$arrayMerge = array_merge($arrayTel,$arrDestGRP);
					}else{
						$arrayMerge = $arrayTel;
					}
					foreach($arrayMerge as $dest){
						if(isset($dest["TEL"]) && $dest["TEL"] != ""){
							$message_body = $dataComing["message_emoji_"];
							$arrayDest["cmd_sms"] = "CMD=".$config["CMD_SMS"]."&FROM=".$config["FROM_SERVICES_SMS"]."&TO=66".(substr($dest["TEL"],1,9))."&REPORT=Y&CHARGE=".$config["CHARGE_SMS"]."&CODE=".$config["CODE_SMS"]."&CTYPE=UNICODE&CONTENT=".$lib->unicodeMessageEncode($message_body);
							$arraySendSMS = $lib->sendSMS($arrayDest);
							if($arraySendSMS["RESULT"]){
								$arrGRPAll[$dest["MEMBER_NO"]] = $dataComing["message_emoji_"];
							}else{
								$bulkInsert[] = "('".$message_body."','".$dest["MEMBER_NO"]."',
										'sms','".$dest["TEL"]."',null,'".$arraySendSMS["MESSAGE"]."','".$payload["username"]."'".(isset($id_template) ? ",".$id_template : ",null").")";
								if(sizeof($bulkInsert) == 1000){
									$func->logSMSWasNotSent($bulkInsert);
									unset($bulkInsert);
								}
							}
						}
					}
					if(sizeof($arrGRPAll) > 0){
						$func->logSMSWasSent($id_template,$arrGRPAll,$arrayMerge,$payload["username"],true);
					}
					if(sizeof($bulkInsert) > 0){
						$func->logSMSWasNotSent($bulkInsert);
						unset($bulkInsert);
						$bulkInsert = array();
					}
					$arrayResult['RESULT'] = TRUE;
					require_once('../../../include/exit_footer.php');
				}else{
					$arrayResult['RESPONSE'] = "ยังไม่รองรับรูปแบบการส่งนี้";
					$arrayResult['RESULT'] = FALSE;
					require_once('../../../include/exit_footer.php');
				}
			}
		}else{
			$arrayResult['RESPONSE'] = "ยังไม่รองรับรูปแบบการส่งนี้";
			$arrayResult['RESULT'] = FALSE;
			require_once('../../../include/exit_footer.php');
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