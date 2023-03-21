<?php

ini_set('default_socket_timeout', 1800);
include(__DIR__ . '/../inc/ONVIFDevicemgmt.inc.php');

$ip = $argv[1];
$port = $argv[2];

$service = 'http://' . $ip . ':' . $port . '/onvif/device_service';
$client = new ONVIF(__DIR__ . '/../WSDL/devicemgmt-mod.wsdl', $service, 'admin', 'badPassword');

try {
    $resp = $client->client->GetSystemLog();
    print "CRITICAL: ONVIF isn't protected\n";
    exit(2);
} catch (SoapFault $exception) {
    if (in_array($exception->getMessage(), ['Incorrect password type. ', 'Sender not Authorized', 'Error 401: HTTP 401 Unauthorized'])) {
        print "OK: ONVIF is protected\n";
        exit(0);
    }

    print "WARNING: ONVIF error '" . $exception->getMessage() . "'\n";
    exit(1);
}
