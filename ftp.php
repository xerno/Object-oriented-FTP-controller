<?
/** Object-oriented FTP controller
 * Writen by Zdenek Kops in 2015; zdenekkops@gmail.com
 * Thanks for inspiration to tendrid@gmail.com at http://php.net/manual/en/book.ftp.php
 * Requirements: PHP 5.3+
 *
 * How to use: call FTP functions without prefix "ftp_" and without first argument
 *
 * Special methods:
 *    connect() and ssl_connect() are disabled, use constructor instead
 *    close() use without any arguments, will close FTP connection and destruct an object
 *    scandir() is an alias of nlist()
 *    is_dir() emulates is_dir() function
 *
 *
 * EXAMPLE:


$ftp = new ftp('123.234.214.124');
$ftp->login('user', 'password');
$dir = $ftp->scandir('.'); // filenames are fully addressed, e.g. "directory/file.suffix"

foreach($dir as $file){

   $local = $destination.'/'.basename($file);

   if($ftp->is_dir($file)){
      mkdir($local);
   }
   else{
      $ftp->get($local, $file,  FTP_BINARY);
   }

}

$ftp->close();

*/



class ftp{

   private $connection;

   private $connection_type;

   private $disabled_functions = ['connect', 'ssl_connect', 'close'];


   public function __construct($url, $port=21, $timeout=90, $require_ssl=false){

      $url = preg_replace('#^(ftp://|)(.*)$#msi', '$2', $url);

      $ftp = ftp_ssl_connect($url, $port, $timeout);

      if($ftp) $this->connection_type = 'SSL-FTP';
      elseif($require_ssl) throw new FTP_Exception('FTP: Cannot establish encrypted connection to "'.$url.'".');
      else{
         $ftp = ftp_connect($url, $port, $timeout);
         if($ftp) $this->connection_type = 'FTP';
         else throw new FTP_Exception('FTP: Cannot establish connection to "'.$url.'".');
      }

      $this->connection = $ftp;
   }


   public function close(){
      @ftp_close($this->connection);
      $this->__destruct();
   }


   public function scandir($path){ // only alias of nlist
      return ftp_nlist($this->connection, $path);
   }


   public function is_dir($path, $slash='/'){ // emulating is_dir() function, $path can be relative
      $path = $slash.trim($path, $slash);
      $last_slash_pos = mb_strrpos($path, $slash);
      $name = mb_substr($path, $last_slash_pos);
      $parent = mb_substr($path, 0, $last_slash_pos).'.';

      $list = ftp_nlist($this->connection, $parent);
      $detailed = ftp_rawlist($this->connection, $parent);
      if($list===false or $detailed===false) return 0; // error
      elseif($detailed[array_search($name, $list)]{0}==='d') return true; // it is a dir
      else return false; // it is not a dir
   }


   public function __call($function, $arguments){
      if(function_exists('ftp_'.$function) and !in_array(strtolower($function), $this->disabled_functions)){
         array_unshift($arguments, $this->connection);
         return call_user_func_array('ftp_'.$function, $arguments);
      }
      else throw new FTP_Exception('FTP: Function "'.$function.'" does not exist.');
   }


   public function __get($name){
      return $this->$name;
   }


   //function __destruct(){}

}

class FTP_Exception extends Exception{}