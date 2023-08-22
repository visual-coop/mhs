<?php
require_once('../autoload.php');

use Dompdf\Dompdf;

$dompdf = new DOMPDF();

if($lib->checkCompleteArgument(['menu_component','account_no','request_date'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DepositStatement')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$fetchMail = $conmysql->prepare("SELECT email FROM gcmemberaccount WHERE member_no = :member_no");
		$fetchMail->execute([':member_no' => $payload["member_no"]]);
		$rowMail = $fetchMail->fetch(PDO::FETCH_ASSOC);
		$arrayAttach = array();
		$account_no = preg_replace('/-/','',$dataComing["account_no"]);
		$getCardPerson = $conoracle->prepare("SELECT card_person FROM mbmembmaster WHERE member_no = :member_no");
		$getCardPerson->execute([':member_no' => $member_no]);
		$rowCardPerson = $getCardPerson->fetch(PDO::FETCH_ASSOC);
		$passwordPDF = filter_var($rowCardPerson["CARD_PERSON"], FILTER_SANITIZE_NUMBER_INT);
		foreach($dataComing["request_date"] as $date_between){
			$fetchDataSTM = $conoracle->prepare("SELECT dpt.DEPTITEMTYPE_DESC AS TYPE_TRAN,dpt.SIGN_FLAG,dps.DEPTSLIP_NO,
																		dps.operate_date as OPERATE_DATE,dps.DEPTITEM_AMT as TRAN_AMOUNT,dps.PRNCBAL 
																		FROM dpdeptstatement dps LEFT JOIN DPUCFDEPTITEMTYPE dpt ON dps.DEPTITEMTYPE_CODE = dpt.DEPTITEMTYPE_CODE
																		WHERE dps.deptaccount_no = :account_no and dps.operate_date BETWEEN to_date(:datebefore,'YYYY-MM-DD') and to_date(:dateafter,'YYYY-MM-DD')
																		ORDER BY dps.SEQ_NO DESC");
			$fetchDataSTM->execute([
				':account_no' => $account_no,
				':datebefore' => $date_between[0],
				':dateafter' => $date_between[1]
			]);
			$arraySTMGrp = array();
			while($rowDataSTM = $fetchDataSTM->fetch(PDO::FETCH_ASSOC)){
				$arraySTM = array();
				$arraySTM["TYPE_TRAN"] = $rowDataSTM["TYPE_TRAN"];
				$arraySTM["SIGN_FLAG"] = $rowDataSTM["SIGN_FLAG"];
				$arraySTM["DEPTSLIP_NO"] = $rowDataSTM["DEPTSLIP_NO"];
				$arraySTM["OPERATE_DATE"] = $lib->convertdate($rowDataSTM["OPERATE_DATE"],'d m Y');
				$arraySTM["TRAN_AMOUNT"] = $rowDataSTM["TRAN_AMOUNT"];
				$arraySTM["PRNCBAL"] = $rowDataSTM["PRNCBAL"];
				$arraySTMGrp[] = $arraySTM;
			}
			$arrayData["STATEMENT"] = $arraySTMGrp;
			$arrayData["MEMBER_NO"] = $payload["member_no"];
			$arrayData["DEPTACCOUNT_NO"] = $lib->formataccount_hidden($account_no,$func->getConstant('hidden_dep'));
			$arrayData["DATE_BETWEEN_FORMAT"] = $lib->convertdate($date_between[0],'d m Y').' - '.$lib->convertdate($date_between[1],'d m Y');
			$arrayData["DATE_BETWEEN"] = $date_between[0].'-'.$date_between[1];
			$arrayGenPDF = generatePDFSTM($dompdf,$arrayData,$lib,$passwordPDF);
			if($arrayGenPDF["RESULT"]){
				$arrayAttach[] = $arrayGenPDF["PATH"];
			}
		}
		$arrayDataTemplate = array();
		$arrayDataTemplate["ACCOUNT_NO"] = $lib->formataccount_hidden($account_no,$func->getConstant('hidden_dep'));
		$template = $func->getTemplateSystem('DepositStatement');
		$arrResponse = $lib->mergeTemplate($template["SUBJECT"],$template["BODY"],$arrayDataTemplate);
		$arrMailStatus = $lib->sendMail($rowMail["email"],$arrResponse["SUBJECT"],$arrResponse["BODY"],$mailFunction,$arrayAttach);
		if($arrMailStatus["RESULT"]){
			foreach($arrayAttach as $path){
				unlink($path);
			}
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS0019",
				":error_desc" => "ส่งเมลไม่ได้ ".$rowMail["email"]."\n"."Error => ".$arrMailStatus["MESSAGE_ERROR"],
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$arrayResult['RESPONSE_CODE'] = "WS0019";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
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

function generatePDFSTM($dompdf,$arrayData,$lib,$password){
	$dompdf = new DOMPDF();
	//style table
	  $html = '<style>
			@font-face {
			  font-family: TH Niramit AS;
			  src: url(../../resource/fonts/TH Niramit AS.ttf);
			}
			@font-face {
				font-family: TH Niramit AS;
				src: url(../../resource/fonts/TH Niramit AS Bold.ttf);
				font-weight: bold;
			}
			* {
			  font-family: TH Niramit AS;
			}

		  body {
			margin-top: 3.6cm;
			margin-bottom:0.5cm;
			padding: 10px 0 0 0;
		  }
		  tr {
			border-right: 0.5px #DDDDDD solid;	
			border-left:0.5px #DDDDDD solid;	
		  }
		  td {
			border-bottom: 0.5px #DDDDDD solid;
		  }
		  table {
			border-spacing: -1px;
			width: 100%;
			border: 0.5px #DDDDDD solid;
		  }
		  th {
			text-align:center;
			color:white;
			padding: 5px;
			font-size: 20px;
			font-weight:bold;
			background-color:#0C6DBF;
			border: 0.5px #DDDDDD solid;	
		  }
		  td{
			padding:5px;
			font-size: 18px;
		  }
		  p{
			margin:0px;
		  }
		  header {
			position: fixed;
			top: 0cm;
			left: 0cm;
			right: 0cm;
			color: #000; 
		}
		.label-type {
			font-weight: bold;
			font-size: 20px;
			padding-top: 80px;
		}
		.frame-info-user {
			line-height: 12px;
			padding: 10px -10px 10px 10px;
			position: fixed;
			left: 440px;
			top: 30px;
			width: 260px;
			height: 90px;
			border: 0.5px #DDDDDD solid;
			border-radius: 5px;
		}

		.label {
			width: 30%;
			padding: 0 5px;
			font-size: 18px;
		}
		  </style>
		';
	//head table
	$html .='
	 <div style="text-align: center;margin-bottom: 0px;" padding:0px; margin-bottom:20px; width:100%;></div>
	<header>
	<div style="position:fixed;">
			   <div style="padding:0px;"><img src="../../resource/logo/logo.jpg" style="width:50px "></div>
			   <div style=" position: fixed;top:2px; left: 60px; font-size:20px; font-weight:bold;">
					สหกรณ์ออมทรัพย์ครูแม่ฮ่องสอน จำกัด (สำนักงานใหญ่)
			   </div>
			   <div style=" position: fixed;top:25px; left: 60px;font-size:20px">
					MAEHONGSON TEACHER SAVING CO-OPERATIVE LIMTED
			   </div>
			   </div>
				<div class="frame-info-user">
					<div style="display:flex;width: 100%;padding-top: -20px;">
					<div class="label">เลขสมาชิก</div>
					<div style="padding-left: 90px;font-weight: bold;font-size: 17px;">'.$arrayData["MEMBER_NO"].'</div>
					</div>
					<div style="display:flex;width: 100%;padding-top: -20px;">
					<div class="label">เลขบัญชีเงินฝาก</div>
					<div style="padding-left: 90px;font-weight: bold;font-size: 17px;">'.$arrayData["DEPTACCOUNT_NO"].'</div>
					</div>
					<div style="display:flex;width: 100%">
					<div class="label">ระหว่างวันที่</div>
					<div style="padding-left: 90px;font-weight: bold;font-size: 17px;">'.$arrayData["DATE_BETWEEN_FORMAT"].'</div>
					</div>
				</div>
			   <div class="label-type">
			   <p style="font-size: 25px;">รายการเดินบัญชีเงินฝาก</p>
			   </div>
			</header>';
	$html .='<main>';
	$html .= '  <div style="width: 100%;">
	<table >
	  <thead>
		<tr>
		  <th style="text-align:center;width:80px;">วัน เดือน ปี</th>
		  <th>รายการ</th>
		  <th>ฝาก</th>
		  <th>ถอน</th>
		  <th>ยอดคงเหลือ</th>
		  <th>เลขอ้างอิง</th>
		</tr>
	  </thead>

	  <tbody>

	';
	// table value
	$count_deposit = 0;
	$count_withdraw = 0;
	$count_sumall = 0;
	$sum_deposit = 0;
	$sum_withdraw = 0;
	$sum_all = 0;
	foreach($arrayData["STATEMENT"] as $stm){
		$count_sumall++;
		$sum_all += $stm["TRAN_AMOUNT"];
	  $html .= '
		<tr>
		  <td style="text-align:center;width:70px;">'.$stm["OPERATE_DATE"].'</td>
		  <td style="text-align:left">'.$stm["TYPE_TRAN"].'</td>';
			if($stm["SIGN_FLAG"] == '1'){
				$count_deposit++;
				$sum_deposit += $stm["TRAN_AMOUNT"];
				$html .= '<td style="text-align:right">'.number_format($stm["TRAN_AMOUNT"],2).'</td>
				 <td style="text-align:right"></td>';
			}else{
				$count_withdraw++;
				$sum_withdraw += $stm["TRAN_AMOUNT"];
				$html .= ' <td style="text-align:right"></td>
				<td style="text-align:right">'.number_format($stm["TRAN_AMOUNT"],2).'</td>';
			}
		  $html .= '<td style="text-align:right">'.number_format($stm["PRNCBAL"],2).'</td>
		  <td style="text-align:center">'.($stm["DEPTSLIP_NO"] ?? "-").'</td>
		</tr>
	';
	}
	//list sum
	$html .='
	  <tr>
		<td ></td>
		<td ><b>รายการถอน '.$count_withdraw.' รายการ</b></td>
		<td ></td>
		<td style="text-align:right"><b>'.number_format($sum_withdraw,2).'</b></td>
		<td ></td>
		<td ></td>
	  </tr>
	  <tr>
		<td ></td>
		<td ><b>รายการฝาก '.$count_deposit.' รายการ</b></td>
		<td style="text-align:right"><b>'.number_format($sum_deposit,2).'</b></td>
		<td ></td>
		<td ></td>
		<td ></td>
	  </tr>
	  <tr>
		<td style="border-bottom:1px solid #000;" ></td>
		<td style="border-bottom:1px solid #000;" ><b>ยอดรวมทุกรายการ '.$count_sumall.' รายการ</b></td>
		<td style="border-bottom:1px solid #000;" ></td>
		<td style="border-bottom:1px solid #000;" ></td>
		<td style="border-bottom:1px solid #000; text-align:right;" ><b>'.number_format($sum_all,2).'</b></td>
		<td style="border-bottom:1px solid #000;" ></td>
	  </tr>
	';

	$html .='</tbody></table>';
	$html .= '</div>';
	$html .='</main>';
	$dompdf = new Dompdf([
		'fontDir' => realpath('../../resource/fonts'),
		'chroot' => realpath('/'),
		'isRemoteEnabled' => true
	]);

	$dompdf->set_paper('A4');
	$dompdf->load_html($html);
	$dompdf->render();
	$pathOutput = __DIR__."/../../resource/pdf/statement/".$arrayData['DEPTACCOUNT_NO']."_".$arrayData["DATE_BETWEEN"].".pdf";
	$dompdf->getCanvas()->page_text(520,  25, "หน้า {PAGE_NUM} / {PAGE_COUNT}","", 12, array(0,0,0));
	$dompdf->getCanvas()->get_cpdf()->setEncryption($password);
	$output = $dompdf->output();
	if(file_put_contents($pathOutput, $output)){
		$arrayPDF["RESULT"] = TRUE;
	}else{
		$arrayPDF["RESULT"] = FALSE;
	}
	$arrayPDF["PATH"] = $pathOutput;
	return $arrayPDF;
}
?>