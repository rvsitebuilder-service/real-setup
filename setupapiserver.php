<?php 


/*
 * -step to setup
 *  -download rvsitebuilder package rvsitebuilder/framework (ได้ stable)
 *  -extract framework
 *  -read rvsitebuilder.json
 *  case: new install
 *      -read vendor-packages and download from key name เพื่อ ต่อ string url download package
 *      -if have vendor bundle package
 *          -download vendor bundle
 *          -extract vendor
 *          -ไปอ่าน apps.json เพื่อ หา default require package
 *          -request ไป route manage เพื่อ install (package name , url)
 *      -if not have
 *          -go to case update
 *  case: update
 *      -read key packages
 *      -check ว่า มี package ไหน ที่ v ไม่ตรงกับ package เดิมบ้าง
 *
 * */


require 'vendor/autoload.php';
use GuzzleHttp\Client;
use splitbrain\PHPArchive\Tar;


$responsetype           = $_SESSION['responsetype'] ;
$action                 = (isset($_SESSION['action']) ? $_SESSION['action'] : '');
$rvsb_installing_token  = $_SESSION['rvsb_installing_token'];
$firstreg               = (isset($_SESSION['firstreg']) ? $_SESSION['firstreg'] : false);
$homeuser               = (isset($_SESSION['homeuser']) ? $_SESSION['homeuser'] : '');
$domainname             = (isset($_SESSION['domainname']) ? $_SESSION['domainname'] : '');
$publicpath             = (isset($_SESSION['public_path']) ? $_SESSION['public_path'] : '');
$dbhost                 = (isset($_SESSION['dbhost']) ? $_SESSION['dbhost'] : '');
$dbname                 = (isset($_SESSION['dbname']) ? $_SESSION['dbname'] : '');
$dbuser                 = (isset($_SESSION['dbuser']) ? $_SESSION['dbuser'] : '');
$dbpassword             = (isset($_SESSION['dbpassword']) ? $_SESSION['dbpassword'] : '');
$ftpaccount             = (isset($_SESSION['ftpaccount']) ? $_SESSION['ftpaccount'] : '');
$ftppassword            = (isset($_SESSION['ftppassword']) ? $_SESSION['ftppassword'] : '');
$appname                = (isset($_SESSION['appname']) ? $_SESSION['appname'] : 'RVsitebuilder');



$setupObj = new RVsitebuilder_Setup_API($responsetype,$rvsb_installing_token);

if($action == '' && $firstreg) {
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
    $setupObj->setup_env($domainname,$publicpath,$dbhost,$dbname,$dbuser,$dbpassword,$ftpaccount,$ftppassword,$appname);
}

if($action == 'install_common_pkg'){
    $setupObj->install_common_pkg();
}

if($action == 'install_all_pkg' && $homeuser != '' && $domainname != '' && $publicpath != ''){
    $setupObj->install_all_pkg($homeuser,$domainname,$publicpath);
}

if($action == 'artisan_call'){
    $setupObj->artisan_call($homeuser,$domainname,$publicpath);
}

if($action == 'finished_setup'){
    $setupObj->finished_setup($homeuser,$domainname);
}

if($action == 'remove_installer_api'){
    $setupObj->remove_installer_api();
}






class RVsitebuilder_Setup_API {
    
    protected $responseType;
    protected $response;
    protected $serverconf;
    protected $downloadurl;
    protected $debug;
    protected $httpasuser;
    
    public function __construct($responsetype,$rvsb_installing_token)
    {   
        //debug var
        $this->debug = false;
        
        //response type
        $this->responseType = $responsetype;
        
        //default response
        $this->response['status'] = false;
        $this->response['message'] = '';
        
        //verify token
        $this->regtoken = $rvsb_installing_token;
        $this->verify_token($rvsb_installing_token);
        
        $this->httpasuser = $this->gethttpasuser();
        
        //download url
        $this->downloadurl = 'https://files.mirror1.rvsitebuilder.com/download';
        
    }
    
    public function gethttpasuser() {
        $homepath_owner = posix_getpwuid(fileowner($_SERVER["DOCUMENT_ROOT"]))['name'];
        $site_run_as = posix_getpwuid(posix_geteuid())['name'];
        if($homepath_owner == $site_run_as){
            return true;
        }
        return false;
    }
    
    public function verify_token($rvsb_installing_token='') {
        if(! file_exists(dirname(__FILE__).'/.Rvsb-Installing-Token')) {
            $this->response['message'] = 'Wrong!!!! token file';
            $this->clear_session();
            return $this->print_response($this->response);
        }
        $tokenvalue = file_get_contents(dirname(__FILE__).'/.Rvsb-Installing-Token');
        if(trim($tokenvalue) != trim($rvsb_installing_token)){
            $this->response['message'] = 'Wrong!!!!';
            $this->clear_session();
            return $this->print_response($this->response);
        }
    }
    
    public function send_token() {
        $this->response['status'] = true;
        $this->response['rvsb_installing_token'] = $this->regtoken;
        $this->clear_session();
        return $this->print_response($this->response);
    }
    
    public function pre_check_php() {
        
        //TODO support http run as not user
        if(! $this->httpasuser) {
            $this->response['message'] = 'HTTP run as nobody.Not currently supported';
            return $this->print_response($this->response);
        }
        
        $phpversion = '7.1.0';
        $phpextension = ['mysqlnd','pdo','gd','curl','iconv','mbstring','fileinfo','exif','zip'];
        $iniconfig = [
            'allow_url_fopen' => 1,
            'memory_limit' => 64 
        ];
        
        //php version
        if (version_compare(PHP_VERSION, $phpversion) < 0) {
            $this->response['message'] = 'System required PHP Version > = 7.1.0';
            return $this->print_response($this->response);
        }
        
        //php extension
        foreach ($phpextension as $extension) {
            if (!extension_loaded($extension)) {
                $this->response['message'] = 'Can not load PHP Extension ('.$extension.')';
                return $this->print_response($this->response);
            }
        }
        
        //php config
        if(ini_get('allow_url_fopen') != $iniconfig['allow_url_fopen']){
            $this->response['message'] = 'Error php.ini, Set allow_url_fopen=ON';
            return $this->print_response($this->response);
        }
        preg_match('/[1-9]+/',ini_get('memory_limit'),$match);
        if($match[0] < $iniconfig['memory_limit']) {
            $this->response['message'] = 'Error php.ini, Set Memory limit at least 64M.';
            return $this->print_response($this->response);
        }
        
        $this->response['status'] = true;
        $this->response['message'] = 'PHP Version,Extentsion,INI OK';
        $this->clear_session();
        return $this->print_response($this->response);
        
    }
    
    public function download_framework() {
        
        //download framework
        $downloadframework = $this->download('GET' , $this->downloadurl.'/rvsitebuilder/framework' , dirname(__FILE__).'/framework.tar.gz');
        if(! $downloadframework){
            $this->response['message'] = 'Can not download framework';
            $this->clear_session();
            return $this->print_response($this->response);
        }
        //extract framework
        $extractframework = $this->extract(dirname(__FILE__).'/framework.tar.gz',dirname(__FILE__).'/tmp/');
        if(! $extractframework) {
            $this->response['message'] = 'Can not extract framework.tar.gz';
            $this->clear_session();
            return $this->print_response($this->response);
        }
        
        $this->response['status'] = true;
        $this->response['message'] = 'Download Framework Success';
        return $this->print_response($this->response);
    } 
    
    public function download_vendor() {
        
        //check rvsitebuilder.json
        if(! file_exists(dirname(__FILE__).'/tmp/rvsitebuilder.json')){
            $this->response['message'] = 'Can not open file rvsitebuilder.json';
            $this->clear_session();
            return $this->print_response($this->response);
        }
        
        //read rvsitebuilder.json
        $rvsbjson = json_decode(file_get_contents(dirname(__FILE__).'/tmp/rvsitebuilder.json'), true);
        
        //first download from key vendor-packages (bundle_vendor) if key exists
        // link download = http://files.mirror1.rvsitebuilder.com/download/rvsitebuilder/framework%2Fbundle_vendor/version/0.0.8
        // vendor-packages = rvsitebuilder\/framework\/bundle_vendor
        if(isset($rvsbjson['vendor-packages']) && key($rvsbjson['vendor-packages']) != ''){
            $vendorkey = key($rvsbjson['vendor-packages']);
            list($product_name, $app_name) = preg_split('/\//', $vendorkey, 2);
            $package_name_encoded = '/'.$product_name.'/'.urlencode($app_name);
            $version = '/version/'.$rvsbjson['version'];
            $downloadvendorurl = $this->downloadurl.$package_name_encoded.$version;
            $downloadvendor = $this->download('GET' , $downloadvendorurl , dirname(__FILE__).'/bundle_vendor.tar.gz');
            if(! $downloadvendor) {
                $this->response['message'] = 'Can not download vendor';
                $this->clear_session();
                return $this->print_response($this->response);
            }
            $extractvendor = $this->extract(dirname(__FILE__).'/bundle_vendor.tar.gz',dirname(__FILE__).'/tmp/vendor/');
            if(! $extractvendor) {
                $this->response['message'] = 'Can not extract vendor.tar.gz';
                $this->clear_session();
                return $this->print_response($this->response);
            }
        } 
        
        //lookup and download all from key packages
        //วิธีนี้ อาจเจอ timeout
        else {
            foreach($rvsbjson['packages'] as $package_key => $value){
                $update_package_name = $rvsbjson['packages'][$package_key]['name'];
                $update_package_version = isset($rvsbjson['packages'][$package_key]['version']) ? $rvsbjson['packages'][$package_key]['version'] : '';
                list($product_name, $app_name) = preg_split('/\//', $update_package_name, 2);
                $package_name_encoded = urlencode($app_name);
                if ($update_package_version != '') {
                    $update_package_version = '/version/' . $update_package_version;
                }
                
                $downloadvendorurl = $this->downloadurl.'/'.$product_name.'/'.urlencode($app_name).$update_package_version;
                $downloadvendor = $this->download('GET' , $downloadvendorurl , dirname(__FILE__).'/'.$package_name_encoded.'.tar.gz');
                if(! $downloadvendor) {
                    $this->response['message'] = 'Can not download vendor '.$downloadvendorurl;
                    $this->clear_session();
                    return $this->print_response($this->response);
                }
                $extractvendor = $this->extract(dirname(__FILE__).'/'.$package_name_encoded.'.tar.gz',dirname(__FILE__).'/tmp/vendor/');
                if(! $extractvendor) {
                    $this->response['message'] = 'Can not extract vendor '.$package_name_encoded;
                    $this->clear_session();
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
        
        // Delete all successfully-copied files
        rmdir(dirname(__FILE__).'/tmp/vendor/vendor');
        
        $this->response['status'] = true;
        $this->response['message'] = 'Download Vendor Success';
        return $this->print_response($this->response);
        
                
    }
    
    
    public function download_vendor_bak() {
        
        //### ใช้ download จากtmp domain ที่สร้างไว้ก่อน พี่บูมแก้ link download เสร็จ ต้อง แก้ setupapiserver.php->download_vendor ให้กลับมาเหมือนเดิม
        //### และต้องแก้ setupapiserver.php->download_vendor ให้กลับมา download จาก files.mirror1.rvglobalsoft.net ด้วย
        //### และต้อง pack rvsb cpanel plugin ใหม่ และ build stable ใหม่ ด้วย
        
        $downloadvendorurl = 'http://download.rvglobalsoft.com/bundle_vendor.tar.gz';
        $downloadvendor = $this->download('GET' , $downloadvendorurl , dirname(__FILE__).'/bundle_vendor.tar.gz');
        if(! $downloadvendor) {
            $this->response['message'] = 'Can not download vendor';
            $this->clear_session();
            return $this->print_response($this->response);
        }
        $extractvendor = $this->extract(dirname(__FILE__).'/bundle_vendor.tar.gz',dirname(__FILE__).'/tmp/vendor/');
        if(! $extractvendor) {
            $this->response['message'] = 'Can not extract vendor.tar.gz';
            $this->clear_session();
            return $this->print_response($this->response);
        }
        
        //move vendor package to vendor path
        $files = scandir(dirname(__FILE__).'/tmp/vendor/vendor');
        $source = dirname(__FILE__).'/tmp/vendor/vendor/';
        $destination = dirname(__FILE__).'/tmp/vendor/';
        foreach ($files as $file) {
            if (in_array($file, [".",".."])) continue;
            rename($source.$file, $destination.$file);
        }
        
        // Delete all successfully-copied files
        rmdir(dirname(__FILE__).'/tmp/vendor/vendor');
        
        $this->response['status'] = true;
        $this->response['message'] = 'Download Vendor Success';
        return $this->print_response($this->response);
        
    }
    
    
    
    public function setup_env($domainname,$publicpath,$dbhost,$dbname,$dbuser,$dbpassword,$ftpaccount,$ftppassword,$appname) {
        
        //TODO clear whitespace
        if (preg_match('/\s/',$appname)) $appname = '"'.$appname.'"';
        
        $env_data['APP_URL'] = 'http://'.$domainname;
        $env_data['DB_HOST'] = $dbhost;
        $env_data['DB_DATABASE'] = $dbname;
        $env_data['DB_USERNAME'] = $dbuser;
        $env_data['DB_PASSWORD'] = $dbpassword;
        $env_data['HTTP_AS_USER'] = ($this->httpasuser) ? 'true' : 'false';
        $env_data['APP_NAME']   = $appname;
        $env_data['FTP_ACCOUNT'] = $ftpaccount;
        $env_data['FTP_PASSWORD'] = $ftppassword;
        
        if($this->setEnv(dirname(__FILE__).'/tmp/env.example',$env_data,true)) {
            rename(dirname(__FILE__).'/tmp/env.example',dirname(__FILE__).'/tmp/.env');
            $this->response['status'] = true;
            $this->response['message'] = 'Setup .env Success';
            $this->clear_session();
            return $this->print_response($this->response);
        }
        
        $this->response['message'] = 'Setup .env Failed';
        $this->clear_session();
        return $this->print_response($this->response);
    }
    
    public function install_common_pkg() {
        $commonpkg = [  'blog',
                        'core',
                        'email',
                        'manage',
                        'queuesharedhost',
                        'scheduler',
                        'wysiwyg',
                    ];
        
        foreach ($commonpkg as $pkg) {
            $downloadpkg = $this->download('GET' , $this->downloadurl.'/rvsitebuilder/'.$pkg , dirname(__FILE__).'/'.$pkg.'.tar.gz');
            if(! $downloadpkg){
                $this->response['message'] = 'Can not download package '.$pkg;
                $this->clear_session();
                return $this->print_response($this->response);
            }
            //extract package
            $extractpkg = $this->extract(dirname(__FILE__).'/'.$pkg.'.tar.gz',dirname(__FILE__).'/tmp/packages/');
            if(! $extractpkg) {
                $this->response['message'] = 'Can not extract package '.$pkg;
                $this->clear_session();
                return $this->print_response($this->response);
            }
        }
        
        
        $this->response['status'] = true;
        $this->response['message'] = 'Install Common Package(s) Success';
        return $this->print_response($this->response);
        
    }
    
    public function artisan_call($homeuser,$domainname,$publicpath) {
        
        //loader
        // /home/arnut/rvsitebuildercms/arnut.cpdev1.rvglobalsoft.net/vendor/autoload.php
        $loader = require $homeuser.'/rvsitebuildercms/'.$domainname.'/vendor/autoload.php';

        
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
                }
            }
        }
        
        //call artisan
        $app = require_once  $homeuser.'/rvsitebuildercms/'.$domainname.'/bootstrap/app.php';
        $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
        
        
        //Common
        $kernel->call('key:generate', []);
        $this->print_debug($kernel->output());
        $kernel->call('migrate', ['--force'=>true]);
        $this->print_debug($kernel->output());
        $kernel->call('db:seed', ['--force'=>true]);
        $this->print_debug($kernel->output());
        
        //user secret key
        $kernel->call('rvsitebuilder:updateenduserdb-run', ['secretkey' => $this->generateSecretKey()]);
        $this->print_debug($kernel->output());

// ยกเลิกการรัน seeder 01/15/2019
//         //Manage
//         $kernel->call('db:seed', ['--class'=> Rvsitebuilder\Manage\Database\Seeds\AppSeeder::class,'--force' => true]);
//         $this->print_debug($kernel->output());
//         //Core
//         $kernel->call('db:seed', ['--class'=> Rvsitebuilder\Core\Database\Seeds\AppSeeder::class,'--force' => true]);
//         $this->print_debug($kernel->output());
//         //Blog
//         $kernel->call('db:seed', ['--class'=> Rvsitebuilder\Blog\Database\Seeds\AppSeeder::class,'--force' => true]);
//         $this->print_debug($kernel->output());
//         //Email
//         $kernel->call('db:seed', ['--class'=> Rvsitebuilder\Email\Database\Seeds\AppSeeder::class,'--force' => true]);
//         $this->print_debug($kernel->output());
//         //Scheduler
//         $kernel->call('db:seed', ['--class'=> Rvsitebuilder\Scheduler\Database\Seeds\SchedulerDatabaseSeeder::class,'--force' => true]);
//         $this->print_debug($kernel->output());
      
        //vendor publish
        $kernel->call('vendor:publish', ['--tag'=> 'public','--force' => true]);
        $this->print_debug($kernel->output());
        
        //clear cache
        $kernel->call('cache:clear', []);
        $this->print_debug($kernel->output());
        $kernel->call('config:clear', []);
        $this->print_debug($kernel->output());
        $kernel->call('route:clear', []);
        $this->print_debug($kernel->output());
        $kernel->call('view:clear', []);
        $this->print_debug($kernel->output());
        
        
        
        $this->response['status'] = true;
        $this->response['message'] = 'Artisan Command Success';
        $this->clear_session();
        return $this->print_response($this->response);
    }
    
    public function finished_setup($homeuser,$domainname) {
        //touch install completed
        file_put_contents($homeuser.'/rvsitebuildercms/'.$domainname.'/INSTALL_COMPLETED', '');
        
        $this->response['status'] = true;
        $this->response['message'] = 'Finished Setup';
        $this->clear_session();
        return $this->print_response($this->response);
    }
    
    public function remove_installer_api() {
        //remove file
        if ( file_exists(dirname(__FILE__).'/.Rvsb-Installing-Token') ) unlink(dirname(__FILE__).'/.Rvsb-Installing-Token');
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
        //remove dir
        $this->rrmdir(dirname(__FILE__).'/tmp');
        $this->rrmdir(dirname(__FILE__).'/vendor');
        //response
        $this->response['status'] = true;
        $this->response['message'] = 'Remove Installer';
        $this->clear_session();
        return $this->print_response($this->response);
        
    }
    
    public function generateSecretKey($length = 64) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randstring = '';
        for ($i = 0; $i < $length; $i++) {
            $randstring .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randstring;
    }
    
    public function print_debug($data) {
        if($this->debug) {
            echo '<pre>';
            print_r($data);
            echo '</pre>';
        }
        return true;
    }
    
    public function install_all_pkg($homeuser,$domainname,$publicpath) {
        
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
        }
        
        //move framework/public to public path
        $files = scandir($homeuser.'/rvsitebuildercms/'.$domainname.'/public');
        $source = $homeuser.'/rvsitebuildercms/'.$domainname.'/public/';
        $destination = $publicpath.'/';
        foreach ($files as $file) {
            if (in_array($file, [".",".."])) continue;
            rename($source.$file, $destination.$file);
        }
        
        //chmod
        if($this->httpasuser == false) {
            $this->chmod_r($publicpath.'/storage', 0777);
            $this->chmod_r($publicpath.'/vendor', 0777);
            $this->chmod_r($homeuser.'/rvsitebuildercms/'.$domainname.'/storage', 777);
        }
        
        
        $this->response['status'] = true;
        $this->response['message'] = 'Move Freamwork and Public success';
        return $this->print_response($this->response);
        
    }
    
    function chmod_r($path,$perm) {
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
        $client = new Client([
                                'curl'            => [CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false],
                                'allow_redirects' => false,
                                'cookies'         => true,
                                'verify'          => false
                            ]);
        $client->request($type, $url, ['sink' => $sink]);
        if(file_exists($sink)) {
            return true;
        }
        return false;
    }
    
    public function extract($file,$path) {
        $tar = new Tar();
        $tar->open($file);
        $tar->extract($path);
        return true;
    }
    
    public function print_response($data) {
        if($this->responseType == 'application/json') {
            header('Content-type: application/json');
        }
        echo json_encode( $data );
        exit;
    }
    
    public function clear_session() {
        session_destroy();
        $_SESSION = array();
        return;
    }
    
    public function rrmdir($dir) {
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
    
    public function setEnv($env_file,$env_data = [],$force = false){
        
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
            file_put_contents($env_file, $env);
            return true;
        } else {
            return false;
        }
    }
    
    
   
}

/*
 * echo 'Begin test ftp <br/>';

$ftp_server = 'ftp.amarin.rvwizard.com';
$ftp_user_name = 'testftp@amarin.rvwizard.com';
$ftp_user_pass = 'xxxxxxxx';

$src_dir = '/home/arnut/public_html/rvsitebuilder/tmp';
$ftp_remote_dir = '/rvsitebuildercms/arnut.cprelease.rvglobalsoft.net';

$ftpHandler = new FTP_Handler();
$result = $ftpHandler->connect($ftp_server);
if(!$result['success']){
    echo "Error: " . $result['msg'];
    exit;
}
$ftpHandler->login($ftp_user_name, $ftp_user_pass);
if(!$result['success']){
    echo "Error: " . $result['msg'];
    exit;
}

$ftpHandler->ftp_copy($src_dir, $ftp_remote_dir);
$ftpHandler->close();

echo 'End test ftp <br/>';
 */

class FTP_Handler{
    protected $conn_id;    
    
    public function __construct( ) {
    }
    
    function connect($ftp_server) {
        $result = [];
        $result['success'] = 1;
        $this->conn_id = ftp_connect($ftp_server);
        if ( ! $this->conn_id ) {
            $result['success'] = 0;
            $result['msg'] = 'Fail to connect ' . $ftp_server;
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
    
    function put($dest = '', $source = '', $mode = 'FTP_ASCII') {
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
    function ftp_copy($src_dir, $dst_dir) {
        $debug = 0;
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
                            @ftp_chmod($this->conn_id, 0777, $file);
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
                        @ftp_chmod($this->conn_id, 0777, $file);
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
            @ftp_chmod($this->conn_id, 0777, $file);
        }
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

?>