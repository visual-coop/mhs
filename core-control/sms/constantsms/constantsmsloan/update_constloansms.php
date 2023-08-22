<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','contdata'],$dataComing)){
	if($func->check_permission_core($payload,'sms','constantsmsloan')){
		$arrayGroup = array();
		$arrayChkG = array();
		$fetchConstant = $conmysql->prepare("SELECT
																		id_smsconstantloan,
																		loan_itemtype_code,
																		allow_smsconstantloan
																	FROM
																		smsconstantloan
																	ORDER BY loan_itemtype_code ASC");
		$fetchConstant->execute();
		while($rowMenuMobile = $fetchConstant->fetch(PDO::FETCH_ASSOC)){
			$arrConstans = array();
			$arrConstans["ID_SMSCONSTANTLOAN"] = $rowMenuMobile["id_smsconstantloan"];
			$arrConstans["LOANITEMTYPE_CODE"] = $rowMenuMobile["loan_itemtype_code"];
			$arrConstans["ALLOW_SMSCONSTANTLOAN"] = $rowMenuMobile["allow_smsconstantloan"];
			$arrayChkG[] = $arrConstans;
		}
		$fetchDepttype = $conoracle->prepare("SELECT LOANITEMTYPE_CODE,LOANITEMTYPE_DESC FROM LNUCFLOANITEMTYPE ORDER BY LOANITEMTYPE_CODE ASC");
		$fetchDepttype->execute();
		while($rowDepttype = $fetchDepttype->fetch(PDO::FETCH_ASSOC)){
			$arrayDepttype = array();
				if(array_search($rowDepttype["LOANITEMTYPE_CODE"],array_column($arrayChkG,'LOANITEMTYPE_CODE')) === False){
						$arrayDepttype["ALLOW_SMSCONSTANTLOAN"] = 0;
				}else{
					$arrayDepttype["ALLOW_SMSCONSTANTLOAN"] = $arrayChkG[array_search($rowDepttype["LOANITEMTYPE_CODE"],array_column($arrayChkG,'LOANITEMTYPE_CODE'))]["ALLOW_SMSCONSTANTLOAN"];
				}
				
			$arrayDepttype["LOANITEMTYPE_CODE"] = $rowDepttype["LOANITEMTYPE_CODE"];
			$arrayDepttype["LOANITEMTYPE_DESC"] = $rowDepttype["LOANITEMTYPE_DESC"];
			$arrayGroup[] = $arrayDepttype;
		}
		
			if($dataComing["contdata"] !== $arrayGroup){
				$resultUDiff = array_udiff($dataComing["contdata"],$arrayGroup,function ($loanChange,$loanOri){
					if ($loanChange === $loanOri){
						return 0;
					}else{
						return ($loanChange>$loanOri) ? 1 : -1;
					}
				});
				foreach($resultUDiff as $value_diff){
					if(array_search($value_diff["LOANITEMTYPE_CODE"],array_column($arrayChkG,'LOANITEMTYPE_CODE')) === False){
						$insertBulkCont[] = "('".$value_diff["LOANITEMTYPE_CODE"]."','".$value_diff["ALLOW_SMSCONSTANTLOAN"]."')";
						$insertBulkContLog[]='LOANITEMTYPE_CODE=> '.$value_diff["LOANITEMTYPE_CODE"].' ALLOW_SMSCONSTANTLOAN ='.$value_diff["ALLOW_SMSCONSTANTLOAN"];
					}else{
						$updateConst = $conmysql->prepare("UPDATE smsconstantloan SET allow_smsconstantloan = :ALLOW_SMSCONSTANTLOAN WHERE loan_itemtype_code = :LOANITEMTYPE_CODE");
						$updateConst->execute([
							':ALLOW_SMSCONSTANTLOAN' => $value_diff["ALLOW_SMSCONSTANTLOAN"],
							':LOANITEMTYPE_CODE' => $value_diff["LOANITEMTYPE_CODE"]
						]);
						$updateConstLog = 'LOANITEMTYPE_CODE=> '.$value_diff["LOANITEMTYPE_CODE"].' ALLOW_SMSCONSTANTLOAN='.$value_diff["ALLOW_SMSCONSTANTLOAN"];
					}
				}
				$insertConst = $conmysql->prepare("INSERT smsconstantloan(loan_itemtype_code,allow_smsconstantloan)
																VALUES".implode(',',$insertBulkCont));
				$insertConst->execute();
				$arrayStruc = [
					':menu_name' => "constantsmsloan",
					':username' => $payload["username"],
					':use_list' =>"edit constant sms loan",
					':details' => implode(',',$insertBulkContLog).' '.$updateConstLog
				];
				$log->writeLog('editsms',$arrayStruc);	
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