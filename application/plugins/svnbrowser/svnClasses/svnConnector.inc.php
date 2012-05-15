<?php
/**
 * Build svnConnector class
 * @package nFrame
 */

if(!defined('SVN_HEAD_REVISION')){
	define('SVN_HEAD_REVISION', -1);
}

/**
 * 
 * Ease and Abrstraction layer for discovering information on and in a SVN repository
 * 
 * Designed with a plugin for ProjectPier in mind
 * 
 * @author drno
 */
class svnConnector {
	
	const ACTION_MODIFIED			= 'M';
	const ACTION_ADDED				= 'A';
	const ACTION_DELETED			= 'D';
	const ACTION_REPLACED			= 'R';
	
	const TRANSPORT_METHOD_LOCAL	= 1;
	const TRANSPORT_METHOD_SVN		= 2;
	const TRANSPORT_METHOD_SSH		= 4;
	
	const FILETYPE_DIR				= 'dir';
	const FILETYPE_FILE				= 'file';
	
	// Remember, SVN_REVISION_HEAD is available as well
	//@todo can it be added as a local constant?
		
	/**
	 * the desired transport method, that is `file`, `svn`, `ssh`, etc
	 * @var string
	 */
	private $transportMethod	= 'file';
	
	/**
	 * if a remote system is used, this is where the nessesary authorization is stored for use
	 * @var array
	 */
	private $authorization		= Array(
											'username' => NULL,
											'password' => NULL
										);
	/**
	 * the location to the repository itself, on the filesystem; ideally should not contain any portions of the path inside the repo
	 * @var array
	 */
	private $baseLocation		= NULL;
	/**
	 * the current location inside of the repo, used for moving up/down the tree
	 * @var array
	 */
	private $workingDirectory	= Array();
	
	/**
	 * Abstractor for the as-of-yet experimental and constant-threat-of-changing function names for native PHP SVN functionality 
	 * @var array
	 */
	private $aSvnMethodTranslation = Array(
		'ls'		=> 'svn_ls',
		'cat'		=> 'svn_cat',
		'log'		=> 'svn_log',
		'diff'		=> 'svn_diff',
		'blame'		=> 'svn_blame',
	);
	
	/**
	 * Construction of the Object
	 * @param string|array $options if string, assumed to be the base location of the repository, on the local machine;
	 * 								 if array, can contain `transportMethod`, `authorization`, `baseLocation`, `workingDirectory`;
	 * 								 other values ignored
	 */
	function __construct($options){
		if(!is_array($options)){
			$options = Array('baseLocation' => $options);
		}
		
		$this->_setProperties($options);
		
		//TODO could then also call a translator for $aSvnMethodTranslation for changes between PHP versions
	}
	
	/**
	 * parse through the various properties needed for connecting to the repository
	 * @param array $options the same indices as __construct
	 * @see __construct
	 */
	private function _setProperties($options){
		foreach($options as $key => $val){
			switch ($key) {
				case 'transportMethod':
					$this->transportMethod = $val;
					break;
				case 'authorization':
					if(isset($val['username'])){
						$this->authorization['username'] = $val['username'];
						svn_auth_set_parameter(SVN_AUTH_PARAM_DEFAULT_USERNAME, $this->authorization['username']);
					}
					if(isset($val['password'])){
						$this->authorization['password'] = $val['password'];
						svn_auth_set_parameter(SVN_AUTH_PARAM_DEFAULT_PASSWORD, $this->authorization['password']);
					}
					break;
				case 'baseLocation':
					$this->setBaseLocation($val);
					break;
				case 'workingDirectory':
					$this->setWorkingDirectory($val);
					break;
					
				default:
					break;
			}
		}
	}
	
	/**
	 * Parse and store the location of the repository
	 * 
	 * @todo consider changing to setBaseDirectory???
	 * 
	 * @param array|string $dirToRepo array of values being directories, or a string in Unix fashion
	 * 
	 * @return array the location stored
	 */
	public function setBaseLocation($dirToRepo){
		if(is_null($dirToRepo)){
			return $this->baseLocation;
		}
		
		if(is_array($dirToRepo)){
			$this->baseLocation = $dirToRepo;
		}
		
		if(is_string($dirToRepo)){
			$this->baseLocation = explode('/', trim($dirToRepo,'/') );
		}
		
		return $this->baseLocation;
	}
	
	/**
	 * Build? and return the directory to the Repository
	 * 
	 * the DIRECTORY is the location on the filesystem
	 * 
	 * @param boolean $asArray array (TRUE) form, or string (FALSE, default)
	 * 
	 * @return string|array path to the repository
	 */
	public function getBaseDirectory($asArray = FALSE){
		if($asArray){
			return $this->baseLocation;
		}else{
			return (($this->transportMethod == 'file') ? '/' : '' ) . join('/', $this->baseLocation);
		}
	}
	
	/**
	 * Parse and store the working directory inside of the repository
	 * @param array|string $dirInsideRepo array of values being directories, or a string in Unix fashion
	 */
	public function setWorkingDirectory($dirInsideRepo){
		if(is_null($dirInsideRepo)){
			return $this->workingDirectory;
		}
		
		if(is_array($dirInsideRepo)){
			$this->workingDirectory = $dirInsideRepo;
		}
		
		if(is_string($dirInsideRepo)){
			$this->workingDirectory = explode('/', trim($dirInsideRepo,'/') );
		}
		
		foreach($this->workingDirectory as $key => $val){
			if(strlen($val) < 1){
				unset($this->workingDirectory[$key]);
			}
		}
		
		return $this->workingDirectory;
	}
	
	/**
	 * Build? and return the working directory inside of the repository
	 * @param boolean $asArray array (TRUE) form, or string (FALSE, default)
	 * 
	 * @return string|array path inside the repository
	 */
	public function getWorkingDirectory($asArray = FALSE){
		if($asArray){
			return $this->workingDirectory;
		}else{
			return join('/', $this->workingDirectory);// .'/';
		}
	}
	
	/**
	 * This is the base PATH, as opposed to the base DIRECTORY
	 * 
	 * the PATH is with the transport method
	 * 
	 * @return the base PATH
	 */
	public function getBasePath(){
		//@TODO use getTransportMethod or getTransportPath instead of accessing directly
		return $this->transportMethod . '://' . $this->getBaseDirectory();
	}
	
	/**
	 * Build the full path (transport method inclusive) to the SVN repository
	 * 
	 * @return string full path
	 */
	public function getFullPath($filename = NULL){
		return $this->getBasePath() . '/' . $this->getWorkingDirectory() . ($filename ? $filename : '');
	}
	
	/**
	 * parse a valid version number, allows ints >= -1 (except 0)
	 * 
	 * (-1 is a method to retrieve the top revision)
	 * 
	 * @param integer $version a version/revision number
	 * 
	 * @return integer the parsed number, default is SVN_REVISION_HEAD
	 */
	public function parseVersion($version = NULL){
		if(isset($version)){
			$version = intval($version);
			if(($version >= -1) && ($version != 0)){
				return $version;
			}
		}
		
		return SVN_REVISION_HEAD;
	}
	
	/**
	 * list the files in the current working directory
	 * 
	 * optionally, the desired version may be specified
	 * 
	 * @param NULL|integer $version same as parseVersion
	 */
	public function listDirectoryContents($version = NULL){
		
		$args = Array();
		$args[0] = $this->getFullPath();
		$args[1] = $this->parseVersion($version);
		
		return $this->_callSvnFunction('ls', $args);
	}
	
	public function getLog($verStart = NULL, $verEnd = NULL){
		
		$args = Array();
		$args[0] = $this->getBasePath(); // just the path to repo, nothing more
		$args[1] = $this->parseVersion($verStart);
		$args[2] = $this->parseVersion($verEnd);
		
		if($args[2] == NULL){
			unset($args[2]);
		}  
		
		//array svn_log ( string $repos_url [, int $start_revision [, int $end_revision [, int $limit = 0 [, int $flags = SVN_DISCOVER_CHANGED_PATHS | SVN_STOP_ON_COPY ]]]] )
		return $this->_callSvnFunction('log', $args);
	}
	
	/**
	 * Extract the first action posible and return its type, compare with this class's ACTION_* constants
	 * 
	 * @param array|string $actionInQuestion any section of the log result, though only the first action encounterd will be analyzed
	 * 
	 * @return string|boolean one of the ACTION_* constants, or FALSE if unable to be found (or is unrecognized)
	 */
	public function determineLogAction($actionInQuestion){
		// go down through various possible sections of the log array that we might be handed
		
		//Array ( [0] => Array ( ... , [paths] => Array ( [0] => Array ( [action] => M, ... ) ) ) )

		if(isset($actionInQuestion[0])){
			$actionInQuestion = $actionInQuestion[0];
		}
		
		if(isset($actionInQuestion['paths'])){
			$actionInQuestion = $actionInQuestion['paths'];
		}
		
		if(isset($actionInQuestion[0])){
			$actionInQuestion = $actionInQuestion[0];
		}
		
		if(isset($actionInQuestion['action'])){
			$actionInQuestion = $actionInQuestion['action'];
		}
		
		switch($actionInQuestion){
			case self::ACTION_ADDED:
			case self::ACTION_DELETED:
			case self::ACTION_MODIFIED:
			case self::ACTION_REPLACED:
				return $actionInQuestion;
				break;
			default:
					return FALSE;
		}
	}
	
	/**
	 * Make it easy to abstractly call a SVN function
	 * 
	 * @todo error-handling
	 * 
	 * @param string $function the abstracted name of the function desired
	 * @param array $args the arguments desired by the function
	 * 
	 * @return mixed directly whatever that function will return
	 */
	private function _callSvnFunction($function, $args){
		try {
			return call_user_func_array($this->aSvnMethodTranslation[$function], $args);
		} catch (Exception $e) {
			echo(writelnvar($e, 'Exception'));
		}
		
	}
	
	public function dir_strToArr($dir){
		if(is_string($dir)){
			$childDirectoryNew = explode('/', $dir);
			if(isset($dir[0]) && $dir[0] == '/'){
				$dir = array_merge(array('/'), $childDirectoryNew);
			}else{
				$dir = $childDirectoryNew;
			}
			
			unset($childDirectoryNew);
		}
		
		if(is_array($dir)){
			return $dir;
		}
		
		return Array();
	}
	
	/**
	 * shorthand for listDirectoryContents, Linux terminal command
	 * 
	 * @todo reverse which functions do the work?
	 * 
	 * @param integer $version same as listDirectoryContents
	 */
	public function ls($version = NULL){
		return $this->listDirectoryContents($version);
	}
	
	/**
	 * change directory
	 * @param string|array $childDirectory path to change to, linux terminal string, or array of dirs to try
	 */
	public function cd($childDirectory){
		$childDirectory = $this->dir_strToArr($childDirectory);
		
		if($childDirectory[0] == '/'){
			//start working directory from the base
			$this->setWorkingDirectory(Array());
			array_shift($childDirectory);
		}
		
		$this->setWorkingDirectory(array_merge($this->getWorkingDirectory(TRUE), $childDirectory));

		return $this; // for chaining :)
	}
	
	public function cd_smart($childDirectories, $version = NULL){
		$version = $this->parseVersion($version);
		$desDirs = $this->dir_strToArr($childDirectories);
		
		foreach($desDirs as $desDir){
			$this->cd($desDir);
			if(!$this->ls($version)){
				throw new SVN_DirectoryNotFound_Exception('Directory '.$desDir. ' does not exist in '.($version == SVN_HEAD_REVISION ? 'head revision' : 'revision '.$version));
			}
			//SVN_DirectoryNotFound_Exception
		}
		
		return $this->getWorkingDirectory();
	}
	
	public function diff_revisions($file, $rev1, $rev2){
		$rev1 = intval($rev1);
		$rev2 = intval($rev2);
		$args = array(
						$this->getFullPath($file),
						min($rev1, $rev2),
						$this->getFullPath($file),
						max($rev1, $rev2)
					);
		
		//writelnvar($args, '$args');

		list($diff, $errors) = $this->_callSvnFunction('diff', $args);
		
		$error_contents = NULL;
		if($errors){ $error_contents = $this->_readStream($errors); }
		
		$diff_contents = NULL;
		if($diff){ $diff_contents = $this->_readStream($diff); }
		
		return array( $diff_contents, $error_contents );
	}
	
	private function _readStream(&$stream){
		$contents = '';
		while (!feof($stream)) {
			$contents .= fread($stream, 8192);
		}
		fclose($stream);
		
		return $contents;
	}
	
	public function diff_past($file, $pastRev){
		return $this->diff_revisions($file, $pastRev, SVN_HEAD_REVISION);
	}
	
	public function cat($file = NULL, $rev = NULL){ // NULL but treated as SVN_HEAD_REVISION
		$args = array($this->getFullPath($file), $rev);
		return $this->_callSvnFunction('cat', $args);
	}
	
	public function getEndpoint(){
		return array_pop($this->getWorkingDirectory(TRUE));
	}
	
	public function getType($version = SVN_HEAD_REVISION){
		
		if(count($this->workingDirectory) < 1){
			return 'dir';
		}
		
		$save = $this->getWorkingDirectory(TRUE);
		$this->setWorkingDirectory($this->getParentDir());
		$all = $this->ls($version);
		$this->setWorkingDirectory($save);
		
		$lookingAt = array_pop($save);
		if(isset($all[$lookingAt])){
			return $all[$lookingAt]['type'];
		}
		
		return FALSE;
	}
	
	public function getMimeType($version = SVN_HEAD_REVISION){
		$fileType = $this->getType($version);
		
		if($fileType == self::FILETYPE_FILE){
			$finfo = new finfo(FILEINFO_MIME);
			return $finfo->buffer($this->cat());
		}
		return $fileType;
	}
	
	//@todo @fixme only TRUE so as to not break things already using it, change to conform with getWorkingDirectory
	public function getParentDir($asArray = TRUE){
		$working = $this->getWorkingDirectory(TRUE);
		if(count($working) > 0){
			array_pop($working);
		}
		
		if($asArray){
			return $working;
		}else{
			return join('/', $working) .'/';
		}
	}
	
	public function blame($version = NULL){
		$version = $this->parseVersion($version);
		$args = Array( $this->getFullPath(), $version);
		return $this->_callSvnFunction('blame', $args);
	}
	
	public function getCurrentRevision(){
		$log = $this->getLog();
		return $log[0]['rev'];
	}
}


class SVN_DirectoryNotFound_Exception extends UnexpectedValueException{}
?>