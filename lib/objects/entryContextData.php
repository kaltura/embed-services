<?php

class EntryContextData extends BaseObject {

	var $data;
	public $requireSerialization = true;

	function __construct() {		
	}

	function get() {
		$res = $this->resolveDtoList("KalturaEntryContextDataResult", NULL, true);
		$flavorAssets = new FlavorAssets();
		$flavorAssets->setClientConfiguration($this->rawDataString);
		$result = $flavorAssets->get();
		$res->flavorAssets = $result;
		return $res;	
	}
}
?>