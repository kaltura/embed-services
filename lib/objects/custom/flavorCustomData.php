<?php

class flavorCustomData {

	var $data;
	public $requireSerialization = true;

	function __construct() {

	}

	function run(){
	    global $wgKalturaUdrmSecret;
	    global $wgTvpapiAccountId;
	    global $wgKalturaUdrmEncryptionServer;
        $custom_data = array();
	    $reqData = DataStore::getInstance()->getData("request");
	    $siteGuid = $reqData["initObj"]["SiteGuid"];
	    $udid = $reqData["initObj"]["UDID"];
	    $flavorassets = DataStore::getInstance()->getData("flavorassets");
	    $flavorCustomData = array();
	    if (isset($flavorassets["Files"])){
            foreach ($flavorassets["Files"] as $key => $val) {
                $data = json_encode(array(
                    "ca_system" => 'OTT',
                    "user_token" => $siteGuid,
                    "account_id" => $wgTvpapiAccountId,
                    "content_id" => $val["CoGuid"],
                    "files" => "",
                    "udid" => $udid
                ));

                $custom_data = rawurlencode(base64_encode($data));
                $signature = rawurlencode(base64_encode(sha1($wgKalturaUdrmSecret . $data, true)));

                $encryptionUrl = $wgKalturaUdrmEncryptionServer."?custom_data=".$custom_data."&signature=".$signature;
                $response = $this->getJson($encryptionUrl);
                $contentId = "";
                if (isset($response) && is_array($response) && isset($response[0]) && isset($response[0]["key_id"])){
                    $contentId = $response[0]["key_id"];
                }

                $flavorCustomData[ $val["FileID"] ] = array(
                    "license" => array (
                        "custom_data" => $custom_data,
                        "signature" => $signature
                    ),
                    "contentId" => $contentId
                );
            }
	    }
	    return $flavorCustomData;
	}

	function getJson($url){
	global $cUrlTimeout;
        $cURL = curl_init();

        curl_setopt($cURL, CURLOPT_URL, $url);
        curl_setopt($cURL, CURLOPT_HTTPGET, true);
        curl_setopt($cURL, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($cURL, CURLOPT_TIMEOUT, $cUrlTimeout);

        curl_setopt($cURL, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json'
        ));

        try{
            $result = json_decode(curl_exec($cURL), true);
            if(curl_errno($cURL)){
                $result = "";
            }
        } catch ( Exception $e){
            $result = "";
        }
        curl_close($cURL);

        return $result;
    }
}
?>