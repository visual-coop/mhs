<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','loandata'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constanttypeloan')){
		$arrayGroup = array();
		$arrayLoanCheckGrp = array();
		$fetchLoanTypeCheck = $conmysql->prepare("SELECT LOANTYPE_CODE,IS_CREDITLOAN,IS_LOANREQUEST FROM gcconstanttypeloan");
		$fetchLoanTypeCheck->execute();
		while($rowLoantypeCheck = $fetchLoanTypeCheck->fetch(PDO::FETCH_ASSOC)){
			$arrayLoanCheck = $rowLoantypeCheck;
			$arrayLoanCheckGrp[] = $arrayLoanCheck;
		}
		$fetchLoantype = $conoracle->prepare("SELECT LOANTYPE_CODE,LOANTYPE_DESC FROM LNLOANTYPE ORDER BY LOANTYPE_CODE ASC");
		$fetchLoantype->execute();
		while($rowLoantype = $fetchLoantype->fetch(PDO::FETCH_ASSOC)){
			$arrayLoantype = array();
			if(array_search($rowLoantype["LOANTYPE_CODE"],array_column($arrayLoanCheckGrp,'LOANTYPE_CODE')) === False){
				$arrayLoantype["IS_CREDITLOAN"] = "0";
				$arrayLoantype["IS_LOANREQUEST"] = "0";
			}else{
				$arrayLoantype["IS_CREDITLOAN"] = $arrayLoanCheckGrp[array_search($rowLoantype["LOANTYPE_CODE"],array_column($arrayLoanCheckGrp,'LOANTYPE_CODE'))]["IS_CREDITLOAN"];
				$arrayLoantype["IS_LOANREQUEST"] = $arrayLoanCheckGrp[array_search($rowLoantype["LOANTYPE_CODE"],array_column($arrayLoanCheckGrp,'LOANTYPE_CODE'))]["IS_LOANREQUEST"];
			}
			$arrayLoantype["LOANTYPE_CODE"] = $rowLoantype["LOANTYPE_CODE"];
			$arrayLoantype["LOANTYPE_DESC"] = $rowLoantype["LOANTYPE_DESC"];
			$arrayGroup[] = $arrayLoantype;
		}
		if($dataComing["loandata"] !== $arrayGroup){
			$resultUDiff = array_udiff($dataComing["loandata"],$arrayGroup,function ($loanChange,$loanOri){
				if ($loanChange === $loanOri){
					return 0;
				}else{
					return ($loanChange>$loanOri) ? 1 : -1;
				}
			});
			foreach($resultUDiff as $value_diff){
				if(array_search($value_diff["LOANTYPE_CODE"],array_column($arrayLoanCheckGrp,'LOANTYPE_CODE')) === False){
					$insertBulkCont[] = "('".$value_diff["LOANTYPE_CODE"]."','".$value_diff["IS_CREDITLOAN"]."','".$value_diff["IS_LOANREQUEST"]."')";
					$insertBulkContLog[]='LOANTYPE_CODE=> '.$value_diff["LOANTYPE_CODE"].' IS_CREDITLOAN ='.$value_diff["IS_CREDITLOAN"].' IS_LOANREQUEST ='.$value_diff["IS_LOANREQUEST"];
				}else{
					$updateConst = $conmysql->prepare("UPDATE gcconstanttypeloan SET IS_CREDITLOAN = :IS_CREDITLOAN,IS_LOANREQUEST = :IS_LOANREQUEST WHERE LOANTYPE_CODE = :LOANTYPE_CODE");
					$updateConst->execute([
						':IS_CREDITLOAN' => $value_diff["IS_CREDITLOAN"],
						':IS_LOANREQUEST' => $value_diff["IS_LOANREQUEST"],
						':LOANTYPE_CODE' => $value_diff["LOANTYPE_CODE"]
					]);
					$updateConstLog = 'LOANTYPE_CODE=> '.$value_diff["LOANTYPE_CODE"].' IS_CREDITLOAN ='.$value_diff["IS_CREDITLOAN"].' IS_CREDITLOAN='.$value_diff["IS_LOANREQUEST"];
				}
			}
			$insertConst = $conmysql->prepare("INSERT gcconstanttypeloan(LOANTYPE_CODE,IS_CREDITLOAN,IS_LOANREQUEST)
															VALUES".implode(',',$insertBulkCont));
			$insertConst->execute();
			$arrayStruc = [
				':menu_name' => "constanttypeloan",
				':username' => $payload["username"],
				':use_list' =>"edit constant typeloan",
				':details' => implode(',',$insertBulkContLog).' '.$updateConstLog
			];
			$arrayResult['dataOld'] = $arrayGroup;
			$log->writeLog('manageuser',$arrayStruc);	
			$arrayResult['RESULT'] = TRUE;
			require_once('../../../../include/exit_footer.php');
		}else{
			$arrayResult['RESULT'] = FALSE;
			$arrayResult['RESPONSE'] = "ข้อมูลไม่มีการเปลี่ยนแปลง กรุณาเลือกทำรายการ";
			require_once('../../../../include/exit_footer.php');
		}
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