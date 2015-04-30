<?php
    // The version of the library:
    $wgEmbedServicesVersion = '0.3.rc4';

    // Default debug mode
    $wgEnableScriptDebug = false;

    //Default paths
    $gProxyConfig = "Configuration/proxyConfig.json";
    $gLoggerConfig = "Configuration/loggerConfig.php";
    $gDtoDir = "DTO/";

    //cUrl connection timeout
    $cUrlTimeout = 10;


    /*********************************************************
     * Include local settings override:
    ********************************************************/
    $wgLocalSettingsFile = realpath( dirname( __FILE__ ) ) . '/LocalSettings.php';

    if( is_file( $wgLocalSettingsFile ) ){
    	require_once( $wgLocalSettingsFile );
    }
?>