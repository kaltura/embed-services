<?php
class Multirequest extends BaseObject{

    public $requireSerialization = true;

	function __construct() {
	}

	function isValidService($data) {
         if (isset($data["1:service"]) && $data["1:service"] == "baseEntry"){
            return true;
         } else {
            return false;
        }
    }

	function get() {
	    $baseEntry = new Baseentry();
	    $entryContextData = new EntryContextData();
	    $metaData = new Metadata();
	    $cuePoints = new Cuepoints();
		return array(
			$baseEntry ->get(),
			$entryContextData->get(),
			$metaData->get(),
			$cuePoints->get()
		);
	}
}