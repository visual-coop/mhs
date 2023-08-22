<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','userusagereport')){
		$arrayExecute = array();
		$arrayGrpAll = array();
			if(isset($dataComing["start_date"]) && $dataComing["start_date"] != ""){
				$arrayExecute["start_date"] = $dataComing["start_date"];
			}
			if(isset($dataComing["end_date"]) && $dataComing["end_date"] != ""){
				$arrayExecute["end_date"] = $dataComing["end_date"];
			}
			if($dataComing["date_type"] == 'year'){
				$fetchReportUserLogin = $conmysql->prepare("SELECT member_no, device_name, login_date, logout_date, id_token ,is_login
														FROM  gcuserlogin 
														WHERE is_login != '-55'
															
															".(isset($dataComing["start_date"]) && $dataComing["start_date"] != "" ? 
															"and date_format(login_date,'%Y') >= :start_date" : null)."
															".(isset($dataComing["end_date"]) && $dataComing["end_date"] != "" ? 
															"and date_format(login_date,'%Y') <= :end_date" : null). 
													    " ORDER BY login_date DESC");
			}else if($dataComing["date_type"] == 'month'){
				$fetchReportUserLogin = $conmysql->prepare("SELECT member_no, device_name, login_date, logout_date, id_token ,is_login
														FROM  gcuserlogin 
														WHERE is_login != '-55'
															".(isset($dataComing["start_date"]) && $dataComing["start_date"] != "" ? 
															"and date_format(login_date,'%Y-%m') >= :start_date" : null)."
															".(isset($dataComing["end_date"]) && $dataComing["end_date"] != "" ? 
															"and date_format(login_date,'%Y-%m') <= :end_date" : null)." 
													    ORDER BY login_date DESC");
			}else if($dataComing["date_type"] == 'day'){
				$fetchReportUserLogin = $conmysql->prepare("SELECT member_no, device_name, login_date, logout_date, id_token ,is_login
														FROM  gcuserlogin 
														WHERE is_login != '-55'
															".(isset($dataComing["start_date"]) && $dataComing["start_date"] != "" ? 
															"and date_format(login_date,'%Y-%m-%d') >= :start_date" : null)."
															".(isset($dataComing["end_date"]) && $dataComing["end_date"] != "" ? 
															"and date_format(login_date,'%Y-%m-%d') <= :end_date" : null)." 
														ORDER BY login_date DESC");
			}
	
		$fetchReportUserLogin->execute($arrayExecute);

		$summary = 0;
		while($rowUserLogin = $fetchReportUserLogin->fetch(PDO::FETCH_ASSOC)){
			$arrayUserLogin = array();
			$arrayUserLogin["LOGIN_DATE"]==null?"-":$lib->convertdate($rowUserLogin["login_date"],'d m Y',true); 
			$arrayUserLogin["LOGIN_DATE"] = $rowUserLogin["login_date"]==null?"-":$lib->convertdate($rowUserLogin["login_date"],'d m Y',true);		
			$arrayUserLogin["LOGOUT_DATE"] =$rowUserLogin["logout_date"]==null?"-":$lib->convertdate($rowUserLogin["logout_date"],'d m Y',true); 
			$arrayUserLogin["DEVICE_NAME"] = $rowUserLogin["device_name"];
			$arrayUserLogin["IS_LOGIN"] = $rowUserLogin["is_login"];
			$arrayUserLogin["MEMBER_NO"] = $rowUserLogin["member_no"];
	
			$arrayGrpAll[] = $arrayUserLogin;
		}
		$arrayResult['REPORT_USER_LOGIN'] = $arrayGrpAll;
		$arrayResult['RESULT'] = TRUE;
		require_once('../../../include/exit_footer.php');
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