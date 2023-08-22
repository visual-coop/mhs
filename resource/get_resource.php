<?php

require_once(__DIR__.'/../extension/vendor/autoload.php');

use ReallySimpleJWT\{Parse,Encode,Validate,Jwt};
use ReallySimpleJWT\Exception\ValidateException;

$jsonConfig = file_get_contents(__DIR__.'/../config/config_constructor.json');
$config = json_decode($jsonConfig,true);

function validateJWT($api_token, $secret_key) {
    if (isset($api_token)) {
        $jwt = new Jwt($api_token, $secret_key);
        $parse_token = new Parse($jwt, new Validate(), new Encode());
        $arrayReturn = array();
        try {
            $parsed_token = $parse_token->validate()
                ->validateExpiration()
                ->parse();
            $payload = $parsed_token->getPayload();
            $arrayReturn["PAYLOAD"] = $payload;
            $arrayReturn["VALIDATE"] = true;
            return $arrayReturn;
        } catch (ValidateException $e) {
            $arrayReturn["ERROR_MESSAGE"] = $e->getMessage();
            $arrayReturn["VALIDATE"] = false;
            return $arrayReturn;
        }
    } else {
        $arrayReturn["ERROR_MESSAGE"] = "Not found token";
        $arrayReturn["VALIDATE"] = false;
        return $arrayReturn;
    }
}

function access_to_file($path) {
	$path_with_prefix = preg_replace("/\?.*/", "", __DIR__."/..$path");
    if (file_exists($path_with_prefix)) {
        header("Content-type: " . mime_content_type($path_with_prefix));
        header("Content-Length: " . filesize($path_with_prefix));
        header('Accept-Ranges: bytes');

        readfile($path_with_prefix);
    } else {
        not_found();
    }
}

function deny_access() {
    http_response_code(403);
    echo "403 Forbidden";
}

function not_found() {
	http_response_code(404);
	echo "404 not found";
}


if (isset($_GET["id"])) {
	if (isset($_GET["token"])) {
		$get_request_token = base64_decode($_GET["token"]);
		$token_validated = validateJWT($get_request_token, $config["SECRET_KEY_JWT"]);
		if ($token_validated["VALIDATE"] === true) {
			if (isset($token_validated["PAYLOAD"]["path"]) && ($_GET["id"] == hash("sha256", $token_validated["PAYLOAD"]["path"]))) {
				access_to_file($token_validated["PAYLOAD"]["path"]);
			} else {
				deny_access();
			}
		} else {
			deny_access();
		}
	} else {
		if (isset($_SERVER['HTTP_RESOURCE_TOKEN'])) {
			$token_validated = validateJWT($_SERVER['HTTP_RESOURCE_TOKEN'], $config["SECRET_KEY_JWT"]);
			if ($token_validated["VALIDATE"] === true) {
				if (isset($token_validated["PAYLOAD"]["path"]) && ($_GET["id"] == hash("sha256", $token_validated["PAYLOAD"]["path"]))) {
					access_to_file($token_validated["PAYLOAD"]["path"]);
				} else {
					deny_access();
				}
			} else {
				deny_access();
			}
		} else {
			deny_access();
		}
	}	
} else {
	deny_access();
}