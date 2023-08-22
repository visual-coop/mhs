<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'sms','manageconstperson')){
		//edit list
		if($dataComing["edit_list"]){
			$conmysql->beginTransaction();
			foreach($dataComing["edit_list"] as $update_list){
				if($update_list["PAY_TYPE"] == '1'){
					$updatelist = $conmysql->prepare("UPDATE smsconstantperson SET smscsp_mindeposit = :mindeposit, smscsp_minwithdraw = :minwithdraw,
											is_mindeposit = :is_mindeposit,is_minwithdraw = :is_minwithdraw,smscsp_pay_type = :pay_type,request_flat_date = :effect_date
											 WHERE smscsp_account = :account_no");
					if($updatelist->execute([
						':mindeposit' => $update_list["MINDEPOSIT"],
						':minwithdraw' => $update_list["MINWITHDRAW"],
						':account_no' => $update_list["DEPTACCOUNT_NO"],
						':is_mindeposit' => $update_list["IS_MINDEPOSIT"] ? '1' : '0',
						':is_minwithdraw' => $update_list["IS_MINWITHDRAW"] ? '1' : '0',
						':pay_type' => $update_list["PAY_TYPE"],
						':effect_date' => date("Ym")
					])){
						continue;
					}else{
						$conmysql->rollback();
						$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขรายการได้ กรุณาติดต่อผู้พัฒนา";
						$arrayResult['RESULT'] = FALSE;
						require_once('../../../../include/exit_footer.php');
					}
				}else{
					$updatelist = $conmysql->prepare("UPDATE smsconstantperson SET smscsp_mindeposit = :mindeposit, smscsp_minwithdraw = :minwithdraw,
											is_mindeposit = :is_mindeposit,is_minwithdraw = :is_minwithdraw,smscsp_pay_type = :pay_type,request_flat_date = null
											 WHERE smscsp_account = :account_no");
					if($updatelist->execute([
						':mindeposit' => $update_list["MINDEPOSIT"],
						':minwithdraw' => $update_list["MINWITHDRAW"],
						':account_no' => $update_list["DEPTACCOUNT_NO"],
						':is_mindeposit' => $update_list["IS_MINDEPOSIT"] ? '1' : '0',
						':is_minwithdraw' => $update_list["IS_MINWITHDRAW"] ? '1' : '0',
						':pay_type' => $update_list["PAY_TYPE"],
					])){
						continue;
					}else{
						$conmysql->rollback();
						$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขรายการได้ กรุณาติดต่อผู้พัฒนา";
						$arrayResult['RESULT'] = FALSE;
						require_once('../../../../include/exit_footer.php');
					}
				}
				
			}
			$conmysql->commit();
		}
		
		//insert list
		if($dataComing["insert_list"]){
			$conmysql->beginTransaction();
			$bulkInsert = array();
			foreach($dataComing["insert_list"] as $insert_list){
				if($insert_list["PAY_TYPE"] == '1'){
					$bulkInsert[] = "('".$insert_list["DEPTACCOUNT_NO"]."','".$insert_list["MEMBER_NO"]."',
											'".$insert_list["MINDEPOSIT"]."','".$insert_list["MINWITHDRAW"]."','".($insert_list["IS_MINDEPOSIT"] ? "1" : "0")."',
											'".($insert_list["IS_MINWITHDRAW"] ? "1" : "0")."',
											'".$insert_list["PAY_TYPE"]."','".date("Ym")."')";
				}else{
					$bulkInsert[] = "('".$insert_list["DEPTACCOUNT_NO"]."','".$insert_list["MEMBER_NO"]."',
											'".$insert_list["MINDEPOSIT"]."','".$insert_list["MINWITHDRAW"]."','".($insert_list["IS_MINDEPOSIT"] ? "1" : "0")."',
											'".($insert_list["IS_MINWITHDRAW"] ? "1" : "0")."',
											'".$insert_list["PAY_TYPE"]."',null)";

				}
			}
			$insertlist = $conmysql->prepare("INSERT INTO smsconstantperson (smscsp_account,smscsp_member_no,smscsp_mindeposit,smscsp_minwithdraw,
												is_mindeposit,is_minwithdraw,smscsp_pay_type,request_flat_date) 
												VALUES".implode(',',$bulkInsert)." ON DUPLICATE KEY UPDATE
												smscsp_mindeposit = VALUES(smscsp_mindeposit), smscsp_minwithdraw = VALUES(smscsp_minwithdraw), 
												is_use = '1',smscsp_pay_type = VALUES(smscsp_pay_type),request_flat_date = VALUES(request_flat_date)");
			if($insertlist->execute()){
				$conmysql->commit();
			}else{
				$conmysql->rollback();
				$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มรายการได้ กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
			}
		}
		
		//delete list
		if($dataComing["delete_list"]){
			$conmysql->beginTransaction();
			foreach($dataComing["delete_list"] as $delete_list){
				$deletelist = $conmysql->prepare("UPDATE smsconstantperson SET smscsp_mindeposit = :mindeposit, smscsp_minwithdraw = :minwithdraw,
										is_mindeposit = :is_mindeposit,is_minwithdraw = :is_minwithdraw,smscsp_pay_type = :pay_type,is_use = '0',request_flat_date = null
										 WHERE smscsp_account = :account_no");
				if($deletelist->execute([
					':mindeposit' => $delete_list["MINDEPOSIT"],
					':minwithdraw' => $delete_list["MINWITHDRAW"],
					':account_no' => $delete_list["DEPTACCOUNT_NO"],
					':is_mindeposit' => $delete_list["IS_MINDEPOSIT"] ? '1' : '0',
					':is_minwithdraw' => $delete_list["IS_MINWITHDRAW"] ? '1' : '0',
					':pay_type' => $delete_list["PAY_TYPE"],
				])){
					continue;
				}else{
					$conmysql->rollback();
					$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขรายการได้ กรุณาติดต่อผู้พัฒนา";
					$arrayResult['RESULT'] = FALSE;
					require_once('../../../../include/exit_footer.php');
				}
			}
			$conmysql->commit();
		}
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