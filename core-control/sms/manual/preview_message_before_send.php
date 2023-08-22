<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','type_send','channel_send'],$dataComing)){
	if($func->check_permission_core($payload,'sms','sendmessageall') || $func->check_permission_core($payload,'sms','sendmessageperson')){
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
				$arrGroupAllSuccess = array();
				$arrGroupAllFailed = array();
				$destination = array();
				foreach($dataComing["message_importData"] as $target){
					$destination[] = strtolower($lib->mb_str_pad($target["DESTINATION"]));
				}
				$arrToken = $func->getFCMToken('person',$destination);
				foreach($dataComing["message_importData"] as $dest){
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
								$arrGroupSuccess["DESTINATION"] = $member_no;
								$arrGroupSuccess["REF"] = $dest["DESTINATION"];
								$arrGroupSuccess["REF_MESSAGE"] = $dest["MESSAGE"];
								$arrGroupSuccess["MESSAGE"] = $dest["MESSAGE"].'^'.$dataComing["topic_emoji_"];
								$arrGroupAllSuccess[] = $arrGroupSuccess;
							}else{
								$arrGroupCheckSend["DESTINATION"] = $dest["MEMBER_NO"];
								$arrGroupCheckSend["REF"] = $dest["MEMBER_NO"];
								$arrGroupCheckSend["REF_MESSAGE"] = $dest["MESSAGE"];
								$arrGroupCheckSend["MESSAGE"] = $dest["MESSAGE"].'^บัญชีนี้ไม่ประสงค์รับการแจ้งเตือนข่าวสาร';
								$arrGroupAllFailed[] = $arrGroupCheckSend;
							}
						}else{
							$arrGroupCheckSend["DESTINATION"] = $dest["MEMBER_NO"];
							$arrGroupCheckSend["REF"] = $dest["MEMBER_NO"];
							$arrGroupCheckSend["REF_MESSAGE"] = $dest["MESSAGE"];
							$arrGroupCheckSend["MESSAGE"] = $dest["MESSAGE"].'^ไม่สามารถระบุเครื่องในการรับแจ้งเตือนได้';
							$arrGroupAllFailed[] = $arrGroupCheckSend;
						}
					}
				}
				
				foreach($dataComing["message_importData"] as $memb_diff){
					if(array_search($memb_diff["DESTINATION"], array_column($arrGroupAllSuccess, 'DESTINATION')) === false && 
					array_search($memb_diff["DESTINATION"], array_column($arrGroupAllFailed, 'DESTINATION')) === false){
						$arrGroupCheckSend["DESTINATION"] = $memb_diff["DESTINATION"];
						$arrGroupCheckSend["REF"] = $memb_diff["DESTINATION"];
						$arrGroupCheckSend["REF_MESSAGE"] = $memb_diff["MESSAGE"];
						$arrGroupCheckSend["MESSAGE"] = $memb_diff["MESSAGE"].'^ไม่สามารถระบุเครื่องในการรับแจ้งเตือนได้';
						$arrGroupAllFailed[] = $arrGroupCheckSend;
					}
				}
				$arrayResult['SUCCESS'] = $arrGroupAllSuccess;
				$arrayResult['FAILED'] = $arrGroupAllFailed;
				$arrayResult['RESULT'] = TRUE;
				require_once('../../../include/exit_footer.php');
			}else{
				$arrGroupAllSuccess = array();
				$arrGroupAllFailed = array();
				if($dataComing["type_send"] == "person"){
					$destination = array();
					foreach($dataComing["destination"] as $target){
						$destination[] = strtolower($lib->mb_str_pad($target));
					}
					$arrToken = $func->getFCMToken('person',$destination);
					foreach($arrToken["LIST_SEND"] as $dest){
						if(isset($dest["TOKEN"]) && $dest["TOKEN"] != ""){
							if($dest["RECEIVE_NOTIFY_NEWS"] == "1"){
								$arrGroupSuccess["DESTINATION"] = $dest["MEMBER_NO"];
								$arrGroupSuccess["REF"] = $dest["MEMBER_NO"];
								$arrGroupSuccess["MESSAGE"] = ($dataComing["message_emoji_"] ?? "-").'^'.$dataComing["topic_emoji_"];
								$arrGroupAllSuccess[] = $arrGroupSuccess;
							}else{
								$arrGroupCheckSend["DESTINATION"] = $dest["MEMBER_NO"];
								$arrGroupCheckSend["REF"] = $dest["MEMBER_NO"];
								$arrGroupCheckSend["MESSAGE"] = $dataComing["message_emoji_"].'^บัญชีนี้ไม่ประสงค์รับการแจ้งเตือนข่าวสาร';
								$arrGroupAllFailed[] = $arrGroupCheckSend;
							}
						}else{
							$arrGroupCheckSend["DESTINATION"] = $dest["MEMBER_NO"];
							$arrGroupCheckSend["REF"] = $dest["MEMBER_NO"];
							$arrGroupCheckSend["MESSAGE"] = $dataComing["message_emoji_"].'^ไม่สามารถระบุเครื่องในการรับแจ้งเตือนได้';
							$arrGroupAllFailed[] = $arrGroupCheckSend;
						}
					}
					foreach($arrToken["LIST_SEND_HW"] as $dest){
						if(isset($dest["TOKEN"]) && $dest["TOKEN"] != ""){
							if($dest["RECEIVE_NOTIFY_NEWS"] == "1"){
								$arrGroupSuccess["DESTINATION"] = $dest["MEMBER_NO"];
								$arrGroupSuccess["REF"] = $dest["MEMBER_NO"];
								$arrGroupSuccess["MESSAGE"] = ($dataComing["message_emoji_"] ?? "-").'^'.$dataComing["topic_emoji_"];
								$arrGroupAllSuccess[] = $arrGroupSuccess;
							}else{
								$arrGroupCheckSend["DESTINATION"] = $dest["MEMBER_NO"];
								$arrGroupCheckSend["REF"] = $dest["MEMBER_NO"];
								$arrGroupCheckSend["MESSAGE"] = $dataComing["message_emoji_"].'^บัญชีนี้ไม่ประสงค์รับการแจ้งเตือนข่าวสาร';
								$arrGroupAllFailed[] = $arrGroupCheckSend;
							}
						}else{
							$arrGroupCheckSend["DESTINATION"] = $dest["MEMBER_NO"];
							$arrGroupCheckSend["REF"] = $dest["MEMBER_NO"];
							$arrGroupCheckSend["MESSAGE"] = $dataComing["message_emoji_"].'^ไม่สามารถระบุเครื่องในการรับแจ้งเตือนได้';
							$arrGroupAllFailed[] = $arrGroupCheckSend;
						}
					}
					$arrDiff = array_diff($destination,$arrToken["MEMBER_NO"]);
					foreach($arrDiff as $memb_diff){
						$arrGroupCheckSend["DESTINATION"] = $memb_diff;
						$arrGroupCheckSend["REF"] = $memb_diff;
						$arrGroupCheckSend["MESSAGE"] = $dataComing["message_emoji_"].'^ไม่สามารถระบุเครื่องในการรับแจ้งเตือนได้';
						$arrGroupAllFailed[] = $arrGroupCheckSend;
					}
					$arrayResult['SUCCESS'] = $arrGroupAllSuccess;
					$arrayResult['FAILED'] = $arrGroupAllFailed;
					$arrayResult['RESULT'] = TRUE;
					require_once('../../../include/exit_footer.php');
				}else{
					$arrToken = $func->getFCMToken('all');
					foreach($arrToken["LIST_SEND"] as $dest){
						if(isset($dest["TOKEN"]) && $dest["TOKEN"] != ""){
							if($dest["RECEIVE_NOTIFY_NEWS"] == "1"){
								$arrGroupSuccess["DESTINATION"] = $dest["MEMBER_NO"];
								$arrGroupSuccess["MESSAGE"] = $dataComing["message_emoji_"].'^'.$dataComing["topic_emoji_"];
								$arrGroupAllSuccess[] = $arrGroupSuccess;
							}else{
								$arrGroupCheckSend["DESTINATION"] = $dest["MEMBER_NO"];
								$arrGroupCheckSend["MESSAGE"] = $dataComing["message_emoji_"].'^บัญชีนี้ไม่ประสงค์รับการแจ้งเตือนข่าวสาร';
								$arrGroupAllFailed[] = $arrGroupCheckSend;
							}
						}else{
							$arrGroupCheckSend["DESTINATION"] = $dest["MEMBER_NO"];
							$arrGroupCheckSend["MESSAGE"] = $dataComing["message_emoji_"].'^ไม่สามารถระบุเครื่องในการรับแจ้งเตือนได้';
							$arrGroupAllFailed[] = $arrGroupCheckSend;
						}
					}
					foreach($arrToken["LIST_SEND_HW"] as $dest){
						if(isset($dest["TOKEN"]) && $dest["TOKEN"] != ""){
							if($dest["RECEIVE_NOTIFY_NEWS"] == "1"){
								$arrGroupSuccess["DESTINATION"] = $dest["MEMBER_NO"];
								$arrGroupSuccess["MESSAGE"] = $dataComing["message_emoji_"].'^'.$dataComing["topic_emoji_"];
								$arrGroupAllSuccess[] = $arrGroupSuccess;
							}else{
								$arrGroupCheckSend["DESTINATION"] = $dest["MEMBER_NO"];
								$arrGroupCheckSend["MESSAGE"] = $dataComing["message_emoji_"].'^บัญชีนี้ไม่ประสงค์รับการแจ้งเตือนข่าวสาร';
								$arrGroupAllFailed[] = $arrGroupCheckSend;
							}
						}else{
							$arrGroupCheckSend["DESTINATION"] = $dest["MEMBER_NO"];
							$arrGroupCheckSend["MESSAGE"] = $dataComing["message_emoji_"].'^ไม่สามารถระบุเครื่องในการรับแจ้งเตือนได้';
							$arrGroupAllFailed[] = $arrGroupCheckSend;
						}
					}
					$arrayResult['SUCCESS'] = $arrGroupAllSuccess;
					$arrayResult['FAILED'] = $arrGroupAllFailed;
					$arrayResult['RESULT'] = TRUE;
					require_once('../../../include/exit_footer.php');
				}
			}
		}else if($dataComing["channel_send"] == "sms"){
			if(isset($dataComing["message_importData"]) && $dataComing["message_importData"] != "" && sizeof($dataComing["message_importData"]) > 0){
				$arrGroupAllSuccess = array();
				$arrGroupAllFailed = array();
				$destination = array();
				$arrDestGRP = array();
				$arrDestGRPNotCorrect = array();
				foreach($dataComing["message_importData"] as $target){
					if(mb_strlen($target["DESTINATION"]) <= 8){
						$destination[] = strtolower($lib->mb_str_pad($target["DESTINATION"]));
					}else if(mb_strlen($target["DESTINATION"]) == 10){
						$destination_temp = array();
						$destination_temp["MEMBER_NO"] = null;
						$destination_temp["TEL"] = $target["DESTINATION"];
						$arrDestGRP[] = $destination_temp;
					}else{
						$destination_temp = array();
						$destination_temp["MEMBER_NO"] = null;
						$destination_temp["TEL"] = $target["DESTINATION"];
						$destination_temp["MESSAGE"] = $target["MESSAGE"];
						$arrDestGRPNotCorrect[] = $destination_temp;
					}
				}
				$arrayTel = $func->getSMSPerson('person',$destination);
				if(isset($arrDestGRP)){
					$arrayMerge = array_merge($arrayTel,$arrDestGRP);
				}else{
					$arrayMerge = $arrayTel;
				}
				foreach($dataComing["message_importData"] as $dest){
					$indexFound = array_search($dest["DESTINATION"], array_column($arrayMerge, 'MEMBER_NO')) !== false ? 
					array_search($dest["DESTINATION"], array_column($arrayMerge, 'MEMBER_NO')) : array_search($dest["DESTINATION"], array_column($arrayMerge, 'TEL'));
					if($indexFound !== false){
						$member_no = $arrayMerge[$indexFound]["MEMBER_NO"];
						$telMember = $arrayMerge[$indexFound]["TEL"];
						if(strtolower($lib->mb_str_pad($dest["DESTINATION"]))){
							if(isset($telMember) && $telMember != ""){
								$arrGroupSuccess["DESTINATION"] = $member_no;
								$arrGroupSuccess["REF"] = $dest["DESTINATION"];
								$arrGroupSuccess["TEL"] = $lib->formatphone(substr($telMember,0,10),'-');
								$arrGroupSuccess["MESSAGE"] = $dest["MESSAGE"];
								$arrGroupSuccess["REF_MESSAGE"] = $dest["MESSAGE"];
								$arrGroupAllSuccess[] = $arrGroupSuccess;
							}else{
								$arrGroupCheckSend["DESTINATION"] = $member_no;
								$arrGroupCheckSend["REF"] = $dest["DESTINATION"];
								$arrGroupCheckSend["TEL"] = "ไม่พบเบอร์โทรศัพท์";
								$arrGroupCheckSend["MESSAGE"] = $dest["MESSAGE"];
								$arrGroupCheckSend["REF_MESSAGE"] = $dest["MESSAGE"];
								$arrGroupAllFailed[] = $arrGroupCheckSend;
							}
						}
					}
				}
				foreach($dataComing["message_importData"] as $target){
					if(mb_strlen($target["DESTINATION"]) <= 8){
						$target_dest = strtolower($lib->mb_str_pad($target["DESTINATION"]));
					}else if(mb_strlen($target) == 10){
						$target_dest = $lib->formatphone($target["DESTINATION"],'-');
					}
					if(array_search($target_dest, array_column($arrGroupAllSuccess, 'DESTINATION')) === false && 
					array_search($target_dest, array_column($arrGroupAllSuccess, 'TEL')) === false && array_search($target_dest, array_column($arrGroupAllFailed, 'DESTINATION')) === false){
						$arrGroupCheckSend["DESTINATION"] = $target_dest;
						$arrGroupCheckSend["REF"] = $target["DESTINATION"];
						$arrGroupCheckSend["MESSAGE"] = "ไม่สามารถระบุเลขปลายทางได้";
						$arrGroupCheckSend["REF_MESSAGE"] = $target["MESSAGE"];
						$arrGroupAllFailed[] = $arrGroupCheckSend;
					}
				}
				foreach($arrDestGRPNotCorrect as $target){
					$arrGroupCheckSend["DESTINATION"] = $target["TEL"];
					$arrGroupCheckSend["REF"] = $target["TEL"];
					$arrGroupCheckSend["MESSAGE"] = "ไม่สามารถระบุเลขปลายทางได้";
					$arrGroupCheckSend["REF_MESSAGE"] = $target["MESSAGE"];
					$arrGroupAllFailed[] = $arrGroupCheckSend;
				}
				$arrayResult['SUCCESS'] = $arrGroupAllSuccess;
				$arrayResult['FAILED'] = $arrGroupAllFailed;
				$arrayResult['RESULT'] = TRUE;
				require_once('../../../include/exit_footer.php');
			}else{
				$arrGroupAllSuccess = array();
				$arrGroupAllFailed = array();
				if($dataComing["type_send"] == "person"){
					$destination = array();
					$arrDestGRP = array();
					$arrDestGRPNotCorrect = array();
					foreach($dataComing["destination"] as $target){
						if(mb_strlen($target) <= 8){
							$destination[] = strtolower($lib->mb_str_pad($target));
						}else if(mb_strlen($target) == 10){
							$destination_temp = array();
							$destination_temp["MEMBER_NO"] = null;
							$destination_temp["TEL"] = $target;
							$arrDestGRP[] = $destination_temp;
						}else{
							$destination_temp = array();
							$destination_temp["MEMBER_NO"] = null;
							$destination_temp["TEL"] = $target;
							$arrDestGRPNotCorrect[] = $destination_temp;
						}
					}
					$arrayTel = $func->getSMSPerson('person',$destination);
					if(isset($arrDestGRP)){
						$arrayMerge = array_merge($arrayTel,$arrDestGRP);
					}else{
						$arrayMerge = $arrayTel;
					}
					foreach($arrayMerge as $dest){
						if(isset($dest["TEL"]) && $dest["TEL"] != ""){
							$arrGroupSuccess["DESTINATION"] = $dest["MEMBER_NO"];
							$arrGroupSuccess["REF"] = $dest["MEMBER_NO"] ?? $dest["TEL"];
							$arrGroupSuccess["TEL"] = $lib->formatphone(substr($dest["TEL"],0,10),'-');
							$arrGroupSuccess["MESSAGE"] = ($dataComing["message_emoji_"] ?? "-");
							$arrGroupSuccess["REF_MESSAGE"] = $dataComing["message_emoji_"];
							$arrGroupAllSuccess[] = $arrGroupSuccess;
						}else{
							$arrGroupCheckSend["DESTINATION"] = $dest["MEMBER_NO"];
							$arrGroupCheckSend["REF"] = $dest["MEMBER_NO"];
							$arrGroupCheckSend["TEL"] = "ไม่พบเบอร์โทรศัพท์";
							$arrGroupCheckSend["MESSAGE"] = ($dataComing["message_emoji_"] ?? "-");
							$arrGroupCheckSend["REF_MESSAGE"] = $dataComing["message_emoji_"];
							$arrGroupAllFailed[] = $arrGroupCheckSend;
						}
					}
					foreach($dataComing["destination"] as $target){
						if(mb_strlen($target) <= 8){
							$target_format = strtolower($lib->mb_str_pad($target));
						}else if(mb_strlen($target) == 10){
							$target_format = $lib->formatphone($target,'-');
						}
						if(array_search($target_format, array_column($arrGroupAllSuccess, 'DESTINATION')) === false && 
						array_search($target_format, array_column($arrGroupAllSuccess, 'TEL')) === false && array_search($target_format, array_column($arrGroupAllFailed, 'DESTINATION')) === false){
							$arrGroupCheckSend["DESTINATION"] = $target_format;
							$arrGroupCheckSend["REF"] = $target;
							$arrGroupCheckSend["MESSAGE"] = "ไม่สามารถระบุเลขปลายทางได้";
							$arrGroupCheckSend["REF_MESSAGE"] = $dataComing["message_emoji_"];
							$arrGroupAllFailed[] = $arrGroupCheckSend;
						}
					}
					foreach($arrDestGRPNotCorrect as $target){
						$arrGroupCheckSend["DESTINATION"] = $target["TEL"];
						$arrGroupCheckSend["REF"] = $target["TEL"];
						$arrGroupCheckSend["MESSAGE"] = "ไม่สามารถระบุเลขปลายทางได้";
						$arrGroupCheckSend["REF_MESSAGE"] = $dataComing["message_emoji_"];
						$arrGroupAllFailed[] = $arrGroupCheckSend;
					}
					$arrayResult['SUCCESS'] = $arrGroupAllSuccess;
					$arrayResult['FAILED'] = $arrGroupAllFailed;
					$arrayResult['RESULT'] = TRUE;
					require_once('../../../include/exit_footer.php');
				}else{
					$arrayTel = $func->getSMSPerson('all');
					foreach($arrayTel as $dest){
						if(isset($dest["TEL"]) && $dest["TEL"] != ""){
							$arrGroupSuccess["DESTINATION"] = $dest["MEMBER_NO"];
							$arrGroupSuccess["TEL"] = $lib->formatphone(substr($dest["TEL"],0,10),'-');
							$arrGroupAllSuccess[] = $arrGroupSuccess;
						}else{
							$arrGroupCheckSend["DESTINATION"] = $dest["MEMBER_NO"];
							$arrGroupCheckSend["TEL"] = "ไม่พบเบอร์โทรศัพท์";
							$arrGroupAllFailed[] = $arrGroupCheckSend;
						}
					}
					$arrayResult['SUCCESS'] = $arrGroupAllSuccess;
					$arrayResult['FAILED'] = $arrGroupAllFailed;
					$arrayResult['RESULT'] = TRUE;
					require_once('../../../include/exit_footer.php');
				}
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