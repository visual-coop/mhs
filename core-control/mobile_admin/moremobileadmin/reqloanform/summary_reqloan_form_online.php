<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','loanrequestform')){
		$arrGrp = null;
		$arrayType = array();
		$arrayExecute = array();
		
		if(isset($dataComing["filter_member_no"]) && $dataComing["filter_member_no"] != ""){
			$arrayExecute[':filter_member_no'] = strtolower($lib->mb_str_pad($dataComing["filter_member_no"]));
		}
		if(isset($dataComing["filter_req_docno"]) && $dataComing["filter_req_docno"] != ""){
			$arrayExecute[':filter_req_docno'] = $dataComing["filter_req_docno"];
		}
		if(isset($dataComing["start_date"]) && $dataComing["start_date"] != ""){
			$arrayExecute[':start_date'] = $dataComing["start_date"];
		}
		if(isset($dataComing["end_date"]) && $dataComing["end_date"] != ""){
			$arrayExecute[':end_date'] = $dataComing["end_date"];
		}
		
		$filterQuery = ($dataComing["is_filtered"] ? (
												(isset($dataComing["filter_member_no"]) && $dataComing["filter_member_no"] != '' ? " and member_no = :filter_member_no" : null).
												(isset($dataComing["filter_req_docno"]) && $dataComing["filter_req_docno"] != '' ? " and reqloan_doc = :filter_req_docno" : null).
												(isset($dataComing["start_date"]) && $dataComing["start_date"] != "" ? " and date_format(request_date,'%Y-%m-%d') >= :start_date" : null).
												(isset($dataComing["end_date"]) && $dataComing["end_date"] != "" ? " and date_format(request_date,'%Y-%m-%d') <= :end_date" : null)
												) 
												: " AND DATE_FORMAT(request_date, '%Y-%m-%d') >= now()-interval 3 month");
												
		$getAllReqDocno = $conmysql->prepare("SELECT COUNT(reqloan_doc) AS COUNT_WAITING,
															(SELECT COUNT(reqloan_doc) AS COUNT_PROCESSING FROM gcreqloan WHERE req_status = '7'".$filterQuery.") AS COUNT_PROCESSING,
															(SELECT COUNT(reqloan_doc) AS COUNT_CANCEL FROM gcreqloan WHERE req_status = '9'".$filterQuery.") AS COUNT_CANCEL,
															(SELECT COUNT(reqloan_doc) AS COUNT_DISAPPROVAL FROM gcreqloan WHERE req_status = '-9'".$filterQuery.") AS COUNT_DISAPPROVAL,
															(SELECT COUNT(reqloan_doc) AS COUNT_APPROVE FROM gcreqloan WHERE req_status = '1'".$filterQuery.") AS COUNT_APPROVE
															FROM gcreqloan WHERE req_status = '8'".$filterQuery);
															
		$getAllReqDocno->execute($dataComing["is_filtered"] ? $arrayExecute : []);
		while($rowDocno = $getAllReqDocno->fetch(PDO::FETCH_ASSOC)){
				$arrGrp = $rowDocno;
		}
		
		$arrayResult['COUNT_REQ'] = $arrGrp;
		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
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