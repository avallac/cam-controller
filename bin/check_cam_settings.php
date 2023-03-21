<?php

ini_set('xdebug.var_display_max_depth', 10);
ini_set('default_socket_timeout', 1800);
include(__DIR__ . '/../inc/ONVIFDevicemgmt.inc.php');

$ip = $argv[1];
$port = $argv[2];
$password = $argv[3];

$errors = [];

$data = file_get_contents('http://' . $ip . '/videoosd.asp');
preg_match('|<script>(.+)</script>|', $data, $m);
$settings = explode(';', trim($m[1]));
unset($settings[24]);
$result = [];
foreach ($settings as $setting) {
    $e = explode('=', $setting);
    if (!isset($e[1])) {
        print "WARNING: configuration read error'\n";
        exit(1);
    }
    $result[$e[0]] = trim($e[1], "' ");
}

if ($result['document.outcfg_frm.cktitle.checked'] !== '0') {
    $errors[] = 'osd1:enable';
}

if ($result['document.outcfg_frm.checkTitle2.checked'] !== '0') {
    $errors[] = 'osd2:enable';
}

if ($result['document.outcfg_frm.checkTitle3.checked'] !== '0') {
    $errors[] = 'osd3:enable';
}

if ($result['document.outcfg_frm.checkTitle4.checked'] !== '0') {
    $errors[] = 'osd4:enable';
}

if ($result['document.outcfg_frm.ckdate.checked'] !== '1') {
    $errors[] = 'osdDate:disable';
}

if ($result['document.outcfg_frm.cktime.checked'] !== '1') {
    $errors[] = 'osdTime:disable';
}

if ($result['document.outcfg_frm.ckweek.checked'] !== '0') {
    $errors[] = 'osdWeek:enable';
}

if ($result['document.outcfg_frm.ckbitrate.checked'] !== '0') {
    $errors[] = 'osdBitrate:enable';
}

if ($result['document.outcfg_frm.dateformat.value'] !== '0') {
    $errors[] = 'osdDateFormat:' . $result['document.outcfg_frm.dateformat.value'];
}

$service = 'http://' . $ip . ':' . $port . '/onvif/device_service';

try {
    $client = new ONVIF(__DIR__ . '/../WSDL/media-mod.wsdl', $service, 'admin', $password);
    $num = 0;
    $res = $client->client->GetVideoEncoderConfigurations();
    if ($res->Configurations[$num]->Resolution->Width !== 1920) {
        $errors [] = 'width[' . $num . ']:' . $res->Configurations[$num]->Resolution->Width;
    }
    if ($res->Configurations[$num]->Resolution->Height !== 1080) {
        $errors [] = 'height[' . $num . ']:' . $res->Configurations[$num]->Resolution->Height;
    }
    if ($res->Configurations[$num]->Encoding !== 'H264') {
        $errors [] = 'codec[' . $num . ']:' . $res->Configurations[$num]->Encoding;
    }
    if ($res->Configurations[$num]->H264->H264Profile !== 'Baseline') {
        $errors [] = 'profile[' . $num . ']:' . $res->Configurations[$num]->H264->H264Profile;
    }
    if ($res->Configurations[$num]->H264->GovLength !== 50) {
        $errors [] = 'gov[' . $num . ']:' . $res->Configurations[$num]->H264->GovLength;
    }
    if ($res->Configurations[$num]->RateControl->FrameRateLimit !== 25) {
        $errors [] = 'fps[' . $num . ']:' . $res->Configurations[$num]->RateControl->FrameRateLimit;
    }
    if ($res->Configurations[$num]->RateControl->EncodingInterval !== 1) {
        $errors [] = 'interval[' . $num . ']:' . $res->Configurations[$num]->RateControl->EncodingInterval;
    }
    if ($res->Configurations[$num]->RateControl->BitrateLimit !== 4096) {
        $errors [] = 'bitrate[' . $num . ']:' . $res->Configurations[$num]->RateControl->BitrateLimit;
    }
    if ($res->Configurations[$num]->Quality !== 3.0) {
        $errors [] = 'quality[' . $num . ']:' . $res->Configurations[$num]->Quality;
    }
    $num++;
    if ($res->Configurations[$num]->Resolution->Width !== 640) {
        $errors [] = 'width[' . $num . ']:' . $res->Configurations[$num]->Resolution->Width;
    }
    if ($res->Configurations[$num]->Resolution->Height !== 352) {
        $errors [] = 'height[' . $num . ']:' . $res->Configurations[$num]->Resolution->Height;
    }
    if ($res->Configurations[$num]->Encoding !== 'H264') {
        $errors [] = 'codec[' . $num . ']:' . $res->Configurations[$num]->Encoding;
    }
    if ($res->Configurations[$num]->H264->H264Profile !== 'Baseline') {
        $errors [] = 'profile[' . $num . ']:' . $res->Configurations[$num]->H264->H264Profile;
    }
    if ($res->Configurations[$num]->H264->GovLength !== 30) {
        $errors [] = 'gov[' . $num . ']:' . $res->Configurations[$num]->H264->GovLength;
    }
    if ($res->Configurations[$num]->RateControl->FrameRateLimit !== 15) {
        $errors [] = 'fps[' . $num . ']:' . $res->Configurations[$num]->RateControl->FrameRateLimit;
    }
    if ($res->Configurations[$num]->RateControl->EncodingInterval !== 1) {
        $errors [] = 'interval[' . $num . ']:' . $res->Configurations[$num]->RateControl->EncodingInterval;
    }
    if ($res->Configurations[$num]->RateControl->BitrateLimit !== 768) {
        $errors [] = 'bitrate[' . $num . ']:' . $res->Configurations[$num]->RateControl->BitrateLimit;
    }
    if ($res->Configurations[$num]->Quality !== 3.0) {
        $errors [] = 'quality[' . $num . ']:' . $res->Configurations[$num]->Quality;
    }

    $client = new ONVIF(__DIR__ . '/../WSDL/devicemgmt-mod.wsdl', $service, 'admin', $password);
    $res = $client->client->GetNTP();
    if ($res->NTPInformation->NTPManual->IPv4Address !== '10.2.2.11') {
        $errors [] = 'ntp:' . $res->NTPInformation->NTPManual->DNSname;
    }
    $res = $client->client->GetSystemDateAndTime();
    if ($res->SystemDateAndTime->DateTimeType !== 'NTP') {
        $errors [] = 'dateType:' . $res->SystemDateAndTime->DateTimeType;
    }
    if ($res->SystemDateAndTime->TimeZone->TZ !== 'GMT+03') {
        $errors [] = 'timezone:' . $res->SystemDateAndTime->TimeZone->TZ;
    }
    $res = $client->client->GetNetworkProtocols();
    if ($res->NetworkProtocols[0]->Port !== 80) {
        $errors [] = 'http:' . $res->NetworkProtocols[0]->Port;
    }
    if ($res->NetworkProtocols[1]->Port !== 554) {
        $errors [] = 'rtsp:' . $res->NetworkProtocols[0]->Port;
    }
    $res = $client->client->GetNetworkInterfaces();
    if ($res->NetworkInterfaces->IPv4->Config->Manual->PrefixLength !== 24) {
        $errors [] = 'mask:' . $res->NetworkInterfaces->IPv4->Config->Manual->PrefixLength;
    }
    $res = $client->client->GetNetworkDefaultGateway();
    $gw = preg_replace('|(\d+)$|', '1', $ip);
    if ($res->NetworkGateway->IPv4Address !== $gw) {
        $errors [] = 'gw:' . $res->NetworkGateway->IPv4Address;
    }

    if (empty($errors)) {
        print "OK: settings are ok\n";
        exit(0);
    } else {
        print "CRITICAL: bad settings:" . join(',', $errors) ."\n";
        exit(2);
    }
} catch (SoapFault $exception) {
    print "WARNING: ONVIF error '" . $exception->getMessage() . "'\n";
    exit(1);
}
