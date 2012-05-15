<?php 
	
require_once('debuggingFunctions.php');
require_once('svnConnector.inc.php');
require_once('DiffViewer.inc.php');
require_once('BlameViewer.inc.php');



class svnBrowser {
	
	private $svnConnection	= NULL;
	protected $repoArgs	= NULL;
	private $repoName		= NULL;
	
	private $desDir		= NULL;
	private $version		= NULL;
	
	private	 $precision		= 2;
	private $dataSizes		= Array();
	
	public $imagesBaseDir  = 'uninit/';
	
	public function __construct($repoArgs, $repoName = NULL){
		
		$this->repoArgs = $repoArgs;
		
		if(!$repoName){
			if(isset($this->repoArgs['baseLocation'])){
				$this->repoName = $this->repoArgs['baseLocation'];
			}else{
				$this->repoName = $this->repoArgs;
			}
		}
		
		$this->svnConnection = new svnConnector($this->repoArgs);
		
		$this->dataSizes['KB'] = 1024;
		$this->dataSizes['MB'] = $this->dataSizes['KB'] * 1024;
		$this->dataSizes['GB'] = $this->dataSizes['MB'] * 1024;
		$this->dataSizes['TB'] = $this->dataSizes['GB'] * 1024;
		$this->dataSizes['PB'] = $this->dataSizes['TB'] * 1024; // silly? Yes.
		
		//writelnvar(get_class_methods('svnConnector'), 'get_class_methods: \'svnConnector\'');
		//writelnvar(get_class_methods($this->svnConnection), 'get_class_methods: $this->svnConnection()');
		
		$this->desDir = isset($_GET['dir']) ? $this->sanitizeDirectory($_GET['dir']) : Array();
		
		$this->version = isset($_GET['ver']) ? intval($_GET['ver']) : NULL;
		if(!$this->version || ($this->version < 0)){
			$this->version = SVN_HEAD_REVISION;
		}
		
		switch(isset($_GET['action']) ? $_GET['action'] : NULL){
			case 'blame':
			case 'diff':
				$this->action = $_GET['action'];
				break;
			case NULL:
			default:
				$this->action = NULL;
				break;
		}
	}
	
	public function go(){
		//!!!!!!!!!!!!!!!!!!!!
		//here we go! :)
		//!!!!!!!!!!!!!!!!!!!!
		
		// attempt to enter the directory
		try {
			$this->svnConnection->cd_smart($this->desDir);
		} catch (SVN_DirectoryNotFound_Exception $e) {
			//echo('desired directory was not found, yaddah yaddah');
			//writelnvar($e, 'Exception: ');
			//@TODO give user feedback!!
		}
		
		
		
		switch($this->action){
		
			case 'diff':
				$currentRev = $this->svnConnection->getCurrentRevision();
				$versionToUse = ($this->version == SVN_HEAD_REVISION) ? $currentRev - 1 : $this->version;
				//$diff = $this->svnConnection->diff_revisions(NULL, $this->versionToUse, $currentRev);
				//var_dump($diff);
				$differ = new DiffViewer(
									$this->svnConnection->diff_revisions(NULL, $versionToUse, $currentRev)
									//$diff
								);
				$this->getHeader();
				$differ->buildDisplay();
				$this->getFooter();
				break; 
				
			case 'blame':
				//$currentRev = $this->svnConnection->getCurrentRevision();
				//$versionToUse = ($this->version == SVN_HEAD_REVISION) ? $currentRev - 1 : $this->version;
				//$diff = $this->svnConnection->diff_revisions(NULL, $versionToUse, $currentRev);
				//var_dump($diff);
				
				$versionLinkFormat = '?dir='.implode('/', $this->desDir).'&action=blame&ver=%d';
				$blamer = new BlameViewer($this->svnConnection->blame($this->version), $versionLinkFormat);
				$this->getHeader();
				$blamer->buildDisplay();
				$this->getFooter();
				break; 
			
			default:		
				// if no action, then browse
				switch($this->svnConnection->getType($this->version)){
					case svnConnector::FILETYPE_DIR:
						//writeln('is a dir');
						$this->getHeader();
						$this->displayContents($this->svnConnection->ls($this->version));
						$this->getFooter();
						break;
					case svnConnector::FILETYPE_FILE:
						//writeln('is a file');
						//$this->svnConnection->getMimeType($version));
						// acc. to http://webdesign.about.com/od/php/ht/force_download.htm
						header('Content-disposition: attachment; filename=' . $this->svnConnection->getEndpoint());
						header('Content-type: ' . $this->svnConnection->getMimeType($this->version));
						echo($this->svnConnection->cat(NULL, $this->version));
						break;
				}
				
				break;
		}
	}
	
	public function sanitizeDirectory($desiredDirectory){
		if(is_string($desiredDirectory)){
			$desiredDirectory = urldecode($desiredDirectory);
			// currently, the svn_ls function conditions the url, so "\ " will fail, while " " will work
			//$desiredDirectory = preg_replace('/\ /', '\ ', $desiredDirectory);
			// but, in the event this is no longer the case, here's how to condition it ourselves :P 
			$desiredDirectory = explode('/', urldecode($desiredDirectory));
		}
		
		if(!is_array($desiredDirectory)){
			//die('error: what are you thinkin\', man?!? I need a string or array, and yet you be givin\' me a '.gettype($desiredDirectory).'???');
			// todo handle this more diplomatically (ex: don't die, give default, warn, something
			//@todo warn
			return Array(); 
		}
		
		$safeDirectory = Array();
		foreach ($desiredDirectory as $dirName){
			preg_match_all('/([a-zA-Z\d\.\ -]+)/', $dirName, $matches);
			$safeDirectory[] = @implode('', $matches[1]); // could do implode($matches) but PHP.net says this is bad form (reason on the doc page)
			unset($matches);
		}
		
		foreach($safeDirectory as $key => $val){
			if(strlen($val) < 1){
				unset($safeDirectory[$key]);
			}
		}
		
		return $safeDirectory;
	}
	
	public function displayContents($svnLsResult){
		$parentPath = $this->desDir;
		array_pop($parentPath);
		$desDir_imploded = implode('/', $this->desDir);
		
		$fieldsToDisplay = Array(
									'type'			=> 'Type',
									'name'			=> 'Filename',
									'size'			=> 'Size',
									'last_author'	=> 'Last Author',
									'time'			=> 'Last Edit',
								//	'time_t'		=> '(Timestamp)',
									'created_rev'	=> 'Created',
									'diff'			=> 'Changes',
									'blame'			=> 'Blame Game',
								 );
		foreach($svnLsResult as $key => $val){
			if($val['type']!='dir'){
				$svnLsResult[$key]['blame']	= '<a href="'.$this->getLinkHref(Array('dir' => $desDir_imploded.'/'.$val['name'], 'action'=>'blame')).'">blame</a>';
			}
			if($val['type']!='dir'){
				$svnLsResult[$key]['diff']	= '<a href="'.$this->getLinkHref(Array('dir' => $desDir_imploded.'/'.$val['name'], 'action'=>'diff')).'">diff</a>';
			}
		}
	
		?>
		<h3>Browsing <?php echo($this->repoName)?> (<?php echo(($this->version != SVN_HEAD_REVISION) ? $this->version : 'head revision');?>)</h3>
		<table>
			<thead>
				<tr>
					<?php foreach($fieldsToDisplay as $friendlyFieldName) : ?>
					<th><?php echo($friendlyFieldName); ?></th>
					<?php endforeach; ?>
				</tr>
			</thead>
			<tbody>
				<?php if(count($this->desDir) > 0) : ?>
				<tr>
					<?php foreach(array_keys($fieldsToDisplay) as $field) : ?>
						<?php if(($field == 'name')) : ?>
					<td><a href="<?php echo($this->getLinkHref(Array('dir'=>$this->svnConnection->getParentDir(FALSE)))); ?>">..</a></td>
						<?php else :?>
					<td>&nbsp;</td>
						<?php endif;?>
					<?php endforeach; ?>
				</tr>
				<?php endif; ?>
				<?php foreach($svnLsResult as $entry) : ?>
				<tr>
					<?php foreach(array_keys($fieldsToDisplay) as $field) : ?>
					<td><?php $this->displayContents_field($entry, $field); ?></td>
					<?php endforeach; ?>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}
	
	public function displayContents_field($entry, $field, $extras=NULL){
		//@todo something with $extras, like format the timestamp
		switch($field){
			case 'name' :
				?><a href="<?php echo( $this->getLinkHref(Array('dir'=>implode('/', $this->desDir).'/'.$entry['name'])) ); ?>"><?php echo($entry['name']); ?></a><?php
				break;
			case 'type':
				$image = $this->imagesBaseDir . 'images/';
				switch($entry['type']){
					case 'file':
						$image .= 'file.png';
						break;
					case 'dir':
						$image .= 'folder.png';
						break;
					default:
						$image .= 'unknown.png';
						break;
				}
				
				?><img src="<?php echo($image); ?>" title="<?php echo($entry['type']);?>"><?php
				break;
			case 'size' :
				if($entry['type'] == svnConnector::FILETYPE_DIR){
					echo '&nbsp;';
					return;
				}
				$size = $entry['size'];
				
				if($size <  $this->dataSizes['KB']){
					echo $size . 'B';
				}elseif($size < $this->dataSizes['MB']){
					echo round($size / $this->dataSizes['KB'], $this->precision) . 'KB'; 
				}elseif($size < $increment['GB']){
					echo round($size / $this->dataSizes['MB'], $this->precision) . 'MB'; 
				}elseif($size < $increment['TB']){
					echo round($size / $this->dataSizes['GB'], $this->precision) . 'GB'; 
				}elseif($size < $increment['PB']){
					echo round($size / $this->dataSizes['TB'], $this->precision) . 'TB';
				}else{
					echo round($size / $this->dataSizes['PB'], $this->precision) . 'PB';
				}
				break;
			case 'created_rev' :
				?><a href="<?php echo( $this->getLinkHref(Array('ver'=>$entry['created_rev'])) ); ?>"><?php echo($entry['created_rev']); ?></a><?php
				break;
			case 'last_author' :
			case 'time' :
			case 'time_t' :
			default:
				// in the event we don't recognize the field, attempt to display anyway
				//@watch-me this should be revised when the SVN_* functions are finalized
				echo((isset($entry[$field])) ? $entry[$field] : '&nbsp;');
				break;
		}
	}
	
	//*/
	public function getLinkHref($options){
		
		//edited to be PP specific
		
		// first determine all parameters for this page
		$pageParams = Array();
		// exploded should be Array('a=123', 'b=456', ... );
		foreach(explode('&', $_SERVER['QUERY_STRING']) as $val){
			$tmp = explode('=', $val);
			$pageParams[$tmp[0]] = $tmp[1];
		}
		unset($tmp);
		
		// set defaults
		$pageParams['dir']    = implode('/', $this->desDir);
		$pageParams['ver']    = ($this->version > 0) ? $this->version : NULL;
		$pageParams['action'] = NULL;
		
		// override defaults with any arguments
		foreach($options as $var => $val){
			if(isset($options[$var])){
				$pageParams[$var] = $val;
			}
		}
	
		$toReturn = '?';
		
		//build formatted ? link (could use implode, but for possible NULL and unencoded values)
		foreach($pageParams as $var => $val){
			if($val){
				$toReturn .= $var . '=' . urlencode($val) . '&';
			}
		}
		return substr($toReturn, 0, -1);
	}//*/
	
	public function getHeader(){
	?><!DOCTYPE head PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
	<html>
		<head>
			<title>SVN Browsing</title>
		</head>
		<body>
		<?php 
	}
	
	public function getFooter(){		 
		?>
			</body>
		</html><?php
	}
	
	public function enableErroring(){
		error_reporting(E_ALL);
		ini_set('display_errors', TRUE);
		ini_set('display_startup_errors', TRUE);
	}
	
	/**
	 * 
	 * Enter description here ...
	 * @param string $dir should be some form derived from __DIR__
	 * @param boolean $parseSmartly images folder is in the calling script's directory, make life easy
	 */
	public function imagesBaseDir($dir, $parseSmartly = FALSE){
		if($parseSmartly === TRUE){
			return $this->imagesBaseDir = str_replace(realpath($_SERVER['DOCUMENT_ROOT']), '', $dir ) . '/';
		}else{
			return $this->imagesBaseDir = $dir . '/';
		}
	}
	
}