<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','childcard_id'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'AssistRequest')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$checkRightsReq = $conoracle->prepare("SELECT child_name as CHILD_NAME FROM ASNREQSCHOLARSHIP 
																WHERE CHILDCARD_ID = :child_id and SCHOLARSHIP_YEAR = (EXTRACT(year from sysdate) +543) and APPROVE_STATUS <> -9");
		$checkRightsReq->execute([':child_id' => $dataComing["childcard_id"]]);
		$rowRights = $checkRightsReq->fetch(PDO::FETCH_ASSOC);
		if(empty($rowRights["CHILD_NAME"])){
			$checkReqStatus = $conoracle->prepare("SELECT CHILDCARD_ID,REQUEST_STATUS, CANCEL_REMARK FROM asnreqschshiponline 
															WHERE SCHOLARSHIP_YEAR = (EXTRACT(year from sysdate) +543) and CHILDCARD_ID = :child_id");
			$checkReqStatus->execute([':child_id' => $dataComing["childcard_id"]]);
			$rowReqStatus = $checkReqStatus->fetch(PDO::FETCH_ASSOC);
			if(isset($rowReqStatus["CHILDCARD_ID"])){
				if($rowReqStatus["REQUEST_STATUS"] == 1 || $rowReqStatus["REQUEST_STATUS"] == 11){
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
					$arrayResult['RESPONSE_MESSAGE'] = str_replace('${REMARK}',($rowReqStatus["CANCEL_REMARK"] ?? "เหตุผลบางประการ"),$configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale]);
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
					
				}
			}else{
				$conoracle->beginTransaction();
				$insertSchShipOnline = $conoracle->prepare("INSERT INTO asnreqschshiponline(scholarship_year, member_no, childcard_id, request_status)
																				VALUES((EXTRACT(year from sysdate) +543),:member_no,:child_id,8)");
				if($insertSchShipOnline->execute([
					':member_no' => $member_no,
					':child_id' => $dataComing["childcard_id"]
				])){
					$insertSchShipOnlineDoc = $conoracle->prepare("INSERT INTO asnreqschshiponlinedet(scholarship_year, member_no, childcard_id, seq_no, document_desc, upload_status)
																						(SELECT (EXTRACT(year from sysdate) +543), :member_no, :child_id, seq_no, document_desc, 8 
																							FROM(
																									SELECT 1 as seq_no, 
																										'หน้าปกสมุดผลการศึกษา (ถ้ามี)'			as document_desc
																									from dual
																									union
																									SELECT 2 as seq_no, 
																										'ผลการศึกษา ปีการศึกษา '||(EXTRACT(year from sysdate) +542)||' (ภาคเรียนที่ 1)' as document_desc
																									from dual
																									union
																									SELECT 3 as seq_no, 
																										'ผลการศึกษา ปีการศึกษา '||(EXTRACT(year from sysdate) +542)||' (ภาคเรียนที่ 2)' as document_desc
																									from dual
																									union
																									SELECT 4 as seq_no, 
																										'เอกสารอื่นๆ (ถ้ามี)' as document_desc
																									from dual
																									union
																									SELECT 5 as seq_no, 
																										'ใบเสร็จค่าเทอม ปีการศึกษา '||(EXTRACT(year from sysdate) +543) as document_desc
																									FROM ASNREQSCHOLARSHIP 
																									WHERE SCHOLARSHIP_YEAR = (EXTRACT(year from sysdate) +542) and APPROVE_STATUS = 1 and 
																									school_level in ('13', '26', '33', '43', '53', '62') and
																									CHILDCARD_ID = :child_id ))");
					if($insertSchShipOnlineDoc->execute([
						':member_no' => $member_no,
						':child_id' => $dataComing["childcard_id"]
					])){
						$conoracle->commit();
						$arrayResult['RESULT'] = TRUE;
						require_once('../../include/exit_footer.php');
					}else{
						$conoracle->rollback();
						$arrayResult['RESPONSE_CODE'] = "WS1032";
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						$arrayResult['RESULT'] = FALSE;
						require_once('../../include/exit_footer.php');
						
					}
				}else{
					$conoracle->rollback();
					$filename = basename(__FILE__, '.php');
					$logStruc = [
						":error_menu" => $filename,
						":error_code" => "WS1032",
						":error_desc" => "ไม่สามารถ Insert ลง  "."\n".json_encode($dataComing),
						":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
					];
					$log->writeLog('errorusage',$logStruc);
					$message_error = "ไม่สามารถเปลี่ยนรหัสผ่านได้เพราะไม่สามารถบังคับอุปกรณ์อื่นออกจากระบบได้"."\n"."Data => ".json_encode($dataComing)."\n"."Payload".json_encode($payload);
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