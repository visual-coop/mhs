<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','req_status','reqloan_doc'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','loanrequestform')){
		if($dataComing["req_status"] == '1'){
			$approveReqLoan = $conmysql->prepare("UPDATE gcreqloan SET req_status = '1',remark = :remark,approve_date = NOW(),username = :username WHERE reqloan_doc = :reqloan_doc");
			if($approveReqLoan->execute([
				':remark' => $dataComing["remark"] ?? null,
				':username' => $payload["username"],
				':reqloan_doc' => $dataComing["reqloan_doc"]
			])){
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESULT'] = FALSE;
				$arrayResult['RESPONSE'] = "ไม่สามารถอนุมัติใบคำขอนี้ได้ กรุณาติดต่อผู้พัฒนา";
				echo json_encode($arrayResult);
				exit();
			}
		}else if($dataComing["req_status"] == '7'){
			$approveReqLoan = $conmysql->prepare("UPDATE gcreqloan SET req_status = '7',remark = :remark,username = :username WHERE reqloan_doc = :reqloan_doc");
			if($approveReqLoan->execute([
				':remark' => $dataComing["remark"] ?? null,
				':username' => $payload["username"],
				':reqloan_doc' => $dataComing["reqloan_doc"]
			])){
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESULT'] = FALSE;
				$arrayResult['RESPONSE'] = "ไม่สามารถเปลี่ยนสถานะใบคำขอนี้ได้ กรุณาติดต่อผู้พัฒนา";
				echo json_encode($arrayResult);
				exit();
			}
		}else if($dataComing["req_status"] == '-9'){
			$approveReqLoan = $conmysql->prepare("UPDATE gcreqloan SET req_status = '-9',remark = :remark,username = :username WHERE reqloan_doc = :reqloan_doc");
			if($approveReqLoan->execute([
				':remark' => $dataComing["remark"] ?? null,
				':username' => $payload["username"],
				':reqloan_doc' => $dataComing["reqloan_doc"]
			])){
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESULT'] = FALSE;
				$arrayResult['RESPONSE'] = "ไม่สามารถยกเลิกใบคำขอนี้ได้ กรุณาติดต่อผู้พัฒนา";
				echo json_encode($arrayResult);
				exit();
			}
		}
		$getDataReqDoc = $conmysql->prepare("SELECT member_no FROM gcreqloan WHERE reqloan_doc = :reqloan_doc");
		$getDataReqDoc->execute([':reqloan_doc' => $dataComing["reqloan_doc"]]);
		$rowDataReq = $getDataReqDoc->fetch(PDO::FETCH_ASSOC);
		$arrToken = $func->getFCMToken('person',$rowDataReq["member_no"]);
		$templateMessage = $func->getTemplateSystem('LoanRequestForm',1);
		foreach($arrToken["LIST_SEND"] as $dest){
			$dataMerge = array();
			$dataMerge["REQ_STATUS_DESC"] = $configError["REQ_LOAN_STATUS"][0][$dataComing["req_status"]][0]['th'];
			$dataMerge["LOANDOC_NO"] = $dataComing["reqloan_doc"];
			$message_endpoint = $lib->mergeTemplate($templateMessage["SUBJECT"],$templateMessage["BODY"],$dataMerge);
			$arrPayloadNotify["TO"] = array($dest["TOKEN"]);
			$arrPayloadNotify["ACTION_PAGE"] = "LoanRequestTrack";
			$arrPayloadNotify["ACTION_PARAMS"] = [];
			$arrPayloadNotify["MEMBER_NO"] = array($dest["MEMBER_NO"]);
			$arrMessage["SUBJECT"] = $message_endpoint["SUBJECT"];
			$arrMessage["BODY"] = $message_endpoint["BODY"];
			$arrMessage["PATH_IMAGE"] = null;
			$arrPayloadNotify["PAYLOAD"] = $arrMessage;
			$arrPayloadNotify["TYPE_SEND_HISTORY"] = "onemessage";
			if($func->insertHistory($arrPayloadNotify,'2')){
				$lib->sendNotify($arrPayloadNotify,"person");
			}
		}
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>