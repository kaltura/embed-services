<?php

class Uiconf extends BaseObject {

	var $data;
	public $requireSerialization = true;
	private $serviceName = "uiconf";

	function __construct() {
	}

	function run($tokens){
	    $request = new ProxyRequest($this->serviceName, $tokens);
        $this->setClientConfiguration($tokens);
        return $this->get();
    }

	function get() {
	    $res = new stdClass();
	    $res->config = json_encode($this->getData());
	    return $res;
	}
}
?>