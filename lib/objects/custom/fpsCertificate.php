<?php

class fpsCertificate {

	private $logger;

	function __construct() {
	    $this->logger = Logger::getLogger("FPS");
	}

	function run(){
        $this->logger->info("Start process");
        $timer = new Timer();
        $timer->start();
        $result = array();

	    global $wgKalturaFpsCertificate;

        //Only add the info if the response from TVPAPI returned valid files for playback
        $flavorassets = DataStore::getInstance()->getData("flavorassets");
        if (isset($flavorassets["Files"])){
            if (!empty($wgKalturaFpsCertificate)){
                $this->logger->info("Setting FPS Certificate response");
                $this->logger->debug("FPS Certificate value: " . $wgKalturaFpsCertificate);
                $result = array(
                    "publicCertificate" => $wgKalturaFpsCertificate
                );
            } else {
                $this->logger->warn("FPS certificate is not defined or empty!");
            }
        } else {
            $this->logger->warn("No files in response, not setting FPS Certificate response");
        }

	    $timer->stop();
        $this->logger->info("Finish process in ".$timer->getTimeMs(). " ms");

	    return $result;
	}
}
?>