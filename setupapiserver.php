<?php


require 'vendor/autoload.php';
use GuzzleHttp\Client;
use splitbrain\PHPArchive\Tar;
use Symfony\Component\Filesystem\Filesystem;



$headers = (function_exists('apache_request_headers') || is_callable('apache_request_headers'))  ? apache_request_headers() : rv_apache_request_headers();
$headers = array_change_key_case($headers,CASE_UPPER);
$responsetype           = (isset($headers['ACCEPT']))                   ? $headers['ACCEPT']                : 'application/json';
$ignore_token           = (isset($headers['IGNORE-TOKEN']))             ? $headers['IGNORE-TOKEN']          : 0;
$rvsb_installing_token  = (isset($headers['RVSB-INSTALLING-TOKEN']))    ? $headers['RVSB-INSTALLING-TOKEN'] : 0;
$rvlicensecode          = (isset($headers['RV-LICENSE-CODE']))          ? $headers['RV-LICENSE-CODE']       : '';

//default
$action         = '';
$homeuser       = '';
$domainname     = '';
$publicpath     = '';
$dbhost         = '';
$dbname         = '';
$dbuser         = '';
$dbpassword     = '';
$dbport         = 3306;
$ftpaccount     = '';
$ftppassword    = '';
$appname        = 'RVsitebuilder';
$ftpserver      = '';
$ftpport        = 21;
$adminemail     = '';
$adminpassword  = '';
$adminfirstname = '';
$adminlastname  = '';
$domainport     = '';
$artisancmd     = '';
$artisanparam   = '{}';
$additionalpkg  = '';
$welcomeemailtype = 'default';
$isrepair         = 0;
$cptype         = 'none';
$installtype    = '';

//get
if (isset($_GET['action']))         $action         = $_GET['action'];
if (isset($_GET['homeuser']))       $homeuser       = $_GET['homeuser'];
if (isset($_GET['domainname']))     $domainname     = $_GET['domainname'];
if (isset($_GET['publicpath']))     $publicpath     = $_GET['publicpath'];
if (isset($_GET['dbhost']))         $dbhost         = $_GET['dbhost'];
if (isset($_GET['dbname']))         $dbname         = $_GET['dbname'];
if (isset($_GET['dbuser']))         $dbuser         = $_GET['dbuser'];
if (isset($_GET['dbpass']))         $dbpassword     = $_GET['dbpass'];
if (isset($_GET['dbport']))         $dbport         = $_GET['dbpass'];
if (isset($_GET['ftpaccount']))     $ftpaccount     = $_GET['ftpaccount'];
if (isset($_GET['ftppassword']))    $ftppassword    = $_GET['ftppassword'];
if (isset($_GET['appname']))        $appname        = $_GET['appname'];
if (isset($_GET['ftpserver']))      $ftpserver      = $_GET['ftpserver'];
if (isset($_GET['ftpport']))        $ftpport        = $_GET['ftpport'];
if (isset($_GET['adminemail']))     $adminemail     = $_GET['adminemail'];
if (isset($_GET['adminpassword']))  $adminpassword  = $_GET['adminpassword'];
if (isset($_GET['adminfirstname'])) $adminfirstname = $_GET['adminfirstname'];
if (isset($_GET['adminlastname']))  $adminlastname  = $_GET['adminlastname'];
if (isset($_GET['domainport']))     $domainport     = $_GET['domainport'];
if (isset($_GET['artisancmd']))     $artisancmd     = $_GET['artisancmd'];
if (isset($_GET['artisanparam']))   $artisanparam   = $_GET['artisanparam'];
if (isset($_GET['additionalpkg']))  $additionalpkg  = $_GET['additionalpkg'];
if (isset($_GET['welcomeemailtype']))  $welcomeemailtype  = $_GET['welcomeemailtype'];
if (isset($_GET['isrepair']))       $isrepair  = $_GET['isrepair'];
if (isset($_GET['cptype']))         $cptype  = $_GET['cptype'];
if (isset($_GET['installtype']))    $installtype= $_GET['installtype'];

//post
if (isset($_POST['action']))         $action         = $_POST['action'];
if (isset($_POST['homeuser']))       $homeuser       = $_POST['homeuser'];
if (isset($_POST['domainname']))     $domainname     = $_POST['domainname'];
if (isset($_POST['publicpath']))     $publicpath     = $_POST['publicpath'];
if (isset($_POST['dbhost']))         $dbhost         = $_POST['dbhost'];
if (isset($_POST['dbname']))         $dbname         = $_POST['dbname'];
if (isset($_POST['dbuser']))         $dbuser         = $_POST['dbuser'];
if (isset($_POST['dbpass']))         $dbpassword     = $_POST['dbpass'];
if (isset($_POST['dbport']))         $dbport         = $_POST['dbport'];
if (isset($_POST['ftpaccount']))     $ftpaccount     = $_POST['ftpaccount'];
if (isset($_POST['ftppassword']))    $ftppassword    = $_POST['ftppassword'];
if (isset($_POST['appname']))        $appname        = $_POST['appname'];
if (isset($_POST['ftpserver']))      $ftpserver      = $_POST['ftpserver'];
if (isset($_POST['ftpport']))        $ftpport        = $_POST['ftpport'];
if (isset($_POST['adminemail']))     $adminemail     = $_POST['adminemail'];
if (isset($_POST['adminpassword']))  $adminpassword  = $_POST['adminpassword'];
if (isset($_POST['adminfirstname'])) $adminfirstname = $_POST['adminfirstname'];
if (isset($_POST['adminlastname']))  $adminlastname  = $_POST['adminlastname'];
if (isset($_POST['domainport']))     $domainport     = $_POST['domainport'];
if (isset($_POST['artisancmd']))     $artisancmd     = $_POST['artisancmd'];
if (isset($_POST['artisanparam']))   $artisanparam   = $_POST['artisanparam'];
if (isset($_POST['additionalpkg']))  $additionalpkg  = $_POST['additionalpkg'];
if (isset($_POST['welcomeemailtype']))  $welcomeemailtype  = $_POST['welcomeemailtype'];
if (isset($_POST['isrepair']))          $isrepair  = $_POST['isrepair'];
if (isset($_POST['cptype']))            $cptype  = $_POST['cptype'];
if (isset($_POST['installtype']))       $installtype= $_POST['installtype'];

$setupObj = new RVsitebuilder_Setup_API($responsetype,$rvsb_installing_token,$responsetype,$ignore_token,$rvlicensecode);


//provision
$provisionconfig = [];
$userpathinfo = $setupObj->get_user_path_info();
$userdomain = $setupObj->get_current_domain();
$configfile = $userpathinfo['homepath'] . '/rvsitebuildercms/' . $userdomain. '/provisioning.ini';
if(file_exists($configfile) && $installtype == 'provision') {
    $provisionconfig = parse_ini_file($configfile,true);
    if(isset($provisionconfig['provisioning']['db_host']) && $provisionconfig['provisioning']['db_host'] != '')  $dbhost = $provisionconfig['provisioning']['db_host'];
    if(isset($provisionconfig['provisioning']['db_name']) && $provisionconfig['provisioning']['db_name'] != '')  $dbname = $provisionconfig['provisioning']['db_name'];
    if(isset($provisionconfig['provisioning']['db_user']) && $provisionconfig['provisioning']['db_user'] != '')  $dbuser = $provisionconfig['provisioning']['db_user'];
    if(isset($provisionconfig['provisioning']['db_pass']) && $provisionconfig['provisioning']['db_pass'] != '')  $dbpassword = $provisionconfig['provisioning']['db_pass'];
    if(isset($provisionconfig['provisioning']['db_port']) && $provisionconfig['provisioning']['db_port'] != '')  $dbport = $provisionconfig['provisioning']['db_port'];
    if(isset($provisionconfig['provisioning']['admin_email'])       && $provisionconfig['provisioning']['admin_email'] != '')      $adminemail = $provisionconfig['provisioning']['admin_email'];
    if(isset($provisionconfig['provisioning']['admin_password'])    && $provisionconfig['provisioning']['admin_password'] != '')   $adminpassword = $provisionconfig['provisioning']['admin_password'];
    if(isset($provisionconfig['provisioning']['admin_firstname'])   && $provisionconfig['provisioning']['admin_firstname'] != '')  $adminfirstname = $provisionconfig['provisioning']['admin_firstname'];
    if(isset($provisionconfig['provisioning']['admin_lastname'])    && $provisionconfig['provisioning']['admin_lastname'] != '')   $adminlastname= $provisionconfig['provisioning']['admin_lastname'];
    $homeuser = $userpathinfo['homepath'];
    $domainname = $userdomain;
    $publicpath = $userpathinfo['publicpath'];
}




////////////////
//call to action
////////////////
if($action == '' && !file_exists(dirname(__FILE__).'/.Rvsb-Installing-Token') && $rvsb_installing_token == 0) {
    $setupObj->send_token();
}

if($action == 'pre_check_php'){
    $setupObj->pre_check_php();
}

if($action == 'download_framework'){
    $setupObj->download_framework($homeuser,$domainname,$publicpath,$ftpaccount,$ftppassword,$ftpserver,$ftpport);
}

if($action == 'download_vendor'){
    $setupObj->download_vendor($homeuser,$domainname,$ftpaccount,$ftppassword,$ftpserver,$ftpport);
}

if($action == 'setup_env'){
    $setupObj->setup_env($domainname,$publicpath,$dbhost,$dbname,$dbuser,$dbpassword,$ftpaccount,$ftppassword,$appname,$ftpserver,$ftpport,$domainport,$welcomeemailtype,$homeuser,$cptype);
}

if($action == 'download_common_pkg'){
    $setupObj->download_common_pkg($homeuser,$domainname,$additionalpkg);
}

if($action == 'install_all_pkg' && $homeuser != '' && $domainname != '' && $publicpath != ''){
    $setupObj->install_all_pkg($homeuser,$domainname,$publicpath,$ftpaccount,$ftppassword,$ftpserver,$ftpport);
}

if($action == 'artisan_call'){
    $setupObj->artisan_call($homeuser,$domainname,$publicpath,$adminemail,$adminpassword,$adminfirstname,$adminlastname,$isrepair);
}
if($action == 'artisan_cmd_run'){
    $setupObj->artisan_cmd_run($homeuser,$domainname,$artisancmd,$artisanparam);
}

if($action == 'finished_setup'){
    $setupObj->finished_setup($homeuser,$domainname,$publicpath,$ftpaccount,$ftppassword,$ftpserver,$ftpport);
}

if($action == 'remove_installer_api'){
    $setupObj->remove_installer_api();
}


//call from wizard
if($action == 'get_user_path'){
    $setupObj->get_user_path();
}
if($action == 'check_http_as_user') {
    $setupObj->check_http_as_user();
}
if($action == 'check_license') {
    $setupObj->check_license();
}
if($action == 'disk_required') {
    $setupObj->disk_required();
}
if($action == 'test_database_ftp_connect') {
    $setupObj->test_database_ftp_connect($dbhost,$dbname,$dbuser,$dbpassword,$ftpserver,$ftpaccount,$ftppassword,$ftpport);
}
// if($action == 'validate_developer_token') {
//     $setupObj->validate_developer_token($devtokenkey);
// }
if($action == 'check_dev_mode') {
    $setupObj->check_dev_mode();
}













class RVsitebuilder_Setup_API {
    
    protected $responseType;
    protected $response;
    protected $serverconf;
    protected $debug_log;
    protected $install_log;
    protected $httpasuser;
    protected $installerconfig;
    protected $call_responsetype;
    protected $removeinstallerpath;
    protected $rvlicensecode;
    protected $getversionurl;
    
    public function __construct($responsetype,$rvsb_installing_token,$call_responsetype,$ignore_token,$rvlicensecode)
    {
        //response type
        $this->responseType = $responsetype;
        $this->call_responsetype = $call_responsetype;
        //default response
        $this->response['status'] = false;
        $this->response['message'] = '';
        $this->response['exectime'] = 0;
        //verify token
        $this->verify_token($rvsb_installing_token,$ignore_token);
        $this->httpasuser = $this->gethttpasuser();
        //get installation configfunction getInstallerConfig() {
        $this->installerconfig = $this->getInstallerConfig();
        //mirror url
        $this->mirror = $this->installerconfig['mirror'];
        //debug var
        $this->debug_log = $this->installerconfig['debug_log'];
        //install_log
        $this->install_log =  $this->installerconfig['install_log'];
        //remove installer path
        $this->removeinstallerpath = $this->installerconfig['removeinstallerpath'];
        $this->debugmode = $this->installerconfig['debug_log'];
        //rvlicensecode
        $this->rvlicensecode = $rvlicensecode;
        //getversion url
        $this->getversionurl = 'https://getversion.rvsitebuilder.com';
    }
    
    public function getInstallerConfig() {
        $this->print_debug_log('======'.__METHOD__.'======');
        
        //defaultconfig
        $defconfig = [];
        $defconfigpath = dirname(__FILE__).'/rvsitebuilderinstallerconfig_dist/config.ini';
        if(file_exists($defconfigpath)){
            $defconfig = parse_ini_file($defconfigpath,true);
            $this->print_debug_log("Default Installer config ".json_encode($defconfig));
        }
        
        //overwrite installer config by root config (in public path /home/user/public_html/.rvsitebuilderinstallerconfig)
        $rootconfig = [];
        $rootconfigpath = dirname(__FILE__).'/../.rvsitebuilderinstallerconfig/root_config.ini';
        if(file_exists($rootconfigpath)) {
            $rootconfig = parse_ini_file($rootconfigpath,true);
            $this->print_debug_log("Root Installer config ".json_encode($rootconfig));
        }
        $installerconfig1 = array_merge($defconfig,$rootconfig);
        
        //overwrite installer config by user config (in public path /home/user/public_html/.rvsitebuilderinstallerconfig)
        $userconfig = [];
        $userconfigpath =  dirname(__FILE__).'/../.rvsitebuilderinstallerconfig/config.ini';
        if(file_exists($userconfigpath)) {
            $userconfig = parse_ini_file($userconfigpath,true);
            $this->print_debug_log("User Installer config ".json_encode($userconfig));
        }
        $installerconfig2 = array_merge($installerconfig1,$userconfig);
        
        $this->print_debug_log("Final Installer config ".json_encode($installerconfig2));
        return $installerconfig2;
        
    }
    
    
    public function gethttpasuser() {
        $this->print_debug_log('======'.__METHOD__.'======');
        //get by posix_getpwuid
        if(function_exists('posix_getpwuid')){
            $homepath_owner = posix_getpwuid(fileowner($_SERVER["DOCUMENT_ROOT"]))['name'];
            $site_run_as = posix_getpwuid(posix_geteuid())['name'];
            if($homepath_owner == $site_run_as){
                $this->print_debug_log("detectby posix_getpwuid HTTP AS USER   TRUE");
                return true;
            }
        }
        //get by getmyuid and fileowner
        if(function_exists('getmyuid') && function_exists('fileowner')){
            $myuid = getmyuid();
            file_put_contents(dirname(__FILE__).'/testfileowner', '');
            $fileowner = '';
            if(file_exists(dirname(__FILE__).'/testfileowner')){
                $fileowner = fileowner(dirname(__FILE__).'/testfileowner');
                unlink(dirname(__FILE__).'/testfileowner');
                if($fileowner == $myuid){
                    $this->print_debug_log("detectby getmyuid,fileowner HTTP AS USER   TRUE");
                    return true;
                }
            }
        }
        //get by getmyuid and stat
        if(function_exists('getmyuid') && function_exists('stat')){
            $myuid = getmyuid();
            file_put_contents(dirname(__FILE__).'/testfileowner', '');
            $stat = [];
            if(file_exists(dirname(__FILE__).'/testfileowner')){
                $stat = stat(dirname(__FILE__).'/testfileowner');
                unlink(dirname(__FILE__).'/testfileowner');
                if(isset($stat['uid']) && $stat['uid'] == $myuid){
                    $this->print_debug_log("detectby getmyuid,stat HTTP AS USER   TRUE");
                    return true;
                }
            }
        }
        $this->print_debug_log("HTTP AS USER FALSE");
        return false;
    }
    
    public function verify_token($rvsb_installing_token='',$ignore_token=0) {
        $this->print_debug_log('======'.__METHOD__.'======');
        
        if($ignore_token == 1) {
            $this->print_debug_log("Ignore Token");
            $this->print_install_log(__METHOD__.' TRUE (Ignore)');
            return true;
        }
        
        if((!file_exists(dirname(__FILE__).'/.Rvsb-Installing-Token') && ($rvsb_installing_token == 0))) {
            $this->print_debug_log("First Request");
            $this->print_install_log(__METHOD__.' TRUE (First request)');
            return true;
        }
        
        if((file_exists(dirname(__FILE__).'/.Rvsb-Installing-Token') && ($rvsb_installing_token != 0)) ){
            $tokenvalue = file_get_contents(dirname(__FILE__).'/.Rvsb-Installing-Token');
            if(trim($tokenvalue) != trim($rvsb_installing_token)){
                $this->print_debug_log("Token Wrong");
                $this->print_install_log(__METHOD__.' status FALSE (Token Wrong)');
                $this->response['message'] = 'Wrong!!!!';
                return $this->print_response($this->response);
            }
        }
        $this->print_install_log(__METHOD__.' __METHOD__'.' status TRUE (Token pass)');
        return true;
    }
    
    public function send_token() {
        $time_start = microtime(true);
        $this->print_debug_log('======'.__METHOD__.'======');
        
        $this->response['status'] = true;
        $this->response['rvsb_installing_token'] = $this->generateSecretKey(128);
        file_put_contents(dirname(__FILE__).'/.Rvsb-Installing-Token', $this->response['rvsb_installing_token']);
        
        
        $this->response['exectime'] = (microtime(true) - $time_start);
        $this->print_debug_log("Token Key ".$this->response['rvsb_installing_token']);
        $this->print_install_log(__METHOD__.' TRUE '.'timeusage '.$this->response['exectime']);
        return $this->print_response($this->response);
    }
    
    public function pre_check_php() {
        $time_start = microtime(true);
        $this->print_debug_log('======'.__METHOD__.'======');
        
        $this->response['status'] = true;
        
        //php version
        $this->response['check_pre_require']['phpversion']['check'] = true;
        if (version_compare(PHP_VERSION, '7.1.3') < 0) {
            $this->response['check_pre_require']['phpversion']['check'] = false;
            $this->response['check_pre_require']['phpversion']['reason'] = 'System required PHP Version > = 7.1.3';
            $this->response['message'] = 'System required PHP Version > = 7.1.3';
            $this->response['status'] = false;
            $this->print_debug_log("PHP version falase ".PHP_VERSION);
        }
        //php extension
        $this->response['check_pre_require']['mysqlnd']['check'] = true;
        if (!extension_loaded('mysqlnd')) {
            $this->response['check_pre_require']['mysqlnd']['check'] = false;
            $this->response['check_pre_require']['mysqlnd']['reason'] = 'Can not load PHP Extension (mysqlnd)';
            $this->response['message'] = $this->response['message'].' / Can not load PHP Extension (mysqlnd)';
            $this->response['status'] = false;
            $this->print_debug_log("PHP mysqlnd false");
        }
        $this->response['check_pre_require']['pdo']['check'] = true;
        if (!extension_loaded('pdo')) {
            $this->response['check_pre_require']['pdo']['check'] = false;
            $this->response['check_pre_require']['pdo']['reason'] = 'Can not load PHP Extension (pdo)';
            $this->response['message'] = $this->response['message'].' / Can not load PHP Extension (pdo)';
            $this->response['status'] = false;
            $this->print_debug_log("PHP pdo false");
        }
        $this->response['check_pre_require']['curl']['check'] = true;
        if (!extension_loaded('curl')) {
            $this->response['check_pre_require']['curl']['check'] = false;
            $this->response['check_pre_require']['curl']['reason'] = 'Can not load PHP Extension (curl)';
            $this->response['message'] = $this->response['message'].' / Can not load PHP Extension (curl)';
            $this->response['status'] = false;
            $this->print_debug_log("PHP curl false");
        }
        $this->response['check_pre_require']['iconv']['check'] = true;
        if (!extension_loaded('iconv')) {
            $this->response['check_pre_require']['iconv']['check'] = false;
            $this->response['check_pre_require']['iconv']['reason'] = 'Can not load PHP Extension (iconv)';
            $this->response['message'] = $this->response['message'].' / Can not load PHP Extension (iconv)';
            $this->response['status'] = false;
            $this->print_debug_log("PHP iconv false");
        }
        $this->response['check_pre_require']['mbstring']['check'] = true;
        if (!extension_loaded('mbstring')) {
            $this->response['check_pre_require']['mbstring']['check'] = false;
            $this->response['check_pre_require']['mbstring']['reason'] = 'Can not load PHP Extension (mbstring)';
            $this->response['message'] = $this->response['message'].' / Can not load PHP Extension (mbstring)';
            $this->response['status'] = false;
            $this->print_debug_log("PHP mbstring false");
        }
        /*
        $this->response['check_pre_require']['fileinfo']['check'] = true;
        if (!extension_loaded('fileinfo')) {
            $this->response['check_pre_require']['fileinfo']['check'] = false;
            $this->response['check_pre_require']['fileinfo']['reason'] = '';
            $this->response['message'] = $this->response['message'].' / Can not load PHP Extension (fileinfo)';
            $this->response['status'] = false;
            $this->print_debug_log("PHP fileinfo false");
        }
        */
        $this->response['check_pre_require']['image_lib']['check'] = true;
        if (!extension_loaded('imagick') && !extension_loaded('gd')){
            $this->response['check_pre_require']['image_lib']['check'] = false;
            $this->response['check_pre_require']['image_lib']['reason'] = 'Can not load PHP Extension (imagick or gd)';
            $this->response['message'] = $this->response['message'].' / Can not load PHP Extension (imagick or gd)';
            $this->response['status'] = false;
            $this->print_debug_log("PHP imagick or gd false");
        }
        $this->response['check_pre_require']['zip']['check'] = true;
        if (!extension_loaded('zip')) {
            $this->response['check_pre_require']['zip']['check'] = false;
            $this->response['check_pre_require']['zip']['reason'] = 'Can not load PHP Extension (zip)';
            $this->response['message'] = $this->response['message'].' / Can not load PHP Extension (zip)';
            $this->response['status'] = false;
            $this->print_debug_log("PHP zip false");
        }
        //php config
        /*
        $this->response['check_pre_require']['allow_url_fopen']['check'] = true;
        if (ini_get('allow_url_fopen') != 1) {
            $this->response['check_pre_require']['allow_url_fopen']['check'] = false;
            $this->response['check_pre_require']['allow_url_fopen']['reason'] = 'php.ini, Must set allow_url_fopen=ON';
            $this->response['message'] = $this->response['message'].' / php.ini, Must set allow_url_fopen=ON';
            $this->response['status'] = false;
            $this->print_debug_log("PHP allow_url_fopen false");
        }
        */
        $this->response['check_pre_require']['memory_limit']['check'] = true;
        preg_match('/([0-9]+)/',ini_get('memory_limit'),$match);
        if($match[0] < 64) {
            $this->response['check_pre_require']['memory_limit']['check'] = false;
            $this->response['check_pre_require']['memory_limit']['reason'] = 'php.ini, Set Memory limit at least 64M.';
            $this->response['message'] = $this->response['message'].' / php.ini, Set Memory limit at least 64M.';
            $this->response['status'] = false;
            $this->print_debug_log("PHP memory_limit false ".$match[0]);
        }
        //php function get file owner (posix_getpwuid || (getmyuid && (fileowner || stat))
        $this->response['check_pre_require']['dir_and_file']['check'] = true;
        if(! function_exists('posix_getpwuid') && (! function_exists('getmyuid') || (! function_exists('fileowner') && ! function_exists('stat')))){
            $this->response['check_pre_require']['dir_and_file']['check'] = false;
            $this->response['check_pre_require']['dir_and_file']['reason'] = 'Can not load PHP Function (posix_getpwuid or getmyuid,fileowner)';
            $this->response['message'] = $this->response['message'].' / Can not load PHP Function (posix_getpwuid or getmyuid,fileowner)';
            $this->response['status'] = false;
            $this->print_debug_log("PHP dir_and_file false");
        }
        //php function json
        $this->response['check_pre_require']['json']['check'] = true;
        if(! extension_loaded('json')){
            $this->response['check_pre_require']['json']['check'] = false;
            $this->response['check_pre_require']['json']['reason'] = 'Can not load PHP Function (json)';
            $this->response['message'] = $this->response['message'].' / Can not load PHP Function (json)';
            $this->response['status'] = false;
            $this->print_debug_log("PHP json false");
        }
        //php function parse_ini_file
        $this->response['check_pre_require']['parse_ini_file']['check'] = true;
        if(! function_exists('parse_ini_file')){
            $this->response['check_pre_require']['parse_ini_file']['check'] = false;
            $this->response['check_pre_require']['parse_ini_file']['reason'] = 'Can not load PHP Function (parse_ini_file)';
            $this->response['message'] = $this->response['message'].' / Can not load PHP Function (parse_ini_file)';
            $this->response['status'] = false;
            $this->print_debug_log("PHP parse_ini_file false");
        }
        // php function file_put_contents
        $this->response['check_pre_require']['file_put_contents']['check'] = true;
        if(! function_exists('file_put_contents')){
            $this->response['check_pre_require']['file_put_contents']['check'] = false;
            $this->response['check_pre_require']['file_put_contents']['reason'] = 'Can not load PHP Function (file_put_contents)';
            $this->response['message'] = $this->response['message'].' / Can not load PHP Function (file_put_contents)';
            $this->response['status'] = false;
            $this->print_debug_log("PHP file_put_contents false");
        }
        
        //TODO
        // php function pdo_mysql
//         $this->response['check_pre_require']['pdo_mysql']['check'] = true;
//         if(! function_exists('pdo_mysql')){
//             $this->response['check_pre_require']['pdo_mysql']['check'] = false;
//             $this->response['check_pre_require']['pdo_mysql']['reason'] = 'Can not load PHP Function (pdo_mysql)';
//             $this->response['message'] = $this->response['message'].' / Can not load PHP Function (pdo_mysql)';
//             $this->response['status'] = false;
//             $this->print_debug_log("PHP pdo_mysql false");
//         }
        
        //check path is writeable
        $this->response['check_pre_require']['path_writable']['check'] = true;
        $userpathinfo = $this->get_user_path_info();
        if (!is_writable($userpathinfo['homepath'])) {
            $this->response['check_pre_require']['path_writable']['check'] = false;
            $this->response['check_pre_require']['path_writable']['reason'] = 'Path not writeable '.$userpathinfo[homepath];
            $this->response['message'] = $this->response['message'].' / Path not writeable '.$userpathinfo[homepath];
            $this->response['status'] = false;
            $this->print_debug_log("Path not writeable $userpathinfo[homepath]");  
        }
        if (!is_writable($userpathinfo['publicpath'])) {
            $this->response['check_pre_require']['path_writable']['check'] = false;
            $this->response['check_pre_require']['path_writable']['reason'] = 'Path not writeable '.$userpathinfo[publicpath];
            $this->response['message'] = $this->response['message'].' / Path not writeable '.$userpathinfo[publicpath];
            $this->response['status'] = false;
            $this->print_debug_log("Path not writeable $userpathinfo[publicpath]");  
        }
        if (!is_writable(dirname(__FILE__))) {
            $this->response['check_pre_require']['path_writable']['check'] = false;
            $this->response['check_pre_require']['path_writable']['reason'] = 'Path not writeable '.dirname(__FILE__);
            $this->response['message'] = $this->response['message'].' / Path not writeable '.dirname(__FILE__);
            $this->response['status'] = false;
            $this->print_debug_log("Path not writeable ".dirname(__FILE__));  
        }

        
       

        //http as user
        $this->response['httpasuser'] = $this->httpasuser;
        
        $this->print_debug_log("Pre Check ".$this->response['status']." ".$this->response['message']);
        
        $this->response['exectime'] = (microtime(true) - $time_start);
        
        if($this->response['status'] == true) {
            $this->print_install_log(__METHOD__.' TRUE '.' timeusage '.$this->response['exectime']);
            $this->response['message'] = "PHP Version,Extentsion,INI OK";
        } else {
            $this->print_install_log(__METHOD__.' status FALSE '.$this->response['message'].' timeusage '.$this->response['exectime'] .' timeusage '.$this->response['exectime']);
        }
        
        return $this->print_response($this->response);
        
    }
    
    public function download_framework($homeuser,$domainname,$publicpath,$ftpaccount,$ftppassword,$ftpserver,$ftpport) {
        $time_start = microtime(true);
        $this->print_debug_log('======'.__METHOD__.'======');
        
        if($this->httpasuser) {
            $res = $this->download_framework_httpasuser($homeuser,$domainname,$publicpath);
        } else {
            $res = $this->download_framework_no_httpasuser($homeuser,$domainname,$publicpath,$ftpaccount,$ftppassword,$ftpserver,$ftpport);
        }
        
        $this->response['exectime'] = (microtime(true) - $time_start);
        $this->response['status'] = $res['status'];
        $this->response['message'] = $res['message'];
        $this->print_install_log(__METHOD__.' status: '.$this->response['status'].' message: '.$this->response['message'].' timeusage: '.$this->response['exectime']);
        return $this->print_response($this->response);
        
    }
    
    public function download_framework_httpasuser($homeuser,$domainname,$publicpath) {
        $this->print_debug_log('======'.__METHOD__.'======');
        
        $res = [
            'status'    => false,
            'message'   => '',
        ];
        
        $files =  new Filesystem();
        
        //remove first if /home/<user>/rvsitebuildercms/$domainname/bootstrap/cache
        if(file_exists($homeuser.'/rvsitebuildercms/'.$domainname.'/bootstrap/cache')) {
            $files->remove($homeuser.'/rvsitebuildercms/'.$domainname.'/bootstrap/cache');
            $this->print_debug_log("Removed old framwork path ".$homeuser.'/rvsitebuildercms/'.$domainname.'/bootstrap/cache');
        }
        //remove first if /home/<user>/rvsitebuildercms/$domainname/storage
        if(file_exists($homeuser.'/rvsitebuildercms/'.$domainname.'/storage')) {
            $files->remove($homeuser.'/rvsitebuildercms/'.$domainname.'/storage');
            $this->print_debug_log("Removed old framwork path ".$homeuser.'/rvsitebuildercms/'.$domainname.'/storage');
        }
        
        $sha512verify['type'] = 'framework';
        $sha512verify['name'] = 'rvsitebuilder/framework';
            
        //download framework url
        $downloadurl =  $this->mirror.'/download/rvsitebuilder/framework';
        $sha512verify['version_type'] = 'stable';
        if(isset($this->installerconfig['framework']['getversion']) && $this->installerconfig['framework']['getversion'] == 'latest')
        {
            $downloadurl = $this->mirror.'/download/rvsitebuilder/framework/tier/latest';
            $sha512verify['version_type'] = 'latest';
        }
        if(isset($this->installerconfig['framework']['getversion']) && preg_match('/[0-9]+\.[0-9]+\.[0-9]+/',$this->installerconfig['framework']['getversion']))
        {
            $downloadurl = $this->mirror.'/download/rvsitebuilder/framework/version/'.$this->installerconfig['framework']['getversion'];
            $sha512verify['version_type'] = 'version';
            $sha512verify['version'] = $this->installerconfig['framework']['getversion'];
            $sha512verify['getversionurl'] = '/getversion/rvsitebuilder/framework';
        }

        $this->print_debug_log("Download Framework URL ".$downloadurl);
        
        //download framework
        $downloadframework = $this->doDownload('GET' , $downloadurl , dirname(__FILE__).'/framework.tar.gz' , $sha512verify);
        
        if($downloadframework['success'] == false){
            if(file_exists(dirname(__FILE__).'/framework.tar.gz')){
                $files->remove(dirname(__FILE__).'/framework.tar.gz');
            }
            $res['message'] = 'Download Framework  Failed '.$downloadframework['message'];
            $this->print_debug_log("Download Framework  Failed ".$downloadframework['message']);
            return $res;
        }
        
        //extract framework
        $extractframework = $this->doExtract(dirname(__FILE__).'/framework.tar.gz',$homeuser.'/rvsitebuildercms/'.$domainname.'/');
        if($extractframework['success'] == false) {
            if(file_exists(dirname(__FILE__).'/framework.tar.gz')){
                $files->remove(dirname(__FILE__).'/framework.tar.gz');
            }
            $res['message'] = 'Extract Framework Failed  '.$extractframework['message'];
            $this->print_debug_log("Extract Framework Failed ".$extractframework['message']);
            return $res;
        }
        
        //copy framework/public to public path
        $source = $homeuser.'/rvsitebuildercms/'.$domainname.'/public';
        $destination = $publicpath;
        if($files->exists($source.'/index.php')) {
            $copy = $files->copy($source.'/index.php' , $destination.'/index.php' , true);
        }
        if($files->exists($source.'/robots.txt')){
            $copy = $files->copy($source.'/robots.txt' , $destination.'/robots.txt' , true);
        }
        if($files->exists($source.'/storage')){
            $copy = $files->mirror($source.'/storage' , $destination.'/storage' , null ,['override' => true]);
        }
        $this->print_debug_log("Copy $source To $destination");
        
        //delete appsconfig.json
        if($files->exists($homeuser.'/rvsitebuildercms/'.$domainname.'/storage/appsconfig.json')){
            $files->remove($homeuser.'/rvsitebuildercms/'.$domainname.'/storage/appsconfig.json');
            $this->print_debug_log("Removed ".$homeuser.'/rvsitebuildercms/'.$domainname.'/storage/appsconfig.json');
        }
        
        //delete framwork package
        if($files->exists(dirname(__FILE__).'/framework.tar.gz')){
            $files->remove(dirname(__FILE__).'/framework.tar.gz');
            $this->print_debug_log("Removed ".dirname(__FILE__).'/framework.tar.gz');
        }
        
        $res['status'] = true;
        $res['message'] = 'Download Framework Success.';
        return $res;
    }
    
    public function download_framework_no_httpasuser($homeuser,$domainname,$publicpath,$ftpaccount,$ftppassword,$ftpserver,$ftpport) {
        $this->print_debug_log('======'.__METHOD__.'======');
        
        $res = [
            'status'    => false,
            'message'   => '',
        ];
        
        $files =  new Filesystem();
        
        $sha512verify['type'] = 'framework';
        $sha512verify['name'] = 'rvsitebuilder/framework';
        
        //download framework url
        $downloadurl =  $this->mirror.'/download/rvsitebuilder/framework';
        $sha512verify['version_type'] = 'stable';
        if(isset($this->installerconfig['framework']['getversion']) && $this->installerconfig['framework']['getversion'] == 'latest')
        {
            $downloadurl = $this->mirror.'/download/rvsitebuilder/framework/tier/latest';
            $sha512verify['version_type'] = 'latest';
        }
        if(isset($this->installerconfig['framework']['getversion']) && preg_match('/[0-9]+\.[0-9]+\.[0-9]+/',$this->installerconfig['framework']['getversion']))
        {
            $downloadurl = $this->mirror.'/download/rvsitebuilder/framework/version/'.$this->installerconfig['framework']['getversion'];
            $sha512verify['version_type'] = 'version';
            $sha512verify['version'] = $this->installerconfig['framework']['getversion'];
            $sha512verify['getversionurl'] = '/getversion/rvsitebuilder/framework';
        }

        $this->print_debug_log("Download Framework URL ".$downloadurl);
        
        //download framework
        $downloadframework = $this->doDownload('GET' , $downloadurl , dirname(__FILE__).'/framework.tar.gz' , $sha512verify);
        if($downloadframework['success'] == false){
            if(file_exists(dirname(__FILE__).'/framework.tar.gz')){
                $files->remove(dirname(__FILE__).'/framework.tar.gz');
            }
            $res['message'] = 'Can not download framework '.$downloadframework['message'];
            $this->print_debug_log("Download Framework  Failed ".$downloadframework['message']);
            return $res;
        }
        
        //extract framework
        $extractframework = $this->doExtract(dirname(__FILE__).'/framework.tar.gz',dirname(__FILE__).'/tmp/');
        if($extractframework['success'] == false) {
            if(file_exists(dirname(__FILE__).'/framework.tar.gz')){
                $files->remove(dirname(__FILE__).'/framework.tar.gz');
            }
            $res['message'] = 'Can not extract framework.tar.gz '.$extractframework['message'];
            $this->print_debug_log("Extract Framework Failed ".$extractframework['message']);
            return $res;
        }
        
        //delete appsconfig.json
        $files = new Filesystem();
        if(file_exists(dirname(__FILE__).'/tmp/storage/appsconfig.json')){
            $files->remove(dirname(__FILE__).'/tmp/storage/appsconfig.json');
            $this->print_debug_log("Removed ".dirname(__FILE__).'/tmp/storage/appsconfig.json');
        }
        
        //delete framwork package
        if($files->exists(dirname(__FILE__).'/framework.tar.gz')){
            $files->remove(dirname(__FILE__).'/framework.tar.gz');
            $this->print_debug_log("Removed ".dirname(__FILE__).'/framework.tar.gz');
        }
        
        $res['status'] = true;
        $res['message'] = 'Download Framework Success.';
        return $res;
        
    }
    
    public function download_vendor($homeuser,$domainname,$ftpaccount,$ftppassword,$ftpserver,$ftpport) {
        $time_start = microtime(true);
        $this->print_debug_log('======'.__METHOD__.'======');
        if($this->httpasuser) {
            $res = $this->download_vendor_httpasuser($homeuser,$domainname);
        } else {
            $res = $this->download_vendor_no_httpasuser($homeuser,$domainname,$ftpaccount,$ftppassword,$ftpserver,$ftpport);
        }
        
        $this->response['exectime'] = (microtime(true) - $time_start);
        $this->response['status'] = $res['status'];
        $this->response['message'] = $res['message'];
        $this->print_install_log(__METHOD__.' status: '.$this->response['status'].' message: '.$this->response['message'].' timeusage: '.$this->response['exectime']);
        return $this->print_response($this->response);
    }
    
    public function download_vendor_httpasuser($homeuser,$domainname){
        $this->print_debug_log('======'.__METHOD__.'======');
        
        $res = [
            'status'    => false,
            'message'   => '',
        ];
        
        $files = new Filesystem();
        
        //check rvsitebuilder.json
        if(! $files->exists($homeuser.'/rvsitebuildercms/'.$domainname.'/rvsitebuilder.json')){
            $res['message'] = "Not found ".$homeuser.'/rvsitebuildercms/'.$domainname.'/rvsitebuilder.json';
            return $res;
        }
        
        //read rvsitebuilder.json
        $rvsbjson = json_decode(file_get_contents($homeuser.'/rvsitebuildercms/'.$domainname.'/rvsitebuilder.json'), true);
        // first download from key vendor-packages (bundle_vendor) if key exists
        // link download = http://files.mirror1.rvsitebuilder.com/download/rvsitebuilder/framework%2Fbundle_vendor/version/0.0.8
        // vendor-packages = rvsitebuilder\/framework\/bundle_vendor
        if(isset($rvsbjson['vendor-packages']) && key($rvsbjson['vendor-packages']) != ''){
            $this->print_debug_log("Download Vendor From bundle_vendor");
            $vendorkey = key($rvsbjson['vendor-packages']);
            list($product_name, $app_name) = preg_split('/\//', $vendorkey, 2);
            $package_name_encoded = '/'.$product_name.'/'.urlencode($app_name);
            $version = '/version/'.$rvsbjson['version'];
            $downloadvendorurl = $this->mirror.'/download'.$package_name_encoded.$version;
            $this->print_debug_log("Vendor URL download ".$downloadvendorurl);
            $downloadvendor = $this->doDownload('GET' , $downloadvendorurl , dirname(__FILE__).'/bundle_vendor.tar.gz');
            if($downloadvendor['success'] == false) {
                if($files->exists(dirname(__FILE__).'/bundle_vendor.tar.gz')) {
                    $files->remove(dirname(__FILE__).'/bundle_vendor.tar.gz');
                }
                $res['message'] = 'Download vendor Failed '.$downloadvendor['message'];
                $this->print_debug_log("Can not download ".$downloadvendorurl.' '.$downloadvendor['message']);
                return $res;
            }
            $extractvendor = $this->doExtract(dirname(__FILE__).'/bundle_vendor.tar.gz',$homeuser.'/rvsitebuildercms/'.$domainname.'/');
            if($extractvendor['success'] == false) {
                if($files->exists(dirname(__FILE__).'/bundle_vendor.tar.gz')) {
                    $files->remove(dirname(__FILE__).'/bundle_vendor.tar.gz');
                }
                $res['message'] = 'Extract bundle_vendor.tar.gz Failed '.$extractvendor['message'];
                $this->print_debug_log("Extract bundle_vendor.tar.gz Failed ".dirname(__FILE__).'/bundle_vendor.tar.gz'.' to '.$homeuser.'/rvsitebuildercms/'.$domainname.'/bundle_vendor.tar.gz '.$extractvendor['message']);
                return $res;
            }
            
            //remove bundle_vendor.tar.gz
            if($files->exists(dirname(__FILE__).'/bundle_vendor.tar.gz')) {
                $files->remove(dirname(__FILE__).'/bundle_vendor.tar.gz');
                $this->print_debug_log("Removed ".dirname(__FILE__).'/bundle_vendor.tar.gz');
            }
        }
        
        //lookup and download all from key packages
        //วิธีนี้ อาจเจอ timeout
        else {
            $this->print_debug_log("Download Vendor From list package");
            foreach($rvsbjson['packages'] as $package_key => $value){
                $update_package_name = $rvsbjson['packages'][$package_key]['name'];
                $update_package_version = isset($rvsbjson['packages'][$package_key]['version']) ? $rvsbjson['packages'][$package_key]['version'] : '';
                list($product_name, $app_name) = preg_split('/\//', $update_package_name, 2);
                $package_name_encoded = urlencode($app_name);
                if ($update_package_version != '') {
                    $update_package_version = '/version/' . $update_package_version;
                }
                
                $downloadvendorurl = $this->mirror.'/download/'.$product_name.'/'.urlencode($app_name).$update_package_version;
                $this->print_debug_log("Package URL Download ".$downloadvendorurl);
                
                $sha512verify['type'] = 'vendor';
                $sha512verify['getversionurl'] = '/getversion/vendor/'.urlencode($app_name).$update_package_version;
                $sha512verify['version'] = $rvsbjson['packages'][$package_key]['version'];
                $sha512verify['name'] = $rvsbjson['packages'][$package_key]['name'];
                   
                $downloadvendor = $this->doDownload('GET' , $downloadvendorurl , dirname(__FILE__).'/'.$package_name_encoded.'.tar.gz' , $sha512verify);
                if($downloadvendor['success'] == false) {
                    if($files->exists(dirname(__FILE__).'/'.$package_name_encoded.'.tar.gz')) {
                        $files->remove(dirname(__FILE__).'/'.$package_name_encoded.'.tar.gz');
                    }
                    $res['message'] = 'Download vendor Failed '.$downloadvendorurl.' '.$downloadvendor['message'];
                    $this->print_debug_log("Download vendor Failed ".$downloadvendorurl.' '.$downloadvendor['message']);
                    return $res;
                }
                $extractvendor = $this->doExtract(dirname(__FILE__).'/'.$package_name_encoded.'.tar.gz',$homeuser.'/rvsitebuildercms/'.$domainname.'/vendor/');
                if($extractvendor['success'] == false) {
                    if($files->exists(dirname(__FILE__).'/'.$package_name_encoded.'.tar.gz')) {
                        $files->remove(dirname(__FILE__).'/'.$package_name_encoded.'.tar.gz');
                    }
                    $res['message'] = 'Extract vendor Failed '.$package_name_encoded.' '.$extractvendor['message'];
                    $this->print_debug_log("Extract vendor Failed ".dirname(__FILE__).'/'.$package_name_encoded.'.tar.gz '.$extractvendor['message']);
                    return $res;
                }
                
                if($files->exists(dirname(__FILE__).'/'.$package_name_encoded.'.tar.gz')) {
                    $files->remove(dirname(__FILE__).'/'.$package_name_encoded.'.tar.gz');
                    $this->print_debug_log("Removed ".dirname(__FILE__).'/'.$package_name_encoded.'.tar.gz');
                }
            }
            
        }
        $res['status'] = true;
        $res['message'] = 'Download Vendor Success';
        return $res;
        
    }
    
    public function download_vendor_no_httpasuser($homeuser,$domainname,$publicpath,$ftpaccount,$ftppassword,$ftpserver,$ftpport){
        $this->print_debug_log('======'.__METHOD__.'======');
        
        $res = [
            'status'    => false,
            'message'   => '',
        ];
        
        $files = new Filesystem();
        
        //check rvsitebuilder.json
        if(! file_exists(dirname(__FILE__).'/tmp/rvsitebuilder.json')){
            $res['message'] = "Not found ".dirname(__FILE__).'/tmp/rvsitebuilder.json';
            return $res;
        }
        
        //read rvsitebuilder.json
        $rvsbjson = json_decode(file_get_contents(dirname(__FILE__).'/tmp/rvsitebuilder.json'), true);
        
        //first download from key vendor-packages (bundle_vendor) if key exists
        // link download = http://files.mirror1.rvsitebuilder.com/download/rvsitebuilder/framework%2Fbundle_vendor/version/0.0.8
        // vendor-packages = rvsitebuilder\/framework\/bundle_vendor
        if(isset($rvsbjson['vendor-packages']) && key($rvsbjson['vendor-packages']) != ''){
            $this->print_debug_log("Download Vendor From bundle_vendor");
            $vendorkey = key($rvsbjson['vendor-packages']);
            list($product_name, $app_name) = preg_split('/\//', $vendorkey, 2);
            $package_name_encoded = '/'.$product_name.'/'.urlencode($app_name);
            $version = '/version/'.$rvsbjson['version'];
            $downloadvendorurl = $this->mirror.'/download'.$package_name_encoded.$version;
            $this->print_debug_log("Vendor URL download ".$downloadvendorurl);
            $downloadvendor = $this->doDownload('GET' , $downloadvendorurl , dirname(__FILE__).'/bundle_vendor.tar.gz' );
            if($downloadvendor['success'] == false) {
                if($files->exists(dirname(__FILE__).'/bundle_vendor.tar.gz')) {
                    $files->remove(dirname(__FILE__).'/bundle_vendor.tar.gz');
                }
                $res['message'] = 'Can not download vendor '.$downloadvendor['message'];
                $this->print_debug_log("Can not download ".$downloadvendorurl.' '.$downloadvendor['message']);
                return $res;
            }
            $extractvendor = $this->doExtract(dirname(__FILE__).'/bundle_vendor.tar.gz',dirname(__FILE__).'/tmp/vendor/');
            if($extractvendor['success'] == false) {
                if($files->exists(dirname(__FILE__).'/bundle_vendor.tar.gz')) {
                    $files->remove(dirname(__FILE__).'/bundle_vendor.tar.gz');
                }
                $res['message'] = 'Can not extract vendor.tar.gz '.$extractvendor['message'];
                $this->print_debug_log("Can not extract ".dirname(__FILE__).'/bundle_vendor.tar.gz');
                return $res;
            }
            
            //remove bundle_vendor.tar.gz
            if($files->exists(dirname(__FILE__).'/bundle_vendor.tar.gz')) {
                $files->remove(dirname(__FILE__).'/bundle_vendor.tar.gz');
                $this->print_debug_log("Removed ".dirname(__FILE__).'/bundle_vendor.tar.gz');
            }
        }
        
        //lookup and download all from key packages
        //วิธีนี้ อาจเจอ timeout
        else {
            $this->print_debug_log("Download Vendor From list package");
            foreach($rvsbjson['packages'] as $package_key => $value){
                $update_package_name = $rvsbjson['packages'][$package_key]['name'];
                $update_package_version = isset($rvsbjson['packages'][$package_key]['version']) ? $rvsbjson['packages'][$package_key]['version'] : '';
                list($product_name, $app_name) = preg_split('/\//', $update_package_name, 2);
                $package_name_encoded = urlencode($app_name);
                if ($update_package_version != '') {
                    $update_package_version = '/version/' . $update_package_version;
                }
                
                $downloadvendorurl = $this->mirror.'/download/'.$product_name.'/'.urlencode($app_name).$update_package_version;
                $this->print_debug_log("Package URL Download ".$downloadvendorurl);
                
                $sha512verify['type'] = 'vendor';
                $sha512verify['getversionurl'] = '/getversion/vendor/'.urlencode($app_name).$update_package_version;
                $sha512verify['version'] = $rvsbjson['packages'][$package_key]['version'];
                $sha512verify['name'] = $rvsbjson['packages'][$package_key]['name'];
                
                $downloadvendor = $this->doDownload('GET' , $downloadvendorurl , dirname(__FILE__).'/'.$package_name_encoded.'.tar.gz' , $sha512verify);
                if($downloadvendor['success'] == false) {
                    if($files->exists(dirname(__FILE__).'/'.$package_name_encoded.'.tar.gz')) {
                        $files->remove(dirname(__FILE__).'/'.$package_name_encoded.'.tar.gz');
                    }
                    $res['message'] = 'Can not download vendor '.$downloadvendorurl.' '.$downloadvendor['message'];
                    $this->print_debug_log("Can not download ".$downloadvendorurl.' '.$downloadvendor['message']);
                    return $res;
                }
                $extractvendor = $this->doExtract(dirname(__FILE__).'/'.$package_name_encoded.'.tar.gz',dirname(__FILE__).'/tmp/vendor/');
                if($extractvendor['success'] == false) {
                    if($files->exists(dirname(__FILE__).'/'.$package_name_encoded.'.tar.gz')) {
                        $files->remove(dirname(__FILE__).'/'.$package_name_encoded.'.tar.gz');
                    }
                    $res['message'] = 'Can not extract vendor '.$package_name_encoded.' '.$extractvendor['message'];
                    $this->print_debug_log("Can not extract ".dirname(__FILE__).'/'.$package_name_encoded.'.tar.gz '.$extractvendor['message']);
                    return $res;
                }
                
                if($files->exists(dirname(__FILE__).'/'.$package_name_encoded.'.tar.gz')) {
                    $$files->remove(dirname(__FILE__).'/'.$package_name_encoded.'.tar.gz');
                    $this->print_debug_log("Removed ".dirname(__FILE__).'/'.$package_name_encoded.'.tar.gz');
                }
            }
            
        }
        
        //move vendor package to vendor path
        $source = dirname(__FILE__).'/tmp/vendor/vendor';
        $destination = dirname(__FILE__).'/tmp/vendor';
        $copy = $files->mirror($source, $destination);
        $remove = $files->remove($source);
        $this->print_debug_log("Moved vendor from $source to $destination");
        
        $res['status'] = false;
        $res['message'] = 'Download Vendor Success';
        return $res;
        
    }
    
    public function setup_env($domainname,$publicpath,$dbhost,$dbname,$dbuser,$dbpassword,$ftpaccount,$ftppassword,$appname,$ftpserver,$ftpport,$domainport,$welcomeemailtype,$homeuser,$cptype) {
        $time_start = microtime(true);
        $this->print_debug_log('======'.__METHOD__.'======');
        
        //clear whitespace
        if (preg_match('/\s/',$appname)) $appname = '"'.$appname.'"';
        //add domain port
        $appurl = ($domainport != '') ?  'https://'.$domainname.':'.$domainport : 'https://'.$domainname ;
        
        $env_data = [];
        $env_data['APP_URL'] = $appurl;
        $env_data['DB_HOST'] = $dbhost;
        $env_data['DB_DATABASE'] = $dbname;
        $env_data['DB_USERNAME'] = $dbuser;
        $env_data['DB_PASSWORD'] = $dbpassword;
        $env_data['HTTP_AS_USER'] = ($this->httpasuser) ? 'true' : 'false';
        $env_data['APP_NAME']   = $appname;
        $env_data['FTP_ACCOUNT'] = $ftpaccount;
        $env_data['FTP_PASSWORD'] = $ftppassword;
        $env_data['FTP_SERVER'] = $ftpserver;
        $env_data['FTP_PORT'] = $ftpport;
        $env_data['DOCUMENT_ROOT'] = $publicpath;
        $env_data['RV_MIRROR']  = $this->mirror;
        $env_data['DEVELOPER_TOKEN_KEY']  = '';
        $env_data['WELCOME_EMAIL_TYPE']  = $welcomeemailtype;
        $env_data['CP_TYPE']  = $cptype;
        $env_data['MAIL_FROM_ADDRESS']  = 'admin'.'@'.$domainname;
        
        
        $this->print_debug_log("ENV data ".json_encode($env_data));
        
        if($this->httpasuser) {
            $res = $this->do_setupenv($homeuser.'/rvsitebuildercms/'.$domainname, $env_data, true);
        }else {
            $res = $this->do_setupenv(dirname(__FILE__).'/tmp', $env_data, true);
        }
        
        $this->response['status'] = $res['status'];
        $this->response['message'] = $res['message'];
        $this->response['exectime'] = (microtime(true) - $time_start);
        $this->print_install_log(__METHOD__.' status:'.$this->response['status'].' message: '.$this->response['message'].' timeusage: '.$this->response['exectime']);
        return $this->print_response($this->response);
    }
    
    
    public function download_common_pkg($homeuser,$domainname,$additionalpkg) {
        $time_start = microtime(true);
        $this->print_debug_log('======'.__METHOD__.'======');
        
        $commonpkg = [
            'blog',
            'core',
            'email',
            'manage',
            'queuesharedhost',
            'scheduler',
            'wysiwyg',
            //TODO เอา marketing ออก พร้อมกับ rvsb release 7.2
            'marketing'
        ];
        
        if($additionalpkg != '') {
            $packages = json_decode($additionalpkg,true);
            foreach ($packages as $package) {
                array_push($commonpkg , $package);
            }
        }

        $this->print_debug_log("Common Package list ".json_encode($commonpkg));
        
        if($this->httpasuser) {
            $res = $this->download_common_pkg_httpasuser($commonpkg,$homeuser,$domainname);
        } else {
            $res = $this->download_common_pkg_no_httpasuser($commonpkg);
        }
    
        $this->response['status'] = $res['status'];
        $this->response['message'] = $res['message'];
        $this->response['exectime'] = (microtime(true) - $time_start);
        $this->print_install_log(__METHOD__.' status: '.$this->response['status'].' message: '.$this->response['message'].' timeusage: '.$this->response['exectime']);
        return $this->print_response($this->response);
        
    }
    
    public function download_common_pkg_no_httpasuser($commonpkg) {
        $this->print_debug_log('======'.__METHOD__.'======');
        
        $res = [
            'status'    => false,
            'message'   => '',
        ];
        
        $files = new Filesystem();
        foreach ($commonpkg as $pkg) {
            
            $sha512verify['type'] = 'commonkpg';
            $sha512verify['name'] = 'rvsitebuilder/'.$pkg;

            $downloadurl = $this->mirror.'/download/rvsitebuilder/'.$pkg ;
            $sha512verify['version_type'] = 'stable';
            if(isset($this->installerconfig[$pkg]['getversion']) && $this->installerconfig[$pkg]['getversion'] == 'latest')
            {
                $downloadurl = $this->mirror.'/download/rvsitebuilder/'.$pkg.'/tier/latest';
                $sha512verify['version_type'] = 'latest';
            }
            if(isset($this->installerconfig[$pkg]['getversion']) && preg_match('/[0-9]+\.[0-9]+\.[0-9]+/',$this->installerconfig[$pkg]['getversion']))
            {
                $downloadurl = $this->mirror.'/download/rvsitebuilder/'.$pkg.'/version/'.$this->installerconfig[$pkg]['getversion'];
                $sha512verify['version_type'] = 'version';
                $sha512verify['version'] = $this->installerconfig[$pkg]['getversion'];
                $sha512verify['getversionurl'] = 'getversion/rvsitebuilder/'.$pkg.'/version/'.$this->installerconfig[$pkg]['getversion'];
            }
            
            $this->print_debug_log("Download Common Package URL ".$downloadurl);
            
            //download package
            $downloadpkg = $this->doDownload('GET' , $downloadurl , dirname(__FILE__).'/'.$pkg.'.tar.gz' , $sha512verify);
            if($downloadpkg['success'] == false){
                if($files->exists(dirname(__FILE__).'/'.$pkg.'.tar.gz')) {
                    $files->remove(dirname(__FILE__).'/'.$pkg.'.tar.gz');
                }
                $res['message'] = 'Can not download package '.$pkg.' '.$downloadpkg['message'];
                $this->print_debug_log("Can not download package ".$pkg.' '.$downloadpkg['message']);
                return $res;
            }
            //extract package
            $extractpkg = $this->doExtract(dirname(__FILE__).'/'.$pkg.'.tar.gz',dirname(__FILE__).'/tmp/packages/');
            if($extractpkg['success'] == false) {
                if($files->exists(dirname(__FILE__).'/'.$pkg.'.tar.gz')) {
                    $files->remove(dirname(__FILE__).'/'.$pkg.'.tar.gz');
                }
                $res['message'] = 'Can not extract package '.$pkg.' '.$extractpkg['message'];
                $this->print_debug_log("Can not extract package ".$pkg.' '.$extractpkg['message']);
                return $res;
            }
            
            $rvsbjson = json_decode(file_get_contents(dirname(__FILE__).'/tmp/packages/rvsitebuilder/'.$pkg.'/rvsitebuilder.json'), true);
            foreach($rvsbjson['packages'] as $package_key => $value){
                $update_package_name = $rvsbjson['packages'][$package_key]['name'];
                $update_package_version = isset($rvsbjson['packages'][$package_key]['version']) ? $rvsbjson['packages'][$package_key]['version'] : '';
                list($product_name, $app_name) = preg_split('/\//', $update_package_name, 2);
                $app_name = urldecode($app_name);
                $package_name_encoded = urlencode($app_name);
                
                if(is_dir(dirname(__FILE__).'/tmp/' . $product_name . '/' . $app_name)){
                    $this->print_debug_log("Is DIR ".dirname(__FILE__).'/tmp/' . $product_name . '/' . $app_name." continue ");
                    continue;
                }
                
                if ($update_package_version != '') {
                    $update_package_version = '/version/' . $update_package_version;
                }

                $downloadvendorurl = $this->mirror.'/download/'.$product_name.'/'.urlencode($app_name).$update_package_version;
                $sha512vendorverify = array(
                    'type' => 'vendor',
                    'getversionurl' => '/getversion/vendor/'.urlencode($app_name).$version,
                    'version' => $rvsbjson['packages'][$package_key]['version'],
                    'name' => urlencode($app_name)
                );                
                
                $this->print_debug_log("Download vendor URL ".$downloadvendorurl);
                
                $downloadvendor = $this->doDownload('GET' , $downloadvendorurl , dirname(__FILE__).'/'.$package_name_encoded.'.tar.gz' , $sha512vendorverify);
                if($downloadvendor['success'] == false) {
                    if($files->exists(dirname(__FILE__).'/'.$package_name_encoded.'.tar.gz')) {
                        $files->remove(dirname(__FILE__).'/'.$package_name_encoded.'.tar.gz');
                    }
                    $res['message'] = 'Can not download vendor '.$downloadvendorurl.' '.$downloadvendor['message'];
                    $this->print_debug_log("Can not download vendor URL ".$downloadvendorurl.' '.$downloadvendor['message']);
                    return $res;
                }
                $extractvendor = $this->doExtract(dirname(__FILE__).'/'.$package_name_encoded.'.tar.gz',dirname(__FILE__).'/tmp/');
                if($extractvendor['success'] == false) {
                    if($files->exists(dirname(__FILE__).'/'.$package_name_encoded.'.tar.gz')) {
                        $files->remove(dirname(__FILE__).'/'.$package_name_encoded.'.tar.gz');
                    }
                    $res['message'] = 'Can not extract vendor '.$package_name_encoded.' '.$extractvendor['message'];
                    $this->print_debug_log("Can not extract ".dirname(__FILE__).'/'.$package_name_encoded.'.tar.gz '.$extractvendor['message']);
                    return $res;
                }
                
                $files->remove(dirname(__FILE__).'/'.$package_name_encoded.'.tar.gz');
                $this->print_debug_log("Removed ".dirname(__FILE__).'/'.$package_name_encoded.'.tar.gz');
                
            }
            
            $files->remove(dirname(__FILE__).'/'.$pkg.'.tar.gz');
            $this->print_debug_log("Removed ".dirname(__FILE__).'/'.$pkg.'.tar.gz');
        }
        
        $res['status'] =  true;
        $res['message'] = 'Download common package success.';
        return $res;
        
    }
    
    public function download_common_pkg_httpasuser($commonpkg,$homeuser,$domainname) {
        $this->print_debug_log('======'.__METHOD__.'======');
        
        $res = [
            'status' => false,
            'message' => ''
        ];
        
        $files = new Filesystem();
        
        foreach ($commonpkg as $pkg) {
            
            $sha512verify['type'] = 'commonkpg';
            $sha512verify['name'] = 'rvsitebuilder/'.$pkg;

            $downloadurl = $this->mirror.'/download/rvsitebuilder/'.$pkg ;
            $sha512verify['version_type'] = 'stable';
            if(isset($this->installerconfig[$pkg]['getversion']) && $this->installerconfig[$pkg]['getversion'] == 'latest')
            {
                $downloadurl = $this->mirror.'/download/rvsitebuilder/'.$pkg.'/tier/latest';
                $sha512verify['version_type'] = 'latest';
            }
            if(isset($this->installerconfig[$pkg]['getversion']) && preg_match('/[0-9]+\.[0-9]+\.[0-9]+/',$this->installerconfig[$pkg]['getversion']))
            {
                $downloadurl = $this->mirror.'/download/rvsitebuilder/'.$pkg.'/version/'.$this->installerconfig[$pkg]['getversion'];
                $sha512verify['version_type'] = 'version';
                $sha512verify['version'] = $this->installerconfig[$pkg]['getversion'];
                $sha512verify['getversionurl'] = '/getversion/rvsitebuilder/'.$pkg.'/version/'.$this->installerconfig[$pkg]['getversion'];
            }
            
            $this->print_debug_log("Download Common Package URL ".$downloadurl);
            
            //download package
            $downloadpkg = $this->doDownload('GET' , $downloadurl , dirname(__FILE__).'/'.$pkg.'.tar.gz' , $sha512verify);
            if($downloadpkg['success'] == false){
                if($files->exists(dirname(__FILE__).'/'.$pkg.'.tar.gz')) {
                    $files->remove(dirname(__FILE__).'/'.$pkg.'.tar.gz');
                }
                $res['message'] = 'Can not download package '.$pkg.' '.$downloadpkg['message'];
                $this->print_debug_log("Can not download package ".$pkg.' '.$downloadpkg['message']);
                return $res;
            }
            
            //extract package
            $extractpkg = $this->doExtract(dirname(__FILE__).'/'.$pkg.'.tar.gz' , $homeuser.'/rvsitebuildercms/'.$domainname.'/packages/');
            if($extractpkg['success'] == false) {
                if($files->exists(dirname(__FILE__).'/'.$pkg.'.tar.gz')) {
                    $files->remove(dirname(__FILE__).'/'.$pkg.'.tar.gz');
                }
                $res['message'] = 'Can not extract package '.$pkg.' '.$extractpkg['message'];
                $this->print_debug_log("Can not extract package ".$pkg.' '.$extractpkg['message']);
                return $res;
            }
            
            $rvsbjson = json_decode(file_get_contents($homeuser.'/rvsitebuildercms/'.$domainname.'/packages/rvsitebuilder/'.$pkg.'/rvsitebuilder.json'), true);
            
            foreach($rvsbjson['packages'] as $package_key => $value){
                $update_package_name = $rvsbjson['packages'][$package_key]['name'];
                $update_package_version = isset($rvsbjson['packages'][$package_key]['version']) ? $rvsbjson['packages'][$package_key]['version'] : '';
                list($product_name, $app_name) = preg_split('/\//', $update_package_name, 2);
                $app_name = urldecode($app_name);
                $package_name_encoded = urlencode($app_name);
                
                if(is_dir($homeuser.'/rvsitebuildercms/'.$domainname.'/'.$product_name .'/'.$app_name)){
                    $this->print_debug_log("Is DIR ".$homeuser.'/rvsitebuildercms/'.$domainname.'/'.$product_name .'/'.$app_name." continue ");
                    continue;
                }
                
                if ($update_package_version != '') {
                    $update_package_version = '/version/' . $update_package_version;
                }
                
                $downloadvendorurl = $this->mirror.'/download/'.$product_name.'/'.urlencode($app_name).$update_package_version;
                $sha512vendorverify = array(
                    'type' => 'vendor',
                    'getversionurl' => '/getversion/'.$product_name.'/'.urlencode($app_name).$update_package_version,
                    'version' => $rvsbjson['packages'][$package_key]['version'],
                    'name' => $rvsbjson['packages'][$package_key]['name']
                );                
                
                $this->print_debug_log("Download vendor URL ".$downloadvendorurl);
                $downloadvendor = $this->doDownload('GET' , $downloadvendorurl , dirname(__FILE__).'/'.$package_name_encoded.'.tar.gz' , $sha512vendorverify);
                if($downloadvendor['success'] == false) {
                    if($files->exists(dirname(__FILE__).'/'.$package_name_encoded.'.tar.gz')) {
                        $files->remove(dirname(__FILE__).'/'.$package_name_encoded.'.tar.gz');
                    }
                    $res['message'] = 'Can not download vendor '.$downloadvendorurl.' '.$downloadvendor['message'];
                    $this->print_debug_log("Can not download vendor URL ".$downloadvendorurl.' '.$downloadvendor['message']);
                    return $res;
                }
                $extractvendor = $this->doExtract(dirname(__FILE__).'/'.$package_name_encoded.'.tar.gz' , $homeuser.'/rvsitebuildercms/'.$domainname.'/');
                if($extractvendor['success'] == false) {
                    if($files->exists(dirname(__FILE__).'/'.$package_name_encoded.'.tar.gz')) {
                        $files->remove(dirname(__FILE__).'/'.$package_name_encoded.'.tar.gz');
                    }
                    $res['message'] = 'Can not extract vendor '.$package_name_encoded.' '.$extractvendor['message'];
                    $this->print_debug_log("Can not extract ".dirname(__FILE__).'/'.$package_name_encoded.'.tar.gz '.$extractvendor['message']);
                    return $res;
                }
                
                $files->remove(dirname(__FILE__).'/'.$package_name_encoded.'.tar.gz');
                $this->print_debug_log("Removed ".dirname(__FILE__).'/'.$package_name_encoded.'.tar.gz');
            }
            
            $files->remove(dirname(__FILE__).'/'.$pkg.'.tar.gz');
            $this->print_debug_log("Removed ".dirname(__FILE__).'/'.$pkg.'.tar.gz');
        }
        $res['status'] =  true;
        $res['message'] = 'Download common package success.';
        return $res;
    }
    
    
    public function artisan_call($homeuser,$domainname,$publicpath,$adminemail,$adminpassword,$adminfirstname,$adminlastname,$isrepair) {
        $time_start = microtime(true);
        $this->print_debug_log('======'.__METHOD__.'======');
        
        //loader
        // /home/arnut/rvsitebuildercms/arnut.cpdev1.rvglobalsoft.net/vendor/autoload.php
        $loader = require $homeuser.'/rvsitebuildercms/'.$domainname.'/vendor/autoload.php';
        $this->print_debug_log("require  ".$homeuser.'/rvsitebuildercms/'.$domainname.'/vendor/autoload.php');
        
        //change path app_path/rvsitebuildercms/storage/packages to app_path/rvsitebuildercms/packages
        $packagesPath = $homeuser.'/rvsitebuildercms/'.$domainname.'/packages';
        $vendor_names = scandir($packagesPath);
        foreach($vendor_names as $vendor_name){
            if($vendor_name === '.' || $vendor_name === '..') {continue;}
            $package_names = scandir($packagesPath . '/' . $vendor_name);
            foreach($package_names as $package_name){
                if($package_name === '.' || $package_name === '..') {continue;}
                $auto_load_file = $packagesPath . '/' . $vendor_name . '/' . $package_name . '/vendor/autoload.php';
                if(is_file($auto_load_file)){
                    require $auto_load_file;
                    $this->print_debug_log("require  ".$auto_load_file);
                }
            }
        }
        
        //call artisan
        $app = require_once  $homeuser.'/rvsitebuildercms/'.$domainname.'/bootstrap/app.php';
        $this->print_debug_log('require_one '.$homeuser.'/rvsitebuildercms/'.$domainname.'/bootstrap/app.php');
        $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
        
        
        //Common
        $unit_time_start = microtime(true);
        $kernel->call('key:generate', []);
        $this->print_debug_log($kernel->output());
        $unit_exec_time = (microtime(true) - $unit_time_start);
        $this->print_install_log('artisan key:generate timeusage '.$unit_exec_time);
        
        $unit_time_start = microtime(true);
        $kernel->call('migrate', ['--force'=>true]);
        $this->print_debug_log($kernel->output());
        $unit_exec_time = (microtime(true) - $unit_time_start);
        $this->print_install_log('artisan migrate --force timeusage '.$unit_exec_time);
        
        if(! $isrepair) {
            $unit_time_start = microtime(true);
            $kernel->call('db:seed', ['--force'=>true]);
            $this->print_debug_log($kernel->output());
            $unit_exec_time = (microtime(true) - $unit_time_start);
            $this->print_install_log('artisan db:seed --force timeusage '.$unit_exec_time);
            
            //user secret key
            $unit_time_start = microtime(true);
            $kernel->call('rvsitebuilder:updateenduserdb-run', ['dbkey'=>'secretkey','dbvalue'=>$this->generateSecretKey()]);
            $this->print_debug_log($kernel->output());
            $unit_exec_time = (microtime(true) - $unit_time_start);
            $this->print_install_log('artisan rvsitebuilder:updateenduserdb-run timeusage '.$unit_exec_time);
            
            
            if(function_exists('proc_open') && function_exists('proc_close')){
                //email notify to admin
                $this->print_debug_log("Send welcome email to admin");
                $unit_time_start = microtime(true);
                try{
                    $kernel->call('rvsitebuilder:notifynewinstall', []);
                    $this->print_debug_log($kernel->output());
                } catch (Exception $e){
                    $this->print_debug_log($e->getMessage());
                }
                $unit_exec_time = (microtime(true) - $unit_time_start);
                $this->print_install_log('rvsitebuilder:notifynewinstall timeusage'.$unit_exec_time);
            }
            
        }
        
        
        //update user admin information
        if($adminemail != '') {
            $this->print_debug_log("Update user info to DB adminemail=$adminemail");
            $unit_time_start = microtime(true);
            $kernel->call('rvsitebuilder:updateuserinfo-run', ['user_id' => 1,'update_key' => 'email', 'update_val' => $adminemail]);
            $this->print_debug_log($kernel->output());
            $unit_exec_time = (microtime(true) - $unit_time_start);
            $this->print_install_log('artisan rvsitebuilder:updateuserinfo-run timeusage '.$unit_exec_time);
        }
        if($adminpassword != '') {
            $this->print_debug_log("Update user info to DB adminpassword=$adminpassword");
            $unit_time_start = microtime(true);
            $kernel->call('rvsitebuilder:updateuserinfo-run', ['user_id' => 1,'update_key' => 'password', 'update_val' => $adminpassword]);
            $this->print_debug_log($kernel->output());
            $unit_exec_time = (microtime(true) - $unit_time_start);
            $this->print_install_log('artisan rvsitebuilder:updateuserinfo-run timeusage '.$unit_exec_time);
        }
        if($adminfirstname != '') {
            $this->print_debug_log("Update user info to DB adminfirstname=$adminfirstname");
            $unit_time_start = microtime(true);
            $kernel->call('rvsitebuilder:updateuserinfo-run', ['user_id' => 1,'update_key' => 'first_name', 'update_val' => $adminfirstname]);
            $this->print_debug_log($kernel->output());
            $unit_exec_time = (microtime(true) - $unit_time_start);
            $this->print_install_log('artisan rvsitebuilder:updateuserinfo-run timeusage '.$unit_exec_time);
        }
        if($adminlastname != '') {
            $this->print_debug_log("Update user info to DB adminlastname=$adminlastname");
            $unit_time_start = microtime(true);
            $kernel->call('rvsitebuilder:updateuserinfo-run', ['user_id' => 1,'update_key' => 'last_name', 'update_val' => $adminlastname]);
            $this->print_debug_log($kernel->output());
            $unit_exec_time = (microtime(true) - $unit_time_start);
            $this->print_install_log('artisan rvsitebuilder:updateuserinfo-run timeusage '.$unit_exec_time);
        }
        
        
        //vendor publish
        if($isrepair){
            //remove logo in public befor vendor:publish because if will overwrite site logo in public_html
            $logofile = $homeuser.'/rvsitebuildercms/'.$domainname.'/public/storage/images/logo.png';
            $files = new Filesystem();
            if($files->exists($logofile)){
                $files->remove($logofile);
            }
        }
        $unit_time_start = microtime(true);
        $kernel->call('vendor:publish', ['--tag'=> 'public','--force' => true]);
        $this->print_debug_log($kernel->output());
        $unit_exec_time = (microtime(true) - $unit_time_start);
        $this->print_install_log('artisan vendor:publish timeusage '.$unit_exec_time);
        
        //clear cache
        $unit_time_start = microtime(true);
        $kernel->call('cache:clear', []);
        $this->print_debug_log($kernel->output());
        $unit_exec_time = (microtime(true) - $unit_time_start);
        $this->print_install_log('artisan cache:clear timeusage '.$unit_exec_time);
        
        $unit_time_start = microtime(true);
        $kernel->call('config:clear', []);
        $this->print_debug_log($kernel->output());
        $unit_exec_time = (microtime(true) - $unit_time_start);
        $this->print_install_log('artisan config:clear timeusage '.$unit_exec_time);
        
        
        /*
         $unit_time_start = microtime(true);
         $kernel->call('config:cache', []);
         $this->print_debug_log($kernel->output());
         $unit_exec_time = (microtime(true) - $unit_time_start);
         $this->print_install_log('artisan config:cache timeusage '.$unit_exec_time);
         */
        
        $unit_time_start = microtime(true);
        $kernel->call('route:clear', []);
        $this->print_debug_log($kernel->output());
        $unit_exec_time = (microtime(true) - $unit_time_start);
        $this->print_install_log('artisan route:clear timeusage '.$unit_exec_time);
        
        $unit_time_start = microtime(true);
        $kernel->call('view:clear', []);
        $this->print_debug_log($kernel->output());
        $unit_exec_time = (microtime(true) - $unit_time_start);
        $this->print_install_log('artisan view:clear timeusage '.$unit_exec_time);
        
        
        
        $this->response['status'] = true;
        $this->response['message'] = 'Artisan Command Success';
        $this->response['exectime'] = (microtime(true) - $time_start);
        $this->print_install_log(__METHOD__.' status TRUE'.' timeusage '.$this->response['exectime']);
        return $this->print_response($this->response);
    }
    
    public function artisan_cmd_run($homeuser,$domainname,$artisancmd,$artisanparam){
        //called from tryout
        $time_start = microtime(true);
        $this->print_debug_log('======'.__METHOD__.'======');
        
        //loader
        // /home/arnut/rvsitebuildercms/.cpdev1.rvglobalsoft.net/vendor/autoload.php
        $loader = require $homeuser.'/rvsitebuildercms/'.$domainname.'/vendor/autoload.php';
        $this->print_debug_log("require  ".$homeuser.'/rvsitebuildercms/'.$domainname.'/vendor/autoload.php');
        
        //change path app_path/rvsitebuildercms/storage/packages to app_path/rvsitebuildercms/packages
        $packagesPath = $homeuser.'/rvsitebuildercms/'.$domainname.'/packages';
        
        $vendor_names = scandir($packagesPath);
        foreach($vendor_names as $vendor_name){
            if($vendor_name === '.' || $vendor_name === '..') {continue;}
            $package_names = scandir($packagesPath . '/' . $vendor_name);
            foreach($package_names as $package_name){
                if($package_name === '.' || $package_name === '..') {continue;}
                $auto_load_file = $packagesPath . '/' . $vendor_name . '/' . $package_name . '/vendor/autoload.php';
                if(is_file($auto_load_file)){
                    require $auto_load_file;
                    $this->print_debug_log("require  ".$auto_load_file);
                }
            }
        }
        
        //call artisan
        $app = require_once  $homeuser.'/rvsitebuildercms/'.$domainname.'/bootstrap/app.php';
        $this->print_debug_log('require_one '.$homeuser.'/rvsitebuildercms/'.$domainname.'/bootstrap/app.php');
        $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
        
        if($artisancmd != '' && $artisanparam !='{}') {
            $param = json_decode($artisanparam ,  true);
            $kernel->call($artisancmd, $param);
            $this->print_debug_log($kernel->output());
        }
        
        //clear cache
        $unit_time_start = microtime(true);
        $kernel->call('cache:clear', []);
        $this->print_debug_log($kernel->output());
        $unit_exec_time = (microtime(true) - $unit_time_start);
        $this->print_install_log('artisan cache:clear timeusage '.$unit_exec_time);
        
        $unit_time_start = microtime(true);
        $kernel->call('config:clear', []);
        $this->print_debug_log($kernel->output());
        $unit_exec_time = (microtime(true) - $unit_time_start);
        $this->print_install_log('artisan config:clear timeusage '.$unit_exec_time);
        
        $unit_time_start = microtime(true);
        $kernel->call('route:clear', []);
        $this->print_debug_log($kernel->output());
        $unit_exec_time = (microtime(true) - $unit_time_start);
        $this->print_install_log('artisan route:clear timeusage '.$unit_exec_time);
        
        $unit_time_start = microtime(true);
        $kernel->call('view:clear', []);
        $this->print_debug_log($kernel->output());
        $unit_exec_time = (microtime(true) - $unit_time_start);
        $this->print_install_log('artisan view:clear timeusage '.$unit_exec_time);
        
        
        $this->response['status'] = true;
        $this->response['message'] = 'Artisan Command Run Success';
        $this->response['exectime'] = (microtime(true) - $time_start);
        $this->print_install_log(__METHOD__.' status TRUE'.' timeusage '.$this->response['exectime']);
        return $this->print_response($this->response);
        
    }
    
    public function finished_setup($homeuser,$domainname,$publicpath,$ftpaccount,$ftppassword,$ftpserver,$ftpport) {
        $time_start = microtime(true);
        $this->print_debug_log('======'.__METHOD__.'======');
        
        //default
        if($this->httpasuser){
            //touch install completed
            $res =  $this->finished_setup_httpasuser($homeuser,$domainname,$publicpath);
        }
        //ftp
        else {
            //install completed
            $res = $this->finished_setup_no_httpasuser($homeuser,$domainname,$publicpath,$ftpaccount,$ftppassword,$ftpserver,$ftpport);
        }
        
        $this->response['status'] = $res['status'];
        $this->response['message'] = $res['message'];
        $this->response['exectime'] = (microtime(true) - $time_start);
        $this->print_install_log(__METHOD__.' status: '.$this->response['status'].' message: '.$this->response['message'].' timeusage: '.$this->response['exectime']);
        return $this->print_response($this->response);
        
    }
    
    public function finished_setup_httpasuser($homeuser,$domainname,$publicpath){
        $this->print_debug_log('======'.__METHOD__.'======');
        
        $res = [
            'status' => false,
            'message' => ''
        ];
        
        $files = new Filesystem();
        
        $this->print_debug_log("TOUCH INSTALL_COMPLETED");
        
        //touch install complete
        $files->touch($homeuser.'/rvsitebuildercms/'.$domainname.'/INSTALL_COMPLETED');
        //install_log , error_log , rvdebug
        if($files->exists($publicpath.'/rvsitebuilder/install_log.txt') && $this->debugmode){
            $files->copy($publicpath.'/rvsitebuilder/install_log.txt', $publicpath.'/rvsitebuilder_install_log.txt');
        }
        if($files->exists($publicpath.'/rvsitebuilder/error_log') && $this->debugmode){
            $files->copy($publicpath.'/rvsitebuilder/error_log', $publicpath.'/rvsitebuilder_install_error_log.txt');
        }
        if($files->exists($publicpath.'/rvsitebuilder/rvdebug.php') && $this->debugmode){
            $files->copy($publicpath.'/rvsitebuilder/rvdebug.php', $publicpath.'/rvdebug.php');
        }
        //rename .html,.htm to .html.bak,.htm.bak when exist .html,.htm file
        if($files->exists($publicpath.'/index.html')){
            $files->rename($publicpath.'/index.html' , $publicpath.'/index.html.bak');
        }
        if($files->exists($publicpath.'/index.htm')){
            $files->rename($publicpath.'/index.htm' , $publicpath.'/index.htm.bak');
        }
        
        $res['status'] = true;
        $res['message'] = 'Make finished Setup (Default)';
        $this->print_debug_log('Make finished Setup (Default)');
        return $res;
    }
    
    public function finished_setup_no_httpasuser($homeuser,$domainname,$publicpath,$ftpaccount,$ftppassword,$ftpserver,$ftpport){
        $this->print_debug_log('======'.__METHOD__.'======');
        
        $res = [
            'status' => false,
            'message' => ''
        ];
        
        $this->print_debug_log("FTP INSTALL_COMPLETED");
        
        $ftpHandler = new FTP_Handler();
        $result = $ftpHandler->connect($ftpserver);
        if(!$result['success']){
            $res['message'] = 'Error '.$result['msg'];
            $this->print_debug_log($res['message']);
            return $res;
        }
        $result = $ftpHandler->login($ftpaccount, $ftppassword);
        if(!$result['success']){
            $res['message'] = 'Error '.$result['msg'];
            $this->print_debug_log($res['message']);
            return $res;
        }
        
        $result = $ftpHandler->put($publicpath.'/rvsitebuilder/INSTALL_COMPLETED','/rvsitebuildercms/'.$domainname.'/INSTALL_COMPLETED',FTP_BINARY);
        
        //install_log , error_log
        $exploded = explode('/',$publicpath);
        $public_html = '/'.end($exploded);
        if(file_exists($publicpath.'/rvsitebuilder/install_log.txt')){
            $result = $ftpHandler->put($publicpath.'/rvsitebuilder/install_log.txt' , '/'.$public_html.'/rvsitebuilder_install_log.txt',FTP_BINARY);
        }
        if(file_exists($publicpath.'/rvsitebuilder/error_log')){
            $result = $ftpHandler->put($publicpath.'/rvsitebuilder/error_log' , '/'.$public_html.'/rvsitebuilder_install_error_log.txt' ,FTP_BINARY);
        }
        if(file_exists($publicpath.'/rvsitebuilder/rvdebug.php')){
            $result = $ftpHandler->put($publicpath.'/rvsitebuilder/rvdebug.php' , '/'.$public_html.'/rvdebug.php' ,FTP_BINARY);
        }
        if(file_exists($publicpath.'/index.html')){
            $result = $ftpHandler->rename($publicpath.'/index.html' , '/'.$public_html.'/index.html.bak');
        }
        if(file_exists($publicpath.'/index.htm')){
            $result = $ftpHandler->rename($publicpath.'/index.htm' , '/'.$public_html.'/index.htm.bak');
        }
        
        $ftpHandler->close();
        
        $res['status'] = true;
        $res['message'] = 'Make finished Setup (FTP)';
        $this->print_debug_log('Make finished Setup (FTP)');
        return $res;
        
    }
    
    public function remove_installer_api() {
        $time_start = microtime(true);
        $this->print_debug_log('======'.__METHOD__.'======');
        
        //remove installer dir , setup.zip
        if($this->removeinstallerpath) {
            $files = new Filesystem();
            $files->remove(dirname(__FILE__).'/../rvsitebuilder');
            $files->remove(dirname(__FILE__).'/../setup.zip');
            $this->print_debug_log("Removed Installer Path / Removed setup.zip");
        } else {
            $this->print_debug_log("Not removed installer path by root or user installer config");
        }
        
        //response
        $this->response['status'] = true;
        $this->response['message'] = 'Remove Installer';
        $this->response['exectime'] = (microtime(true) - $time_start);
        $this->print_install_log(__METHOD__.' status TRUE'.' timeusage '.$this->response['exectime']);
        return $this->print_response($this->response);
        
    }
    
//     public function validate_developer_token($devtokenkey) {
//         $time_start = microtime(true);
//         $this->print_debug_log('======'.__METHOD__.'======');
        
//         $this->response['dev_token'] = false;
        
//         try {
//             $client = new Client([
//                 'curl'            => [CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false],
//                 'allow_redirects' => false,
//                 'cookies'         => true,
//                 'verify'          => false
//             ]);
//             $this->print_debug_log("Validate developer token Type=POST URL=http://license3.rvglobalsoft.com/validatetoken Devtoken=$devtokenkey");
//             $form_params = ['devtokenkey'=>$devtokenkey];
//             $res = $client->request('POST', 'http://license3.rvglobalsoft.com/validatetoken' ,['form_params' => $form_params]);
//             $this->print_debug_log('Server Response Status '.$res->getStatusCode());
//             $this->print_debug_log('Server Response Header '.json_encode((array) $res->getHeaders()));
//             $rescontent = json_decode($res->getBody(), true);
//             if (isset($rescontent['status']) && true == $rescontent['status']) {
//                 $this->response['dev_token'] = true;
//             } else {
//                 $this->response['message'] = $rescontent['message'];
//             }
//         } catch (\Exception $e) {
//             $this->print_debug_log('Validate developer token error '.$e->getMessage());
//             $this->response['message']  = $e->getMessage();
//         }
//         $this->response['status'] = true;
//         $this->response['exectime'] = (microtime(true) - $time_start);
//         return $this->print_response($this->response);
//     }
    
    public function generateSecretKey($length = 64) {
        $this->print_debug_log('======'.__METHOD__.'======');
        
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randstring = '';
        for ($i = 0; $i < $length; $i++) {
            $randstring .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randstring;
    }
    
    
    
    public function install_all_pkg($homeuser,$domainname,$publicpath,$ftpaccount,$ftppassword,$ftpserver,$ftpport) {
        $time_start = microtime(true);
        $this->print_debug_log('======'.__METHOD__.'======');
        
        $moveby = '';
        $joinresmsg = '';
        if($this->httpasuser == true) {
            $moveby = 'default';
            $res = $this->create_htaccess_httpasuser($homeuser,$domainname,$publicpath);
        } else {
            $moveby = 'FTP';
            $exploded = explode('/',$publicpath);
            $public_html = '/'.end($exploded);
            $res = $this->move_to_app_path_no_httpasuser($homeuser,$domainname,$publicpath,$ftpaccount,$ftppassword,$ftpserver,$ftpport,$public_html);
            $joinresmsg = $res['message'];
            $res = $this->create_htaccess_no_httpasuser($homeuser,$domainname,$publicpath,$ftpaccount,$ftppassword,$ftpserver,$ftpport,$public_html);
            $joinresmsg = $joinresmsg . $res['message'];
            $res = $this->chmod_no_httpasuser($homeuser,$domainname,$publicpath,$ftpaccount,$ftppassword,$ftpserver,$ftpport,$public_html);
            $joinresmsg = $joinresmsg . $res['message'];
        }
        
        $this->response['status'] = $res['status'];
        $this->response['message'] = $res['message'].' '.$joinresmsg.' by'.$moveby;
        $this->response['exectime'] = (microtime(true) - $time_start);
        $this->print_install_log(__METHOD__.' status: '.$this->response['status'].' message: '.$this->response['message'].' timeusage: '.$this->response['exectime']);
        return $this->print_response($this->response);
        
    }
    
    function create_htaccess_httpasuser($homeuser,$domainname,$publicpath) {
        $this->print_debug_log('======'.__METHOD__.'======');
        
        $res = [
            'status' =>  true,
            'message' => ''
        ];
        
        $files =  new Filesystem();
        
        //old htaccess
        $oldhtaccess = '';
        if ($files->exists($publicpath.'/.htaccess')) {
            $oldhtaccess = file_get_contents($publicpath.'/.htaccess');
            $this->print_debug_log("Has Old .htaccess $publicpath/.htaccess");
        }
        
        //write new .htaccess to docroot
        $frameworkhtaccess = '';
        if ($files->exists($homeuser.'/rvsitebuildercms/'.$domainname.'/public/.htaccess')) {
            $frameworkhtaccess = file_get_contents($homeuser.'/rvsitebuildercms/'.$domainname.'/public/.htaccess');
            $frameworkhtaccess = "#Start Rvsitebuilder7 htaccess\n".
                                 $frameworkhtaccess."\n".
                                 "#End Rvsitebuilder7 htaccess\n";
        }
        
        //case not old htaccess
        if ($oldhtaccess == '') {
            $writehtaccess =  file_put_contents($publicpath.'/.htaccess' , $frameworkhtaccess);
            $this->print_debug_log("file put new .htaccess ".$publicpath.'/.htaccess');
        }
        //case old htaccess to prepend
        elseif ($oldhtaccess != '' && !preg_match('/^#Start Rvsitebuilder7 htaccess$/im', $oldhtaccess)) {
            $writeoldhtaccess = file_put_contents($publicpath.'/.htaccess.backup' , $oldhtaccess);
            $this->print_debug_log("file put backup .htaccess ".$publicpath.'/.htaccess.backup');
            $writehtaccess =  file_put_contents($publicpath.'/.htaccess' , $frameworkhtaccess."\n".$oldhtaccess);
            $this->print_debug_log("file put prepend .htaccess ".$publicpath.'/.htaccess');
        }
        //case old htaccess and has old rvsb htaccess
        else {
            //htaccess has rvsitebuilder content , write new rvsb htaccess
            $newhtaccess = preg_replace('/#Start Rvsitebuilder7 htaccess.*?#End Rvsitebuilder7 htaccess/mis', $frameworkhtaccess ,$oldhtaccess);
            $writehtaccess =  file_put_contents($publicpath.'/.htaccess' , $newhtaccess);
            $this->print_debug_log("write old htaccess  ,cause it already has a rvsitebuilder7 htaccess content");
        }
        
        if($writehtaccess) {
            $res['status'] = true;
            $res['message'] = 'Write htaccess success.';
            $this->print_debug_log("Write htaccess success.");
        } else {
            $res['message'] = 'Write htaccess failed.';
            $this->print_debug_log("Write htaccess failed.");
        }
        
        return $res;
        
    }
    
    function move_to_app_path_no_httpasuser($homeuser,$domainname,$publicpath,$ftpaccount,$ftppassword,$ftpserver,$ftpport,$public_html) {
        $this->print_debug_log('======'.__METHOD__.'======');
        
        $res = [
            'status' => false,
            'message' => ''
        ];
        
        $ftpHandler = new FTP_Handler();
        $result = $ftpHandler->connect($ftpserver);
        if(!$result['success']){
            $res['message'] = 'Error '.$result['msg'];
            $this->print_debug_log("Can not connect FTP ".$result['msg']);
            return $res;
        }
        $result = $ftpHandler->login($ftpaccount, $ftppassword);
        if(!$result['success']){
            $res['message'] = 'Error '.$result['msg'];
            $this->print_debug_log("Can not login FTP ".$result['msg']);
            return $res;
        }
        if(!file_exists($ftp_remote_dir)){
            $result = $ftpHandler->ftp_make_dir($homeuser,$ftp_remote_dir);
            if(!$result['success']) {
                $res['message'] = 'Error '.$result['msg'];
                $this->print_debug_log("Can make dir  FTP $homeuser , $ftp_remote_dir ".$result['msg']);
                return $res;
            }
        }
        
        
        #copy file to framework path
        $src_dir = $publicpath.'/rvsitebuilder/tmp';
        $ftp_remote_dir = '/rvsitebuildercms/'.$domainname;
        $ftpHandler->ftp_copy($src_dir, $ftp_remote_dir);
        $this->print_debug_log("FTP copy framework $src_dir To $ftp_remote_dir");
        
        #copy file to public path
        $src_dir = $homeuser.'/rvsitebuildercms/'.$domainname.'/public';
        $ftpHandler->ftp_copy($src_dir, $public_html , ['.htaccess']);
        $this->print_debug_log("FTP copy public $src_dir To $public_html");
        
        #close connect
        $ftpHandler->close();
        
        $res['status'] = true;
        $res['message'] = 'Move Freamwork and Public success (FTP)';
        return $res;
        
    }
    
    public function create_htaccess_no_httpasuser($homeuser,$domainname,$publicpath,$ftpaccount,$ftppassword,$ftpserver,$ftpport,$public_html) {
        $this->print_debug_log('======'.__METHOD__.'======');
        
        $res = [
            'status' => false,
            'message' => ''
        ];
        
        $ftpHandler = new FTP_Handler();
        $result = $ftpHandler->connect($ftpserver);
        if(!$result['success']){
            $res['message'] = 'Error '.$result['msg'];
            $this->print_debug_log("Can not connect FTP ".$result['msg']);
            return $res;
        }
        $result = $ftpHandler->login($ftpaccount, $ftppassword);
        if(!$result['success']){
            $res['message'] = 'Error '.$result['msg'];
            $this->print_debug_log("Can not login FTP ".$result['msg']);
            return $res;
        }
        if(!file_exists($ftp_remote_dir)){
            $result = $ftpHandler->ftp_make_dir($homeuser,$ftp_remote_dir);
            if(!$result['success']) {
                $res['message'] = 'Error '.$result['msg'];
                $this->print_debug_log("Can make dir  FTP $homeuser , $ftp_remote_dir ".$result['msg']);
                return $res;
            }
        }
        
        //write .htaccess to domain'docroot
        $frameworkhtaccess = '';
        if (file_exists($homeuser.'/rvsitebuildercms/'.$domainname.'/public/.htaccess')) {
            $frameworkhtaccess = file_get_contents($homeuser.'/rvsitebuildercms/'.$domainname.'/public/.htaccess');
            $frameworkhtaccess = "#Start Rvsitebuilder7 htaccess\n".
                                 $frameworkhtaccess."\n".
                                 "#End Rvsitebuilder7 htaccess\n";
        }
        $oldhtaccess = '';
        if (file_exists($publicpath.'/.htaccess')) {
            $oldhtaccess = file_get_contents($publicpath.'/.htaccess');
        }
        //case not old htaccess
        if ($oldhtaccess == '') {
            //write new htaccess
            $writehtaccess =  file_put_contents(dirname(__FILE__).'/htaccess.tmp' , $frameworkhtaccess);
            $this->print_debug_log("Create tmp new .htaccess ".dirname(__FILE__)."/htaccess.tmp");
            $result = $ftpHandler->put(dirname(__FILE__).'/htaccess.tmp',$public_html.'/.htaccess',FTP_BINARY);
            $this->print_debug_log("FTP put new ".dirname(__FILE__).'/htaccess.tmp'.' To '.$public_html.'/.htaccess');
        }
        //case old htaccess to prepend
        elseif ($oldhtaccess != '' && !preg_match('/^#Start Rvsitebuilder7 htaccess$/im', $oldhtaccess)) {
            //backup old htaccess
            $writeoldhtaccess = file_put_contents(dirname(__FILE__).'/htaccess.backup' , $oldhtaccess);
            $this->print_debug_log("Write backup old .htaccess ".dirname(__FILE__)."/htaccess.backup");
            $result = $ftpHandler->put(dirname(__FILE__).'/htaccess.backup',$public_html.'/.htaccess.backup',FTP_BINARY);
            $this->print_debug_log("FTP put ".dirname(__FILE__).'/htaccess.backup'.' To '.$public_html.'/.htaccess.backup');
            //write new htaccess
            $writehtaccess =  file_put_contents(dirname(__FILE__).'/htaccess.tmp' , $frameworkhtaccess."\n".$oldhtaccess);
            $this->print_debug_log("Create tmp prepend .htaccess ".dirname(__FILE__)."/htaccess.tmp");
            $result = $ftpHandler->put(dirname(__FILE__).'/htaccess.tmp',$public_html.'/.htaccess',FTP_BINARY);
            $this->print_debug_log("FTP put prepend ".dirname(__FILE__).'/htaccess.tmp'.' To '.$public_html.'/.htaccess');
        }
        //case old htaccess and has old rvsb htaccess
        else {
            //htaccess has rvsitebuilder content , write new rvsb htaccess
            $newhtaccess = preg_replace('/#Start Rvsitebuilder7 htaccess.*?#End Rvsitebuilder7 htaccess/mis', $frameworkhtaccess ,$oldhtaccess);
            $writehtaccess =  file_put_contents(dirname(__FILE__).'/htaccess.tmp' , $newhtaccess);
            $result = $ftpHandler->put(dirname(__FILE__).'/htaccess.tmp',$public_html.'/.htaccess',FTP_BINARY);
            $this->print_debug_log("write old htaccess  ,cause it already has a rvsitebuilder7 htaccess content");
        }
        
        #close connect
        $ftpHandler->close();
        
        $res['status'] = true;
        $res['message'] = 'Create .htaccess Success (FTP)';
        
        return $res;
        
    }
    
    public function chmod_no_httpasuser($homeuser,$domainname,$publicpath,$ftpaccount,$ftppassword,$ftpserver,$ftpport,$public_html) {
        $this->print_debug_log('======'.__METHOD__.'======');
        
        $res = [
            'status' => false,
            'message' => ''
        ];
        
        $ftpHandler = new FTP_Handler();
        $result = $ftpHandler->connect($ftpserver);
        if(!$result['success']){
            $res['message'] = 'Error '.$result['msg'];
            $this->print_debug_log("Can not connect FTP ".$result['msg']);
            return $res;
        }
        $result = $ftpHandler->login($ftpaccount, $ftppassword);
        if(!$result['success']){
            $res['message'] = 'Error '.$result['msg'];
            $this->print_debug_log("Can not login FTP ".$result['msg']);
            return $res;
        }
        if(!file_exists($ftp_remote_dir)){
            $result = $ftpHandler->ftp_make_dir($homeuser,$ftp_remote_dir);
            if(!$result['success']) {
                $res['message'] = 'Error '.$result['msg'];
                $this->print_debug_log("Can make dir  FTP $homeuser , $ftp_remote_dir ".$result['msg']);
                return $res;
            }
        }
        
        #chmod folder
        $ftpHandler->ftp_change_mod_r($publicpath.'/storage',$public_html.'/storage' , 0777);
        $this->print_debug_log("FTP change mod ".$publicpath.'/storage'.'  '.$public_html.'/storage'. '0777');
        $ftpHandler->ftp_change_mod_r($publicpath.'/vendor',$public_html.'/vendor' , 0777);
        $this->print_debug_log("FTP change mod ".$publicpath.'/vendor'.'  '.$public_html.'/vendor'. '0777');
        $ftpHandler->ftp_change_mod_r($homeuser.'/rvsitebuildercms/'.$domainname.'/storage','/rvsitebuildercms/'.$domainname.'/storage' , 0777);
        $this->print_debug_log("FTP change mod ".$homeuser.'/rvsitebuildercms/'.$domainname.'/storage'.'  '.'/rvsitebuildercms/'.$domainname.'/storage'. '0777');
        $ftpHandler->ftp_change_mod_r($homeuser.'/rvsitebuildercms/'.$domainname.'/bootstrap','/rvsitebuildercms/'.$domainname.'/bootstrap' , 0777);
        $this->print_debug_log("FTP change mod ".$homeuser.'/rvsitebuildercms/'.$domainname.'/bootstrap'.'  '.'/rvsitebuildercms/'.$domainname.'/bootstrap'. '0777');
        $ftpHandler->ftp_change_mod('/rvsitebuildercms/'.$domainname.'/.env' , 0777);
        $this->print_debug_log("FTP change mod ".$homeuser.'/rvsitebuildercms/'.$domainname.'/bootstrap'.'  '.'/rvsitebuildercms/'.$domainname.'/bootstrap'. '0777');
        
        #chmod installer folder for delete
        $ftpHandler->ftp_change_mod_r($publicpath.'/rvsitebuilder',$public_html.'/rvsitebuilder' , 0777);
        $this->print_debug_log("FTP change mod ".$publicpath.'/rvsitebuilder'.'  '.$public_html.'/rvsitebuilder'. '0777');
        
        #close connect
        $ftpHandler->close();
        
        $res['status'] = true;
        $res['message'] = 'Chmod app directory Success (FTP)';
        
        return $res;
        
    }
    
    public function doDownload($type, $url, $sink , $sha512verify=[]) {
        $time_start = microtime(true);
        $this->print_debug_log('======'.__METHOD__.'======');
        
        $response = [
            'message' => '',
            'success' => false
        ];
        
        $client = new Client([
            'curl'            => [CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false],
            'allow_redirects' => false,
            'cookies'         => true,
            'verify'          => false
        ]);
        $headers = [
            /// Domain user
            'RV-Referer' => $this->get_current_domain(),
            /// บอกให้ทำ GATracking
            'Allow-GATracking' => 'true',
            /// RVGlobalsoft Product
            'RV-Product' => 'rvsitebuilder',
            /// ทำ License-Code ดูตาม function เลย
            'RV-License-Code' => $this->rvlicensecode,
            /// Browser ของ user
            'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.110 Safari/537.36',
            /// ส่ง IP ของ user ให้ด้วย เพราะที่ server เราจะเห็นแค่ IP ของ server ไม่ใช่ IP ของผู้ใช้งานจริงๆ
            'RV-Forword-REMOTE-ADDR' => $this->get_client_ip()
        ];
        
        $this->print_debug_log('Header request to server '.json_encode($headers));
        $this->print_debug_log("Do Download Type=$type URL=$url Synk=$sink LicenseCode=$this->rvlicensecode");
        $res = $client->request(
            $type,
            $url,
            [
                'headers'   => $headers,
                'sink'      => $sink,
                'timeout'   => 180
            ]
            );
        
        $this->print_debug_log('Server Response Status '.$res->getStatusCode());
        $this->print_debug_log('Server Response Header '.json_encode((array) $res->getHeaders()));
        
        if($res->getHeaderLine('RV-DOWNLOAD-RESPONSE') != 'ok') {
            $this->print_debug_log("Download Error, Server Header Response : ".$res->getHeaderLine('RV-DOWNLOAD-RESPONSE-MESSAGE'));
            $response['message'] = $res->getHeaderLine('RV-DOWNLOAD-RESPONSE-MESSAGE');
        }else if(!file_exists($sink)) {
            $this->print_debug_log('Download Error ,file '.$sink.' not exists');    
            $response['message'] = 'Download Error ,file '.$sink.' not exists';
        }else {
            $this->print_debug_log('Download Success ,file '.$sink);
            $response['success'] = true;
        }
        
        /////////////////////////// **start** function verify sha512 ///////////////////////////
        
        if(!empty($sha512verify)){
            
            $file_sha512 = hash_file('sha512' , $sink);
            
            if($sha512verify['type']=='vendor' || $sha512verify['version_type']=='version'){
                $arr_request = $client->request('GET' , $this->getversionurl.$sha512verify['getversionurl']);
                $verify_arr = json_decode($arr_request->getBody() , true);
                $verify_arr = array_change_key_case($verify_arr,CASE_LOWER);
                if(isset($verify_arr[$sha512verify['name']])){
                    $versionsha512 = $verify_arr[$sha512verify['name']]['versions'][$sha512verify['version']]['sha512'];    
                }else{
                    $versionsha512 = $verify_arr[urldecode($sha512verify['name'])]['versions'][$sha512verify['version']]['sha512'];
                }
            // stable|latest
            }else{
                $versions_path = dirname(__FILE__).'/'.$sha512verify['version_type'].'versions.json';
                if(file_exists($versions_path)){
                    $getversions = file_get_contents($versions_path);
                    $verify_arr = json_decode($getversions , true);
                }else{
                    $getversions = $client->request('GET' , $this->getversionurl.'/getversions/tier/'.$sha512verify['version_type']); //***** stable | latest version *****
                    file_put_contents($versions_path , $getversions->getBody());
                    $verify_arr = json_decode($getversions->getBody() , true);
                }
                $versionsha512 = $verify_arr[$sha512verify['name']]['sha512'];
            }
            if($file_sha512!=$versionsha512){
                $response['success'] = false;
                $response['message'] = 'Download error , File validation incorret.';
            }
        }
        
        //////////////////////////// **end** function verify sha512 ////////////////////////////
        
        $exectime = (microtime(true) - $time_start);
        $this->print_install_log(__METHOD__.' status: '.$response['success'].' url: ' . $url .' timeusage: '.$exectime);
        return $response;
    }
    
    public function doExtract($file,$path) {
        $time_start = microtime(true);
        $this->print_debug_log('======'.__METHOD__.'======');
        
        $response['message'] = '';
        $response['success'] = false;
        $this->print_debug_log("Extract $file $path");
        
        try {
            $tar = new Tar();
            $tar->open($file);
            $tar->extract($path);
            $this->print_debug_log("Do Extract Success $file $path");
            $response['success'] = true;
            $exectime = (microtime(true) - $time_start);
            $this->print_install_log(__METHOD__.' status: '.$response['success'].' file: ' . $file .' path: '.$path.' timeusage: '.$exectime);
            return $response;
        } catch (Exception $e) {
            $this->print_debug_log("Do Extract Error ".$e->getMessage());
            $response['message'] = $e->getMessage();
            $exectime = (microtime(true) - $time_start);
            $this->print_install_log(__METHOD__.' status: '.$response['success'].' file: ' . $file .' path: '.$path.' timeusage: '.$exectime);
            return $response;
        }
        
    }
    
    public function get_current_domain() {
        $this->print_debug_log('======'.__METHOD__.'======');
        $domainname = '';
        if(isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] != '') {
            $domainname = $_SERVER['HTTP_HOST'];
        }
        if(isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] != ''){
            $domainname = $_SERVER['SERVER_NAME'];
        }
        $domainname = str_replace("www.","",$domainname);
        $this->print_debug_log("Current domain ".$domainname);
        return $domainname;
    }
    
    public function get_client_ip() {
        $this->print_debug_log('======'.__METHOD__.'======');
        
        $ipaddress = '';
        if (isset($_SERVER['HTTP_CLIENT_IP']))
        {    $ipaddress = $_SERVER['HTTP_CLIENT_IP']; }
        else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        {    $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR']; }
        else if(isset($_SERVER['HTTP_X_FORWARDED']))
        {    $ipaddress = $_SERVER['HTTP_X_FORWARDED']; }
        else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
        {    $ipaddress = $_SERVER['HTTP_FORWARDED_FOR']; }
        else if(isset($_SERVER['HTTP_FORWARDED']))
        {    $ipaddress = $_SERVER['HTTP_FORWARDED']; }
        else if(isset($_SERVER['REMOTE_ADDR']))
        {    $ipaddress = $_SERVER['REMOTE_ADDR']; }
        else
        {    $ipaddress = 'UNKNOWN'; }
        $this->print_debug_log("Remote IP Address ".$ipaddress);
        return $ipaddress;
    }
    
    public function print_response($data) {
        $this->print_debug_log('======'.__METHOD__.'======');
        
        if($this->responseType == 'application/json' || $this->call_responsetype == 'application/json') {
            header('Content-type: application/json');
        }
        echo json_encode( $data );
        exit;
    }
    
    /*
     * updateuserinfo('1','last_name','bbbbb');
     */
    public function updateuserinfo($user_id,$update_key,$update_val){
        $this->print_debug_log('======'.__METHOD__.'======');
        
        $response = [];
        $response['success'] = 'true';
        $response['message'] = '';
        $db_server_name = getEnvData('DB_HOST');
        $db_port = getEnvData('DB_PORT');
        $db_name = getEnvData('DB_DATABASE');
        $db_user_name = getEnvData('DB_USERNAME');
        $db_password = getEnvData('DB_PASSWORD');
        
        $this->print_debug_log("Update User info user_id=$user_id update_key=$update_key update_val=$update_val");
        $this->print_debug_log("Database info db_server_name=$db_server_name db_port=$db_port db_name=$db_name db_user_name=$db_user_name db_password=$db_password");
        
        if($update_key == 'password'){
            $update_val = bcrypt($update_val);
        }
        
        $mysqli = new mysqli($db_server_name, $db_user_name, $db_password, $db_name);
        if (mysqli_connect_errno()) {
            $this->print_debug_log("DB connect error ".mysqli_connect_error());
            $response['success'] = 'false';
            $response['message'] = sprintf("Connect failed : %s\n", mysqli_connect_error());
            return $response;
        }
        
        $sql = sprintf('UPDATE `users` SET `%s` = \'%s\', `password_changed_at` = NULL, `deleted_at` = NULL WHERE `users`.`id` = ?;', $update_key, $update_val);
        
        if($query = $mysqli->prepare($sql)){
            $query->bind_param('s', $user_id);
            $query->execute();
        } else {
            $this->print_debug_log("DB update error ".$mysqli->error);
            $error = $mysqli->errno . ' ' . $mysqli->error;
            $response['success'] = 'false';
            $response['message'] = $error;
            return $response;
        }
        return $response;
    }
    
    public function getEnvData($envKey = ''){
        $this->print_debug_log('======'.__METHOD__.'======');
        
        $this->print_debug_log("env key $envKey");
        
        $valuefromkey = '';
        
        if($envKey != ''){
            //set path for /home/user or /var/www
            $envPath = self::_getUserPath();
            
            // Read .env-file
            //$env = file_get_contents(base_path() . '/.env');
            $env = file_get_contents($envPath['home'] . '/.env');
            
            // Split string on every " " and write into array
            $env = preg_split('/\s+/', $env);
            
            // Loop through .env-data
            foreach($env as $env_key => $env_value){
                // Turn the value into an array and stop after the first split
                // So it's not possible to split e.g. the App-Key by accident
                $entry = explode("=", $env_value, 2);
                //check for comment #KEY (#) too
                if($entry[0] == $envKey || substr($entry[0],1) == $envKey){
                    // If yes, get value from key
                    $valuefromkey = $entry[1];
                }
            }
            
        }
        $this->print_debug_log("Value from key $valuefromkey");
        return $valuefromkey;
    }
    
    public function do_setupenv($env_path,$env_data = [],$force = false){
        $this->print_debug_log('======'.__METHOD__.'======');
        
        $this->print_debug_log("ENV File=$env_path Data=".json_encode($env_data)." Force=$force");
        
        $res = [
            'status' => false,
            'message' => '',
        ];
        
        if(count($env_data) > 0){
            // Read .env-file
            $env = file_get_contents($env_path.'/env.example');
            // Split string on every "\n" and write into array
            $env = preg_split('/\n/', $env);
            // Loop through given data
            foreach((array)$env_data as $key => $value){
                // Loop through .env-data
                $found = 0;
                foreach($env as $env_key => $env_value){
                    // Turn the value into an array and stop after the first split
                    // So it's not possible to split e.g. the App-Key by accident
                    $entry = explode("=", $env_value, 2);
                    // Check, if new key fits the actual .env-key
                    //check for comment #KEY (#) too
                    if($entry[0] == $key || substr($entry[0],1) == $key){
                        // If yes, overwrite it with the new one
                        $env[$env_key] = $key . "=" . $value;
                        $found = 1;
                    } else {
                        // If not, keep the old one
                        $env[$env_key] = $env_value;
                    }
                }
                if(!$found && $force){
                    array_push($env,"$key=$value");
                }
            }
            // Turn the array back to an String
            $env = implode("\n", $env);
            
            file_put_contents($env_path.'/.env', $env);
            //file_put_contents(dirname(__FILE__).'/tmp/.env', $env);
            
            $res['status'] = true;
            $res['message'] = 'Update ENV Success';
        } else {
            $this->print_debug_log("Array env_data is empty");
            $res['message'] = 'Array env_data is empty';
        }
        
        return $res;
    }
    
   
    
    public function get_user_path() {
        $time_start = microtime(true);
        $this->print_debug_log('======'.__METHOD__.'======');
        
        $userpathinfo = $this->get_user_path_info();
        $this->response['status'] = true;
        $this->response['homepath'] = $userpathinfo['homepath'];
        $this->response['publicpath'] = $userpathinfo['publicpath'];
        $this->response['exectime'] = (microtime(true) - $time_start);
        $this->print_debug_log('User path info '.json_encode($userpathinfo));
        $this->print_install_log(__METHOD__." TRUE ".json_encode($userpathinfo).' timeusage '.$this->response['exectime']);
        return $this->print_response($this->response);
        
    }
    
    
    public function get_user_path_info(){
        $time_start = microtime(true);
        $this->print_debug_log('======'.__METHOD__.'======');        
        $userpathinfo = []; 
        $user_path = '';        
        $document_root = $_SERVER['DOCUMENT_ROOT'];
        $user_paths = [];
        $open_basedir_paths = $this->get_open_basedir_paths();
        if(count($open_basedir_paths)) {            
            $this->print_debug_log('==== case define open_basedir ====');
            $user_paths = $open_basedir_paths;
        } else {
            $this->print_debug_log('==== case no open_basedir ====');
            if(isset($_SERVER['HOME'])) {
                if (!in_array($_SERVER['HOME'], $user_paths)) {
                    array_push($user_paths, $_SERVER['HOME']);
                }                
            }
            $posix_user_path = '';            
            if(function_exists('posix_getuid')) {
                $webuid = posix_getuid();
                $userinfo = posix_getpwuid($webuid);
                if(is_dir($userinfo['dir'])){            
                    if (!in_array($userinfo['dir'], $user_paths)) {
                        array_push($user_paths, $userinfo['dir']);
                    }                    
                }                
            }    
            // case  have posix_getpwuid get uid by owner dir
            if(function_exists('posix_getpwuid')) {
                $stat = stat($document_root);            
                $userinfo = posix_getpwuid($stat['uid']);
                if(is_dir($userinfo['dir'])) {
                    if (!in_array($userinfo['dir'], $user_paths)) {
                        array_push($user_paths, $userinfo['dir']);
                    }
                }
                $userinfo = posix_getpwuid($stat['gid']);
                if(is_dir($userinfo['dir'])) {
                    if (!in_array($userinfo['dir'], $user_paths)) {
                        array_push($user_paths, $userinfo['dir']);
                    }
                }
            }
            // case  find home path from document_root ( /home/amarin/public_html => /home/amarin )
            if($posix_user_path == '') {
                $paths = preg_split("/\//", $document_root);
                $loop_dim = count($paths);
                for($i=0; $i < $loop_dim; $i++) {
                    $test_path = join('/', $paths);
                    if(is_dir($test_path)) {
                        if (!in_array($test_path, $user_paths)) {
                            array_push($user_paths, $test_path);
                        }
                    }
                    array_pop($paths);
                }
            }            
        }   
        
        foreach($user_paths as $user_path_var) {
            if(is_file($user_path_var)) {
                $this->print_debug_log('skip ' . $user_path_var);
                continue;
            }
            if($user_path_var == $document_root) {
                $this->print_debug_log('skip ' . $user_path_var);
                continue;
            }
            if(preg_match("/(^\/tmp)|(\/tmp$)/",$user_path_var)){
                $this->print_debug_log('skip ' . $user_path_var);
                continue;
            }
            if(is_writable($user_path_var)) {
                $user_path = $user_path_var;
                break;
            }
        }           

        $userpathinfo['homepath'] = $user_path;
        $userpathinfo['publicpath'] = $document_root;
        
        return $userpathinfo;        
    }

    public function get_open_basedir_paths() {
        $open_basedir_paths = [];
        if(function_exists('ini_get')){
            $open_basedir_str =  ini_get('open_basedir');
            $open_basedir_str = trim($open_basedir_str);
            if($open_basedir_str != ''){
                $open_basedir_str = strtolower($open_basedir_str);
                $open_basedir_paths = explode(":", $open_basedir_str);
            }            
        }
        return $open_basedir_paths;
    }
    
    
    public function check_http_as_user() {
        $time_start = microtime(true);
        $this->print_debug_log('======'.__METHOD__.'======');
        
        $this->response['status'] = true;
        $this->response['httpasuser'] = $this->httpasuser;
        $this->response['exectime'] = (microtime(true) - $time_start);
        $this->print_install_log(__METHOD__." TRUE ".' timeusage '.$this->response['exectime']);
        $this->print_debug_log("HTTP AS USER $this->httpasuser");
        
        return $this->print_response($this->response);
    }
    
    
    
    public function test_database_ftp_connect($dbhost,$dbname,$dbuser,$dbpassword,$ftpserver,$ftpaccount,$ftppassword,$ftpport) {
        $time_start = microtime(true);
        $this->print_debug_log('======'.__METHOD__.'======');
        
        ini_set('display_errors', 0);
        
        $this->print_debug_log("Test Connect dbhost=$dbhost dbname=$dbname dbuser=$dbuser dbpassword=$dbpassword ftpserver=$ftpserver ftpaccount=$ftpaccount ftppassword=$ftppassword ftpport=$ftpport");
        
        //db
        $this->response['db_connect']['status'] = true;
        $this->response['db_connect']['message'] = "";
        $conn = new mysqli($dbhost, $dbuser, $dbpassword,$dbname);
        if ($conn->connect_error) {
            $this->print_debug_log("Database Connect Error ".$conn->connect_error);
            $this->response['db_connect']['status'] = false;
            $this->response['db_connect']['message'] = "Database connection failed! ".$conn->connect_error;
        }
        
        //ftp
        $this->response['ftp_connect']['status'] = true;
        $this->response['ftp_connect']['message'] = '';
        if($ftpserver != '' && $ftpaccount != '' && $ftppassword != '') {
            $ftpHandler = new FTP_Handler();
            $result = $ftpHandler->connect($ftpserver);
            if(!$result['success']){
                $this->print_debug_log("FTP Connect Error ".$result['msg']);
                $this->response['ftp_connect']['status'] = false;
                $this->response['ftp_connect']['message'] = 'Error '.$result['msg'];
            }
            $result = $ftpHandler->login($ftpaccount, $ftppassword);
            if(!$result['success']){
                $this->print_debug_log("FTP Login Error ".$result['msg']);
                $this->response['ftp_connect']['status'] = false;
                $this->response['ftp_connect']['message'] = 'Error '.$result['msg'];
            }
        }
        
        $this->response['status'] = true;
        $this->response['exectime'] = (microtime(true) - $time_start);
        $this->print_install_log(__METHOD__." TRUE ".json_encode($this->response).' timeusage '.$this->response['exectime']);
        
        return $this->print_response($this->response);
    }
    
    public function check_license() {
        $time_start = microtime(true);
        $this->print_debug_log('======'.__METHOD__.'======');
        
        $this->response['status'] = true;
        $this->response['exectime'] = (microtime(true) - $time_start);
        $this->print_install_log(__METHOD__." TRUE ".' timeusage '.$this->response['exectime']);
        return $this->print_response($this->response);
    }
    
    public function check_dev_mode() {
        $time_start = microtime(true);
        $this->print_debug_log('======'.__METHOD__.'======');
        
        $userpathinfo = $this->get_user_path_info();
        $this->response['dev_mode'] = false;
        if(file_exists($userpathinfo['homepath'].'/devmode')) {
            $this->response['dev_mode'] = true;
        }
        
        
        $this->response['status'] = true;
        $this->response['exectime'] = (microtime(true) - $time_start);
        $this->print_install_log(__METHOD__." TRUE ".' timeusage '.$this->response['exectime']);
        return $this->print_response($this->response);
    }
    
    public function disk_required() {
        $time_start = microtime(true);
        $this->print_debug_log('======'.__METHOD__.'======');
        
        $this->response['status'] = true;
        $this->response['exectime'] = (microtime(true) - $time_start);
        $this->print_install_log(__METHOD__." TRUE ".' timeusage '.$this->response['exectime']);
        return $this->print_response($this->response);
    }
    
    public function print_debug_log($msg = '') {
        if($this->debug_log == true){
            if(file_exists(dirname(__FILE__).'/install_log.txt')) {
                file_put_contents(
                    dirname(__FILE__).'/install_log.txt',
                    'DEBUG LOG >> ' .$msg.PHP_EOL ,
                    FILE_APPEND | LOCK_EX
                    );
            }
            elseif(file_exists(dirname(__FILE__).'/../rvsitebuilder_install_log.txt')){
                file_put_contents(
                    dirname(__FILE__).'/../rvsitebuilder_install_log.txt',
                    'DEBUG LOG >> ' .$msg.PHP_EOL ,
                    FILE_APPEND | LOCK_EX
                    );
            }
        }
        return true;
    }
    
    public function print_install_log($msg = '') {
        if($this->install_log == true){
            if(file_exists(dirname(__FILE__).'/install_log.txt')) {
                file_put_contents(
                    dirname(__FILE__).'/install_log.txt',
                    'INSTALL LOG >> ' .$msg.PHP_EOL ,
                    FILE_APPEND | LOCK_EX
                    );
            }
            elseif(file_exists(dirname(__FILE__).'/../rvsitebuilder_install_log.txt')){
                file_put_contents(
                    dirname(__FILE__).'/../rvsitebuilder_install_log.txt',
                    'INSTALL LOG >> ' .$msg.PHP_EOL ,
                    FILE_APPEND | LOCK_EX
                    );
            }
        }
        return true;
    }
    
}



class FTP_Handler{
    
    protected $conn_id;
    
    public function __construct( ) {
    }
    
    function connect($ftpserver) {
        $result = [];
        $result['success'] = 1;
        $this->conn_id = ftp_connect($ftpserver);
        if ( ! $this->conn_id ) {
            $result['success'] = 0;
            $result['msg'] = 'Fail to connect ' . $ftpserver;
        }
        return $result;
    }
    
    function login($ftp_user_name, $ftp_user_pass) {
        $result = [];
        $result['success'] = 1;
        $login = ftp_login($this->conn_id, $ftp_user_name, $ftp_user_pass);
        if ( ! $login ) {
            $result['success'] = 0;
            $result['msg'] = 'Fail to login ftp ';
        }
        
        ftp_pasv( $this->conn_id, true );
        return $result;
    }
    
    function nlist($path = '.') {
        $dirLists = ftp_nlist($this->conn_id, $path);
        return $dirLists;
    }
    
    function put($source = '' , $dest = '' , $mode = 'FTP_ASCII') {
        $result = [];
        $result['success'] = 1;
        $upload = ftp_put($this->conn_id, $dest, $source, $mode);
        if (!$upload) {
            $result['success'] = 0;
            $result['msg'] = 'Fail to ftp upload ';
        }
        return $result;
    }

    function rename($old_file , $new_file){
        $result = [];
        $result['success'] = 1;
        $upload = ftp_rename($this->conn_id , $old_file , $new_file);
        if(!$upload){
            $result['success'] = 0;
            $result['msg'] = 'Fail to rename ftp';
        }
    }

    /*
     $src_dir = '/home/amarin/public_html/source';
     $ftp_remote_dir = 'dest';
     ftp_copy(/home/amarin/public_html/source, dest)
     ftp_copy(/home/amarin/public_html/source, dest/app)
     */
    function ftp_copy($src_dir, $dst_dir , $ignore = []) {
        $debug = false;
        $chdir = $dst_dir;
        if($debug){
            echo "- ftp_copy $src_dir , $dst_dir <br/>";
            echo "-- ftp_chdir to $chdir <br/>";
        }
        ftp_chdir($this->conn_id, $chdir);
        
        if(is_dir($src_dir)){
            $dir = new DirectoryIterator($src_dir);
            foreach($dir as $fileinfo) {
                $file = $fileinfo->getFilename();
                
                if(in_array($file , $ignore)){
                    continue;
                }
                
                if ($file != "." && $file != "..") {
                    if (is_dir($src_dir."/".$file)) {
                        if (!$this->ftp_is_dir($this->conn_id, $file)) {
                            ftp_chdir($this->conn_id, $dst_dir);
                            $pushd = ftp_pwd($this->conn_id);
                            if($debug){
                                echo "--- ftp pwd = $pushd<br/>";
                                echo "--- Not found ftp dir now do ftp_mkdir $file at $dst_dir<br/>";
                            }
                            @ftp_mkdir($this->conn_id, $file);
                            @ftp_chmod($this->conn_id, 0755, $file);
                        }
                        $this->ftp_copy($src_dir."/".$file, $dst_dir."/".$file);
                    }
                    else {
                        ftp_chdir($this->conn_id, $dst_dir);
                        $pushd = ftp_pwd($this->conn_id);
                        if($debug){
                            echo "------ ftp pwd = $pushd<br/>";
                            echo "------ ftp_put $file to $dst_dir<br/>";
                        }
                        $pushd = ftp_pwd($this->conn_id);
                        $upload = ftp_put($this->conn_id, $file, $src_dir."/".$file, FTP_BINARY);
                        @ftp_chmod($this->conn_id, 0644, $file);
                    }
                }
            }
        }else{
            ftp_chdir($this->conn_id, $dst_dir);
            $pushd = ftp_pwd($this->conn_id);
            if($debug){
                echo "------ ftp pwd = $pushd<br/>";
                echo "------ ftp_put $file to $dst_dir<br/>";
            }
            $upload = ftp_put($this->conn_id, $file, $src_dir."/".$file, FTP_BINARY);
            @ftp_chmod($this->conn_id, 0644, $file);
        }
    }
    
    function ftp_change_mod_r($realpath , $path , $perm=0777) {
        
        ftp_chmod($this->conn_id, $perm, $path);
        ftp_chdir($this->conn_id, $path);
        
        $dir = new DirectoryIterator($realpath);
        foreach($dir as $fileinfo) {
            $file = $fileinfo->getFilename();
            if ($file != "." && $file != "..") {
                @ftp_chmod($this->conn_id, $perm, $path.'/'.$file);
                if (is_dir($realpath.'/'.$file)) {
                    $this->ftp_change_mod_r( $realpath.'/'.$file , $path.'/'.$file , $perm);
                }
            }
        }
        return true;
    }
    
    function ftp_change_mod($path , $perm=0777) {
        
        ftp_chmod($this->conn_id, $perm, $path);
        return true;
    }
    
    function ftp_make_dir ($homeuserdir , $dir) {
        
        $result['success'] = 0;
        $result['msg'] = 'Fail to create directory '.$dir;
        
        @ftp_chdir($this->conn_id, $homeuserdir);
        $parts = explode('/',$dir);
        foreach($parts as $part){
            if(!@ftp_chdir($this->conn_id, $part)){
                ftp_mkdir($this->conn_id, $part);
                ftp_chdir($this->conn_id, $part);
            }
        }
        
        if(@ftp_chdir($this->conn_id, $dir)) {
            $result['success'] = 1;
            $result['msg'] = 'success';
        }
        
        return $result;
    }
    
    
    
    function ftp_is_dir($dir)
    {
        $pushd = ftp_pwd($this->conn_id);
        
        if ($pushd !== false && @ftp_chdir($this->conn_id, $dir))
        {
            ftp_chdir($this->conn_id, $pushd);
            return true;
        }
        
        return false;
    }
    
    function close() {
        if($this->conn_id){
            ftp_close($this->conn_id);
        }
        return true;
    }
    
}

function getFrameworkVendorPath($filePath = ''){
    $vendorDir = realpath(dirname($filePath) . '/../../../../../') . '/vendor';
    return $vendorDir;
}

function getPackageBaseDir($filePath = ''){
    $baseDir = realpath(dirname($filePath) . '/../../');
    return $baseDir;
}

function rv_apache_request_headers() {
    $arh = array();
    $rx_http = '/\AHTTP_/';
    foreach($_SERVER as $key => $val) {
        if( preg_match($rx_http, $key) ) {
            $arh_key = preg_replace($rx_http, '', $key);
            $rx_matches = array();
            // do some nasty string manipulations to restore the original letter case
            // this should work in most cases
            $rx_matches = explode('_', $arh_key);
            if( count($rx_matches) > 0 and strlen($arh_key) > 2 ) {
                foreach($rx_matches as $ak_key => $ak_val) {
                    $rx_matches[$ak_key] = ucfirst($ak_val);
                    $arh_key = implode('-', $rx_matches);
                }
            }
            $arh[$arh_key] = $val;
        }
    }
    return( $arh );
}

?>