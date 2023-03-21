<?php

$ip = $argv[1];
$port = $argv[2];

$url = "rtsp://" . $ip . ':' . $port . "/ch01";
$curl = curl_init();
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_RTSP_STREAM_URI, $url);
curl_setopt($curl, CURLOPT_RTSP_REQUEST, CURL_RTSPREQ_DESCRIBE);
curl_setopt($curl, CURLOPT_HTTPHEADER, array('User-Agent: LibVLC/2.2.1 (LIVE555 Streaming Media v2014.07.25)'));
$res = curl_exec($curl);
if ($res === '') {
    print "OK: RTSP is protected\n";
    exit(0);
} elseif ($res === null) {
    print "WARNING: RTSP isn't available\n";
    exit(1);
} else {
    print "CRITICAL: RTSP isn't protected\n";
    exit(2);
}
url_close($curl);