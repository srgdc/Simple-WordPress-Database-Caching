<?php
/**
 * Super Simplr DB Query Caching for WordPress
 *
 * @package WordPress
 * @since 3.0
 *
 * ... To use this class just include the file in you plugin or theme and then use. Make sure
 * the cache directory is writable. For usage instructions visit http://www.mikevanwinkle.com/wordpress/super-simple-db-cache-for-wp
 *
 */
define(CACHE_DIR,dirname(__FILE__).'/cache');

class WPCacheDB extends wpdb {
	
	public $cache_key;
	public $cache_log_file = 'log.txt';
	public $cache_dir = CACHE_DIR;
	public $cache_file;
	public $cache_file_name;
	public $cache_time = 26000;
	public $result;
	public $caching;
	
	function __construct($dbuser,$dbpassword,$dbname,$dbhost) {
		parent::__construct($dbuser,$dbpassword,$dbname,$dbhost);
		$this->set_prefix('wp_');
	}
	
	
	/**
	 * Perform a MySQL database query, using current database connection.
	 *
	 * More information can be found on the codex page.
	 *
	 * @since 0.71
	 *
	 * @param string $query Database query
	 * @return int|false Number of rows affected/selected or false on error
	 */
		
	
	function cached_result($query,$output,$flag ='',$time = '') {
		if($time != '') {
			$this->cache_time = $time; 
		} 
		
		if(isset($flag) AND !is_dir($this->cache_dir.'/'.$flag)) {
			mkdir($this->cache_dir.'/'.$flag,0755);

		}
		
		$this->cache_log_file = $this->cache_dir."/log.txt";
		if(!is_dir($this->cache_dir)) { mkdir($this->cache_dir, 0755); }
		
		if(file_exists($this->cache_log_file)) {
			$this->cache_log = unserialize(file_get_contents($this->cache_log_file));
		} else {
			$this->cache_log = array();
		}
		
		if(!$this->cache_log[$query]) {
			$this->cache_log[$query] = md5($query);
		}
		
		$this->cache_file = $this->cache_log[$query];
		if(isset($flag)) {
			$this->cache_file_name = $this->cache_dir.'/'.$flag.'/'.$this->cache_file.'.txt';
		} else {
			$this->cache_file_name = $this->cache_dir.'/default/'.$this->cache_file.'.txt';
		}
		

		
		if( file_exists($this->cache_file_name) AND ( time() - filemtime($this->cache_file_name) < $this->cache_time) ) {
					$result = unserialize(file_get_contents($this->cache_file_name));
					return $result;
					exit();	
		} else {
				$result = $this->get_results($query,$output);
				if(!empty($result)) {
					file_put_contents($this->cache_file_name,serialize($result)); 
					return $result;
				}	
		}
		
		file_put_contents($this->cache_log_file,serialize($this->cache_log));
	}
	
	public static function clear($flag = '') {
		if($flag != '') {
			$flag = trim($flag,'/').'/';
		} 
		
		$dir = CACHE_DIR.'/'.$flag;
		$mydir = opendir(CACHE_DIR.'/'.$flag);
    while(false !== ($file = readdir($mydir))) {
        if($file != "." && $file != "..") {
            chmod($dir.$file, 0777);
           	unlink($dir.$file) or DIE("couldn't delete $dir$file<br />");
        }
    }
    closedir($mydir);
	}
	
}
