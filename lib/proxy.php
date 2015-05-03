<?php
	/**
	* 
	*/
	class ProxyRequest
	{
		private $response;
		private $logger;

		function __construct($service, $urlTokens){
		    $this->logger = Logger::getLogger("proxy");
			$this->config = $this->getConfig();
			foreach($this->config as $config){
				if (in_array($service, $config["services"])){

				    $this->logger->debug("Found request service ".$service." in config services");
				    if (isset($urlTokens[$config["token"]])){
				        $this->logger->debug("Found request service token ".$config["token"]." in request");
				        if ($config["decodeToken"] == "true"){
						    $this->partnerRequestData = json_decode($urlTokens[$config["token"]]);
						} else {
						    $this->partnerRequestData = $urlTokens[$config["token"]];
						}
						$this->get($config["type"], $config["method"], $config["redirectTo"], $this->partnerRequestData);
						DataStore::getInstance()->setData("request", "", json_decode(json_encode($this->partnerRequestData), true));
						$this->setData($config["dataStores"]);
					}
				}	
			}
		}

		function getConfig(){
		    global $gProxyConfig;
			$data = file_get_contents($gProxyConfig, FILE_USE_INCLUDE_PATH);
			return json_decode($data, TRUE);
		}

		function getDtoConfig($dtoName) {
			$dataStoreName = 'DTO_'.$dtoName;//strtolower(get_class($this));
			//if (!apc_exists($dataStoreName)) {
				//echo "Load from file: ".$dataStoreName;
				$data = file_get_contents('./TVinci/'.$dtoName/*strtolower(get_class($this))*/.".json", FILE_USE_INCLUDE_PATH);
				//apc_store($dataStoreName, $data);
			//} else {
			//	echo "Load from cache: ".$dataStoreName;
			//	$data = apc_fetch($dataStoreName);
			//}

			return json_decode($data, TRUE);
		}
	
		function get($type, $method, $url, $params){
            $this->logger->info("Routing request to ".$url);

            $data = json_encode($this->objectToArray($params), true);

            $this->logger->debug("Routing type: ".$type.", method: ".$method);
            $this->logger->debug("Routing request with params=". $data);

            $start = microtime(true);
			switch($type){
			    case "file":
			        $result = $this->getFile($method, $url, $data);
                    break;
			    case "rest":
			        $result = $this->getRest($method, $url, $data);
                    break;
			}
            $this->response = json_decode($result, true);

            if (empty($this->response) ||
                (is_array($this->response) && count($this->response) == 0)){
                $this->logger->warn("Response is empty");
            }

            $this->logger->debug("Response=". $result);
            $total = microtime(true) - $start;
            $this->logger->info("Response time = ".$total. " seconds");
		}

		function setData($dataStores){
		    $this->logger->info("Set response data in containers");
			foreach ($dataStores as $dataStore => $container) {
			    $this->logger->debug("Set ".$dataStore." data in container ". $container);
				DataStore::getInstance()->setData($dataStore, $container, $this->response);
			}
		}

		function getRest($method, $url, $data = "")
        {
            $curl = curl_init();

            switch ($method)
            {
                case "POST":
                    curl_setopt($curl, CURLOPT_POST, 1);

                    if ($data){
                        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                    }

                    break;
                case "PUT":
                    curl_setopt($curl, CURLOPT_PUT, 1);
                    break;
                default:
                    if ($data)
                        $url = sprintf("%s?%s", $url, http_build_query($data));
            }

            // Optional Authentication:
            //curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            //curl_setopt($curl, CURLOPT_USERPWD, "username:password");

            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_HEADER, true );
            curl_setopt($curl, CURLOPT_USERAGENT, isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '' );
            $ip = $this->getIp();
            if ($ip != ""){
                curl_setopt($curl, CURLOPT_HTTPHEADER, array("REMOTE_ADDR: $ip", "X_FORWARDED_FOR: $ip", "Expect:"));
            }

            $response = curl_exec( $curl );
            $response = preg_split( '/([\r\n][\r\n])\\1/', $response, 2 );

            list( $headers, $contents ) = $response;
            $this->logger->debug("Response headers=". $headers);
            $headers = preg_split( '/[\r\n]+/', $headers );

            foreach($headers as $header) {
                if ( preg_match( '/^(?:Content-Type|Content-Language|Set-Cookie):/i', $header ) ) {
                    header($header);
                }
            }

            return $contents;
		}

		function getIp(){
            $ip = '';
            $http_headers = getallheaders();
            if (isset($http_headers['X-KALTURA-REMOTE-ADDR'])){
                $tempList = explode(',', $http_headers['X-KALTURA-REMOTE-ADDR']);
                $ip = $tempList[0];
                $this->logger->debug("Request origin IP=". $ip);
            } elseif (isset($http_headers['X_KALTURA_REMOTE_ADDR'])){
                $tempList = explode(',', $http_headers['X_KALTURA_REMOTE_ADDR']);
                $ip = $tempList[0];
                $this->logger->debug("Request origin IP=". $ip);
            }else {
                $this->logger->warn('Could not retrieve origin IP');
            }

            return $ip;
        }

		function getFile($method, $url, $data = ""){
            $fileParts = pathinfo($url);
            $data = trim($data, '"');
            if ($fileParts['filename'] == "*"){
                $file = $fileParts['dirname']."/".$data.".json";
            } else {
                $file = $fileParts['dirname']."/".$fileParts['filename']."_".$data.".".$fileParts["extension"];
            }
            $data = @file_get_contents($file, FILE_USE_INCLUDE_PATH);
            return $data;
        }

		function resolveObject($base, $extend){
			$newObj = array();			
			foreach ($base as $key=>$val) {
				if (is_array($val)){
					$newObj[$key] = $this->resolveObject($val, $extend[$key]);
				} else {
					$newObj[$key] = isset($extend[$key]) ? $extend[$key] : $val == "NULL" ? NULL : $val;
				}
			}			
			return $newObj;
		}

		function objectToArray($d) { 
			if (is_object($d)) { 
				$d = get_object_vars($d); 
			}   
			if (is_array($d)) { 
				return array_map(__METHOD__, $d); 
			} else { 
				return $d; 
			} 
		}
	}
?>