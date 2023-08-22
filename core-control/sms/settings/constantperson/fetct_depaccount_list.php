<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','member_no'],$dataComing)){
	if($func->check_permission_core($payload,'sms','manageconstperson')){
		$arrayGroup = array();
		//fetch smsConstantDept
		$fetchConstant = $conoracle->prepare("select deptaccount_no from dpdeptmaster where member_no = :member_no and deptclose_status = 0 ORDER BY deptaccount_no ASC");
		$fetchConstant->execute([
					':member_no' => $dataComing["member_no"]
				]);
		while($rowMenuMobile = $fetchConstant->fetch(PDO::FETCH_ASSOC)){
			$arrConstans = array();
			$arrConstans["DEPTACCOUNT_NO"] = $rowMenuMobile["DEPTACCOUNT_NO"];
			$arrayGroup[] = $arrConstans;
		}
		$arrayResult["DEPTACC_DATA"] = $arrayGroup;
		$arrayResult["RESULT"] = TRUE;
		require_once('../../../../include/exit_footer.php');
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../../../include/exit_footer.php');
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../../include/exit_footer.php');
}
?>