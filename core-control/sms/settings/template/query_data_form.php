<?php
ini_set("memory_limit","-1");
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','query_message_spc_'],$dataComing)){
	if($func->check_permission_core($payload,'sms','managetemplate')){
		if(strtolower(substr(trim($dataComing["query_message_spc_"]),0,6)) === "select"){
			$arrayData = array();
			$arrColumn = array();
			$queryDataForm = $conoracle->prepare($dataComing["query_message_spc_"]);
			$queryDataForm->execute();
			while($rowData = $queryDataForm->fetch(PDO::FETCH_ASSOC)){
				$arrDataForm = array();
				$arrColumn = array_keys($rowData);
				foreach($arrColumn as $column_name){
					$arrDataForm[$column_name] = $rowData[$column_name];
				}
				$arrayData[] = $arrDataForm;
			}
			$arrayResult['DATA'] = $arrayData;
			$arrayResult['COLUMN'] = $arrColumn;
			$arrayResult['RESULT'] = TRUE;
			require_once('../../../../include/exit_footer.php');
		}else{
			$arrayResult['RESPONSE'] = "คำสั่งนี้ ไม่ได้รับอนุญาตให้ใช้งานได้";
			$arrayResult['RESULT'] = FALSE;
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