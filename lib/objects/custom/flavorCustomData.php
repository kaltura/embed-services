<?php

class flavorCustomData {

	var $data;
	private $logger;

	function __construct() {
	    $this->logger = Logger::getLogger("UDRM");
	}

	function run(){
        $this->logger->info("Start UDRM process");
        $timer = new Timer();
        $timer->start();
        $result = array();

	    global $wgKalturaUdrmSecret;
	    global $wgTvpapiAccountId;
	    global $wgAdditionalCasSystemId;
	    global $wgKalturaUdrmEncryptionServer;
        $custom_data = array();
	    $reqData = DataStore::getInstance()->getData("request");
	    if (isset ($reqData) &&
	        isset ($reqData["initObj"]) &&
	        isset ($reqData["initObj"]["SiteGuid"]) &&
	        isset ($reqData["initObj"]["UDID"])){
            $siteGuid = $reqData["initObj"]["SiteGuid"];
            $udid = $reqData["initObj"]["UDID"];
            $additionalCasSystemId = $wgAdditionalCasSystemId;
            if (isset($reqData["groupID"])){
                $additionalCasSystemId = $reqData["groupID"];
            }
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
                        "udid" => $udid,
                        "additional_cas_system" => $additionalCasSystemId
                    ));

                    $this->logger->debug("Flavor UDRM metadata: " . $data);

                    $custom_data = rawurlencode(base64_encode($data));
                    $signature = rawurlencode(base64_encode(sha1($wgKalturaUdrmSecret . $data, true)));

                    $flavorCustomData[ $val["FileID"] ] = array(
                        "custom_data" => $custom_data,
                        "signature" => $signature
                    );

                    $this->logger->debug("Flavor UDRM data: " . json_encode($flavorCustomData[ $val["FileID"] ]));

                }

                $result = array(
                    "flavorData" => $flavorCustomData
                );
            }
        } else {
            $this->logger->warn("UDRM service: Request data not found, skipping UDRM request");
        }
	    
	    $timer->stop();
        $this->logger->info("Finish UDRM process in ".$timer->getTimeMs(). " ms");
	    
	    return $result;
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
            $cUrlError = curl_error($cURL);
            if($cUrlError){
                $this->logger->error("UDRM request failed " . $cUrlError);
                $result = "";
            }
        } catch ( Exception $e){
            $this->logger->error("UDRM request failed " . $e->getMessage());
            $result = "";
        }
        curl_close($cURL);

        return $result;
    }
}
?>