<?php

class FTP_Handler
{
    protected $conn_id;

    public function __construct()
    {
    }

    public function connect($ftpserver): array
    {
        $result = [];
        $result['success'] = 1;
        $this->conn_id = ftp_connect($ftpserver);
        if (!$this->conn_id) {
            $result['success'] = 0;
            $result['msg'] = 'Fail to connect ' . $ftpserver;
        }
        return $result;
    }

    public function login($ftp_user_name, $ftp_user_pass): array
    {
        $result = [];
        $result['success'] = 1;
        $login = ftp_login($this->conn_id, $ftp_user_name, $ftp_user_pass);
        if (!$login) {
            $result['success'] = 0;
            $result['msg'] = 'Fail to login ftp ';
        }

        ftp_pasv($this->conn_id, true);
        return $result;
    }

    public function nlist($path = '.')
    {
        $dirLists = ftp_nlist($this->conn_id, $path);
        return $dirLists;
    }

    public function put($source = '', $dest = '', $mode = 'FTP_ASCII'): array
    {
        $result = [];
        $result['success'] = 1;
        $upload = ftp_put($this->conn_id, $dest, $source, $mode);
        if (!$upload) {
            $result['success'] = 0;
            $result['msg'] = 'Fail to ftp upload ';
        }
        return $result;
    }

    public function rename($old_file, $new_file): array
    {
        $result = [];
        $result['success'] = 1;
        $upload = ftp_rename($this->conn_id, $old_file, $new_file);
        if (!$upload) {
            $result['success'] = 0;
            $result['msg'] = 'Fail to rename ftp';
        }
    }

    public function ftp_copy($src_dir, $dst_dir, $ignore = [])
    {
        $debug = false;
        $chdir = $dst_dir;
        if ($debug) {
            echo "- ftp_copy $src_dir , $dst_dir <br />";
            echo "-- ftp_chdir to $chdir <br />";
        }
        ftp_chdir($this->conn_id, $chdir);

        if (is_dir($src_dir)) {
            $dir = new DirectoryIterator($src_dir);
            foreach ($dir as $fileinfo) {
                $file = $fileinfo->getFilename();

                if (in_array($file, $ignore)) {
                    continue;
                }

                if ($file != "." && $file != "..") {
                    if (is_dir($src_dir . "/" . $file)) {
                        if (!$this->ftp_is_dir($this->conn_id, $file)) {
                            ftp_chdir($this->conn_id, $dst_dir);
                            $pushd = ftp_pwd($this->conn_id);
                            if ($debug) {
                                echo "--- ftp pwd = $pushd<br />";
                                echo "--- Not found ftp dir now do ftp_mkdir $file at $dst_dir<br />";
                            }
                            @ftp_mkdir($this->conn_id, $file);
                            @ftp_chmod($this->conn_id, 0755, $file);
                        }
                        $this->ftp_copy($src_dir . "/" . $file, $dst_dir . "/" . $file);
                    } else {
                        ftp_chdir($this->conn_id, $dst_dir);
                        $pushd = ftp_pwd($this->conn_id);
                        if ($debug) {
                            echo "------ ftp pwd = $pushd<br />";
                            echo "------ ftp_put $file to $dst_dir<br />";
                        }
                        $pushd = ftp_pwd($this->conn_id);
                        $upload = ftp_put($this->conn_id, $file, $src_dir . "/" . $file, FTP_BINARY);
                        @ftp_chmod($this->conn_id, 0644, $file);
                    }
                }
            }
        } else {
            ftp_chdir($this->conn_id, $dst_dir);
            $pushd = ftp_pwd($this->conn_id);
            if ($debug) {
                echo "------ ftp pwd = $pushd<br />";
                echo "------ ftp_put $file to $dst_dir<br />";
            }
            $upload = ftp_put($this->conn_id, $file, $src_dir . "/" . $file, FTP_BINARY);
            @ftp_chmod($this->conn_id, 0644, $file);
        }
    }

    public function ftp_change_mod_r($realpath, $path, $perm = 0777)
    {
        ftp_chmod($this->conn_id, $perm, $path);
        ftp_chdir($this->conn_id, $path);

        $dir = new DirectoryIterator($realpath);
        foreach ($dir as $fileinfo) {
            $file = $fileinfo->getFilename();
            if ($file != "." && $file != "..") {
                @ftp_chmod($this->conn_id, $perm, $path . '/' . $file);
                if (is_dir($realpath . '/' . $file)) {
                    $this->ftp_change_mod_r($realpath . '/' . $file, $path . '/' . $file, $perm);
                }
            }
        }
        return true;
    }

    public function ftp_change_mod($path, $perm = 0777): bool
    {
        ftp_chmod($this->conn_id, $perm, $path);
        return true;
    }

    public function ftp_make_dir($homeuserdir, $dir)
    {
        $result['success'] = 0;
        $result['msg'] = 'Fail to create directory ' . $dir;

        @ftp_chdir($this->conn_id, $homeuserdir);
        $parts = explode('/', $dir);
        foreach ($parts as $part) {
            if (!@ftp_chdir($this->conn_id, $part)) {
                ftp_mkdir($this->conn_id, $part);
                ftp_chdir($this->conn_id, $part);
            }
        }

        if (@ftp_chdir($this->conn_id, $dir)) {
            $result['success'] = 1;
            $result['msg'] = 'success';
        }

        return $result;
    }



    public function ftp_is_dir($dir): bool
    {
        $pushd = ftp_pwd($this->conn_id);

        if ($pushd !== false && @ftp_chdir($this->conn_id, $dir)) {
            ftp_chdir($this->conn_id, $pushd);
            return true;
        }

        return false;
    }

    public function close(): bool
    {
        if ($this->conn_id) {
            ftp_close($this->conn_id);
        }
        return true;
    }
}
