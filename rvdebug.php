<a href="<?php $_SERVER['HTTP_HOST'] ?>/rvsitebuilder/rvdebug.php?action=show_debug_dashboard" target="_self">Dashboard</a>
<br/>
LOGS (
<a href="<?php $_SERVER['HTTP_HOST'] ?>/rvsitebuilder/rvdebug.php?action=show_install_log" target="_self">Install Log</a>
|
<a href="<?php $_SERVER['HTTP_HOST'] ?>/rvsitebuilder/rvdebug.php?action=show_error_log" target="_self">Error Log</a>
|
<a href="<?php $_SERVER['HTTP_HOST'] ?>/rvsitebuilder/rvdebug.php?action=show_rvsitebuilder_install_log" target="_self">Install Log (After Installed)</a>
|
<a href="<?php $_SERVER['HTTP_HOST'] ?>/rvsitebuilder/rvdebug.php?action=show_rvsitebuilder_install_error_log" target="_self">Error Log (After Installed)</a>
)
</br>


<?php

$action = isset($_GET['action']) ? $_GET['action'] : 'show_debug_dashboard';

$rvdebugObj = new RVDebug($app);

if(method_exists($rvdebugObj,$action)){
    echo "<h2>Action $action</h2>";
    $rvdebugObj->$action();
}


class RVDebug{
    protected $rvsitebuilder_dir;
    
    public function __construct()
    {
        $this->rvsitebuilder_dir = dirname(__FILE__);   
    }
    
    function show_debug_dashboard(){        
        $this->showphpinfo();
    }
    
    function showphpinfo(){
        phpinfo();
    }
    
    function show_install_log(){
        $logfile = $this->rvsitebuilder_dir . '/install_log.txt';
        $this->print_log($logfile);        
    }
    
    function show_error_log(){
        $logfile = $this->rvsitebuilder_dir . '/error_log';
        $this->print_log($logfile);
    }
    
    function show_rvsitebuilder_install_log(){
        $logfile = $this->rvsitebuilder_dir . '/../rvsitebuilder_install_log.txt';
        $this->print_log($logfile);
    }
    
    function show_rvsitebuilder_install_error_log(){
        $logfile = $this->rvsitebuilder_dir . '/../rvsitebuilder_install_error_log.txt';
        $this->print_log($logfile);
    }
    
    function print_log($logfile = ''){
        if(!is_file($logfile)){
            echo "Not found log file";
            return 0;
        }
        echo '<textarea style="width:100%;height: 500px;line-height: 1; font-size: 12px;">';
        $my_file = fopen($logfile, "r");
        while (! feof ($my_file))
        {
            echo fgets($my_file);
        }
        
        fclose($my_file);
        echo "</textarea>";
        return 1;
    }
}




?>


