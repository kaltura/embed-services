<?php

class FlavorAssets extends BaseObject {

	var $data;
	public $requireSerialization = true;

	function __construct() {
		$this->data = $this->getData();
	}

	function get() {
		return $this->resolveDtoList("KalturaFlavorAsset");	
	}
}
?>