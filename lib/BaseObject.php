<?php
abstract class BaseObject {	

	private $loggers;
	private $dtoData = NULL;
	public $rawDataString = NULL;
	private $clientConfiguration = NULL;

	public static function getClass() {
		 return new static;
	}

	public function setClientConfiguration($data){
	    $this->rawDataString = $data;
	    if (isset($data["1:filter:freeText"])){
            $configObj = json_decode($data["1:filter:freeText"], TRUE);
            if (isset($configObj["config"])){
                $filterConfig = $configObj["config"];
                if (isset($filterConfig[strtolower(get_class($this))])){
                    $this->clientConfiguration = $filterConfig[strtolower(get_class($this))];
                }
            }
        }
	}

	private function resolveConfiguration($defaultConfig){
	    global $wgEmbedServicesVersion, $wgPartnerId;
	    if ($this->clientConfiguration != NULL){
	        if (isset($this->clientConfiguration["filters"])){
	            if (!isset($defaultConfig["pointers"]["filters"])){
                    $defaultConfig["pointers"]["filters"] = array();
                }
	            $defaultConfig["pointers"]["filters"] = $this->clientConfiguration["filters"];
	        }
	        if (isset($this->clientConfiguration["vars"])){
	            if (!isset($defaultConfig["pointers"]["vars"])){
	                $defaultConfig["pointers"]["vars"] = array();
	            }
                $defaultConfig["pointers"]["vars"] = array_merge($defaultConfig["pointers"]["vars"], $this->clientConfiguration["vars"]);
            }
        }
        //If partner id was set in localsettings(when deployment is per instance) then check if it wasn't already passed
        //via flashvar and if not then set it
        if (isset($wgPartnerId) && !empty($wgPartnerId)){
            if (!isset($defaultConfig["pointers"]["vars"])){
                $defaultConfig["pointers"]["vars"] = array();
            }
            if (!isset($defaultConfig["pointers"]["vars"]["partnerId"])){
                $defaultConfig["pointers"]["vars"]["partnerId"] = $wgPartnerId;
            }
        }
        //Set request data to be available to all DTOs
        $defaultConfig["pointers"]["vars"]["requestData"] = DataStore::getInstance()->getData("request");
        $defaultConfig["pointers"]["vars"]["embedServicesVersion"] = $wgEmbedServicesVersion;

        return $defaultConfig;
	}

	public function isValidService($data) {
	    return true;
	}

	abstract protected function get();

	public function getDtoConfig($dtoName) {
	    global $gDtoDir;

	    if (is_null($this->dtoData)){
            $dataStoreName = 'DTO_'.$dtoName;//strtolower(get_class($this));
            //if (!apc_exists($dataStoreName)) {
                //echo "Load from file: ".$dataStoreName;
                $data = file_get_contents($gDtoDir.$dtoName/*strtolower(get_class($this))*/.".json", FILE_USE_INCLUDE_PATH);
            //	apc_store($dataStoreName, $data);
            //} else {
                //echo "Load from cache: ".$dataStoreName;
            //	$data = apc_fetch($dataStoreName);
            //}

            $this->dtoData = json_decode($data, TRUE);
		}

		return $this->dtoData;
	}

	public function setData($data) {
		DataStore::getInstance()->setData(strtolower(get_class($this)), $data);
	}	

	public function getData() {
		return $dataStoreName = DataStore::getInstance()->getData(strtolower(get_class($this)));
	}

	function getClassVars($class) {
		return $class_vars = get_class_vars($class);
	}

	function resolveDtoList($implementClass, $responseClass = NULL, $unwrap = false){
		//Set loggers
		$this->loggers = new stdClass();
        $this->loggers->main = Logger::getLogger("main");
        $this->loggers->dto = Logger::getLogger("DTO");
        $this->loggers->main->info("Resolving ".get_called_class());

        $timer = new Timer();
        $timer->start();

		//Fetch data
		$data = $this->getData();

		$classVars = array();
    	$dtoConf = array();

    	$this->loggers->dto->debug("Resolving params: implementClass=".json_encode($implementClass) . ", responseClass=".$responseClass . ", unwrap=".$unwrap);

		//Get all implemented classes vars and data transfer objects
		if (is_array($implementClass)){
        	foreach ($implementClass as $classKey) {
    			$classVars[$classKey] = $this->getClassVars($classKey);
        		$dtoConf[$classKey] = $this->getDtoConfig($classKey);     
    		}
	    } else {
	    	$classVars[$implementClass] = $this->getClassVars($implementClass);
        	$dtoConf[$implementClass] = $this->getDtoConfig($implementClass);     
	    }

	    $resolved = array();
	    //Iterate over all data transfer objects
        foreach ($dtoConf as $classKey => $dtoConfObj) {
            $dtoConfObj = $this->resolveConfiguration($dtoConfObj);
            $this->loggers->dto->info("Try to implement class ".$classKey);
            //Fetch the key-value pairs mappings
        	$resolvers = $dtoConfObj["resolver"];
        	//Get the Kaltura object class properties names
        	$classVarsObj = isset($classVars[$classKey]) ? array_keys($classVars[$classKey]) : array();
        	$this->loggers->dto->debug("classVarsObj=".json_encode($classVarsObj));

        	//Check if an iterator is set
        	$iterator = isset($dtoConfObj["pointers"]["iterator"]) &&
        				!empty($dtoConfObj["pointers"]["iterator"]) ||
        				is_numeric($dtoConfObj["pointers"]["iterator"]) ? $dtoConfObj["pointers"]["iterator"] : NULL;

        	$this->loggers->dto->info("Set iterator: ".$iterator);

  			//Fetch data to iterate over
  			if (!is_null($iterator) || is_numeric($iterator)){
  			    $items = isset($data[$iterator]) ? $data[$iterator] : array();
  			} else {
  			    $items = $data;
  			}
  			// check if needs wrapping for iterator
  			$items = (isset($dtoConfObj["pointers"]["wrap"]) && $dtoConfObj["pointers"]["wrap"] == "true") ? array($items) : $items;

        	$this->loggers->dto->debug("Resolve items for iteration: ".json_encode($items));

        	$filters = (isset($dtoConfObj["pointers"]["filters"]) &&
                       !empty($dtoConfObj["pointers"]["filters"])) ? $dtoConfObj["pointers"]["filters"] : NULL;

        	$vars = (isset($dtoConfObj["pointers"]["vars"]) &&
                    !empty($dtoConfObj["pointers"]["vars"])) ? $dtoConfObj["pointers"]["vars"] : NULL;

        	//Iterate over data and convert using the DTO
        	if (isset($items) && is_array($items)){
                foreach ($items as $item) {
                    //Check if this is a subType and if it should be included
                    $this->loggers->dto->debug("Iterate over item: ".json_encode($item));
                    if (isset($dtoConfObj["pointers"]["subTypeIdentifier"]) &&
                        isset($dtoConfObj["pointers"]["include"])){
                        if ($dtoConfObj["pointers"]["include"] == true ){
                            //Check if subType key matches current DTO
                            foreach($dtoConfObj["pointers"]["subTypeIdentifier"] as $subTypeIdentifierKey => $subTypeIdentifierVal){
                                if ($subTypeIdentifierVal != $item[$subTypeIdentifierKey]){
                                    $this->loggers->dto->debug("Found different subTypeIdentifierKey(".$subTypeIdentifierKey."), iterate over next item");
                                    continue;
                                }
                            }
                        } else {
                            $this->loggers->dto->debug("Do not include subtype $classKey, iterate over next item");
                            continue;
                        }
                    }
                    //Filter items
                    if (!is_null($filters)){
                        if (isset($filters["exclude"])){
                            $skip = false;
                            foreach($filters["exclude"] as $filterKey => $filterVals){
                                if (isset($item[$filterKey])){
                                    foreach($filterVals as $filterVal){
                                        if ($item[$filterKey] == $filterVal){
                                            $skip = true;
                                        }
                                    }
                                }
                            }
                            if ($skip){
                                continue;
                            }
                        } elseif (isset($filters["include"])){
                            $skip = true;
                            foreach($filters["include"] as $filterKey => $filterVals){
                                if (isset($item[$filterKey])){
                                    foreach($filterVals as $filterVal){
                                        if ($item[$filterKey] == $filterVal){
                                            $skip = false;
                                        }
                                    }
                                }
                            }
                            if ($skip){
                                continue;
                            }
                        }
                    }
                    $resolvedItem = "";
                    //Resolve keys using DTO mapping definitions
                    foreach ($resolvers as $resolverKey => $resolverExp) {
                        if (in_array($resolverKey, $classVarsObj)){
                            $this->loggers->dto->debug("Found key '".$resolverKey."' in resolver and in implemented class, resolving...");
                            if (is_array($resolverExp)){
                                $resolvedItem[$resolverKey] = array();
                                foreach($resolverExp as $expKey=>$expVal){
                                    $res = Lexer::getInstance()->resolve($expVal, $item, $data, $vars);
                                    $resolvedItem[$resolverKey][$expKey] = $res;
                                }
                            } else {
                                $res = Lexer::getInstance()->resolve($resolverExp, $item, $data, $vars);
                                $resolvedItem[$resolverKey] = $res;//$dataItem[$key];
                            }
                        }
                    }
                    $this->loggers->dto->debug("Instantiate implemented class: '".$classKey."' with data = ".json_encode($resolvedItem));
                    array_push($resolved, new $classKey($resolvedItem));
                }
        	}
        }

		$timer->stop();
        $this->loggers->main->info("Resolve DTO time = ".$timer->getTimeMs(). " ms");
        if ($unwrap){
		    return isset($resolved[0]) ? $resolved[0] : new $implementClass();
		} elseif (is_null($responseClass)){
			return $resolved;
		} else {
			return new $responseClass(array(
					'objects'    => $resolved,
					'totalCount' => count($resolved),
				));
		}
	}
}
?>