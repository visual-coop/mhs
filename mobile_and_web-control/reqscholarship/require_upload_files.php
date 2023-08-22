<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','childcard_id'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ScholarshipRequest')){
		$arrFileUploaded = array();
		$checkFileUpload = $conoracle->prepare("SELECT seq_no, document_desc FROM asnreqschshiponlinedet
															WHERE SCHOLARSHIP_YEAR = (EXTRACT(year from sysdate) +543) and CHILDCARD_ID = :child_id and upload_status = 1 order by seq_no");
		$checkFileUpload->execute([':child_id' => $dataComing["childcard_id"]]);
		while($rowFileUpload = $checkFileUpload->fetch(PDO::FETCH_ASSOC)){
			$arrFileUploaded[] = $rowFileUpload;
		}
		$arrUploadFiles = array();
		$arrayFileManda = [
			(object) [
				'seq_no' => 1,
				'document_desc' => 'หน้าปกสมุดผลการศึกษา ระบุข้อมูลชื่อบุตร ชื่อโรงเรียนและชั้นปีการศึกษา (ถ้ามี)',
				'mandatory' => '0',
			],
			(object) [
				'seq_no' => 2,
				'document_desc' => 'ผลการศึกษา ปีการศึกษา '.(date('Y') + 542).' (ภาคเรียนที่ 1) โดยเกรดต้องสมบูรณ์ ไม่ติด 0 ร. มส. มผ. I หรือ F',
				'mandatory' => '1',
			],
			(object) [
				'seq_no' => 3,
				'document_desc' => 'ผลการศึกษา ปีการศึกษา '.(date('Y') + 542).' (ภาคเรียนที่ 2) โดยเกรดต้องสมบูรณ์ ไม่ติด 0 ร. มส. มผ. I หรือ F',
				'mandatory' => '1',
			],
			(object) [
				'seq_no' => 4,
				'document_desc' => 'เอกสารอื่นๆ (ถ้ามี)',
				'mandatory' => '0',
			]
		];
		foreach($arrayFileManda as $fileObj){
			if(array_search($fileObj->seq_no,array_column($arrFileUploaded,'SEQ_NO')) === False){
				$arrayUpload = array();
				$arrayUpload["UPLOAD_NAME"] = date('YmdHis').$fileObj->seq_no;
				$arrayUpload["UPLOAD_SEQ"] = $fileObj->seq_no;
				$arrayUpload["UPLOAD_LABEL"] = $fileObj->document_desc;
				$arrayUpload["IS_MANDATORY"] = $fileObj->mandatory;
				$arrayUpload["IS_UPLOADED"] = 0;
				$arrUploadFiles[] = $arrayUpload;
			}else{
				$arrayUpload = array();
				$arrayUpload["UPLOAD_NAME"] = date('YmdHis').$fileObj->seq_no;
				$arrayUpload["UPLOAD_SEQ"] = $fileObj->seq_no;
				$arrayUpload["UPLOAD_LABEL"] = $fileObj->document_desc;
				$arrayUpload["IS_MANDATORY"] = 0;
				$arrayUpload["IS_UPLOADED"] = 1;
				$arrUploadFiles[] = $arrayUpload;
			}
		}
		$getUploadFiles = $conoracle->prepare("SELECT 5 as seq_no, 'ใบเสร็จค่าเทอม ปีการศึกษา '||(EXTRACT(year from sysdate) +543) as document_desc,'1' as manda
																		FROM ASNREQSCHOLARSHIP 
																		WHERE SCHOLARSHIP_YEAR = (EXTRACT(year from sysdate) +542) and APPROVE_STATUS = 1 and 
																		school_level in ('13', '26', '33', '43', '53', '62') and
																		CHILDCARD_ID = :child_id");
		$getUploadFiles->execute([':child_id' => $dataComing["childcard_id"]]);
		while($rowUploadFile = $getUploadFiles->fetch(PDO::FETCH_ASSOC)){
			if(array_search($rowUploadFile["SEQ_NO"],array_column($arrFileUploaded,'SEQ_NO')) === False){
				$arrayUpload = array();
				$arrayUpload["UPLOAD_NAME"] = date('YmdHis').$rowUploadFile["SEQ_NO"];
				$arrayUpload["UPLOAD_SEQ"] = $rowUploadFile["SEQ_NO"];
				$arrayUpload["UPLOAD_LABEL"] = $rowUploadFile["DOCUMENT_DESC"];
				$arrayUpload["IS_MANDATORY"] = $rowUploadFile["MANDA"];
				$arrayUpload["IS_UPLOADED"] = 0;
				$arrUploadFiles[] = $arrayUpload;
			}else{
					$arrayUpload = array();
				$arrayUpload["UPLOAD_NAME"] = date('YmdHis').$rowUploadFile["SEQ_NO"];
				$arrayUpload["UPLOAD_SEQ"] = $rowUploadFile["SEQ_NO"];
				$arrayUpload["UPLOAD_LABEL"] = $rowUploadFile["DOCUMENT_DESC"];
				$arrayUpload["IS_MANDATORY"] = 0;
				$arrayUpload["IS_UPLOADED"] = 1;
				$arrUploadFiles[] = $arrayUpload;
			}
		}
		$arrayResult['LIST_UPLOAD'] = $arrUploadFiles;
		$arrayResult['RESULT'] = TRUE;
		require_once('../../include/exit_footer.php');
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