<?php

/**
 * Class FtpBasic : for very basic ftp operation
 * Modified By : Md. Atiqur Rahman
 * Email : atiq.cse.cu0506.su@gmail.com
 * skype: atiq.cu
 * Cell : +8801766282673
 * Last Modified : 22nd April 2015
 */



class FtpBasic {

    private $ftpServer;
    private $userName;
    private $ftpPass;
    private $dstDir = null;
    private $rootDir = null;
    private $ftpConnId;
    public $log = array();
    public $maxSize = 500;  // in kb


    /**
     * @param $userName - FTP USER NAME
     * @param $ftpPass - FTP PASSWORD
     * @param string $ftpServer - SERVER NAME (Default - localhost)
     * @param null $dstDir
     */
    public function __construct($userName, $ftpPass, $ftpServer='localhost', $dstDir=null){
        if ($userName!="" && $ftpPass!="") {
            $this->ftpServer = $ftpServer;
            $this->userName = $userName;
            $this->ftpPass = $ftpPass;
            if($dstDir!=null) $this->dstDir = $dstDir;
        }else{
            $this->log[]='Could not connect due to bad parameter';
            return false;
        }
        return $this->connect();
    }


    /**
     * Connecting to FTP server
     * @return bool
     */
    private function connect(){
        if (!isset($this->ftpConnId) || !$this->ftpConnId) {
            $this->ftpConnId = @ftp_connect($this->ftpServer);
            $login_result = @ftp_login($this->ftpConnId, $this->userName, $this->ftpPass);

            if (!$this->ftpConnId || !$login_result) {
                $this->log[] = 'FTP connection has failed!';
                $this->log[] = 'Attempted to connect to ' . $this->ftpServer . ' for user ' . $this->userName . '';
                unset($this->ftpConnId);
                return false;
            } else {
                $this->log[] = 'Connected to ' . $this->ftpServer . ', for user ' . $this->userName. '';
                $this->rootDir = ftp_pwd($this->ftpConnId);
                if($this->dstDir!=null){
                    $this->log[] = 'Destination directory given ...connecting to destination directory...';
                    return $this->setDir($this->dstDir);
                }
                //destination parameter not passed so current destination is the connection's root.
                $this->dstDir = $this->rootDir;
                return true;
            }
        }
        return true;
    }


    /**
     * Need to improve :todo
     * @param $pSourceFile - normally uploaded by form $_FILES
     * @param $pTargetFile - desired name to upload the source in current destination
     * @param bool $overWrite - flag for if file already exists
     * @param int $mode FTP_BINARY | FTP_ASCII |...
     * @return bool
     */
    function sendFile($pSourceFile, $pTargetFile, $overWrite= true, $mode=FTP_BINARY){

        if($this->connect()){
            if($fp=fopen($pSourceFile, 'r')) {

                // mode not working here !!! :TODO
                $upload=@ftp_fput($this->ftpConnId, $pTargetFile, $fp, FTP_BINARY);
                fclose($fp);
                if($upload){
                    $this->log[]="Uploaded $pSourceFile to ".ftp_pwd($this->ftpConnId)." as $pTargetFile";
                    $this->log['lastPath']=ftp_pwd($this->ftpConnId)."/".$pTargetFile;
                    $this->log['nameOnly']=$pTargetFile;
                    return true;
                }else {
                    $this->log[]='FTP upload has failed! resource - '.$this->ftpConnId;
                    $this->log[]='Destination File Path: '.ftp_pwd($this->ftpConnId).'/'.$pTargetFile;
                    $this->log[]='Source File Name: '.$pSourceFile;
                    $this->log[]="Source File existence status: $pSourceFile - ".(is_file($pSourceFile)?'Exist':'Not Found');
                    $this->log[]='Current Ftp Directory: '.ftp_pwd($this->ftpConnId);
                    $this->log['lastError']='Upload failed for unknown reason. target : Ftp Directory: '.ftp_pwd($this->ftpConnId).':'.$pTargetFile;
                    return false;
                }
            }else{
                $this->log[]='upload failed due to un-readable source!';
                return false;
            }
        }else{
            $this->log[]='upload failed due to ftp connection failure!';
            return false;
        }
    }

    /**
     * Need to improve in functionality and usage
     * @param $type - which will be checked
     * @param string $checkFor - flag for checking category, currently no use. :todo
     * @return bool
     */
    public function validateFileMime($type, $checkFor='image'){
        $conf['image'][]='image/gif';
        $conf['image'][]='image/jpg';
        $conf['image'][]='image/jpeg';
        $conf['image'][]='image/png';
        $conf['image'][]='image/png';
        $conf['image'][]='image/bmp';
        //$conf['image'][]='image/x-png'; // x- means non standard
        //$conf['image'][]='image/pjpeg';
        //$conf['image'][]='image/x-icon';
        //$conf['image'][]='image/svg+xml';   //.svg


        $conf['text'][]='text/html';
        $conf['text'][]='text/csv';
        $conf['text'][]='text/calendar';

        $conf['app'][]='application/pdf';
        $conf['app'][]='application/javascript';
        $conf['app'][]='application/json';
        $conf['app'][]='application/x-bittorrent';

        $conf['video'][]='video/3gpp';
        $conf['video'][]='video/mp4';
        $conf['video'][]='video/ogg';
        $conf['video'][]='video/webm';
        $conf['video'][]='video/x-msvideo'; //.avi
        $conf['video'][]='video/x-flv'; //.flv

        foreach($conf['image'] as $imgType) if($type == $imgType) return true;
        return false;
    }


    /**
     * This is for handling $_FILES array of single file
     * Input : sendFromFile($_FILE[formFieldName], false, false, 'test_');
     * @param $file
     * @param bool $sizeValidation
     * @param bool $overWrite
     * @param string $prefix
     * @param bool $onlyPrefix
     * @return array
     */
    public function sendFromFile($file, $sizeValidation=false, $overWrite=true, $prefix='', $onlyPrefix= false){

        $returned= array();
        if($file['error']!=0){
            $returned['error']='error occurred  when uploaded';
            $returned['result']=false;
            return $returned ;

        }
        if(!$this->validateFileMime($file['type'])){
            $returned['error']='Invalid/un-supported type of file';
            $returned['result']=false;
            return $returned ;
        }
        if($sizeValidation){
            if($file['size']>$this->maxSize){
                $returned['error']='file size is bigger than allowed';
                $returned['result']=false;
                return $returned ;
            }
        }
        $ext = end(explode('.',$file['name']));
        if($onlyPrefix===false)    $targetName = $prefix.''.$file['name'];
        else $targetName = $prefix.'.'.$ext;
        if($this->sendFile($file['tmp_name'], $targetName, $overWrite)){
            $returned['error']=null;
            $returned['result']=true;
            $returned['path']=$this->log['lastPath'];
            $returned['fileName']=$this->log['nameOnly'];

        }else{
            $returned['error']='file upload failed!! '.$this->log['lastError']. '(check last five entry of log for more information)';
            $returned['log'] = $this->log ;
            $returned['result']=false;
        }
        return $returned ;
    }

    /**
     * This function is for handling $FILES array of multiple file : $_FILES[fieldName] variable
     * Input : sendFromFileArray($_FILES[formFiledName], false, false, 'testing__')
     * @param $filesArray - should be $_FILES[formFiledName]
     * @param bool $sizeValidation
     * @param bool $overWrite
     * @param string $prefix
     * @return array
     */
    public function sendFromFileArray($filesArray, $sizeValidation=false, $overWrite=true, $prefix=''){

        $returned= array();
        foreach($filesArray['error'] as $key=>$val){
            if($val !=0){
                $returned[$key]['error']='error occurred  when uploaded';
                $returned[$key]['result']=false;
                continue;
            }
            if(!$this->validateFileMime($filesArray['type'][$key])){
                $returned[$key]['error']='Invalid/un-supported type of image';
                $returned[$key]['result']=false;
                continue;
            }
            if($sizeValidation){
                if($filesArray['size'][$key]>$this->maxSize){
                    $returned[$key]['error']='file size is bigger than allowed';
                    $returned[$key]['result']=false;
                    continue;
                }
            }
            $targetName = $prefix.''.$filesArray['name'][$key];
            if($this->sendFile($filesArray['tmp_name'][$key], $targetName, $overWrite)){
                $returned[$key]['error']=null;
                $returned[$key]['result']=true;
                $returned[$key]['path']=$this->log['lastPath'];
                continue;
            }else{
                $returned[$key]['error']='file upload failed!! '.$this->log['lastError']. '(check last five entry of log for more information)';
                $returned[$key]['log'] = $this->log ;
                $returned[$key]['result']=false;
                continue;
            }
        }
        return $returned;
    }

    /**
     * This function is for handling $_FILES variable which is array of files with array of multiple files
     * Example : $_FILES =>array('atiq'=>array('name' =>array('bla.png','blaBla.jpg','BlaBlab.jpeg',...), 'error'=>array(....),..), 'bird'=>array(like atiq),...)
     * Input : sendFromFilesArray($_FILES, false, false, 'test__');
     * @param $filesArray - should be $_FILES
     * @param bool $sizeValidation - default is 500 kb
     * @param bool $overWrite
     * @param string $prefix - common prefix  for all uploaded file
     * @return array
     */
    public function sendFromFilesArray($filesArray, $sizeValidation=false, $overWrite=true, $prefix=''){

        $returned= array();
        foreach($filesArray as $name=>$fl){
            foreach($fl['error'] as $key=>$val){
                if($val !=0){
                    $returned[$name][$key]['error']='error occurred  when uploaded';
                    $returned[$name][$key]['result']=false;
                    continue;
                }
                if(!$this->validateFileMime($fl['type'][$key])){
                    $returned[$name][$key]['error']='Invalid/un-supported type of image';
                    $returned[$name][$key]['result']=false;
                    continue;
                }
                if($sizeValidation){
                    if($fl['size'][$key]>$this->maxSize){
                        $returned[$name][$key]['error']='file size is bigger than allowed';
                        $returned[$name][$key]['result']=false;
                        continue;
                    }
                }
                $targetName = $prefix.''.$fl['name'][$key];
                if($this->sendFile($fl['tmp_name'][$key], $targetName, $overWrite)){
                    $returned[$name][$key]['error']=null;
                    $returned[$name][$key]['result']=true;
                    $returned[$name][$key]['path']=$this->log['lastPath'];
                    continue;
                }else{
                    $returned[$name][$key]['error']='file upload failed!! '.$this->log['lastError']. '(check last five entry of log for more information)';
                    $returned[$name][$key]['log'] = $this->log ;
                    $returned[$name][$key]['result']=false;
                    continue;
                }
            }
        }
        return $returned;
    }

    /**
     * Set a new destination for upload
     * @param $destinationPath - destination String
     * @return bool - false on failure , true on success.
     */
    private function setDir($destinationPath){

        if(strlen($destinationPath)<1){
            $this->log[]= 'Destination path not found!';
            return false;
        }

        if(@ftp_chdir($this->ftpConnId, $destinationPath)){
            $this->log[]= 'Destination path Changed to '.$destinationPath;
            $this->dstDir = $destinationPath ;
            return true;
        }
        $this->log[]= 'Destination path Change failed for '.$destinationPath;
        return false;
    }

    /**
     * Resetting the destination to its default permitted dir
     * @return bool
     */
    public function resetDestinationDirToRoot(){
        return $this->setDir($this->rootDir);
    }

    /**
     * Get current destination Directory
     * mainly debugging purpose
     * @return null -
     */
    public function getCurrentDestinationPath(){
        return $this->dstDir;
    }

    /**
     * @param $destinationPath
     * @return bool
     */
    public function changeDestinationDir($destinationPath){

        if(strlen($destinationPath)<1){
            $this->log[]= 'Destination path not found!';
            return false;
        }

        if($this->connect()){
            if(@ftp_chdir($this->ftpConnId, $destinationPath)){
                $this->log[]= 'Destination path Changed to '.$destinationPath;
                return true;
            }
            $this->log[]= 'Destination path Change failed for '.$destinationPath;
            return false;
        }
        $this->log[]= 'Destination path Change failed for '.$destinationPath.' due to ftp connection failure!';
        return false;
    }

    /**
     * @param $pDir
     * @return bool
     */
    public function isDirExists($pDir) {
        if($this->connect()){
            $curDir=ftp_pwd($this->ftpConnId);
            if(@ftp_chdir($this->ftpConnId, $pDir)){
                ftp_chdir($this->ftpConnId, $curDir);
                $this->log[]= 'Directory exists - '.$pDir;
                return true;
            }
            $this->log[]= 'Directory do not exists - '.$pDir;
            return false;
        }
        $this->log[]= 'Directory existence check failed due to ftp connection failure! ';
        return false;
    }


    /**
     * Disconnecting FTP connection
     * @return bool
     */
    function disConnect(){
        if(isset($this->ftpConnId) && $this->ftpConnId){
            ftp_quit($this->ftpConnId);
            unset($this->ftpConnId);
        }
        return true;
    }


    /**
     * This function is mainly for debugging purpose
     * Sometimes it get confusing to give the value of setDst method
     * this will help to find desired destination value
     * @param bool $recursive - default parameter
     * @return bool | array - false on connection failure | array of dirs
     */
    public function checkDestinationDirs($recursive=false){
        if($this->connect()){
            $contents['nList'] = ftp_nlist($this->ftpConnId, ".");
            $contents['rList'] = ftp_rawlist($this->ftpConnId, '/', $recursive);
            return $contents;
        }
        return false;
    }

    /**
     * Dumping variable for debugging
     * @param $var
     * @param bool $die
     * @param null $die_msg
     * @param bool $varDump
     */
    public static function dump($var, $die=true, $die_msg=NULL, $varDump=false){

        if('comment'===$die){   echo '<!-- ';}
        echo '<pre>';

        if($varDump===true)  var_dump($var);
        elseif(is_array($var) && count($var)>0) print_r($var);
        elseif(is_object($var)) print_r($var);
        else var_dump($var);

        echo '</pre>';
        if($die==='comment') echo '-->';
        if($die===true)	die('Died from dump helper...'.$die_msg);
    }

 }
