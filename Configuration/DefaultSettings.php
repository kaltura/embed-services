<?php
    // The version of the library:
    $wgEmbedServicesVersion = '0.3.2';

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

    /*********************************************************
     * Include local settings override:
    ********************************************************/
    $wgLocalSettingsFile = realpath( dirname( __FILE__ ) ) . '/LocalSettings.php';

    if( is_file( $wgLocalSettingsFile ) ){
    	require_once( $wgLocalSettingsFile );
    }
?>