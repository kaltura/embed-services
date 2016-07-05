<?php
    // The version of the library:
    $wgEmbedServicesVersion = '0.4';

    // Default debug mode
    $wgEnableScriptDebug = false;

    //Default paths
    $gProxyConfig = "Configuration/proxyConfig.json";
    $gLoggerConfig = "Configuration/loggerConfig.php";
    $gDtoDir = "DTO/";

    //cUrl connection timeout
    $cUrlTimeout = 10;

    //Set unique ID for log
    $_SERVER['suid'] = str_replace(".", "", microtime(true));

    //Define this id for additional CAS system checks in uDRM, default is 0, meaning no additional system defined
    $wgAdditionalCasSystemId = 0;

    /*********************************************************
     * Include local settings override:
    ********************************************************/
    $wgLocalSettingsFile = realpath( dirname( __FILE__ ) ) . '/LocalSettings.php';

    if( is_file( $wgLocalSettingsFile ) ){
    	require_once( $wgLocalSettingsFile );
    }
?>