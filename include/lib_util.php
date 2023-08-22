<?php

namespace Utility;

const BAHT_TEXT_NUMBERS = array('ศูนย์', 'หนึ่ง', 'สอง', 'สาม', 'สี่', 'ห้า', 'หก', 'เจ็ด', 'แปด', 'เก้า');
const BAHT_TEXT_UNITS = array('', 'สิบ', 'ร้อย', 'พัน', 'หมื่น', 'แสน', 'ล้าน');
const BAHT_TEXT_ONE_IN_TENTH = 'เอ็ด';
const BAHT_TEXT_TWENTY = 'ยี่';
const BAHT_TEXT_INTEGER = 'ถ้วน';
const BAHT_TEXT_BAHT = 'บาท';
const BAHT_TEXT_SATANG = 'สตางค์';
const BAHT_TEXT_POINT = 'จุด';

class library {
	
	public function generate_token(){
		$data = openssl_random_pseudo_bytes( 16 );
		$data[6] = chr( ord( $data[6] ) & 0x0f | 0x40 );
		$data[8] = chr( ord( $data[8] ) & 0x3f | 0x80 );
		return vsprintf( '%s%s-%s-%s-%s-%s%s%s', str_split( bin2hex( $data ), 4 ) );
	}
	public function convertdate($date,$format="D m Y",$is_time=false){
		if(isset($date)){
			$date = preg_replace('|/|','-',$date);
			$thaimonth = ["","มกราคม","กุมภาพันธ์","มีนาคม","เมษายน","พฤษภาคม","มิถุนายน","กรกฏาคม","สิงหาคม","กันยายน","ตุลาคม","พฤศจิกายน","ธันวาคม"];
			$thaishort = ["","ม.ค.","ก.พ.","มี.ค.","เม.ย.","พ.ค.","มิ.ย.","ก.ค.","ส.ค.","ก.ย.","ต.ค.","พ.ย.","ธ.ค."];
			$arrSeparate = [" ","-","/"];
			if($is_time){
				$dateConvert = date("Y-m-d-H-i-s",strtotime($date));
			}else{
				$dateConvert = date("Y-m-d",strtotime($date));
			}
			$separate;
			foreach($arrSeparate as $sep_value) {
				if(strpos($format, $sep_value)){
					$separate = $sep_value;
					break;
				}
			}
			$datearray = explode('-',$dateConvert);
			$formatArray = explode($separate,$format);
			$dateConverted;
			foreach($formatArray as $key_format => $value_format) {
				if($key_format == 0){
					switch($value_format){
						case "D" :
						case "d" : $dateConverted = $datearray[2];
							break;
						case "Y" : $dateConverted = ($datearray[0]+543);
							break;
						case "y" : $dateConverted = $datearray[0];
							break;				
					}
				}else{
					switch($value_format){
						case "D" :
						case "d" : $dateConverted .= $separate.$datearray[2];
							break;
						case "M" : $dateConverted .= $separate.$thaimonth[$datearray[1]*1];
							break;
						case "m" : $dateConverted .= $separate.$thaishort[$datearray[1]*1];
							break;
						case "N" :
						case "n" : $dateConverted .= $separate.$datearray[1];
							break;
						case "Y" : $dateConverted .= $separate.($datearray[0]+543);
							break;
						case "y" : $dateConverted .= $separate.($datearray[0]);
							break;
					}
				}
			}
			if($is_time){
				$dateConverted .= ' '.$datearray[3].':'.$datearray[4].(isset($datearray[5]) && $datearray[5] > 0 ? ':'.$datearray[5] : null).' น.';
			}
			return $dateConverted;
		}else{
			return '-';
		}
	}
	public function count_duration($date,$format="ym"){
		$date = preg_replace('|/|','-',$date);
		$dateconverted = new \DateTime($date);
		$dateNow = new \DateTime(date('d-m-Y'));
		$date_duration = $dateNow->diff($dateconverted);
		if($format == "ym"){
			return  $date_duration->y ." ปี " .$date_duration->m." เดือน";
		}else if($format == "m"){
			return (($date_duration->y)*12)+($date_duration->m);			
		}else if($format == "d"){
			return $date_duration->days;			
		}     
	}
	public function formatcitizen($idcard,$separate=" "){
		if(isset($idcard)){
			$str1 = substr($idcard,0,1);
			$str2 = substr($idcard,1,4);
			$str3 = substr($idcard,5,5);
			$str4 = substr($idcard,10,2);
			$str5 = substr($idcard,12,1);
			return $str1.$separate.$str2.$separate.$str3.$separate.$str4.$separate.$str5;
		}else{
			return '-';
		}
	}
	public function formatphone($phone,$separate=" "){
		if(isset($phone)){
			$str1 = substr($phone,0,3);
			$str2 = substr($phone,3,3);
			$str3 = substr($phone,6);
			return $str1.$separate.$str2.$separate.$str3;
		}else{
			return '-';
		}
	}
	public function formataccount($account_no,$format) {
		if(isset($account_no) && isset($format)){
			$formatArray = explode('-',$format);
			$account_text = '';
			for($i = 0;$i < sizeof($formatArray);$i++){
				if($i == 0){
					$account_text = substr($account_no,$i,strlen($formatArray[$i]));
				}else{
					$account_text .= '-'.substr($account_no,strlen(preg_replace('/-/','',$account_text)),strlen($formatArray[$i]));
				}
			}
			return $account_text;
		}else{
			return '-';
		}
	}
	public function formataccount_hidden($account_no,$format) {
		if(isset($account_no) && isset($format)){
			$account_text = '';
			if(strpos($account_no,'-') === FALSE){
				$account_no = $this->formataccount($account_no,$format);
			}
			for($i = 0; $i < strlen($account_no);$i++){
				if($format[$i] == 'h'){
					$account_text .= 'x';
				}else{
					$account_text .= $account_no[$i];
				}
			}
			return $account_text;
		}else{
			return '-';
		}
	}
	public function formatcontract($contract_no,$format) {
		if(isset($contract_no) && isset($format)){
			$formatArray = explode('/',$format);
			$contract_text = '';
			for($i = 0;$i < sizeof($formatArray);$i++){
				if($i == 0){
					$contract_text = mb_substr($contract_no,$i,mb_strlen($formatArray[$i]));
				}else{
					$contract_text .= '/'.mb_substr($contract_no,mb_strlen(preg_replace('/-/','',$contract_text)));
				}
			}
			return $contract_text;
		}else{
			return '-';
		}
	}
	public function convertperiodkp($period,$diff_year=false){
		if(isset($period)){
			$thaimonth = ["","มกราคม","กุมภาพันธ์","มีนาคม","เมษายน","พฤษภาคม","มิถุนายน","กรกฏาคม","สิงหาคม","กันยายน","ตุลาคม","พฤศจิกายน","ธันวาคม"];
			$year = substr($period,0,4);
			$monthOne = str_replace('0','',substr($period,4,1));
			$monthTwo= substr($period,5);
			$month = $monthOne.$monthTwo;
			if($diff_year){
				return $thaimonth[$month].' '.($year + 543);
			}else{
				return $thaimonth[$month].' '.($year);
			}
		}else{ 
			return ""; 
		}
	}
	public function mergeTemplate($template_subject,$template_body,$data=[]) {
		$arrayText = array();
		if(isset($template_subject)){
			preg_match_all('/\\${(.*?)\\}/',$template_subject,$arrayColSubject);
			foreach($arrayColSubject[1] as $key => $column){
				if(isset($data[strtoupper($column)])){
					$template_subject = preg_replace('/\\'.$arrayColSubject[0][$key].'/',$data[strtoupper($column)],$template_subject);
				}
			}
		}
		preg_match_all('/\\${(.*?)\\}/',$template_body,$arrayColBody);
		foreach($arrayColBody[1] as $key => $column){
			if(isset($data[strtoupper($column)])){
				$template_body = preg_replace('/\\'.$arrayColBody[0][$key].'/',$data[strtoupper($column)],$template_body);
			}
		}
		if(isset($template_subject)){
			$arrayText["SUBJECT"] = $template_subject;
		}
		$arrayText["BODY"] = $template_body;
		return $arrayText;
	}
	public function sendSMS($arrayDestination,$bulk=false) {
		$json = file_get_contents(__DIR__.'/../config/config_constructor.json');
		$config = json_decode($json,true);
		$arrayGrpSms = array();
		try{
			$clientWS = new \SoapClient($config["URL_CORE_SMS"]."SMScore.svc?wsdl");
			try {
				if($bulk){
					foreach($arrayDestination as $dest){
						$argumentWS = [
							"Member_No" => $dest["member_no"],
							"MobilePhone" => $dest["tel"],
							"Message" => $dest["message"]
						];
						$resultWS = $clientWS->__call("RqSendOTP", array($argumentWS));
						$responseSoap = $resultWS->RqSendOTPResult;
						$arraySms["MEMBER_NO"] = $dest["member_no"];
						$arraySms["RESULT"] = $responseSoap;
						$arrayGrpSms[] = $arraySms;
					}
					return $arrayGrpSms;
				}else{
					$argumentWS = [
						"Member_No" => $arrayDestination["member_no"],
						"MobilePhone" => $arrayDestination["tel"],
						"Message" => $arrayDestination["message"]
					];
					$resultWS = $clientWS->__call("RqSendOTP", array($argumentWS));
					$responseSoap = $resultWS->RqSendOTPResult;
					$arrayGrpSms["MEMBER_NO"] = $dest["member_no"];
					$arrayGrpSms["RESULT"] = $responseSoap;
					return $arrayGrpSms;
				}
			}catch(SoapFault $e){
				$arrayGrpSms["RESULT"] = FALSE;
				return $arrayGrpSms;
			}
		}catch(Throwable $e){
			$arrayGrpSms["RESULT"] = FALSE;
			return $arrayGrpSms;
			return false;
		}
	}
	public function sendMail($email,$subject,$body,$mailFunction,$attachment_path=[]) {
		$json = file_get_contents(__DIR__.'/../config/config_constructor.json');
		$json_data = json_decode($json,true);
		$mailFunction->SMTPDebug = 0;
		$mailFunction->isSMTP();
		$mailFunction->SMTPOptions = [
			'ssl' => [
				'verify_peer' => false,
				'verify_peer_name' => false,
				'allow_self_signed' => true
			]
		];
		$mailFunction->Host = 'mail.gensoft.co.th';
		$mailFunction->SMTPAuth = true;
		$mailFunction->Username = $json_data["MAIL"];
		$mailFunction->Password = $json_data["PASS_MAIL"];
		$mailFunction->SMTPSecure = 'tls';
		$mailFunction->Port = $json_data["PORT_MAILSERVER"] ?? 587;
		$mailFunction->XMailer = 'gensoft.co.th Mailer';
		$mailFunction->CharSet = 'UTF-8';
		$mailFunction->Hostname = 'gensoft.co.th';
		$mailFunction->Helo = 'Gensoft-Mail';
		$mailFunction->Encoding = 'quoted-printable';
		$mailFunction->setFrom($json_data["MAIL"], $json_data["NAME_APP"]);
		$mailFunction->addAddress($email);
		$mailFunction->isHTML(true);
		$mailFunction->Subject = $subject;
		$mailFunction->Body = $body;
		if(sizeof($attachment_path) > 0){
			foreach($attachment_path as $attch_path){
				$mailFunction->addAttachment($attch_path);
			}
		}
		if(!$mailFunction->send()){
			$arrRes["RESULT"] = FALSE;
			$arrRes["MESSAGE_ERROR"] = $mailFunction->ErrorInfo;
			return $arrRes;
		}else{
			$arrRes["RESULT"] = TRUE;
			return $arrRes;
		}
	}

	public function base64_to_img($encode_string,$file_name,$output_file,$webP=null) {
		if(self::getBase64ImageSize($encode_string) < 10000){
			$data_Img = explode(',',$encode_string);
			if(isset($data_Img[1])){
				$dataImg = base64_decode($data_Img[1]);
				$info_img = explode('/',$data_Img[0]);
				$ext_img = str_replace('base64','',$info_img[1]);
				$im_string = imageCreateFromString($dataImg);
				if (!$im_string) {
					return false;
				}else{
					if(isset($webP)){
						$filename = $file_name.'.'.$ext_img;
						$destination = $output_file.'/'.$filename;
						$webP_destination = $output_file.'/'.$file_name.'.webp';
						if($ext_img == 'png'){
							//fix background transparent 
							imagesavealpha($im_string, true);
							imagepng($im_string, $destination, 2);
							$webP->convert($destination,$webP_destination,[]);
							$arrPath = array();
							$arrPath["normal_path"] = $filename;
							$arrPath["webP_path"] = $file_name.'.webp';
							return $arrPath;
						}else if($ext_img == 'jpg' || $ext_img == 'jpeg'){
							imagejpeg($im_string, $destination, 70);
							$webP->convert($destination,$webP_destination,[]);
							$arrPath = array();
							$arrPath["normal_path"] = $filename;
							$arrPath["webP_path"] = $file_name.'.webp';
							return $arrPath;
						}else{
							return false;
						}
					}else{
						$filename = $file_name.'.'.$ext_img;
						$destination = $output_file.'/'.$filename;
						if($ext_img == 'png'){
							//fix background transparent 
							imagesavealpha($im_string, true);
							imagepng($im_string, $destination, 2);
							$arrPath = array();
							$arrPath["normal_path"] = $filename;
							return $arrPath;
						}else if($ext_img == 'jpg' || $ext_img == 'jpeg'){
							imagejpeg($im_string, $destination, 70);
							$arrPath = array();
							$arrPath["normal_path"] = $filename;
							return $arrPath;
						}else{
							return false;
						}
					}
				}
			}
		}else{
			return 'oversize';
		}
	}
	public function base64_to_pdf($encode_string,$file_name,$output_file) {
		$data_Img = explode(',',$encode_string);
		if(isset($data_Img[1])){
			$dataPdf = base64_decode($data_Img[1]);
			$destination = $output_file.'/'.$file_name.'.pdf';
			if(file_put_contents($destination,$dataPdf)){
				$arrPath["normal_path"] = $file_name.'.pdf';
				return $arrPath;
			}else{
				return false;
			}
		}
	}
	private function getBase64ImageSize($base64Image){
		try{
			$size_in_bytes = (int) (strlen(rtrim($base64Image, '=')) * 3 / 4);
			$size_in_kb    = $size_in_bytes / 1024;
			
			return $size_in_kb;
		}
		catch(Exception $e){
			return $e;
		}
	}
	
	public function imgtobase($path){
		$path = __DIR__.'/..'.$path;
		$img_type = pathinfo($path, PATHINFO_EXTENSION);
		$data_img = file_get_contents($path);
		return 'data:image/' . $img_type . ';base64,' . base64_encode($data_img);
	}
	
	public function text_limit($text, $limit = 50, $end = '...'){
		if (mb_strwidth($text, 'UTF-8') <= $limit) {
			return $text;
		}
		return rtrim(mb_strimwidth($text, 0, $limit, '', 'UTF-8')).$end;
	}
	public function randomText($type='number',$length=4){
		if($type == 'number'){
			$characters = '0123456789';
		}else if($type == 'string'){
			$characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
		}else{
			$characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
		}
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}
	public function sendNotify($payload,$type_send){
		$json = file_get_contents(__DIR__.'/../config/config_constructor.json');
		$json_data = json_decode($json,true);
		if (!defined('API_ACCESS_KEY')) define( 'API_ACCESS_KEY', $json_data["FIREBASE_SERVER_KEY"] );
		if($type_send == 'person'){
			$data = [
				"registration_ids" => $payload["TO"],
				"priority" => "high",
				"content_available" => true,
				"notification" => [
					"title" => $payload["PAYLOAD"]["SUBJECT"],
					"body" => $payload["PAYLOAD"]["BODY"],
					"sound" => "default",
					"image" => $payload["PAYLOAD"]["PATH_IMAGE"] ?? null
				],
				"data" => [
					"TYPE" => $payload["TYPE_NOTIFY"] ?? "1",
					"READ_STATUS" => "0",
					"action_page" => $payload["ACTION_PAGE"] ?? "Notification",
					"action_params" => $payload["ACTION_PARAMS"] ?? [ "notificationActive" => $payload["TYPE_NOTIFY"] ?? "1" ],
				]
			];
		}else if($type_send == 'all'){
			$data = [
				"to" => $payload["TO"],
				"priority" => "high",
				"content_available" => true,
				"notification" => [
					"title" => $payload["PAYLOAD"]["SUBJECT"],
					"body" => $payload["PAYLOAD"]["BODY"],
					"sound" => "default",
					"image" => $payload["PAYLOAD"]["PATH_IMAGE"] ?? null
				],
				"data" => [
					"TYPE" => $payload["TYPE_NOTIFY"] ?? "1",
					"READ_STATUS" => "0",
					"action_page" => $payload["ACTION_PAGE"] ?? "Notification",
					"action_params" => $payload["ACTION_PARAMS"] ?? [ "notificationActive" => $payload["TYPE_NOTIFY"] ?? "1" ],
				]
			];
		}
		$headers = [
			 'Authorization: key=' . API_ACCESS_KEY, 
			 'Content-Type: application/json'
		];      
		$ch = curl_init();  

		curl_setopt( $ch,CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send' );                                                                  
		curl_setopt( $ch,CURLOPT_POST, true );  
		curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
		curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode($data));                                                                  
																												 
		$result = curl_exec($ch);
		
		if(isset($result) && $result !== FALSE){
			$resultNoti = json_decode($result);
			curl_close ($ch);
			if(isset($resultNoti)){
				if($resultNoti->success || ($type_send == 'all' && isset($resultNoti->message_id))){
					return true;
				}else{
					return false;
				}
			}else{
				return false;
			}
		}else{
			curl_close ($ch);
			return false;
		}
	}
	private $hw_access_token = null;
    private $hw_token_expiredtime = null;
    public function sendNotifyHW($payload,$type_send){
		$json = file_get_contents(__DIR__.'/../config/config_constructor.json');
		$json_data = json_decode($json,true);
		if (!defined('HW_APPID')) define('HW_APPID', $json_data["HW_APPID"] ?? "");
		if (!defined('HW_APPSECRET')) define('HW_APPSECRET', $json_data["HW_APPSECRET"] ?? "");

		//refresh_token
		if (!isset($this->hw_access_token) || (time() > $this->hw_token_expiredtime)) {
			$header = array(
				"Content-Type: application/x-www-form-urlencoded;charset=utf-8"
			);
			$data = http_build_query(
				[
					"grant_type" => "client_credentials",
					"client_secret" => HW_APPSECRET,
					"client_id" => HW_APPID
				]
			);

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, "https://oauth-login.cloud.huawei.com/oauth2/v2/token");
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			$res = curl_exec($ch);
			curl_close($ch);
				
			if(isset($res) && $res !== FALSE){
				$result = json_decode($res);
				if (!array_key_exists("access_token", $result)) {
					$this->hw_access_token = null;
				}else {
					$this->hw_access_token = $result->access_token;
					$this->hw_token_expiredtime = time() + $result->expires_in;
				}
			}else {
				$this->hw_access_token = null;
			}
		}

		if (!isset($this->hw_access_token)){
			// access_token is null
			return false;
		}
            
		if($type_send == 'all'){
			$data = [
				"message" => [
					"android" => [
						"data" => json_encode(
							[
								"title" => $payload["PAYLOAD"]["SUBJECT"],
								"body" => $payload["PAYLOAD"]["BODY"],
								"image" => $payload["PAYLOAD"]["PATH_IMAGE"] ?? "",
								"action_page" => $payload["ACTION_PAGE"] ?? "Notification",
								"action_params" => json_encode($payload["ACTION_PARAMS"] ?? ["notificationActive" => $payload["TYPE_NOTIFY"] ?? "1"])
							]
						)
					],
					"topic" => str_replace("/topics/", '', $payload["TO"])
				]
			];
		}else {
			$data = [
				"message" => [
					"android" => [
						"data" => json_encode(
							[
								"title" => $payload["PAYLOAD"]["SUBJECT"],
								"body" => $payload["PAYLOAD"]["BODY"],
								"image" => $payload["PAYLOAD"]["PATH_IMAGE"] ?? "",
								"action_page" => $payload["ACTION_PAGE"] ?? "Notification",
								"action_params" => json_encode($payload["ACTION_PARAMS"] ?? ["notificationActive" => $payload["TYPE_NOTIFY"] ?? "1"])
							]
						)
					],
					"token" => $payload["TO"]
				]
			];
		}

		$header = array("Content-Type: application/json", "Authorization: Bearer {$this->hw_access_token}");

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, str_replace('{appid}', HW_APPID, "https://push-api.cloud.huawei.com/v1/{appid}/messages:send"));   
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

		$res = curl_exec($ch);
		curl_close($ch);
		if(isset($res) && $res !== FALSE){
			$resultNoti = json_decode($res);
			if(isset($resultNoti)){
				if($resultNoti->code == "80000000"){
					return true;
				}else{
					return false;
				}    
			}else {
				return false;
			}
		}else {
			return false;
		}
	}

	public function fetch_payloadJWT($token,$jwt_function,$secret_key){
		return $jwt_function->getPayload($token, $secret_key);
	}
	public function checkCompleteArgument($dataincome,$dataComing) {
		foreach($dataincome as $data){
			if(isset($dataComing[$data]) && ($dataComing[$data] == '0' || !empty($dataComing[$data]))){
				continue;
			}else{
				return false;
			}
		}
		return true;
	}
	public function addLogtoTxt($dataLog,$pathfile){
		$dataLog["TIME"] = date("Y-m-d H:i:s");
		file_put_contents(__DIR__.'/../log/'.$pathfile.'.txt', json_encode($dataLog) . PHP_EOL, FILE_APPEND);
	}

	public function getnumberofYear($year){
		$days=0; 
		for($month=1;$month<=12;$month++){ 
			$days = $days + cal_days_in_month(CAL_GREGORIAN,$month,$year);
		}
		return $days;
	}
	public function posting_data($url,$payload,$header=[]) {
		$ch = curl_init( $url );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode($payload) );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array_merge(array('Content-Type: application/json; charset=utf-8', 'Accept: application/json'),$header));
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt( $ch, CURLOPT_TIMEOUT, 300);
		$result = curl_exec($ch);
		if($result){
			curl_close($ch);
			return $result;
		}else{
			$arrayErr = array();
			$arrayErr["RESPONSE_MESSAGE"] = curl_error($ch);
			$arrayErr["RESULT"] = FALSE;
			curl_close ($ch);
			return $arrayErr;
		}
	}
	public function baht_text($number, $include_unit = true, $display_zero = true){
		if (!is_numeric($number)) {
			return null;
		}

		$log = floor(log($number, 10));
		if ($log > 5) {
			$millions = floor($log / 6);
			$million_value = pow(1000000, $millions);
			$normalised_million = floor($number / $million_value);
			$rest = $number - ($normalised_million * $million_value);
			$millions_text = '';
			for ($i = 0; $i < $millions; $i++) {
				$millions_text .= BAHT_TEXT_UNITS[6];
			}
			return $this->baht_text($normalised_million, false) . $millions_text . $this->baht_text($rest, true, false);
		}

		$number_str = (string)floor($number);
		$text = '';
		$unit = 0;

		if ($display_zero && $number_str == '0') {
			$text = BAHT_TEXT_NUMBERS[0];
		} else for ($i = strlen($number_str) - 1; $i > -1; $i--) {
			$current_number = (int)$number_str[$i];

			$unit_text = '';
			if ($unit == 0 && $i > 0) {
				$previous_number = isset($number_str[$i - 1]) ? (int)$number_str[$i - 1] : 0;
				if ($current_number == 1 && $previous_number > 0) {
					$unit_text .= BAHT_TEXT_ONE_IN_TENTH;
				} else if ($current_number > 0) {
					$unit_text .= BAHT_TEXT_NUMBERS[$current_number];
				}
			} else if ($unit == 1 && $current_number == 2) {
				$unit_text .= BAHT_TEXT_TWENTY;
			} else if ($current_number > 0 && ($unit != 1 || $current_number != 1)) {
				$unit_text .= BAHT_TEXT_NUMBERS[$current_number];
			}

			if ($current_number > 0) {
				$unit_text .= BAHT_TEXT_UNITS[$unit];
			}

			$text = $unit_text . $text;
			$unit++;
		}

		if ($include_unit) {
			$text .= BAHT_TEXT_BAHT;

			$satang = explode('.', number_format($number, 2, '.', ''))[1];
			$text .= $satang == 0
				? BAHT_TEXT_INTEGER
				: $this->baht_text($satang, false) . BAHT_TEXT_SATANG;
		} else {
			$exploded = explode('.', $number);
			if (isset($exploded[1])) {
				$text .= BAHT_TEXT_POINT;
				$decimal = (string)$exploded[1];
				for ($i = 0; $i < strlen($decimal); $i++) {
					$text .= BAHT_TEXT_NUMBERS[$decimal[$i]];
				}
			}
		}

		return $text;
	}
	public function mb_str_pad($input,$pad_length="8",$pad_string="0",$pad_style=STR_PAD_LEFT,$encoding="UTF-8"){
		return str_pad($input,strlen($input)-mb_strlen($input,$encoding)+$pad_length,$pad_string,$pad_style);
	}
	public function sendLineNotify($message){
		$json = file_get_contents(__DIR__.'/../config/config_constructor.json');
		$json_data = json_decode($json,true);
		$token = $json_data["LINE_NOTIFY"];
		$headers = array();
		$headers[] = 'content-type: application/x-www-form-urlencoded';
		$headers[] = 'Authorization: Bearer '.$token;
		$ch = curl_init();

		curl_setopt( $ch,CURLOPT_URL, "https://notify-api.line.me/api/notify" );                                                                  
		curl_setopt( $ch,CURLOPT_POST, true );  
		curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
		curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch,CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt( $ch,CURLOPT_POSTFIELDS, "message="." | ".$json_data["COOP_KEY"]." | ".$message);                                                                  
																													 
		curl_exec($ch);
	}
	public function truncateDecimal($amt,$precision){
		$step = pow(10,$precision);
		$value = intval($step * $amt);
		return $value / $step;
	}
	public function roundDecimal($amt,$round_type){
		$amtRound = $this->truncateDecimal($amt,2);
		$amtRaw = $this->truncateDecimal($amtRound,0);
		$fraction = floatval($amtRound - $amtRaw);
		$fractionRaw = $this->truncateDecimal($fraction,1);
		$fracVal = floatval($fraction - $fractionRaw);
		$roundFrac = 0.00;
		switch ($round_type){
			case 1:
				//ปัดที่ละสลึง
				if ($fraction > 0.00 && $fraction <= 0.25) { $roundFrac = 0.25; }
				if ($fraction > 0.25 && $fraction <= 0.50) { $roundFrac = 0.50; }
				if ($fraction > 0.25 && $fraction <= 0.75) { $roundFrac = 0.75; }
				if ($fraction > 0.75 && $fraction <= 0.99) { $roundFrac = 1.00; }
				break;
			case 2:
				//ปัดที่ละ 5 สตางค์
				if ($fracVal == 0.00) { return $amt; }
				if ($fracVal == 0.05) { return $amt; }
				if ($fracVal >= 0.01 && $fracVal <= 0.04) { $fracVal = 0.05; }
				if ($fracVal >= 0.06 && $fracVal <= 0.09) { $fracVal = 0.10; }
				$roundFrac = floatval($fractionRaw) + $fracVal;

				break;
			case 3:
				//ปัดที่ละ 10 สตางค์

				if ($fracVal == 0.00)
				{
					return $amt;
				}
				else
				{
					$fracVal = 0.10;
				}
				$roundFrac = floatval($fractionRaw) + $fracVal;

				break;

			case 4:
				//ปัดเต็มบาท

				if ($fraction > 0.49)
				{
					$roundFrac = 1.00;
				}
				else
				{
					$roundFrac = 0.00;
				}

				break;
			case 99:
				$roundFrac = $fraction;
				break;

			default:
				$roundFrac = $fraction;
				break;
		}
		return $amtRaw + floatval($roundFrac);
	}	
	public function generate_token_access_resource($path,$jwt_function,$secret_key) {
		$payload = array();
		$payload["path"] = $path;
		$payload["exp"] = time() + 900; //2592000;

		return $jwt_function->customPayload($payload, $secret_key);
	}
	public function generate_jwt_token($data,$jwt_function,$secret_key) {
		if (!array_key_exists('exp', $data)) {
			$data["exp"] = time() + 900;
		}
		return $jwt_function->customPayload($data, $secret_key);
	}
}
?>