<?php
ini_set('display_errors', false);
ini_set('error_log', __DIR__ . '/../log/external_error.log');

header("Access-Control-Allow-Methods: POST");

header("Content-type: application/json;charset=utf8");

header('Access-Control-Allow-Origin: *');

header('Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS');

header('Access-Control-Allow-Headers: X-Requested-With,Authorization,Content-Type');


require_once(__DIR__ . '/../extension/vendor/autoload.php');
require_once(__DIR__ . '/../include/connection.php');
require_once(__DIR__ . '/../include/lib_util.php');
require_once(__DIR__ . '/../include/validate_input.php');


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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

	$sql = "
		SELECT * FROM gcbindaccount WHERE member_no = {$body->member_no} AND bank_code = {$body->bank_code} ORDER BY update_date DESC;";

	$getCoopKey = $conmysql->prepare($sql);
	$getCoopKey->execute();
	$result = array();

	if ($getCoopKey->rowCount() > 0) {
		while ($rowCoopKey = $getCoopKey->fetch(PDO::FETCH_ASSOC)) {
			$result[] = $rowCoopKey;
		}
		$arrayResult['RESPONSE_CODE'] = 200;
		$arrayResult['RESPONSE_MESSAGE'] = "Success";
		$arrayResult['RESPONSE_SQL'] = $sql;
		$arrayResult['RESULT'] = TRUE;
		$arrayResult['dataCoop'] = $result;
		print json_encode($arrayResult);
		//echo $sql_sc."\r\n commit;";
		exit();
	} else {
		$arrayResult['RESPONSE_CODE'] = 404;
		$arrayResult['RESPONSE_MESSAGE'] = "Not Found";
		$arrayResult['RESPONSE_SQL'] = $sql;
		$arrayResult['RESULT'] = FALSE;
		//echo json_encode($arrayResult);
		print json_encode($arrayResult);
		exit();
	}

} else {
	http_response_code(500);
}
?>