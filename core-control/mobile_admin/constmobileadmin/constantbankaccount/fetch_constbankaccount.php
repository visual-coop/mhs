<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantdeptaccount')){
		$arrayGroup = array();
		$fetchConstant = $conmysql->prepare("SELECT
										bank.bank_code,
										bank.bank_name,
										bank.bank_short_name,
										bank.bank_logo_path,
										bank.bank_format_account,
										bank.bank_format_account_hide,
										bank.id_palette,
										color.type_palette,
										color.color_main,
										color.color_secon,
										color.color_deg,
										color.color_text,
										bank.fee_deposit,
										bank.fee_withdraw
									FROM
										csbankdisplay bank
									INNER JOIN gcpalettecolor color ON
										bank.id_palette = color.id_palette");
		$fetchConstant->execute();
		while($rowAccount = $fetchConstant->fetch(PDO::FETCH_ASSOC)){
			$arrConstans = array();
			$arrConstans["ID_PALETTE"] = $rowAccount["id_palette"];
			$arrConstans["BANK_CODE"] = $rowAccount["bank_code"];
			$arrConstans["BANK_NAME"] = $rowAccount["bank_name"];
			$arrConstans["BANK_SHORT_NAME"] = $rowAccount["bank_short_name"];
			$arrConstans["BANK_LOGO_PATH"] = $rowAccount["bank_logo_path"];
			$arrConstans["BANK_FORMAT_ACCOUNT"] = $rowAccount["bank_format_account"];
			$arrConstans["BANK_FORMAT_ACCOUNT_HIDE"] = $rowAccount["bank_format_account_hide"];
			$arrConstans["TYPE_PALETTE"] = $rowAccount["type_palette"];
			$arrConstans["COLOR_MAIN"] = $rowAccount["color_main"];
			$arrConstans["COLOR_SECON"] = $rowAccount["color_secon"];
			$arrConstans["COLOR_DEG"] = $rowAccount["color_deg"];
			$arrConstans["COLOR_TEXT"] = $rowAccount["color_text"];
			$arrConstans["FEE_DEPOSIT"] = $rowAccount["fee_deposit"];
			$arrConstans["FEE_WITHDRAW"] = $rowAccount["fee_withdraw"];
			
			$arrConstans["BANK_CONSTANT"] = [];
			$fetchBankMapping = $conmysql->prepare("SELECT bc.id_bankconstant,
											bc.transaction_cycle,
											bc.max_numof_deposit,
											bc.max_numof_withdraw,
											bc.min_deposit,
											bc.max_deposit,
											bc.min_withdraw,
											bc.max_withdraw,
											bcp.id_bankconstantmapping 
											FROM gcbankconstant bc
											LEFT JOIN gcbankconstantmapping bcp ON bc.id_bankconstant = bcp.id_bankconstant
											WHERE bcp.bank_code = :bank_code AND bcp.is_use = '1'");
			$fetchBankMapping->execute([
				':bank_code' => $rowAccount["bank_code"]
			]);
			while($rowBankMapping = $fetchBankMapping->fetch(PDO::FETCH_ASSOC)){
				$arrMapping = [];
				$arrMapping["ID_BANKCONSTANT"] = $rowBankMapping["id_bankconstant"];
				if($rowBankMapping["transaction_cycle"] == "day"){
					$arrMapping["TRANSACTION_CYCLE"] = "รายวัน";
				}else if($rowBankMapping["transaction_cycle"] == "time"){
					$arrMapping["TRANSACTION_CYCLE"] = "รายครั้ง";
				}else if($rowBankMapping["transaction_cycle"] == "month"){
					$arrMapping["TRANSACTION_CYCLE"] = "รายเดือน";
				}else if($rowBankMapping["transaction_cycle"] == "year"){
					$arrMapping["TRANSACTION_CYCLE"] = "รายปี";
				}else{
					$arrMapping["TRANSACTION_CYCLE"] = $rowBankMapping["transaction_cycle"];
				}
				$arrMapping["MAX_NUMOF_DEPOSIT"] = $rowBankMapping["max_numof_deposit"] == "-1" ? "ไม่จำกัด" : number_format($rowBankMapping["max_numof_deposit"],0)." ครั้ง";
				$arrMapping["MAX_NUMOF_WITHDRAW"] = $rowBankMapping["max_numof_withdraw"] == "-1" ? "ไม่จำกัด" : number_format($rowBankMapping["max_numof_withdraw"],0)." ครั้ง";
				$arrMapping["MIN_DEPOSIT"] = $rowBankMapping["min_deposit"] == "-1" ? "ไม่จำกัด" :  number_format($rowBankMapping["min_deposit"],2)." บาท";
				$arrMapping["MAX_DEPOSIT"] = $rowBankMapping["max_deposit"] == "-1" ? "ไม่จำกัด" :  number_format($rowBankMapping["max_deposit"],2)." บาท";
				$arrMapping["MIN_WITHDRAW"] = $rowBankMapping["min_withdraw"] == "-1" ? "ไม่จำกัด" :  number_format($rowBankMapping["min_withdraw"],2)." บาท";
				$arrMapping["MAX_WITHDRAW"] = $rowBankMapping["max_withdraw"] == "-1" ? "ไม่จำกัด" :  number_format($rowBankMapping["max_withdraw"],2)." บาท";
				$arrMapping["ID_BANKCONSTANTMAPPING"] = $rowBankMapping["id_bankconstantmapping"];
				$arrConstans["BANK_CONSTANT"][] = $arrMapping;
			}
			
			$arrayGroup[] = $arrConstans;
		}
		$arrayResult["BANKACCOUNT_DATA"] = $arrayGroup;
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