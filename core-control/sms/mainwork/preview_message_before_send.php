<?php
require_once('../../autoload.php');
if($lib->checkCompleteArgument(['unique_id','message_emoji_','type_send','channel_send','id_query'],$dataComing)){
	if($func->check_permission_core($payload,'sms','sendmessageall') || $func->check_permission_core($payload,'sms','sendmessageperson')){
		if($dataComing["channel_send"] == "mobile_app"){
			$getQuery = $conmysql->prepare("SELECT sms_query,column_selected,is_bind_param,target_field,condition_target FROM smsquery WHERE id_smsquery = :id_query");
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
				$arrGroupAllSuccess = array();
				$arrGroupAllFailed = array();
				$rowQuery = $getQuery->fetch(PDO::FETCH_ASSOC);
				$arrColumn = explode(',',$rowQuery["column_selected"]);
				if($rowQuery["is_bind_param"] == '0'){
					$queryTarget = $conoracle->prepare($rowQuery['sms_query']);
					$queryTarget->execute();
					while($rowTarget = $queryTarget->fetch(PDO::FETCH_ASSOC)){
						$arrGroupCheckSend = array();
						$arrGroupMessage = array();
						$arrTarget = array();
						foreach($arrColumn as $column){
							$arrTarget[$column] = $rowTarget[strtoupper($column)] ?? null;
						}
						$arrToken = $func->getFCMToken('person',$rowTarget[$rowQuery["target_field"]]);
						$arrMessage = $lib->mergeTemplate($dataComing["topic_emoji_"],$dataComing["message_emoji_"],$arrTarget);
						if(isset($arrToken["LIST_SEND"][0]["TOKEN"]) && $arrToken["LIST_SEND"][0]["TOKEN"] != ""){
							if($arrToken["LIST_SEND"][0]["RECEIVE_NOTIFY_NEWS"] == "1"){
								$arrGroupSuccess["DESTINATION"] = $arrToken["LIST_SEND"][0]["MEMBER_NO"];
								$arrGroupSuccess["MESSAGE"] = $arrMessage["BODY"].'^'.$arrMessage["SUBJECT"];
								$arrGroupAllSuccess[] = $arrGroupSuccess;
							}else{
								$arrGroupCheckSend["DESTINATION"] = $rowTarget[$rowQuery["target_field"]];
								$arrGroupCheckSend["MESSAGE"] = $arrMessage["BODY"].'^บัญชีนี้ไม่ประสงค์รับการแจ้งเตือนข่าวสาร';
								$arrGroupAllFailed[] = $arrGroupCheckSend;
							}
						}else{
							if(isset($arrToken["LIST_SEND_HW"][0]["TOKEN"]) && $arrToken["LIST_SEND_HW"][0]["TOKEN"] != ""){
								if($arrToken["LIST_SEND_HW"][0]["RECEIVE_NOTIFY_NEWS"] == "1"){
									$arrGroupSuccess["DESTINATION"] = $arrToken["LIST_SEND_HW"][0]["MEMBER_NO"];
									$arrGroupSuccess["MESSAGE"] = $arrMessage["BODY"].'^'.$arrMessage["SUBJECT"];
									$arrGroupAllSuccess[] = $arrGroupSuccess;
								}else{
									$arrGroupCheckSend["DESTINATION"] = $rowTarget[$rowQuery["target_field"]];
									$arrGroupCheckSend["MESSAGE"] = $arrMessage["BODY"].'^บัญชีนี้ไม่ประสงค์รับการแจ้งเตือนข่าวสาร';
									$arrGroupAllFailed[] = $arrGroupCheckSend;
								}
							}else{
								$arrGroupCheckSend["DESTINATION"] = $rowTarget[$rowQuery["target_field"]];
								$arrGroupCheckSend["MESSAGE"] = $arrMessage["BODY"].'^ไม่สามารถระบุเครื่องในการรับแจ้งเตือนได้';
								$arrGroupAllFailed[] = $arrGroupCheckSend;
							}
						}
					}
					$arrayResult['SUCCESS'] = $arrGroupAllSuccess;
					$arrayResult['FAILED'] = $arrGroupAllFailed;
					$arrayResult['RESULT'] = TRUE;
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
							$arrGroupCheckSend = array();
							$arrGroupMessage = array();
							$arrTarget = array();
							foreach($arrColumn as $column){
								$arrTarget[$column] = $rowTarget[strtoupper($column)] ?? null;
							}
							if($condition[1] == $rowQuery["target_field"]){
								$arrToken = $func->getFCMToken('person',$target);
							}else{
								$arrToken = $func->getFCMToken('person',$rowTarget[$rowQuery["target_field"]]);
							}
							$arrMessage = $lib->mergeTemplate($dataComing["topic_emoji_"],$dataComing["message_emoji_"],$arrTarget);
							if(isset($arrToken["LIST_SEND"][0]["TOKEN"]) && $arrToken["LIST_SEND"][0]["TOKEN"] != ""){
								if($arrToken["LIST_SEND"][0]["RECEIVE_NOTIFY_NEWS"] == "1"){
									$arrGroupSuccess["DESTINATION"] = $arrToken["LIST_SEND"][0]["MEMBER_NO"];
									$arrGroupSuccess["MESSAGE"] = $arrMessage["BODY"].'^'.$arrMessage["SUBJECT"];
									if($condition[1] == $rowQuery["target_field"]){
										$arrGroupSuccess["REF"] = $arrToken["LIST_SEND"][0]["MEMBER_NO"];
									}else{
										$arrGroupSuccess["REF"] = $target;
									}
									$arrGroupAllSuccess[] = $arrGroupSuccess;
								}else{
									$arrGroupCheckSend["DESTINATION"] = $rowTarget[$rowQuery["target_field"]];
									$arrGroupCheckSend["MESSAGE"] = $arrMessage["BODY"].'^บัญชีนี้ไม่ประสงค์รับการแจ้งเตือนข่าวสาร';
									if($condition[1] == $rowQuery["target_field"]){
										$arrGroupCheckSend["REF"] = $rowTarget[$rowQuery["target_field"]];
									}else{
										$arrGroupCheckSend["REF"] = $target;
									}
									$arrGroupAllFailed[] = $arrGroupCheckSend;
								}
							}else{
								if(isset($arrToken["LIST_SEND_HW"][0]["TOKEN"]) && $arrToken["LIST_SEND_HW"][0]["TOKEN"] != ""){
									if($arrToken["LIST_SEND_HW"][0]["RECEIVE_NOTIFY_NEWS"] == "1"){
										$arrGroupSuccess["DESTINATION"] = $arrToken["LIST_SEND_HW"][0]["MEMBER_NO"];
										$arrGroupSuccess["MESSAGE"] = $arrMessage["BODY"].'^'.$arrMessage["SUBJECT"];
										if($condition[1] == $rowQuery["target_field"]){
											$arrGroupSuccess["REF"] = $arrToken["LIST_SEND_HW"][0]["MEMBER_NO"];
										}else{
											$arrGroupSuccess["REF"] = $target;
										}
										$arrGroupAllSuccess[] = $arrGroupSuccess;
									}else{
										$arrGroupCheckSend["DESTINATION"] = $rowTarget[$rowQuery["target_field"]];
										$arrGroupCheckSend["MESSAGE"] = $arrMessage["BODY"].'^บัญชีนี้ไม่ประสงค์รับการแจ้งเตือนข่าวสาร';
										if($condition[1] == $rowQuery["target_field"]){
											$arrGroupCheckSend["REF"] = $rowTarget[$rowQuery["target_field"]];
										}else{
											$arrGroupCheckSend["REF"] = $target;
										}
										$arrGroupAllFailed[] = $arrGroupCheckSend;
									}
								}else{
									$arrGroupCheckSend["DESTINATION"] = $rowTarget[$rowQuery["target_field"]];
									$arrGroupCheckSend["MESSAGE"] = $arrMessage["BODY"].'^ไม่สามารถระบุเครื่องในการรับแจ้งเตือนได้';
									if($condition[1] == $rowQuery["target_field"]){
										$arrGroupCheckSend["REF"] = $rowTarget[$rowQuery["target_field"]];
									}else{
										$arrGroupCheckSend["REF"] = $target;
									}
									$arrGroupAllFailed[] = $arrGroupCheckSend;
								}
							}
						}else{
							$arrGroupCheckSend["DESTINATION"] = $target;
							$arrGroupCheckSend["REF"] = $target;
							$arrGroupCheckSend["MESSAGE"] = $dataComing["message_emoji_"].'^ไม่พบข้อมูลในสิ่งที่ต้องการค้นหา';
							$arrGroupAllFailed[] = $arrGroupCheckSend;
						}
					}
					$arrayResult['SUCCESS'] = $arrGroupAllSuccess;
					$arrayResult['FAILED'] = $arrGroupAllFailed;
					$arrayResult['RESULT'] = TRUE;
					require_once('../../../include/exit_footer.php');
				}
			}else{
				$arrayResult['RESPONSE'] = "ไม่พบชุดคิวรี่ข้อมูล กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../include/exit_footer.php');
			}
		}else{
			$getQuery = $conmysql->prepare("SELECT sms_query,column_selected,is_bind_param,target_field,condition_target FROM smsquery WHERE id_smsquery = :id_query");
			$getQuery->execute([':id_query' => $dataComing["id_query"]]);
			if($getQuery->rowCount() > 0){
				$arrGroupAllSuccess = array();
				$arrGroupAllFailed = array();
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
						$arrayTel = $func->getSMSPerson('person',$rowTarget[$rowQuery["target_field"]]);
						foreach($arrayTel as $dest){
							if(isset($dest["TEL"]) && $dest["TEL"] != ""){
								$arrGroupSuccess["DESTINATION"] = $dest["MEMBER_NO"];
								$arrGroupSuccess["REF"] = $dest["MEMBER_NO"];
								$arrGroupSuccess["TEL"] = $lib->formatphone($dest["TEL"],'-');
								$arrGroupSuccess["MESSAGE"] = $arrMessage["BODY"];
								$arrGroupAllSuccess[] = $arrGroupSuccess;
							}else{
								$arrGroupCheckSend["DESTINATION"] = $dest["MEMBER_NO"];
								$arrGroupCheckSend["REF"] = $dest["MEMBER_NO"];
								$arrGroupCheckSend["TEL"] = "ไม่พบเบอร์โทรศัพท์";
								$arrGroupCheckSend["MESSAGE"] = $arrMessage["BODY"];
								$arrGroupAllFailed[] = $arrGroupCheckSend;
							}
						}
					}
					$arrayResult['SUCCESS'] = $arrGroupAllSuccess;
					$arrayResult['FAILED'] = $arrGroupAllFailed;
					$arrayResult['RESULT'] = TRUE;
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
						while($rowTarget = $queryTarget->fetch(PDO::FETCH_ASSOC)){
							$arrGroupCheckSend = array();
							$arrGroupMessage = array();
							$arrTarget = array();
							foreach($arrColumn as $column){
								$arrTarget[$column] = $rowTarget[strtoupper($column)] ?? null;
							}
							if($condition[1] == $rowQuery["target_field"]){
								$arrayTel = $func->getSMSPerson('person',$destination);
							}else{
								$arrayTel = $func->getSMSPerson('person',$rowTarget[$rowQuery["target_field"]]);
							}
							$arrMessage = $lib->mergeTemplate(null,$dataComing["message_emoji_"],$arrTarget);
							foreach($arrayTel as $dest){
								if(isset($dest["TEL"]) && $dest["TEL"] != ""){
									$arrGroupSuccess["DESTINATION"] = $dest["MEMBER_NO"];
									if($condition[1] == $rowQuery["target_field"]){
										$arrGroupSuccess["REF"] = $dest["MEMBER_NO"];
									}else{
										$arrGroupSuccess["REF"] = $destination;
									}
									$arrGroupSuccess["TEL"] = $lib->formatphone($dest["TEL"],'-');
									$arrGroupSuccess["MESSAGE"] = $arrMessage["BODY"];
									$arrGroupAllSuccess[] = $arrGroupSuccess;
								}else{
									$arrGroupCheckSend["DESTINATION"] = $dest["MEMBER_NO"];
									if($condition[1] == $rowQuery["target_field"]){
										$arrGroupCheckSend["REF"] = $destination;
									}
									$arrGroupCheckSend["TEL"] = "ไม่พบเบอร์โทรศัพท์";
									$arrGroupCheckSend["MESSAGE"] = $arrMessage["BODY"];
									$arrGroupAllFailed[] = $arrGroupCheckSend;
								}
							}
						}
						if(array_search($destination, array_column($arrGroupAllSuccess, 'REF')) === false && array_search($destination, array_column($arrGroupAllFailed, 'DESTINATION')) === false
						&& array_search($destination, array_column($arrGroupAllSuccess, 'REF')) === false){
							$arrGroupCheckSend["DESTINATION"] = $destination;
							$arrGroupCheckSend["REF"] = $destination;
							$arrGroupCheckSend["TEL"] = "-";
							$arrGroupCheckSend["MESSAGE"] = "ไม่สามารถระบุเลขปลายทางได้";
							$arrGroupAllFailed[] = $arrGroupCheckSend;
						}
					}
					$arrayResult['SUCCESS'] = $arrGroupAllSuccess;
					$arrayResult['FAILED'] = $arrGroupAllFailed;
					$arrayResult['RESULT'] = TRUE;
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