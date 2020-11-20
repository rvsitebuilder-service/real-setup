<?php

register_shutdown_function(function () {
    //if header is sent , because worker function is responsed install task status
    if (!headers_sent()) {
        header('Content-type: application/json');
        //ref https://www.php.net/manual/en/errorfunc.constants.php
        //error type 2 is warning
        $error = error_get_last();
        if (null !== $error && $error['type'] != 2) {
            $response = [];
            $response['status'] = false;
            $response['exectime'] = '';
            $response['message'] = ' This error has to be fixed by RVsitebuilder team. Please submit a ticket with Hosting Access and Domain name provided to us directly <a href="https://rvglobalsoft.com/tickets/new&deptId=5" target="_blank">Here.</a> ';
            if (isset($error['message'])) {
                $response['message'] = $response['message'] . ' (' . $error['message'] . ')';
            }
            echo json_encode($response);
            exit;
        }
    }
});

require 'vendor/autoload.php';

use GuzzleHttp\Client;
use splitbrain\PHPArchive\Tar;
use Symfony\Component\Filesystem\Filesystem;


$headers = (function_exists('apache_request_headers') || is_callable('apache_request_headers'))  ? apache_request_headers() : rv_apache_request_headers();
$headers = array_change_key_case($headers, CASE_UPPER);
$responsetype = $headers['ACCEPT'] ?? 'application/json';
$ignore_token = $headers['IGNORE-TOKEN'] ?? false;
$rvsb_installing_token = $headers['RVSB-INSTALLING-TOKEN'] ?? false;
$rvlicensecode = $headers['RV-LICENSE-CODE'] ?? '';

//default
$action         = $_GET['action'] ?? $_POST['action'] ?? '';
$homeuser       = $_GET['homeuser'] ?? $_POST['homeuser'] ?? '';
$domainname     = $_GET['domainname'] ?? $_POST['domainname'] ?? '';
$publicpath     = $_GET['publicpath'] ?? $_POST['publicpath'] ?? '';
$dbhost         = $_GET['dbhost'] ?? $_POST['dbhost'] ?? '';
$dbname         = $_GET['dbname'] ?? $_POST['dbname'] ?? '';
$dbuser         = $_GET['dbuser'] ?? $_POST['dbuser'] ?? '';
$dbpassword     = $_GET['dbpass'] ?? $_POST['dbpass'] ?? '';
$dbport         = $_GET['dbport'] ?? $_POST['dbport'] ?? 3306;
$ftpaccount     = $_GET['ftpaccount'] ?? $_POST['ftpaccount'] ?? '';
$ftppassword    = $_GET['ftppassword'] ?? $_POST['ftppassword'] ?? '';
$appname        = $_GET['appname'] ?? $_POST['appname'] ?? 'RVsitebuilder';
$ftpserver      = $_GET['ftpserver'] ?? $_POST['ftpserver'] ?? '';
$ftpport        = $_GET['ftpport'] ?? $_POST['ftpport'] ?? 21;
$adminemail     = $_GET['adminemail'] ?? $_POST['adminemail'] ?? '';
$adminpassword  = $_GET['adminpassword'] ?? $_POST['adminpassword'] ?? '';
$adminfirstname = $_GET['adminfirstname'] ?? $_POST['adminfirstname'] ?? '';
$adminlastname  = $_GET['adminlastname'] ?? $_POST['adminlastname'] ?? '';
$domainport     = $_GET['domainport'] ?? $_POST['domainport'] ?? '';
$artisancmd     = $_GET['artisancmd'] ?? $_POST['artisancmd'] ?? '';
$artisanparam   = $_GET['artisanparam'] ?? $_POST['artisanparam'] ?? '{}';
$additionalpkg  = $_GET['additionalpkg'] ?? $_POST['additionalpkg'] ?? '';
$welcomeemailtype = $_GET['welcomeemailtype'] ?? $_POST['welcomeemailtype'] ?? 'default';
$isrepair         = $_GET['isrepair'] ?? $_POST['isrepair'] ?? false;
$cptype         = $_GET['cptype'] ?? $_POST['cptype'] ?? 'none';
$installtype    = $_GET['installtype'] ?? $_POST['installtype'] ?? '';
$devemail       = $_GET['devemail'] ?? $_POST['devemail'] ?? '';
$devkey         = $_GET['devkey'] ?? $_POST['devkey'] ?? '';
$devlicensejwtkey = $_GET['devlicensejwtkey'] ?? $_POST['devlicensejwtkey'] ?? '';
$dbengine = $_GET['dbengine'] ?? $_POST['dbengine'] ?? 'mysql';
$dbcharset = $_GET['dbcharset'] ?? $_POST['dbcharset'] ?? '';
$dbcollation = $_GET['dbcollation'] ?? $_POST['dbcollation'] ?? '';

require "RVsitebuilder_Setup_API.php";

$setupObj = new RVsitebuilder_Setup_API($responsetype, $rvsb_installing_token, $responsetype, $ignore_token, $rvlicensecode);

//provision
$provisionconfig = [];
$userpathinfo = $setupObj->get_user_path_info();
$userdomain = $setupObj->get_current_domain();
$configfile = $userpathinfo['homepath'] . '/rvsitebuildercms/' . $userdomain . '/provisioning.ini';
if (file_exists($configfile) && $installtype == 'provision') {
    $provisionconfig = parse_ini_file($configfile, true);

    if (isset($provisionconfig['provisioning']['db_host']) && $provisionconfig['provisioning']['db_host'] != '') {
        $dbhost = $provisionconfig['provisioning']['db_host'];
    }
    if (isset($provisionconfig['provisioning']['db_name']) && $provisionconfig['provisioning']['db_name'] != '') {
        $dbname = $provisionconfig['provisioning']['db_name'];
    }
    if (isset($provisionconfig['provisioning']['db_user']) && $provisionconfig['provisioning']['db_user'] != '') {
        $dbuser = $provisionconfig['provisioning']['db_user'];
    }
    if (isset($provisionconfig['provisioning']['db_pass']) && $provisionconfig['provisioning']['db_pass'] != '') {
        $dbpassword = $provisionconfig['provisioning']['db_pass'];
    }
    if (isset($provisionconfig['provisioning']['db_port']) && $provisionconfig['provisioning']['db_port'] != '') {
        $dbport = $provisionconfig['provisioning']['db_port'];
    }
    if (isset($provisionconfig['provisioning']['admin_email'])       && $provisionconfig['provisioning']['admin_email'] != '') {
        $adminemail = $provisionconfig['provisioning']['admin_email'];
    }
    if (isset($provisionconfig['provisioning']['admin_password'])    && $provisionconfig['provisioning']['admin_password'] != '') {
        $adminpassword = $provisionconfig['provisioning']['admin_password'];
    }
    if (isset($provisionconfig['provisioning']['admin_firstname'])   && $provisionconfig['provisioning']['admin_firstname'] != '') {
        $adminfirstname = $provisionconfig['provisioning']['admin_firstname'];
    }
    if (isset($provisionconfig['provisioning']['admin_lastname'])    && $provisionconfig['provisioning']['admin_lastname'] != '') {
        $adminlastname = $provisionconfig['provisioning']['admin_lastname'];
    }
    $homeuser = $userpathinfo['homepath'];
    $domainname = $userdomain;
    $publicpath = $userpathinfo['publicpath'];
}

////////////////
//call to action
////////////////
if ($action == '' && !file_exists(dirname(__FILE__) . '/.Rvsb-Installing-Token') && $rvsb_installing_token == 0) {
    $setupObj->send_token();
}

if ($action == 'pre_check_php') {
    $setupObj->pre_check_php();
}

if ($action == 'download_framework') {
    $setupObj->download_framework($homeuser, $domainname, $publicpath, $ftpaccount, $ftppassword, $ftpserver, $ftpport);
}

if ($action == 'download_vendor') {
    $setupObj->download_vendor($homeuser, $domainname, $ftpaccount, $ftppassword, $ftpserver, $ftpport);
}

if ($action == 'setup_env') {
    $setupObj->setup_env($domainname, $publicpath, $dbhost, $dbname, $dbuser, $dbpassword, $ftpaccount, $ftppassword, $appname, $ftpserver, $ftpport, $domainport, $welcomeemailtype, $homeuser, $cptype, $devlicensejwtkey, $dbengine, $dbport);
}

if ($action == 'download_common_pkg') {
    $setupObj->download_common_pkg($homeuser, $domainname, $additionalpkg);
}

if ($action == 'install_all_pkg' && $homeuser != '' && $domainname != '' && $publicpath != '') {
    $setupObj->install_all_pkg($homeuser, $domainname, $publicpath, $ftpaccount, $ftppassword, $ftpserver, $ftpport);
}

if ($action == 'artisan_call') {
    $setupObj->artisan_call($homeuser, $domainname, $publicpath, $adminemail, $adminpassword, $adminfirstname, $adminlastname, $isrepair);
}
if ($action == 'artisan_cmd_run') {
    $setupObj->artisan_cmd_run($homeuser, $domainname, $artisancmd, $artisanparam);
}

if ($action == 'finished_setup') {
    $setupObj->finished_setup($homeuser, $domainname, $publicpath, $ftpaccount, $ftppassword, $ftpserver, $ftpport);
}

if ($action == 'remove_installer_api') {
    $setupObj->remove_installer_api();
}


//call from wizard
if ($action == 'get_user_path') {
    $setupObj->get_user_path();
}
if ($action == 'check_http_as_user') {
    $setupObj->check_http_as_user();
}
if ($action == 'check_license') {
    $setupObj->check_license();
}
if ($action == 'disk_required') {
    $setupObj->disk_required();
}
if ($action == 'test_database_ftp_connect') {
    $setupObj->test_database_ftp_connect($dbhost, $dbname, $dbuser, $dbpassword, $ftpserver, $ftpaccount, $ftppassword, $ftpport, $dbengine, $dbport);
}
if ($action == 'developer_license_addnewsite') {
    $setupObj->developer_license_addnewsite($devemail, $devkey);
}
if ($action == 'check_dev_mode') {
    $setupObj->check_dev_mode();
}
if ($action == 'check_server_license') {
    $setupObj->check_server_license();
}

function getFrameworkVendorPath($filePath = ''): string
{
    $vendorDir = realpath(dirname($filePath) . '/../../../../../') . '/vendor';
    return $vendorDir;
}

function getPackageBaseDir($filePath = ''): string
{
    $baseDir = realpath(dirname($filePath) . '/../../');
    return $baseDir;
}

function rv_apache_request_headers(): array
{
    $arh = array();
    $rx_http = '/\AHTTP_/';
    foreach ($_SERVER as $key => $val) {
        if (preg_match($rx_http, $key)) {
            $arh_key = preg_replace($rx_http, '', $key);
            $rx_matches = array();
            // do some nasty string manipulations to restore the original letter case
            // this should work in most cases
            $rx_matches = explode('_', $arh_key);
            if (count($rx_matches) > 0 and strlen($arh_key) > 2) {
                foreach ($rx_matches as $ak_key => $ak_val) {
                    $rx_matches[$ak_key] = ucfirst($ak_val);
                    $arh_key = implode('-', $rx_matches);
                }
            }
            $arh[$arh_key] = $val;
        }
    }
    return ($arh);
}
