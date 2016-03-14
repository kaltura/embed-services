<?php
class Multirequest extends BaseObject{

    public $requireSerialization = true;
    private $serviceName = "multirequest";

	function __construct() {
	}

	function isValidService($data) {
         $key = array_search("baseentry", array_map('strtolower', $data));
         if (($key !== false) && (strpos($key, ":service") !== false)){
            return true;
         } else {
            return false;
        }
    }

    function run($tokens){
        $request = new ProxyRequest($this->serviceName, $tokens);
        $this->setClientConfiguration($tokens);
        return $this->get();
    }

	function get() {
	    $baseEntry = new Baseentry();
	    $baseEntry->setClientConfiguration($this->rawDataString);
	    $entryContextData = new EntryContextData();
	    $entryContextData->setClientConfiguration($this->rawDataString);
	    $metaData = new Metadata();
	    $metaData->setClientConfiguration($this->rawDataString);
	    $cuePoints = new Cuepoints();
	    $cuePoints->setClientConfiguration($this->rawDataString);
		return array(
			$baseEntry ->get(),
			$entryContextData->get(),
			$metaData->get(),
			$cuePoints->get()
		);
	}
}