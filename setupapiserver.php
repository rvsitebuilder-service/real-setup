<?php 


require 'vendor/autoload.php';
use GuzzleHttp\Client;
use splitbrain\PHPArchive\Tar;

$headers                = apache_request_headers();
$responsetype           = (isset($headers['Accept']))                   ? $headers['Accept']                : 'application/json';
$ignore_token           = (isset($headers['Ignore-Token']))             ? $headers['Ignore-Token']          : 0;
$rvsb_installing_token  = (isset($headers['Rvsb-installing-Token']))    ? $headers['Rvsb-installing-Token'] : 0;
$action                 = (isset($_GET['action']))                      ? $_GET['action']         : '';
$homeuser               = (isset($_GET['homeuser']))                    ? $_GET['homeuser']         : '';
$domainname             = (isset($_GET['domainname']))                  ? $_GET['domainname']       : '';
$publicpath             = (isset($_GET['publicpath']))                  ? $_GET['publicpath']       : '';
$dbhost                 = (isset($_GET['dbhost']))                      ? $_GET['dbhost']           : '';
$dbname                 = (isset($_GET['dbname']))                      ? $_GET['dbname']           : '';
$dbuser                 = (isset($_GET['dbuser']))                      ? $_GET['dbuser']           : '';
$dbpassword             = (isset($_GET['dbpass']))                      ? $_GET['dbpass']           : '';
$ftpaccount             = (isset($_GET['ftpaccount']))                  ? $_GET['ftpaccount']       : '';
$ftppassword            = (isset($_GET['ftppassword']))                 ? $_GET['ftppassword']      : '';
$appname                = (isset($_GET['appname']))                     ? $_GET['appname']          : 'RVsitebuilder';
$ftpserver              = (isset($_GET['ftpserver']))                   ? $_GET['ftpserver']        : '';
$ftpport                = (isset($_GET['ftpport']))                     ? $_GET['ftp_port']         : 21;
$adminemail             = (isset($_GET['adminemail']))                  ? $_GET['adminemail']       : '';
$adminpassword          = (isset($_GET['adminpassword']))               ? $_GET['adminpassword']    : '';
$adminfirstname         = (isset($_GET['adminfirstname']))              ? $_GET['adminfirstname']   : '';
$adminlastname          = (isset($_GET['adminlastname']))               ? $_GET['adminlastname']    : '';



$setupObj = new RVsitebuilder_Setup_API($responsetype,$rvsb_installing_token,$call_responsetype,$ignore_token);


if($action == '' && !file_exists(dirname(__FILE__).'/.Rvsb-Installing-Token' && $rvsb_installing_token == 0)) {
    $setupObj->send_token();
}

if($action == 'pre_check_php'){
    $setupObj->pre_check_php();
}

if($action == 'download_framework'){
    $setupObj->download_framework();
}

if($action == 'download_vendor'){
    $setupObj->download_vendor();
}

if($action == 'setup_env'){
    $setupObj->setup_env($domainname,$publicpath,$dbhost,$dbname,$dbuser,$dbpassword,$ftpaccount,$ftppassword,$appname,$ftpserver,$ftpport);
}

if($action == 'download_common_pkg'){
    $setupObj->download_common_pkg();
}

if($action == 'install_all_pkg' && $homeuser != '' && $domainname != '' && $publicpath != ''){
    $setupObj->install_all_pkg($homeuser,$domainname,$publicpath,$ftpaccount,$ftppassword,$ftpserver,$ftpport);
}

if($action == 'artisan_call'){
    $setupObj->artisan_call($homeuser,$domainname,$publicpath,$adminemail,$adminpassword,$adminfirstname,$adminlastname);
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








class RVsitebuilder_Setup_API {
    
    protected $responseType;
    protected $response;
    protected $serverconf;
    protected $downloadurl;
    protected $debug;
    protected $httpasuser;
    protected $installerconfig;
    protected $call_responsetype;
    protected $removeinstallerpath;
    
    public function __construct($responsetype,$rvsb_installing_token,$call_responsetype,$ignore_token)
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
    }
    
    public function getInstallerConfig() {
        $this->print_debug_log(__METHOD__);
        
        //defaultconfig
        $defconfig = parse_ini_file(dirname(__FILE__).'/rvsitebuilderinstallerconfig_dist/config.ini',true);
        $this->print_debug_log("Installer config ".join(',',$defconfig));
        
        //overwrite installer config by user (in public path /home/user/public_html/)
        $userconfig = [];
        if(file_exists(__DIR__.'/../.rvsitebuilderinstallerconfig/config.ini')) {
            $userconfig = parse_ini_file(__DIR__.'/../.rvsitebuilderinstallerconfig/config.ini',true);
            $this->print_debug_log("Installer config by user".join(',',array_merge($defconfig,$userconfig)));
        }
        
        return array_merge($defconfig,$userconfig);
    }
    
    
    public function gethttpasuser() {
        $this->print_debug_log(__METHOD__);
        
        if(function_exists('posix_getpwuid')){
            $homepath_owner = posix_getpwuid(fileowner($_SERVER["DOCUMENT_ROOT"]))['name'];
            $site_run_as = posix_getpwuid(posix_geteuid())['name'];
            if($homepath_owner == $site_run_as){
                $this->print_debug_log("HTTP AS USER   TRUE");
                return true;
            }
        }
        $this->print_debug_log("HTTP AS USER   FALSE");
        return false;
    }
    
    public function verify_token($rvsb_installing_token='',$ignore_token=0) {
        $this->print_debug_log(__METHOD__);
        
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
                $this->print_install_log(__METHOD__.' FALSE (Token Wrong)');
                $this->response['message'] = 'Wrong!!!!';
                return $this->print_response($this->response);
            }
        }
        $this->print_install_log(__METHOD__.' FALSE (Token pass)');
        return true;
    }
    
    public function send_token() {
        $time_start = microtime(true);
        $this->print_debug_log(__METHOD__);
        
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
        $this->print_debug_log(__METHOD__);
        
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
        $this->response['check_pre_require']['gd']['check'] = true;
        if (!extension_loaded('gd')) {
            $this->response['check_pre_require']['gd']['check'] = false;
            $this->response['check_pre_require']['gd']['reason'] = 'Can not load PHP Extension (gd)';
            $this->response['message'] = $this->response['message'].' / Can not load PHP Extension (gd)';
            $this->response['status'] = false;
            $this->print_debug_log("PHP gd false");
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
        $this->response['check_pre_require']['fileinfo']['check'] = true;
        if (!extension_loaded('fileinfo')) {
            $this->response['check_pre_require']['fileinfo']['check'] = false;
            $this->response['check_pre_require']['fileinfo']['reason'] = '';
            $this->response['message'] = $this->response['message'].' / Can not load PHP Extension (fileinfo)';
            $this->response['status'] = false;
            $this->print_debug_log("PHP fileinfo false");
        }
        $this->response['check_pre_require']['exif']['check'] = true;
        if (!extension_loaded('exif')) {
            $this->response['check_pre_require']['exif']['check'] = false;
            $this->response['check_pre_require']['exif']['reason'] = 'Can not load PHP Extension (exif)';
            $this->response['message'] = $this->response['message'].' / Can not load PHP Extension (exif)';
            $this->response['status'] = false;
            $this->print_debug_log("PHP exif false");
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
        $this->response['check_pre_require']['allow_url_fopen']['check'] = true;
        if (ini_get('allow_url_fopen') != 1) {
            $this->response['check_pre_require']['allow_url_fopen']['check'] = false;
            $this->response['check_pre_require']['allow_url_fopen']['reason'] = 'php.ini, Must set allow_url_fopen=ON';
            $this->response['message'] = $this->response['message'].' / php.ini, Must set allow_url_fopen=ON';
            $this->response['status'] = false;
            $this->print_debug_log("PHP allow_url_fopen false");
        }
        $this->response['check_pre_require']['memory_limit']['check'] = true;
        preg_match('/([0-9]+)/',ini_get('memory_limit'),$match);
        if($match[0] < 64) {
            $this->response['check_pre_require']['memory_limit']['check'] = false;
            $this->response['check_pre_require']['memory_limit']['reason'] = 'php.ini, Set Memory limit at least 64M.';
            $this->response['message'] = $this->response['message'].' / php.ini, Set Memory limit at least 64M.';
            $this->response['status'] = false;
            $this->print_debug_log("PHP memory_limit false ".$match[0]);
        }
        //php function posix_getpwuid
        $this->response['check_pre_require']['posix_getpwuid']['check'] = true;
        if(! function_exists('posix_getpwuid')){
            $this->response['check_pre_require']['posix_getpwuid']['check'] = false;
            $this->response['check_pre_require']['posix_getpwuid']['reason'] = 'Can not load PHP Function (posix_getpwuid)';
            $this->response['message'] = $this->response['message'].' / Can not load PHP Function (posix_getpwuid)';
            $this->response['status'] = false;
            $this->print_debug_log("PHP posix_getpwuid false");
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
        
        //http as user
        $this->response['httpasuser'] = $this->httpasuser;
        
        $this->print_debug_log("Pre Check ".$this->response['status']." ".$this->response['message']);
        
        $this->response['exectime'] = (microtime(true) - $time_start);
        
        if($this->response['status'] == true) {
            $this->print_install_log(__METHOD__.' TRUE '.' timeusage '.$this->response['exectime']);
            $this->response['message'] = "PHP Version,Extentsion,INI OK";
        } else {
            $this->print_install_log(__METHOD__.' FALSE '.$this->response['message'].' timeusage '.$this->response['exectime'] .' timeusage '.$this->response['exectime']);
        }
        
        return $this->print_response($this->response);
        
    }
    
    public function download_framework() {
        $time_start = microtime(true);
        $this->print_debug_log(__METHOD__);
        
        //download framework
        $downloadurl =  $this->mirror.'/download/rvsitebuilder/framework';
        if(isset($this->installerconfig['framework']['getversion']) && $this->installerconfig['framework']['getversion'] == 'latest') 
        { $downloadurl = $this->mirror.'/download/rvsitebuilder/framework/tier/latest'; }
        if(isset($this->installerconfig['framework']['getversion']) && preg_match('/[0-9]+\.[0-9]+\.[0-9]+/',$this->installerconfig['framework']['getversion'])) 
        { $downloadurl = $this->mirror.'/download/rvsitebuilder/framework/version/'.$this->installerconfig['framework']['getversion']; }
        
        $this->print_debug_log("Download Framework URL ".$downloadurl);
        
        $downloadframework = $this->download('GET' , $downloadurl , dirname(__FILE__).'/framework.tar.gz');
        if(! $downloadframework){
            $this->response['message'] = 'Can not download framework';
            $this->response['exectime'] = (microtime(true) - $time_start);
            $this->print_install_log(__METHOD__.' FALSE (Download Framework  Failed)'.$downloadurl.' timeusage '.$this->response['exectime']);
            $this->print_debug_log("Download Framework  Failed");
            return $this->print_response($this->response);
        }
        //extract framework
        $extractframework = $this->extract(dirname(__FILE__).'/framework.tar.gz',dirname(__FILE__).'/tmp/');
        if(! $extractframework) {
            $this->response['message'] = 'Can not extract framework.tar.gz';
            $this->response['exectime'] = (microtime(true) - $time_start);
            $this->print_install_log(__METHOD__.' FALSE (Extract Framework Failed)'.' timeusage '.$this->response['exectime']);
            $this->print_debug_log("Extract Framework Failed");
            return $this->print_response($this->response);
        }
        
        $this->response['exectime'] = (microtime(true) - $time_start);
        $this->response['status'] = true;
        $this->response['message'] = 'Download Framework Success';
        $this->print_install_log(__METHOD__.' TRUE'.' timeusage '.$this->response['exectime']);
        return $this->print_response($this->response);
    } 
    
    public function download_vendor() {
        $time_start = microtime(true);
        $this->print_debug_log(__METHOD__);
        
        //check rvsitebuilder.json
        if(! file_exists(dirname(__FILE__).'/tmp/rvsitebuilder.json')){
            $this->response['message'] = 'Can not open file rvsitebuilder.json';
            $this->response['exectime'] = (microtime(true) - $time_start);
            $this->print_install_log(__METHOD__.' FALSE'."Not found ".dirname(__FILE__).'/tmp/rvsitebuilder.json'.' timeusage '.$this->response['exectime']);
            return $this->print_response($this->response);
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
            $downloadvendor = $this->download('GET' , $downloadvendorurl , dirname(__FILE__).'/bundle_vendor.tar.gz');
            if(! $downloadvendor) {
                $this->response['message'] = 'Can not download vendor';
                $this->response['exectime'] = (microtime(true) - $time_start);
                $this->print_install_log(__METHOD__.' FALSE (Can not download vendor) '.$downloadvendorurl.' timeusage '.$this->response['exectime']);
                $this->print_debug_log("Can not download ".$downloadvendorurl);
                return $this->print_response($this->response);
            }
            $extractvendor = $this->extract(dirname(__FILE__).'/bundle_vendor.tar.gz',dirname(__FILE__).'/tmp/vendor/');
            if(! $extractvendor) {
                $this->response['message'] = 'Can not extract vendor.tar.gz';
                $this->response['exectime'] = (microtime(true) - $time_start);
                $this->print_install_log(__METHOD__.' FALSE (Can not extract vendor.tar.gz) '.' timeusage '.$this->response['exectime']);
                $this->print_debug_log("Can not extract ".dirname(__FILE__).'/bundle_vendor.tar.gz');
                return $this->print_response($this->response);
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
                $downloadvendor = $this->download('GET' , $downloadvendorurl , dirname(__FILE__).'/'.$package_name_encoded.'.tar.gz');
                if(! $downloadvendor) {
                    $this->response['message'] = 'Can not download vendor '.$downloadvendorurl;
                    $this->response['exectime'] = (microtime(true) - $time_start);
                    $this->print_install_log(__METHOD__.' FALSE (Can not download vendor) '.$downloadvendorurl.' timeusage '.$this->response['exectime']);
                    $this->print_debug_log("Can not download ".$downloadvendorurl);
                    return $this->print_response($this->response);
                }
                $extractvendor = $this->extract(dirname(__FILE__).'/'.$package_name_encoded.'.tar.gz',dirname(__FILE__).'/tmp/vendor/');
                if(! $extractvendor) {
                    $this->response['message'] = 'Can not extract vendor '.$package_name_encoded;
                    $this->response['exectime'] = (microtime(true) - $time_start);
                    $this->print_install_log(__METHOD__.' FALSE (Can not extract vendor) '.$package_name_encoded.' timeusage '.$this->response['exectime']);
                    $this->print_debug_log("Can not extract ".dirname(__FILE__).'/'.$package_name_encoded.'.tar.gz');
                    return $this->print_response($this->response);
                }
                unlink(dirname(__FILE__).'/'.$package_name_encoded.'.tar.gz');
            }
            
        }
        
        //move vendor package to vendor path
        $files = scandir(dirname(__FILE__).'/tmp/vendor/vendor');
        $source = dirname(__FILE__).'/tmp/vendor/vendor/';
        $destination = dirname(__FILE__).'/tmp/vendor/';
        foreach ($files as $file) {
            if (in_array($file, [".",".."])) continue;
            rename($source.$file, $destination.$file);
        }
        $this->print_debug_log("Moved vendor from $source to $destination");
        
        // Delete all successfully-copied files
        rmdir(dirname(__FILE__).'/tmp/vendor/vendor');
        $this->print_debug_log("Removed ".dirname(__FILE__).'/tmp/vendor/vendor');
        
        $this->response['status'] = true;
        $this->response['message'] = 'Download Vendor Success';
        $this->response['exectime'] = (microtime(true) - $time_start);
        $this->print_install_log(__METHOD__.' TRUE'.' timeusage '.$this->response['exectime']);
        return $this->print_response($this->response);
        
        
    }
    
    
    public function setup_env($domainname,$publicpath,$dbhost,$dbname,$dbuser,$dbpassword,$ftpaccount,$ftppassword,$appname,$ftpserver,$ftpport) {
        $time_start = microtime(true);
        $this->print_debug_log(__METHOD__);
        
        //TODO clear whitespace
        if (preg_match('/\s/',$appname)) $appname = '"'.$appname.'"';
        $env_data = [];
        $env_data['APP_URL'] = 'https://'.$domainname;
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
        
        $this->print_debug_log("ENV data ".join(', ', $env_data));
        
        if($this->setEnv(dirname(__FILE__).'/tmp/env.example',$env_data,true)) {
            //rename(dirname(__FILE__).'/tmp/env.example',dirname(__FILE__).'/tmp/.env');
            $this->response['status'] = true;
            $this->response['message'] = 'Setup .env Success';
            $this->response['exectime'] = (microtime(true) - $time_start);
            $this->print_install_log(__METHOD__.' TRUE'.' timeusage '.$this->response['exectime']);
            return $this->print_response($this->response);
        }
        
        $this->response['message'] = 'Setup .env Failed';
        $this->response['exectime'] = (microtime(true) - $time_start);
        $this->print_install_log(__METHOD__.' FALSE (Can not setup env)'.' timeusage '.$this->response['exectime']);
        return $this->print_response($this->response);
    }
    
    
    public function download_common_pkg() {
        $time_start = microtime(true);
        $this->print_debug_log(__METHOD__);
        
        $commonpkg = [  'blog',
                        'core',
                        'email',
                        'manage',
                        'queuesharedhost',
                        'scheduler',
                        'wysiwyg',
                    ];
        
        $this->print_debug_log("Common Package ".join(', ', $commonpkg));
        
        foreach ($commonpkg as $pkg) {
            
            $downloadurl = $this->mirror.'/download/rvsitebuilder/'.$pkg ;
            if(isset($this->installerconfig[$pkg]['getversion']) && $this->installerconfig[$pkg]['getversion'] == 'latest') 
            { $downloadurl = $this->mirror.'/download/rvsitebuilder/'.$pkg.'/tier/latest'; }
            if(isset($this->installerconfig[$pkg]['getversion']) && preg_match('/[0-9]+\.[0-9]+\.[0-9]+/',$this->installerconfig[$pkg]['getversion'])) 
            { $downloadurl = $this->mirror.'/download/rvsitebuilder/'.$pkg.'/version/'.$this->installerconfig[$pkg]['getversion']; }
            

            $downloadpkg = $this->download('GET' , $downloadurl , dirname(__FILE__).'/'.$pkg.'.tar.gz');
            
            $this->print_debug_log("Download Common Package URL ".$downloadpkg);
            
            if(! $downloadpkg){
                $this->response['message'] = 'Can not download package '.$pkg;
                $this->response['exectime'] = (microtime(true) - $time_start);
                $this->print_install_log(__METHOD__.' FALSE (Can not download package) '.$pkg.' timeusage '.$this->response['exectime']);
                $this->print_debug_log("Can not download package ".$pkg);
                return $this->print_response($this->response);
            }
            //extract package
            $extractpkg = $this->extract(dirname(__FILE__).'/'.$pkg.'.tar.gz',dirname(__FILE__).'/tmp/packages/');
            if(! $extractpkg) {
                $this->response['message'] = 'Can not extract package '.$pkg;
                $this->response['exectime'] = (microtime(true) - $time_start);
                $this->print_install_log(__METHOD__.' FALSE (Can not extract package) '.$pkg.' timeusage '.$this->response['exectime']);
                $this->print_debug_log("Can not extract package ".$pkg);
                return $this->print_response($this->response);
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
                
                $this->print_debug_log("Download vendor URL ".$downloadvendorurl);
                
                $downloadvendor = $this->download('GET' , $downloadvendorurl , dirname(__FILE__).'/'.$package_name_encoded.'.tar.gz');
                if(! $downloadvendor) {
                    $this->response['message'] = 'Can not download vendor '.$downloadvendorurl;
                    $this->response['exectime'] = (microtime(true) - $time_start);
                    $this->print_install_log(__METHOD__.' FALSE (Can not download vendor) '.$downloadvendorurl.' timeusage '.$this->response['exectime']);
                    $this->print_debug_log("Can not download vendor URL ".$downloadvendorurl);
                    return $this->print_response($this->response);
                }
                $extractvendor = $this->extract(dirname(__FILE__).'/'.$package_name_encoded.'.tar.gz',dirname(__FILE__).'/tmp/');
                if(! $extractvendor) {
                    $this->response['message'] = 'Can not extract vendor '.$package_name_encoded;
                    $this->response['exectime'] = (microtime(true) - $time_start);
                    $this->print_install_log(__METHOD__.' FALSE (Can not extract vendor) '.$package_name_encoded.' timeusage '.$this->response['exectime']);
                    $this->print_debug_log("Can not extract ".dirname(__FILE__).'/'.$package_name_encoded.'.tar.gz');
                    return $this->print_response($this->response);
                }
                $this->print_debug_log("Removed ".dirname(__FILE__).'/'.$package_name_encoded.'.tar.gz');
                unlink(dirname(__FILE__).'/'.$package_name_encoded.'.tar.gz');
            }
            
        }
        
        $this->response['status'] = true;
        $this->response['message'] = 'Install Common Package(s) Success';
        $this->response['exectime'] = (microtime(true) - $time_start);
        $this->print_install_log(__METHOD__.' TRUE'.' timeusage '.$this->response['exectime']);
        return $this->print_response($this->response);
        
    }
    
    
    public function artisan_call($homeuser,$domainname,$publicpath,$adminemail,$adminpassword,$adminfirstname,$adminlastname) {
        $time_start = microtime(true);
        $this->print_debug_log(__METHOD__);
        
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
        $kernel->call('key:generate', []);
        $this->print_debug_log($kernel->output());
        $kernel->call('migrate', ['--force'=>true]);
        $this->print_debug_log($kernel->output());
        $kernel->call('db:seed', ['--force'=>true]);
        $this->print_debug_log($kernel->output());
        //user secret key
        $kernel->call('rvsitebuilder:updateenduserdb-run', ['secretkey' => $this->generateSecretKey()]);
        $this->print_debug_log($kernel->output());
        //vendor publish
        $kernel->call('vendor:publish', ['--tag'=> 'public','--force' => true]);
        $this->print_debug_log($kernel->output());
        //clear cache
        $kernel->call('cache:clear', []);
        $this->print_debug_log($kernel->output());
        $kernel->call('config:clear', []);
        $this->print_debug_log($kernel->output());
        $kernel->call('route:clear', []);
        $this->print_debug_log($kernel->output());
        $kernel->call('view:clear', []);
        $this->print_debug_log($kernel->output());
        //update admin info from wizard request
        if($adminemail != '' && $adminpassword != '') {
            $this->print_debug_log("Update user info to DB adminemail=$adminemail adminpassword=$adminpassword adminfirstname=$adminfirstname adminlastname=$adminlastname");
            $kernel->call('rvsitebuilder:updateuserinfo-run', ['user_id' => 1,'update_key' => 'email', 'update_val' => $adminemail]);
            $this->print_debug_log($kernel->output());
            $kernel->call('rvsitebuilder:updateuserinfo-run', ['user_id' => 1,'update_key' => 'password', 'update_val' => $adminpassword]);
            $this->print_debug_log($kernel->output());
            $kernel->call('rvsitebuilder:updateuserinfo-run', ['user_id' => 1,'update_key' => 'first_name', 'update_val' => $adminfirstname]);
            $this->print_debug_log($kernel->output());
            $kernel->call('rvsitebuilder:updateuserinfo-run', ['user_id' => 1,'update_key' => 'last_name', 'update_val' => $adminlastname]);
            $this->print_debug_log($kernel->output());
        }
       
        $this->response['status'] = true;
        $this->response['message'] = 'Artisan Command Success';
        $this->response['exectime'] = (microtime(true) - $time_start);
        $this->print_install_log(__METHOD__.' TRUE'.' timeusage '.$this->response['exectime']);
        return $this->print_response($this->response);
    }
    
    public function finished_setup($homeuser,$domainname,$publicpath,$ftpaccount,$ftppassword,$ftpserver,$ftpport) {
        $time_start = microtime(true);
        $this->print_debug_log(__METHOD__);
        
        //default 
        if($this->httpasuser){
            //touch install completed
            $this->print_debug_log("TOUCH INSTALL_COMPLETED");
            file_put_contents($homeuser.'/rvsitebuildercms/'.$domainname.'/INSTALL_COMPLETED', '');
            
            //install_log , error_log
            if(file_exists($publicpath.'/rvsitebuilder/install_log.txt')){
                copy($publicpath.'/rvsitebuilder/install_log.txt', $publicpath.'/rvsitebuilder_install_log.txt');
            }
            if(file_exists($publicpath.'/rvsitebuilder/error_log')){
                copy($publicpath.'/rvsitebuilder/error_log', $publicpath.'/rvsitebuilder_install_error_log.txt');
            }
            
            $this->response['status'] = true;
            $this->response['message'] = 'Finished Setup (Default)';
            $this->response['exectime'] = (microtime(true) - $time_start);
            $this->print_install_log(__METHOD__.' TRUE'.' timeusage '.$this->response['exectime']);
            return $this->print_response($this->response);
        } 
        //ftp 
        else {
            //install completed
            $this->print_debug_log("FTP INSTALL_COMPLETED");
            $ftpHandler = new FTP_Handler();
            $result = $ftpHandler->connect($ftpserver);
            if(!$result['success']){
                $this->response['message'] = 'Error '.$result['msg'];
                $this->print_install_log(__METHOD__.' FALSE (FTP connect ) '.$result['msg']);
                return $this->print_response($this->response);
            }
            $result = $ftpHandler->login($ftpaccount, $ftppassword);
            if(!$result['success']){
                $this->response['message'] = 'Error '.$result['msg'];
                $this->print_install_log(__METHOD__.' FALSE (FTP login ) '.$result['msg']);
                return $this->print_response($this->response);
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
            
            $ftpHandler->close();
            
            $this->response['status'] = true;
            $this->response['message'] = 'Finished Setup (FTP)';
            $this->response['exectime'] = (microtime(true) - $time_start);
            $this->print_install_log(__METHOD__.' TRUE'.' timeusage '.$this->response['exectime']);
            return $this->print_response($this->response);
        }
        
    }
    
    
    public function remove_installer_api() {
        $time_start = microtime(true);
        $this->print_debug_log(__METHOD__);
        
        //remove file
        if(! $this->removeinstallerpath == true) {
            
            if ( file_exists(dirname(__FILE__).'/.Rvsb-Installing-Token') ) unlink(dirname(__FILE__).'/.Rvsb-Installing-Token');
            if ( file_exists(dirname(__FILE__).'/framework.tar.gz') ) unlink(dirname(__FILE__).'/framework.tar.gz');
            if ( file_exists(dirname(__FILE__).'/bundle_vendor.tar.gz') ) unlink(dirname(__FILE__).'/bundle_vendor.tar.gz');
            if ( file_exists(dirname(__FILE__).'/blog.tar.gz') ) unlink(dirname(__FILE__).'/blog.tar.gz');
            if ( file_exists(dirname(__FILE__).'/core.tar.gz') ) unlink(dirname(__FILE__).'/core.tar.gz');
            if ( file_exists(dirname(__FILE__).'/email.tar.gz') ) unlink(dirname(__FILE__).'/email.tar.gz');
            if ( file_exists(dirname(__FILE__).'/manage.tar.gz') ) unlink(dirname(__FILE__).'/manage.tar.gz');
            if ( file_exists(dirname(__FILE__).'/queuesharedhost.tar.gz') ) unlink(dirname(__FILE__).'/queuesharedhost.tar.gz');
            if ( file_exists(dirname(__FILE__).'/README.md') ) unlink(dirname(__FILE__).'/README.md');
            if ( file_exists(dirname(__FILE__).'/scheduler.tar.gz') ) unlink(dirname(__FILE__).'/scheduler.tar.gz');
            if ( file_exists(dirname(__FILE__).'/setup.tar.gz') ) unlink(dirname(__FILE__).'/setup.tar.gz');
            if ( file_exists(dirname(__FILE__).'/wysiwyg.tar.gz') ) unlink(dirname(__FILE__).'/wysiwyg.tar.gz');
            if ( file_exists(dirname(__FILE__).'/composer.json') ) unlink(dirname(__FILE__).'/composer.json');
            if ( file_exists(dirname(__FILE__).'/composer.lock') ) unlink(dirname(__FILE__).'/composer.lock');
            if ( file_exists(dirname(__FILE__).'/INSTALL_COMPLETED') ) unlink(dirname(__FILE__).'/INSTALL_COMPLETED');
            if ( file_exists(dirname(__FILE__).'/install.html') ) unlink(dirname(__FILE__).'/install.html');
            if ( file_exists(dirname(__FILE__).'/install.php') ) unlink(dirname(__FILE__).'/install.php');
            if ( file_exists(dirname(__FILE__).'/install.tar.gz') ) unlink(dirname(__FILE__).'/install.tar.gz');
            if ( file_exists(dirname(__FILE__).'/logo_rvsitebuilder.png') ) unlink(dirname(__FILE__).'/logo_rvsitebuilder.png');
            if ( file_exists(dirname(__FILE__).'/logorvsitebuilder.png') ) unlink(dirname(__FILE__).'/logorvsitebuilder.png');
            if ( file_exists(dirname(__FILE__).'/install_log.txt') ) unlink(dirname(__FILE__).'/install_log.txt');
            if ( file_exists(dirname(__FILE__).'/error_log') ) unlink(dirname(__FILE__).'/error_log');
            if ( file_exists(dirname(__FILE__).'/setup.php') ) unlink(dirname(__FILE__).'/setup.php');
            //if ( file_exists(dirname(__FILE__).'/setupapiserver.php') ) unlink(dirname(__FILE__).'/setupapiserver.php');
            if ( file_exists(dirname(__FILE__).'/../domainready.png') ) unlink(dirname(__FILE__).'/../domainready.png');
            
            //remove dir
            $this->rrmdir(dirname(__FILE__).'/tmp');
            $this->rrmdir(dirname(__FILE__).'/vendor');
            $this->rrmdir(dirname(__FILE__).'/src');
            //$this->rrmdir(dirname(__FILE__).'/../rvsitebuilder');
            
            $this->print_debug_log("Removed Installer Path");
        }
        
        //response
        $this->response['status'] = true;
        $this->response['message'] = 'Remove Installer';
        $this->response['exectime'] = (microtime(true) - $time_start);
        $this->print_install_log(__METHOD__.' TRUE'.' timeusage '.$this->response['exectime']);
        return $this->print_response($this->response);
        
    }
    
    
    public function generateSecretKey($length = 64) {
        $this->print_debug_log(__METHOD__);
        
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randstring = '';
        for ($i = 0; $i < $length; $i++) {
            $randstring .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randstring;
    }
    
    
   
    
    
    public function install_all_pkg($homeuser,$domainname,$publicpath,$ftpaccount,$ftppassword,$ftpserver,$ftpport) {
        $this->print_debug_log(__METHOD__);
        
        if($this->httpasuser == true) {
            $this->print_debug_log("Copy Framework Default");
            $this->copyFileDefault($homeuser,$domainname,$publicpath);
        } else {
            $this->print_debug_log("Copy Framework FTP");
            $this->copyFileFTP($homeuser,$domainname,$publicpath,$ftpaccount,$ftppassword,$ftpserver,$ftpport);            
        }
        
        return;
       
    }
    
    function copyFileFTP($homeuser,$domainname,$publicpath,$ftpaccount,$ftppassword,$ftpserver,$ftpport) {
        $time_start = microtime(true);
        $this->print_debug_log(__METHOD__);
        
        //TODO remove first if /home/<user>/rvsitebuildercms/$domainname
        
        
        $src_dir = $publicpath.'/rvsitebuilder/tmp';
        $ftp_remote_dir = '/rvsitebuildercms/'.$domainname;
        $this->print_debug_log("Copy FTP $src_dir To $ftp_remote_dir");
        
        $ftpHandler = new FTP_Handler();
        $result = $ftpHandler->connect($ftpserver);
        if(!$result['success']){
            $this->response['message'] = 'Error '.$result['msg'];
            $this->response['exectime'] = (microtime(true) - $time_start);
            $this->print_debug_log("Can not connect FTP ".$result['msg']);
            $this->print_install_log(__METHOD__.' FALSE (Can not connect FTP) '.$result['msg'].' timeusage '.$this->response['exectime']);
            return $this->print_response($this->response);
        }
        $result = $ftpHandler->login($ftpaccount, $ftppassword);
        if(!$result['success']){
            $this->response['message'] = 'Error '.$result['msg'];
            $this->response['exectime'] = (microtime(true) - $time_start);
            $this->print_debug_log("Can not login FTP ".$result['msg']);
            $this->print_install_log(__METHOD__.' FALSE (Can not login FTP) '.$result['msg'].' timeusage '.$this->response['exectime']);
            return $this->print_response($this->response);
        }
        if(!file_exists($ftp_remote_dir)){
            $result = $ftpHandler->ftp_make_dir($homeuser,$ftp_remote_dir);
            if(!$result['success']) {
                $this->response['message'] = 'Error '.$result['msg'];
                $this->response['exectime'] = (microtime(true) - $time_start);
                $this->print_debug_log("Can make dir  FTP $homeuser , $ftp_remote_dir ".$result['msg']);
                $this->print_install_log(__METHOD__.' FALSE (Can not make dir FTP) '.$result['msg'].' timeusage '.$this->response['exectime']);
                return $this->print_response($this->response);
            }
        }
        
        #copy file to framework path
        $ftpHandler->ftp_copy($src_dir, $ftp_remote_dir);
        $this->print_debug_log("FTP copy framework $src_dir To $ftp_remote_dir");
        
        #copy file to public path
        $src_dir = $homeuser.'/rvsitebuildercms/'.$domainname.'/public';
        $exploded = explode('/',$publicpath);
        $public_html = '/'.end($exploded);
        $ftpHandler->ftp_copy($src_dir, $public_html , ['.htaccess']);
        $this->print_debug_log("FTP copy public $src_dir To $public_html");
        
        //write .htaccess to domain'docroot
        $frameworkhtaccess = '';
        if (file_exists($homeuser.'/rvsitebuildercms/'.$domainname.'/public/.htaccess')) {
            $frameworkhtaccess = file_get_contents($homeuser.'/rvsitebuildercms/'.$domainname.'/public/.htaccess');
        }
        $oldhtaccess = '';
        if (file_exists($publicpath.'/.htaccess')) {
            $oldhtaccess = file_get_contents($publicpath.'/.htaccess');
            $this->print_debug_log("Has Old .htaccess $publicpath/.htaccess");
        }
        $writeoldhtaccess = file_put_contents(dirname(__FILE__).'/htaccess.backup' , $oldhtaccess);
        $writehtaccess =  file_put_contents(dirname(__FILE__).'/htaccess.tmp' , $frameworkhtaccess."\n".$oldhtaccess);
        if(trim($oldhtaccess) != ''){
            $result = $ftpHandler->put(dirname(__FILE__).'/htaccess.backup',$public_html.'/.htaccess.backup',FTP_BINARY);
            $this->print_debug_log("FTP put ".dirname(__FILE__).'/htaccess.backup'.' To '.$public_html.'/.htaccess.backup');
        }
        $result = $ftpHandler->put(dirname(__FILE__).'/htaccess.tmp',$public_html.'/.htaccess',FTP_BINARY);
        $this->print_debug_log("FTP put ".dirname(__FILE__).'/htaccess.tmp'.' To '.$public_html.'/.htaccess');
        
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
        
        $this->response['status'] = true;
        $this->response['message'] = 'Move Freamwork and Public success (FTP)';
        $this->response['exectime'] = (microtime(true) - $time_start);
        $this->print_install_log(__METHOD__.' TRUE (By FTP)'.' timeusage '.$this->response['exectime']);
        
        return $this->print_response($this->response);
    }
    
    function copyFileDefault($homeuser,$domainname,$publicpath) {
        $time_start = microtime(true);
        $this->print_debug_log(__METHOD__);
        
        //remove first if /home/<user>/rvsitebuildercms/$domainname
        if(file_exists($homeuser.'/rvsitebuildercms/'.$domainname)) {
            $this->rrmdir($homeuser.'/rvsitebuildercms/'.$domainname);
            $this->print_debug_log("Removed old framwork path ".$homeuser.'/rvsitebuildercms/'.$domainname);
        }
        
        //move temp to freamwork path
        $files = scandir(dirname(__FILE__).'/tmp');
        $source = dirname(__FILE__).'/tmp/';
        $destination = $homeuser.'/rvsitebuildercms/'.$domainname.'/';
        if (!file_exists($destination)) {
            mkdir($destination, 0755, true);
        }
        foreach ($files as $file) {
            if (in_array($file, [".",".."])) continue;
            rename($source.$file, $destination.$file);
            $this->print_debug_log("Moved $source$file To $destination$file");
        }
        
        //move framework/public to public path
        $source = $homeuser.'/rvsitebuildercms/'.$domainname.'/public';
        $destination = $publicpath;
        $files = new File_Handler();
        $copy = $files->copyDirectory($source, $destination,['.htaccess']);
        $this->print_debug_log("Copy $source To $destination");
        
        //write new .htaccess to domain'docroot
        $frameworkhtaccess = '';
        if (file_exists($homeuser.'/rvsitebuildercms/'.$domainname.'/public/.htaccess')) {
            $frameworkhtaccess = file_get_contents($homeuser.'/rvsitebuildercms/'.$domainname.'/public/.htaccess');
        }
        $oldhtaccess = '';
        if (file_exists($publicpath.'/.htaccess')) {
            $oldhtaccess = file_get_contents($publicpath.'/.htaccess');
            $this->print_debug_log("Has Old .htaccess $publicpath/.htaccess");
        }
        if(trim($oldhtaccess) != ''){
            $writeoldhtaccess = file_put_contents($publicpath.'/.htaccess.backup' , $oldhtaccess);
            $this->print_debug_log("file put backup .htaccess ".$publicpath.'/.htaccess.backup');
        }
        $writehtaccess =  file_put_contents($publicpath.'/.htaccess' , $frameworkhtaccess."\n".$oldhtaccess);
        $this->print_debug_log("file put new .htaccess ".$publicpath.'/.htaccess');
        
        $this->response['status'] = true;
        $this->response['message'] = 'Move Freamwork and Public success (default)';
        $this->response['exectime'] = (microtime(true) - $time_start);
        $this->print_install_log(__METHOD__.' TRUE (By Default)'.' timeusage '.$this->response['exectime']);
        return $this->print_response($this->response);
    }
    
    
    function chmod_r($path,$perm) {
        $this->print_debug_log(__METHOD__);
        
        if(!is_dir($path)) {
            return true;
        }
        $dir = new DirectoryIterator($path);
        foreach ($dir as $item) {
            chmod($item->getPathname(), $perm);
            if ($item->isDir() && !$item->isDot()) {
                $this->chmod_r($item->getPathname());
            }
        }
        return true;
    }
    
    
    public function download($type, $url, $sink) {
        $this->print_debug_log(__METHOD__);
        
        $client = new Client([
                                'curl'            => [CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false],
                                'allow_redirects' => false,
                                'cookies'         => true,
                                'verify'          => false
                            ]);
        $this->print_debug_log("Do Download Type=$type URL=$url Synk=$sink");
        $client->request($type, $url, ['sink' => $sink]);
        if(file_exists($sink)) {
            $this->print_debug_log("Do Download Completed $url");
            return true;
        }
        $this->print_debug_log("Do Download failed $url");
        return false;
    }
    
    public function extract($file,$path) {
        $this->print_debug_log(__METHOD__);
        
        $this->print_debug_log("Do Extract $file $path");
        $tar = new Tar();
        $tar->open($file);
        $tar->extract($path);
        $this->print_debug_log("Do Extract Completed $file $path");
        return true;
    }
    
    
    public function print_response($data) {
        $this->print_debug_log(__METHOD__);
        
        if($this->responseType == 'application/json' || $this->call_responsetype == 'application/json') {
            header('Content-type: application/json');
        }
        echo json_encode( $data );
        exit;
    }
    
    
    
    
    
    public function rrmdir($dir) {
        $this->print_debug_log(__METHOD__);
        
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dir."/".$object) == "dir")
                        $this->rrmdir($dir."/".$object);
                        else unlink   ($dir."/".$object);
                }
            }
            reset($objects);
            rmdir($dir);
        }
        return true;
    }
    
    /*
     * updateuserinfo('1','last_name','bbbbb');
     */
    public function updateuserinfo($user_id,$update_key,$update_val){
        $this->print_debug_log(__METHOD__);
        
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
        $this->print_debug_log(__METHOD__);
        
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
    
    public function setEnv($env_file,$env_data = [],$force = false){
        $this->print_debug_log(__METHOD__);
        
        $this->print_debug_log("ENV File=$env_file Data=".join(',',$env_data)." Force=$force");
        
        if(count($env_data) > 0){
            // Read .env-file
            $env = file_get_contents($env_file);
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
            // And overwrite the .env with the new data
            //file_put_contents($env_file, $env);
            file_put_contents(dirname(__FILE__).'/tmp/.env', $env);
            return true;
        } else {
            $this->print_debug_log("Array env_data is empty");
            return false;
        }
    }
    
    
    
    
    
    public function get_user_path() {
        $time_start = microtime(true);
        $this->print_debug_log(__METHOD__);
        
        $mainHome = '';
     
        $testPathInput = $_SERVER['DOCUMENT_ROOT'];
        
        // case  have posix_getpwuid get uid by owner dir
        if(function_exists('posix_getpwuid')){
            $stat = stat($testPathInput);
            $uid = $stat['uid'];
            $userinfo = posix_getpwuid($uid);
            if(is_dir($userinfo['dir'])){
                $this->response['status'] = true;
                $this->response['homepath'] = $userinfo['dir'];
                $this->response['publicpath'] = $_SERVER['DOCUMENT_ROOT'];
                $this->response['exectime'] = (microtime(true) - $time_start);
                $this->print_debug_log("Case posix_getpwuid ".join(',',$userinfo));
                $this->print_install_log(__METHOD__." TRUE ".join(',',$userinfo).' timeusage '.$this->response['exectime']);
                return $this->print_response($this->response);
            }
        }
        
        // case  cpanel have rvsitebuildercms dir in home
        $paths = preg_split("/\//", $testPathInput);
        $loopDim = count($paths);
        for($i=0; $i < $loopDim; $i++) {
            $testPath = join('/', $paths);
            if(is_dir($testPath . '/rvsitebuildercms'))
            {
                $mainHome = $testPath;
                break;
            }
            array_pop($paths);
        }
        if($mainHome != ''){
            $this->response['status'] = true;
            $this->response['homepath'] = $mainHome;
            $this->response['publicpath'] = $_SERVER['DOCUMENT_ROOT'];
            $this->response['exectime'] = (microtime(true) - $time_start);
            $this->print_debug_log("Case look rvsitebuildercms".join(',',$userinfo));
            $this->print_install_log(__METHOD__." TRUE ".join(',',$userinfo).' timeusage '.$this->response['exectime']);
            
            return $this->print_response($this->response);
        }
        
        
        // case other ../
        if(php_sapi_name() === 'cli'){
            $mainHome = realpath($testPathInput . '/../../');
        }else{
            $mainHome = realpath($testPathInput . '/../');
        }
        $this->response['status'] = true;
        $this->response['homepath'] = $mainHome;
        $this->response['publicpath'] = $_SERVER['DOCUMENT_ROOT'];
        $this->response['exectime'] = (microtime(true) - $time_start);
        $this->print_debug_log("Case recursive path".join(',',$userinfo));
        $this->print_install_log(__METHOD__." TRUE ".join(',',$userinfo).' timeusage '.$this->response['exectime']);
        return $this->print_response($this->response);
        
    }
    
   
    
//     public function check_pre_require() {
//         $this->print_debug_log(__METHOD__);
        
//         //php version
//         $this->response['check_pre_require']['phpversion']['check'] = true;
//         if (version_compare(PHP_VERSION, '7.1.3') < 0) {
//             $this->response['check_pre_require']['phpversion']['check'] = false;
//             $this->response['check_pre_require']['phpversion']['reason'] = '';
//         }
        
//         //php extension
//         $this->response['check_pre_require']['mysqlnd']['check'] = true;
//         if (!extension_loaded('mysqlnd')) {
//             $this->response['check_pre_require']['mysqlnd']['check'] = false;
//             $this->response['check_pre_require']['mysqlnd']['reason'] = '';
//         }
//         $this->response['check_pre_require']['pdo']['check'] = true;
//         if (!extension_loaded('pdo')) {
//             $this->response['check_pre_require']['pdo']['check'] = false;
//             $this->response['check_pre_require']['pdo']['reason'] = '';
//         }
//         $this->response['check_pre_require']['gd']['check'] = true;
//         if (!extension_loaded('gd')) {
//             $this->response['check_pre_require']['gd']['check'] = false;
//             $this->response['check_pre_require']['gd']['reason'] = '';
//         }
//         $this->response['check_pre_require']['curl']['check'] = true;
//         if (!extension_loaded('curl')) {
//             $this->response['check_pre_require']['curl']['check'] = false;
//             $this->response['check_pre_require']['curl']['reason'] = '';
//         }
//         $this->response['check_pre_require']['iconv']['check'] = true;
//         if (!extension_loaded('iconv')) {
//             $this->response['check_pre_require']['iconv']['check'] = false;
//             $this->response['check_pre_require']['iconv']['reason'] = '';
//         }
//         $this->response['check_pre_require']['mbstring']['check'] = true;
//         if (!extension_loaded('mbstring')) {
//             $this->response['check_pre_require']['mbstring']['check'] = false;
//             $this->response['check_pre_require']['mbstring']['reason'] = '';
//         }
//         $this->response['check_pre_require']['fileinfo']['check'] = true;
//         if (!extension_loaded('fileinfo')) {
//             $this->response['check_pre_require']['fileinfo']['check'] = false;
//             $this->response['check_pre_require']['fileinfo']['reason'] = '';
//         }
//         $this->response['check_pre_require']['exif']['check'] = true;
//         if (!extension_loaded('exif')) {
//             $this->response['check_pre_require']['exif']['check'] = false;
//             $this->response['check_pre_require']['exif']['reason'] = '';
//         }
//         $this->response['check_pre_require']['zip']['check'] = true;
//         if (!extension_loaded('zip')) {
//             $this->response['check_pre_require']['zip']['check'] = false;
//             $this->response['check_pre_require']['zip']['reason'] = '';
//         }
        
        
        
//         //php config
//         $this->response['check_pre_require']['allow_url_fopen']['check'] = true;
//         if (ini_get('allow_url_fopen') != 1) {
//             $this->response['check_pre_require']['allow_url_fopen']['check'] = false;
//             $this->response['check_pre_require']['allow_url_fopen']['reason'] = '';
//         }
//         $this->response['check_pre_require']['memory_limit']['check'] = true;
//         preg_match('/([0-9]+)/',ini_get('memory_limit'),$match);
//         if($match[0] < 64) {
//             $this->response['check_pre_require']['memory_limit']['check'] = false;
//             $this->response['check_pre_require']['memory_limit']['reason'] = '';
//         }
        
//         //php function
//         $this->response['check_pre_require']['proc_open']['check'] = true;
//         if (!function_exists('proc_open')) {
//             $this->response['check_pre_require']['proc_open']['check'] = false;
//             $this->response['check_pre_require']['proc_open']['reason'] = '';
//         }
        
//         $this->response['status'] = true;
//         return $this->print_response($this->response);
//     }
    
    public function check_http_as_user() {
        $time_start = microtime(true);
        $this->print_debug_log(__METHOD__);
        
        $this->response['status'] = true;
        $this->response['httpasuser'] = $this->httpasuser;
        $this->response['exectime'] = (microtime(true) - $time_start);
        $this->print_install_log(__METHOD__." TRUE ".' timeusage '.$this->response['exectime']);
        $this->print_debug_log("HTTP AS USER $this->httpasuser");
        
        return $this->print_response($this->response);
    }
    
   
    
    public function test_database_ftp_connect($dbhost,$dbname,$dbuser,$dbpassword,$ftpserver,$ftpaccount,$ftppassword,$ftpport) {
        $time_start = microtime(true);
        $this->print_debug_log(__METHOD__);
        
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
        $this->print_install_log(__METHOD__." TRUE ".join(',',$this->response).' timeusage '.$this->response['exectime']);
        
        return $this->print_response($this->response);
    }
    
    public function check_license() {
        $time_start = microtime(true);
        $this->print_debug_log(__METHOD__);
        
        $this->response['status'] = true;
        $this->response['exectime'] = (microtime(true) - $time_start);
        $this->print_install_log(__METHOD__." TRUE ".' timeusage '.$this->response['exectime']);
        return $this->print_response($this->response);
    }
    
    public function disk_required() {
        $time_start = microtime(true);
        $this->print_debug_log(__METHOD__);
        
        $this->response['status'] = true;
        $this->response['exectime'] = (microtime(true) - $time_start);
        $this->print_install_log(__METHOD__." TRUE ".' timeusage '.$this->response['exectime']);
        return $this->print_response($this->response);
    }
    
    public function print_debug_log($msg = '') {
        if($this->debug_log == true){
            file_put_contents(
                dirname(__FILE__).'/install_log.txt',
                'DEBUG LOG >> ' .$msg.PHP_EOL ,
                FILE_APPEND | LOCK_EX
                );
        }
        return true;
    }
    
    public function print_install_log($msg = '') {
        if($this->install_log == true){
            file_put_contents(
                dirname(__FILE__).'/install_log.txt',
                'INSTALL LOG >> ' .$msg.PHP_EOL ,
                FILE_APPEND | LOCK_EX
                );
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
    
    function testftp() {
        $src_dir = '/home/amarin/public_html/source';
        $dest = 'dest/dir1';
        
        ftp_chdir($this->conn_id, $dest);
        
        $pwd =  ftp_pwd($this->conn_id); // /public_html
        printf("FTP: current directory = %s <br/>", $pwd);
        
        $file = 'aaaa.txt';
        //$upload = ftp_put($this->conn_id, $file, $src_dir."/".$file, FTP_ASCII);
        $file = 'sub1';
        @ftp_mkdir($this->conn_id, $file);
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



class File_Handler{
    
    public function __construct( ) {
    }
    
    public function isDirectory($directory)
    {
        return is_dir($directory);
    }
    
    public function copyDirectory($directory, $destination, $ignore = [], $options = null)
    {
        if (! $this->isDirectory($directory)) {
            return false;
        }
        
        $options = $options ?: FilesystemIterator::SKIP_DOTS;
        
        if (! $this->isDirectory($destination)) {
            $this->makeDirectory($destination, 0777, true);
        }
        
        $items = new FilesystemIterator($directory, $options);
        
        foreach ($items as $item) {
            
            if(in_array($item->getBasename() , $ignore)){
                continue;
            }
            
            $target = $destination.'/'.$item->getBasename();
            
            if ($item->isDir()) {
                $path = $item->getPathname();
                
                if (! $this->copyDirectory($path, $target, $ignore, $options)) {
                    return false;
                }
            }
            
            else {
                if (! $this->copy($item->getPathname(), $target)) {
                    return false;
                }
            }
        }
        
        return true;
    }
    
    public  function makeDirectory($path, $mode = 0755, $recursive = false, $force = false)
    {
        if ($force) {
            return @mkdir($path, $mode, $recursive);
        }
        
        return mkdir($path, $mode, $recursive);
    }
    
    public  function copy($path, $target)
    {
        return copy($path, $target);
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

?>
