<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','loanrequestform')){
		$arrGrp = array();
		$arrayType = array();
		$arrayExecute = array();
		$getLoanTypeDesc = $conoracle->prepare("SELECT loantype_desc,loantype_code FROM lnloantype");
		$getLoanTypeDesc->execute();
		while($rowType = $getLoanTypeDesc->fetch(PDO::FETCH_ASSOC)){
			$arrayType[$rowType["LOANTYPE_CODE"]] = $rowType["LOANTYPE_DESC"];
		}
		
		if(isset($dataComing["req_status"]) && $dataComing["req_status"] != ""){
			$arrayExecute[':req_status'] = $dataComing["req_status"];
		}
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
		if(isset($dataComing["req_status"]) && $dataComing["req_status"] != ""){
			$getAllReqDocno = $conmysql->prepare("SELECT reqloan_doc,member_no,loantype_code,request_amt,period_payment,period,loanpermit_amt,salary_img,citizen_img,req_status,request_date,approve_date,contractdoc_url
															FROM gcreqloan WHERE req_status = :req_status". 
															($dataComing["is_filtered"] ? (
															(isset($dataComing["filter_member_no"]) && $dataComing["filter_member_no"] != '' ? " and member_no = :filter_member_no" : null).
															(isset($dataComing["filter_req_docno"]) && $dataComing["filter_req_docno"] != '' ? " and reqloan_doc = :filter_req_docno" : null).
															(isset($dataComing["start_date"]) && $dataComing["start_date"] != "" ? " and date_format(request_date,'%Y-%m-%d') >= :start_date" : null).
															(isset($dataComing["end_date"]) && $dataComing["end_date"] != "" ? " and date_format(request_date,'%Y-%m-%d') <= :end_date" : null)
															) 
															: " AND DATE_FORMAT(request_date, '%Y-%m-%d') >= now()-interval 3 month"));
			$getAllReqDocno->execute($dataComing["is_filtered"] ? $arrayExecute : [':req_status' => $dataComing["req_status"]]);
			while($rowDocno = $getAllReqDocno->fetch(PDO::FETCH_ASSOC)){
				$arrDocno = array();
				$arrDocno["REQDOC_NO"] = $rowDocno["reqloan_doc"];
				$arrDocno["MEMBER_NO"] = $rowDocno["member_no"];
				
					$fetchMember = $conoracle->prepare("SELECT mp.prename_short,mb.memb_name,mb.memb_surname,
									mb.member_no,mb.MEMBGROUP_CODE 
									FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
									WHERE mb.member_no = :member_no");
				$fetchMember->execute([
					':member_no' => $rowDocno["member_no"]
				]);
				
				$arrDocno["FULLNAME"] = null;
				$arrDocno["MEMBGROUP_CODE"] = null;
				while($rowMember = $fetchMember->fetch(PDO::FETCH_ASSOC)){
					$arrDocno["FULLNAME"] = $rowMember["PRENAME_SHORT"].$rowMember["MEMB_NAME"]." ".$rowMember["MEMB_SURNAME"];
					$arrDocno["MEMBGROUP_CODE"] = $rowMember["MEMBGROUP_CODE"];
				}
			
				$arrDocno["LOANTYPE_CODE"] = $rowDocno["loantype_code"];
				$arrDocno["LOANTYPE_DESC"] = $arrayType[$rowDocno["loantype_code"]];
				$arrDocno["REQUEST_AMT"] = number_format($rowDocno["request_amt"],2);
				//$arrDocno["PERIOD_PAYMENT"] = number_format($rowDocno["period_payment"],2);
				$arrDocno["REQUEST_DATE"] = $lib->convertdate($rowDocno["request_date"],'d m Y',true);
				if($rowDocno["req_status"] == '1'){
					$arrDocno["APPROVE_DATE"] = $lib->convertdate($rowDocno["approve_date"],'d m Y',true);
				}
				$arrDocno["PERIOD"] = $rowDocno["period"];
				$arrDocno["LOANPERMIT_AMT"] = number_format($rowDocno["loanpermit_amt"],2);
				$arrDocno["SALARY_IMG"] = $rowDocno["salary_img"];
				$arrDocno["CITIZEN_IMG"] = $rowDocno["citizen_img"];
				$arrDocno["CONTRACTDOC_URL"] = $rowDocno["contractdoc_url"];
				$arrDocno["REQ_STATUS"]  = $rowDocno["req_status"];
				if($rowDocno["req_status"] == '8'){
					$arrDocno["REQ_STATUS_DESC"] = "รอลงรับ";
				}else if($rowDocno["req_status"] == '1'){
					$arrDocno["REQ_STATUS_DESC"] = "อนุมัติ";
				}else if($rowDocno["req_status"] == '-9'){
					$arrDocno["REQ_STATUS_DESC"] = "ไม่อนุมัติ";
				}else if($rowDocno["req_status"] == '7'){
					$arrDocno["REQ_STATUS_DESC"] = "ลงรับรอตรวจสิทธิ์เพิ่มเติม";
				}else{
					$arrDocno["REQ_STATUS_DESC"] = "ยกเลิก";
				}
				$arrGrp[] = $arrDocno;
			}
		}else{
			$getAllReqDocno = $conmysql->prepare("SELECT reqloan_doc,member_no,loantype_code,request_amt,period_payment,period,loanpermit_amt,salary_img,citizen_img,req_status,request_date,approve_date,contractdoc_url
															FROM gcreqloan WHERE 1=1". 
															($dataComing["is_filtered"] ? (
															(isset($dataComing["filter_member_no"]) && $dataComing["filter_member_no"] != '' ? " and member_no = :filter_member_no" : null).
															(isset($dataComing["filter_req_docno"]) && $dataComing["filter_req_docno"] != '' ? " and reqloan_doc = :filter_req_docno" : null).
															(isset($dataComing["start_date"]) && $dataComing["start_date"] != "" ? " and date_format(request_date,'%Y-%m-%d') >= :start_date" : null).
															(isset($dataComing["end_date"]) && $dataComing["end_date"] != "" ? " and date_format(request_date,'%Y-%m-%d') <= :end_date" : null)
															) 
															: " AND DATE_FORMAT(request_date, '%Y-%m-%d') >= now()-interval 3 month"));
			$getAllReqDocno->execute($dataComing["is_filtered"] ? $arrayExecute : []);
			while($rowDocno = $getAllReqDocno->fetch(PDO::FETCH_ASSOC)){
				$arrDocno = array();
				$arrDocno["REQDOC_NO"] = $rowDocno["reqloan_doc"];
				$arrDocno["MEMBER_NO"] = $rowDocno["member_no"];
				
				$fetchMember = $conoracle->prepare("SELECT mp.prename_short,mb.memb_name,mb.memb_surname,
									mb.member_no,mb.MEMBGROUP_CODE 
									FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
									WHERE mb.member_no = :member_no");
				$fetchMember->execute([
					':member_no' => $rowDocno["member_no"]
				]);
				
				$arrDocno["FULLNAME"] = null;
				$arrDocno["MEMBGROUP_CODE"] = null;
				while($rowMember = $fetchMember->fetch(PDO::FETCH_ASSOC)){
					$arrDocno["FULLNAME"] = $rowMember["PRENAME_SHORT"].$rowMember["MEMB_NAME"]." ".$rowMember["MEMB_SURNAME"];
					$arrDocno["MEMBGROUP_CODE"] = $rowMember["MEMBGROUP_CODE"];
				}
				
				$arrDocno["LOANTYPE_CODE"] = $rowDocno["loantype_code"];
				$arrDocno["LOANTYPE_DESC"] = $arrayType[$rowDocno["loantype_code"]];
				$arrDocno["REQUEST_AMT"] = number_format($rowDocno["request_amt"],2);
				//$arrDocno["PERIOD_PAYMENT"] = number_format($rowDocno["period_payment"],2);
				$arrDocno["REQUEST_DATE"] = $lib->convertdate($rowDocno["request_date"],'d m Y',true);
				if($rowDocno["req_status"] == '1'){
					$arrDocno["APPROVE_DATE"] = $lib->convertdate($rowDocno["approve_date"],'d m Y',true);
				}
				$arrDocno["PERIOD"] = $rowDocno["period"];
				$arrDocno["LOANPERMIT_AMT"] = number_format($rowDocno["loanpermit_amt"],2);
				$arrDocno["SALARY_IMG"] = $rowDocno["salary_img"];
				$arrDocno["CITIZEN_IMG"] = $rowDocno["citizen_img"];
				$arrDocno["CONTRACTDOC_URL"] = $rowDocno["contractdoc_url"];
				$arrDocno["REQ_STATUS"]  = $rowDocno["req_status"];
				if($rowDocno["req_status"] == '8'){
					$arrDocno["REQ_STATUS_DESC"] = "รอลงรับ";
				}else if($rowDocno["req_status"] == '1'){
					$arrDocno["REQ_STATUS_DESC"] = "อนุมัติ";
				}else if($rowDocno["req_status"] == '-9'){
					$arrDocno["REQ_STATUS_DESC"] = "ไม่อนุมัติ";
				}else if($rowDocno["req_status"] == '7'){
					$arrDocno["REQ_STATUS_DESC"] = "ลงรับรอตรวจสิทธิ์เพิ่มเติม";
				}else{
					$arrDocno["REQ_STATUS_DESC"] = "ยกเลิก";
				}
				$arrGrp[] = $arrDocno;
			}
		}
		$arrayResult['REQ_LIST'] = $arrGrp;
		$arrayResult['REQ_MESSAGE'] = $dataComing["is_filtered"] ? null : "รายการใบคำขอกู้ 3 เดือนล่าสุด";
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