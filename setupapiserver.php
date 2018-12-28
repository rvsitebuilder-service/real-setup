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
$action                 = $_SESSION['action'];
$rvsb_installing_token  = $_SESSION['rvsb_installing_token'];
$firstreg               = (isset($_SESSION['firstreg']) ? $_SESSION['firstreg'] : false);
    


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







class RVsitebuilder_Setup_API {
    
    protected $pathPublic;
    protected $responseType;
    protected $response;
    protected $serverconf;
    protected $downloadurl;
    
    public function __construct($responsetype,$rvsb_installing_token)
    {   
        //response type
        $this->responseType = $responsetype;
        
        //default response
        $this->response['status'] = false;
        $this->response['message'] = '';
        
        //verify token
        $this->regtoken = $rvsb_installing_token;
        $this->verify_token($rvsb_installing_token);
        
        //download url
        $this->downloadurl = 'http://files.mirror1.rvsitebuilder.com/download';
        
    }
    
    public function verify_token($rvsb_installing_token='') {
        if(! file_exists(dirname(__FILE__).'/.Rvsb-Installing-Token')) {
            $this->response['message'] = 'Wrong!!!! token file';
            $this->clear_session();
            return $this->print_response($this->response);
        }
        $tokenvalue = file_get_contents(dirname(__FILE__).'/.Rvsb-Installing-Token');
        if($tokenvalue != $rvsb_installing_token){
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
        
        //read rvsitebuilder.json
        if(! file_exists(dirname(__FILE__).'/tmp/rvsitebuilder.json')){
            $this->response['message'] = 'Can not open file rvsitebuilder.json';
            $this->clear_session();
            return $this->print_response($this->response);
        }
        $rvsbjson = json_decode(file_get_contents(dirname(__FILE__).'/tmp/rvsitebuilder.json'), true);
        
        //download from key vendor-packages
        if(isset($rvsbjson['vendor-packages']) && key($rvsbjson['vendor-packages']) != ''){
            $link = '/'.key($rvsbjson['vendor-packages']);
            $version = '/version/'.$rvsbjson['version'];
            $downloadvendorurl = $this->downloadurl.$link.$version;
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
        
        //move vendor package to vendor path
        $files = scandir(dirname(__FILE__).'/tmp/vendor/vendor');
        $source = dirname(__FILE__).'/tmp/vendor/vendor/';
        $destination = dirname(__FILE__).'/tmp/vendor/';
        foreach ($files as $file) {
            if (in_array($file, array(".",".."))) continue;
            rename($source.$file, $destination.$file);
        }
        
        // Delete all successfully-copied files
        // TODO de
        rmdir(dirname(__FILE__).'/tmp/vendor/vendor');
        
        $this->response['status'] = true;
        $this->response['message'] = 'Download Vendor Success';
        return $this->print_response($this->response);
        
        
        //TODO loop all package and download if $rvsbjson['vendor-packages'] NOT SET
        
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
    
   
}

?>