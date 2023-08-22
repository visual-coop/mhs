<?php
ini_set('display_errors', false);
ini_set('error_log', __DIR__ . '/../log/external_error.log');
header("Access-Control-Allow-Methods: POST");
header("Content-type: application/json;charset=utf8");

require_once(__DIR__ . '/../extension/vendor/autoload.php');
require_once(__DIR__ . '/../include/connection.php');
require_once(__DIR__ . '/../include/lib_util.php');
require_once(__DIR__ . '/../include/validate_input.php');

header('Access-Control-Allow-Origin: *');

header('Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS');

header('Access-Control-Allow-Headers: X-Requested-With,Authorization,Content-Type');


use Utility\library;
use Connection\connection;

$con = new connection();
$lib = new library();
$conmysql = $con->connecttomysql();
$conoracle = $con->connecttooracle();

$lang_locale = "th";

$jsonConfig = file_get_contents(__DIR__ . '/../config/config_constructor.json');
$config = json_decode($jsonConfig, true);
$jsonConfigError = file_get_contents(__DIR__ . '/../config/config_indicates_error.json');
$configError = json_decode($jsonConfigError, true);

$body = json_decode(file_get_contents('php://input'));
$row = count($body);

$sql = "";
$table = "";


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if ($body->actionFlag == 0) {
		$sql = "UPDATE gcbindaccount SET bindaccount_status = '-9' WHERE member_no = '{$body->member_no}' AND bank_code = '{$body->bank_code}';";
	} else if ($body->actionFlag == 1) {
		$currentDate = date('Y-m-d H:i:s');
		$sql = "UPDATE gcbindaccount SET 
		bindaccount_status = '1', deptaccount_no_bank = '{$body->bank_account_no}'
		,update_date = '{$currentDate}' 
		WHERE sigma_key = '{$body->bind_sigma_key}'";

	} else if ($body->actionFlag == 2) {

		$placeHolders = ":sigma_key,:member_no,:deptaccount_no_coop,:deptaccount_no_bank,:citizen_id,:mobile_no,
			:bank_account_name,:bank_account_name_en,:bank_code,:consent_date,:bind_date,:update_date,:unbind_date,:bindaccount_status,
			:id_token,:reg_ref,:account_payfee";

		$gc_column = "sigma_key,member_no,deptaccount_no_coop,deptaccount_no_bank,citizen_id,mobile_no,
		bank_account_name,bank_account_name_en,bank_code,consent_date,bind_date,update_date,unbind_date,bindaccount_status,
		id_token,reg_ref,account_payfee";

		foreach ($body->data as $key) {

			$value = array(
				'sigma_key' => $key->sigma_key,
				'member_no' => $key->member_no,
				'deptaccount_no_coop' => $key->deptaccount_no_coop,
				'deptaccount_no_bank' => $key->deptaccount_no_bank,
				'citizen_id' => $key->citizen_id,
				'mobile_no' => $key->mobile_no,
				'bank_account_name' => $key->bank_account_name,
				'bank_account_name_en' => $key->bank_account_name_en,
				'bank_code' => $key->bank_code,
				'consent_date' => $key->consent_date,
				'bind_date' => $key->bind_date,
				'update_date' => $key->update_date,
				'unbind_date' => $key->unbind_date,
				'bindaccount_status' => $key->bindaccount_status,
				'id_token' => $key->id_token,
				'reg_ref' => $key->reg_ref,
				'account_payfee' => $key->account_payfee
			);

			$sql = "INSERT INTO gcbindaccount ($gc_column) VALUES ($placeHolders)";
			$result = $conmysql->prepare($sql);
			$result->execute($value);
		}

		$arrayResult['RESPONSE_CODE'] = 200;
		$arrayResult['RESPONSE_MESSAGE'] = "Success";
		//$arrayResult['RESPONSE_SQL'] = $sql;
		$arrayResult['RESULT'] = TRUE;
		print json_encode($arrayResult);
		exit();
	}


	if ($sql != '') {
		$result = $conmysql->prepare($sql);
		$result->execute();

		if ($result) {
			$arrayResult['RESPONSE_CODE'] = 200;
			$arrayResult['RESPONSE_MESSAGE'] = "Success";
			$arrayResult['RESPONSE_SQL'] = $sql;
			$arrayResult['RESULT'] = TRUE;
			print json_encode($arrayResult);
			exit();
		} else {
			$arrayResult['RESPONSE_CODE'] = 404;
			$arrayResult['RESPONSE_MESSAGE'] = "Not Found";
			$arrayResult['RESPONSE_SQL'] = $sql;
			$arrayResult['RESULT'] = FALSE;
			print json_encode($arrayResult);
			exit();
		}
	} else {
		$arrayResult['RESPONSE_CODE'] = 400;
		$arrayResult['RESPONSE_MESSAGE'] = "Not Found BANK";
		$arrayResult['RESPONSE_SQL'] = $sql;
		$arrayResult['RESULT'] = FALSE;
		print json_encode($arrayResult);
		exit();
	}


} else {
	http_response_code(500);
}
?>