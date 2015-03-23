<?php

class Metadata extends BaseObject {

	var $data;
	public $requireSerialization = true;

	function __construct() {
		$this->data = $this->getData();
	}

	function run(){
        return new stdClass;
    }

	function get() {
		return $this->resolveDtoList("KalturaMetadata", "KalturaMetadataListResponse");
	}
}
?>