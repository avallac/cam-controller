<?php

require __DIR__ . '/../vendor/autoload.php';
include(__DIR__ . '/../inc/ONVIFDevicemgmt.inc.php');

function needUpdateRTSP($ip)
{
    $data = file_get_contents('http://' . $ip . '/netrtsp.asp');
    preg_match('|<script>(.+)</script>|', $data, $m);
    $settings = explode(';', trim($m[1]));
    unset($settings[14]);
    $result = [];
    foreach ($settings as $setting) {
        $e = explode('=', $setting);
        $result[$e[0]] = trim($e[1], "' ");
    }

    if ($result['document.outcfg_frm.onvifpasswordvalue.value'] !== '1') {
        return true;
    }

    if ($result['document.outcfg_frm.ckrz.value'] !== '1') {
        return true;
    }

    return false;
}

function needUpdateOSD($ip)
{
    $data = file_get_contents('http://' . $ip . '/videoosd.asp');
    preg_match('|<script>(.+)</script>|', $data, $m);
    $settings = explode(';', trim($m[1]));
    unset($settings[24]);
    $result = [];
    foreach ($settings as $setting) {
        $e = explode('=', $setting);
        $result[$e[0]] = trim($e[1], "' ");
    }

    if ($result['document.outcfg_frm.cktitle.checked'] !== '0') {
        return true;
    }

    if ($result['document.outcfg_frm.checkTitle2.checked'] !== '0') {
        return true;
    }

    if ($result['document.outcfg_frm.checkTitle3.checked'] !== '0') {
        return true;
    }

    if ($result['document.outcfg_frm.checkTitle4.checked'] !== '0') {
        return true;
    }

    if ($result['document.outcfg_frm.ckdate.checked'] !== '1') {
        return true;
    }

    if ($result['document.outcfg_frm.cktime.checked'] !== '1') {
        return true;
    }

    if ($result['document.outcfg_frm.ckweek.checked'] !== '0') {
        return true;
    }

    if ($result['document.outcfg_frm.ckbitrate.checked'] !== '0') {
        return true;
    }

    if ($result['document.outcfg_frm.dateformat.value'] !== '0') {
        return true;
    }

    return false;
}

if (empty($argv[1])) {
    print "Usage:\n";
    print "fix_cam_settings.php IP Port Password";
}

$ip = $argv[1];
$port = $argv[2];
$password = $argv[3];

print "Старт проверки \033[36m$ip\033[0m\n";
if (needUpdateRTSP($ip)) {
    print "=> Обновляю настройки RTSP/ONVIF\n";
    $client = new \GuzzleHttp\Client();
    $client->post(
        'http://' . $ip . '/webs/netRTSPCfgEx',
        [
            'form_params' => [
                'ckrtsp' => '1',
                'rtspmode' => '1',
                'ckrz' => '1',
                'rtsppack' => '1460',
                'rtspurl' => '127.0.0.1',
                'rtspport' => '554',
                'mulip' => '239.0.0.0',
                'mulvideoport' => '1234',
                'mulaudioport' => '1236',
                'mulvideoport2' => '1240',
                'mulaudioport2' => '1242',
                'onvifpasswordvalue' => '1',
                'onvifpassword' => '1',
            ]
        ]
    );
} else {
    print "=> RTSP без изменений\n";
}

if (needUpdateOSD($ip)) {
    print "=> Обновляю OSD\n";
    $client = new \GuzzleHttp\Client();
    $client->post(
        'http://' . $ip . '/webs/videoOsdCfgEx',
        [
            'form_params' => [
                'title' => '',
                'title2' => '',
                'title3' => '',
                'title4' => '',
                'ckdate' => '1',
                'cktime' => '1',
            ]
        ]
    );
} else {
    print "=> OSD без изменений\n";
}

$service = 'http://' . $ip . ':' . $port .'/onvif/device_service';
$username = 'admin';
$mgmt = new ONVIF(__DIR__ . '/../WSDL/devicemgmt-mod.wsdl', $service, $username, $password);
$media = new ONVIF(__DIR__ . '/../WSDL/media-mod.wsdl', $service, $username, $password);
try {
    $res = $media->client->GetVideoEncoderConfigurations();
    $res->Configurations[0]->Resolution->Width = 1920;
    $res->Configurations[0]->Resolution->Height = 1080;
    $res->Configurations[0]->Encoding = 'H264';
    $res->Configurations[0]->H264->H264Profile = 'Baseline';
    $res->Configurations[0]->H264->GovLength = 50;
    $res->Configurations[0]->RateControl->FrameRateLimit = 25;
    $res->Configurations[0]->RateControl->EncodingInterval = 1;
    $res->Configurations[0]->RateControl->BitrateLimit = 4096;
    $res->Configurations[0]->Quality = 4;

    $res->Configurations[1]->Resolution->Width = 640;
    $res->Configurations[1]->Resolution->Height = 352;
    $res->Configurations[1]->Encoding = 'H264';
    $res->Configurations[1]->H264->H264Profile = 'Baseline';
    $res->Configurations[1]->H264->GovLength = 30;
    $res->Configurations[1]->RateControl->FrameRateLimit = 15;
    $res->Configurations[1]->RateControl->EncodingInterval = 1;
    $res->Configurations[1]->RateControl->BitrateLimit = 768;
    $res->Configurations[1]->Quality = 4;

    print "=> Обновляю поток 1\n";
    $media->client->SetVideoEncoderConfiguration(
        [
            'Configuration' => $res->Configurations[0],
            'ForcePersistence' => true,
        ]
    );

    print "=> Обновляю поток 2\n";
    $media->client->SetVideoEncoderConfiguration(
        [
            'Configuration' => $res->Configurations[1],
            'ForcePersistence' => true,
        ]
    );

    $req = [
        'FromDHCP' => false,
        'NTPManual' => [
            'Type' => 'IPv4',
            'IPv4Address' => '10.2.2.11'
        ]
    ];

    print "=> Обновляю NTP\n";
    $mgmt->client->SetNTP($req);

    $req = array(
        'DateTimeType' => 'NTP',
        'DaylightSavings' => false,
        'TimeZone' => array(
            'TZ' => 'GMT+03',
        ),
    );
    print "=> Обновляю дату\n";
    $mgmt->client->SetSystemDateAndTime($req);

    print "=> Обновляю маску\n";
    $res = $mgmt->client->GetNetworkInterfaces();
    $data = [
        'NetworkInterface' => [
            'Enabled' => true,
            'IPv4' => [
                'Enabled' => true,
                'Manual' => [
                    'Address' => $res->NetworkInterfaces->IPv4->Config->Manual->Address,
                    'PrefixLength' => 24,
                ]
            ]
        ],
        'InterfaceToken' => ''
    ];
    $mgmt->client->SetNetworkInterfaces($data);

    print "=> Перезагрузка\n";
    $mgmt->client->SystemReboot();
}catch (\SoapFault $e) {
    print $e->getMessage() . "\n";
}
