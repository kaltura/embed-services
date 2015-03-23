<?php

require_once (dirname(__FILE__)."/Configuration/defaultSettings.php");
include_once (dirname(__FILE__).'/Utils/logger/Logger.php');
include_once ($gLoggerConfig);

//Ignore logger errors in production
$currErrorLevel = error_reporting();
error_reporting(0);
Logger::configure($loggerConfiguration);
error_reporting($currErrorLevel);

$logger = Logger::getLogger("main");
$logger->info("Start process");
$logger->info("Request received: ".$_SERVER["REQUEST_URI"]);
$start = microtime(true);

$qs       = $_SERVER['QUERY_STRING'];
$main     = new Main();
$response = $main->resolveRequest($qs);
print_r($response);
$total = microtime(true) - $start;

$logger->info("Finish process in ".$total. " seconds");

class Main {

	function __construct() {
	    $requiredModules = array('/lib', '/lib/objects', '/lib/objects/custom', '/Utils', '/lib/kaltura_client_v3', '/lib/kaltura_client_v3/KalturaPlugins');
	    foreach ($requiredModules as $requiredModule) {
	        $this->loadModules($requiredModule);
	    }
	}

	function loadModules($folderName) {
		// load all plugins
		$pluginsFolder = realpath(dirname(__FILE__)).$folderName;
		if (is_dir($pluginsFolder)) {
			$dir = dir($pluginsFolder);
			while (false !== $fileName = $dir->read()) {
				$matches = null;
				if (preg_match('/^([^.]+).php$/', $fileName, $matches)) {
					require_once ("$pluginsFolder/$fileName");
				}
			}
		}
	}

	function resolveRequest($request) {
	    $logger = Logger::getLogger("main");
		parse_str(urldecode($request), $tokens);
		$service = isset($tokens["service"]) ? $tokens["service"] : "";
		if (!empty($service) && isset($service) && class_exists($service, false)) {
			$logger->info("Request service ".$service);
			$serviceHandler = call_user_func(array(ucfirst($service), 'getClass'));
			if ($serviceHandler->isValidService($tokens)){
			    $response = $serviceHandler->run($tokens);
			} else {
                $response = new stdClass;
            }

            if (isset($tokens["callback"])){
                return $tokens["callback"]."(".json_encode($response, true).");";
            } else {
                if ($serviceHandler->requireSerialization){
                    $response = @serialize($response);
                }
                return $response;
            }
		} else {
		    $logger->warn("Tries to request service ".$service." and service wasn't found!");
			return array("message" => "service not found!");
		}
	}
}
?>