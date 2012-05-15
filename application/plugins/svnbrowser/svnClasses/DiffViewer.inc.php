<?php 



class DiffViewer{
	
	private $rawDiffOutput	= NULL;
	private $rawDiffError	= NULL;
	
	private $diffArray		= NULL;
	private $internalFile	= NULL;
	
	private $fileNames		= array('orig'=>NULL, 'new'=> NULL);
	private $fileVersions	= array('orig'=>NULL, 'new'=> NULL);
	
	const EOL				= "\n";
	
	public function __construct($diffResult){
		$this->setVariables($diffResult);
	}
	
	public function setVariables($diffResult){ //keep name, as other formats may be compressed and need the original file to 'fill in the blanks'
		if(is_array($diffResult)){
			$this->rawDiffOutput	= $diffResult[0];
			$this->rawDiffError		= $diffResult[1];
		}else{
			$this->rawDiffOutput = $diffResult;
		}
		
		//parse through the differences, and store by line
		$this->_parseDiff_unified();
		//@todo add in support for context format
	}
	
	private function _parseDiff_unified(){
		$workingOutput = preg_replace('/[\w\W]+---/', '---', $this->rawDiffOutput);
		
		if(strlen($workingOutput) < 1){
			$this->internalFile = NULL;
			return false;
		}
		
		//writelnvar($workingOutput, '$workingOutput');
		$byLineHelper = explode(self::EOL, $workingOutput);
		$numberOfLines = count($byLineHelper);
		$cl = 0; // Current Line
		
		//determine the names of the files
		// --- original
		// +++ new
		preg_match('/---\W([\w\d\.]+)\W+revision\W(\d+)\)/', $byLineHelper[$cl++], $matches);
		//writelnvar($matches, '$matches');
		list( , $this->fileNames['orig'], $this->fileVersions['orig']) = $matches;
		$matches = NULL;
		
		preg_match('/\+\+\+\W([\w\d\.]+)\W+revision\W(\d+)\)/', $byLineHelper[$cl], $matches);
		list( , $this->fileNames['new'], $this->fileVersions['new'] ) = $matches;
		unset($matches);
		
		$currentHunk = -1;
		$lineNumber_orig	= 0;
		$lineNumber_new		= 0;
		$this->internalFile = Array();
		while (++$cl < $numberOfLines){
			$line = substr($byLineHelper[$cl], 1);
			$lineController = ' ';
			if(isset($byLineHelper[$cl][0])){
				$lineController = $byLineHelper[$cl][0];
			}
			
			$this->internalFile[$cl] = Array(
													'content'	=> $line,
													'line'		=> Array(
																			'orig'	=> $lineNumber_orig + 1,
																			'new'	=> $lineNumber_new + 1,
																		 ),
													'type'		=> NULL,
												 );
			
			switch($lineController){
				case ' ':
					//common line
					$this->diffArray[$currentHunk]['common'][] = Array(
																			'content'	=> $line,
																			'line'		=> Array(
																									'orig'	=> ++$lineNumber_orig,
																									'new'	=> ++$lineNumber_new,
																								)
																		);
					$this->internalFile[$cl]['type'] = 'common';
					break;
				case '+':
					//added line
					$this->diffArray[$currentHunk]['added'][] = Array(
																			'content'	=> $line,
																			'line'		=> ++$lineNumber_new,
																		);
					$this->internalFile[$cl]['type'] = 'added';
					$this->internalFile[$cl]['line']['orig'] = false;
					break;
				case '-':
					//removed line
					$this->diffArray[$currentHunk]['removed'][] = Array(
																			'content'	=> $line,
																			'line'		=> ++$lineNumber_orig,
																		);
					$this->internalFile[$cl]['type'] = 'removed';
					$this->internalFile[$cl]['line']['new'] = false;
					break;
				case '@':
					//hunk
					//writelnvar($line, '$line');
					preg_match('/@\W*-(\d+),(\d+)\W+\+(\d+),(\d+)\W+@@([\w\W]*)/', $line, $matches);
					//writelnvar($matches, '$matches');
					
					//ensure some value, mostly for the comment ($5)
					for($n = 0; $n <5; ++$n){
						$matches[$n] = $matches[$n] ? $matches[$n] : false;
					}
					
					$this->diffArray[++$currentHunk] = Array(
												'hunk'=>Array(
																'orig'	=> Array(
																				'start'	=>$matches[1],
																				'end'	=>$matches[2],
																			),
																'new'	=> Array(
																				'start'	=>$matches[3],
																				'end'	=>$matches[4],
																			),
																'comment' => $matches[5],
															),
												'added'		=> Array(),
												'removed'	=> Array(),
												'common'	=> Array(),
											);
					unset($this->internalFile[$cl]);
					break;
				case '\\':
					//something... not sure, comments?
					//writeln('char at '.$cl.' is '.$lineController.' with $line as '.$line);
					unset($this->internalFile[$cl]);
					break;
				default:
					//error, shouldn't be anything else...
					unset($this->internalFile[$cl]);
					break;
			}
		}

	}
	
	public function buildDisplay($args = NULL){
		//build a table based on the file
		//$args should be an array allowing users to specify certain classes or ID's for each element,
		// also other various options, so maybe:
		// $args = Array('options'=>Array('cellspacing'=>'0',

		if(!isset($this->internalFile)){
			
			echo('<p>No changes between revisions</p>');
			
			return false;
		}
		
		$Args = Array('includeStylesheet' => TRUE);
		
		foreach($Args as $key => $value){
			if(isset($args[$key])){
				$Args[$key] = $args[$key];
			}
		}
		unset($args);
		
		if($Args['includeStylesheet']){
			$this->getDefaultStylesheet();
		}
		
		?>
		<table class="diffTable" cellspacing="0px">
			<thead>
				<tr>
					<th colspan="3" class="diffHeader-main">
						<?php echo($this->fileNames['new']); /*@todo path too? option in? */?> (revision <?php echo($this->fileVersions['new']); ?>)
					</th>
				</tr>
				<tr>
					<th class="diffHeader-revision-new">
						@<?php echo($this->fileVersions['new']); ?>
					</th>
					<th class="diffHeader-revision-orig">
						@<?php echo($this->fileVersions['orig']); ?>
					</th>
					<th class="diffHeader-revision-null">&nbsp;</th>
				</tr>
			</thead>
			<tbody>
				<?php 
					foreach($this->internalFile as $cl => $internalLine){
						$this->_getTable_lineRow($internalLine);
					}
				?>
			</tbody>
		</table>
		<?php
	}
	
	private function _getTable_lineRow($internalLine){
		$content = htmlspecialchars($internalLine['content']);
		switch($internalLine['type']){
			case 'added':
				?>
				<tr>
					<td class="diffContent-lineNumber-new"><?php echo($internalLine['line']['new']); ?></td>
					<td class="diffContent-lineNumber-orig">&nbsp;</td>
					<td class="diffContent-line-added"><pre><?php echo($content); ?></pre></td>
				</tr>
				<?php
				break;
			case 'removed':
				?>
				<tr>
					<td class="diffContent-lineNumber-new">&nbsp;</td>
					<td class="diffContent-lineNumber-orig"><?php echo($internalLine['line']['orig']); ?></td>
					<td class="diffContent-line-removed"><pre><?php echo($content); ?></pre></td>
				</tr>
				<?php
				break;
			case 'common':
				?>
				<tr>
					<td class="diffContent-lineNumber-new"><?php echo($internalLine['line']['new']); ?></td>
					<td class="diffContent-lineNumber-orig"><?php echo($internalLine['line']['orig']); ?></td>
					<td class="diffContent-line"><pre><?php echo($content); ?></pre></td>
				</tr>
				<?php
				break;
			default:
				//@fixme @todo some sort of error
				break;
		}
	}
	
	public function getDefaultStylesheet(){
		?>
		<style>
			.diffTable {
				border: #D7D7D7 thin solid;
				color: #484848;
				font-family: Arial; 
			}
			
			.diffTable th, .diffTable td {
				border-left: #D7D7D7 thin solid;
				border-top:  #D7D7D7 thin solid;
				border-right: none;
				border-bottom: none;
				
				margin: 0px;
			}
		
			.diffHeader-main {
				text-align: left;
				
				background-color: #DDDDCC;
			}
		
			.diffHeader-revision-new, .diffHeader-revision-orig, .diffHeader-revision-null, .diffContent-lineNumber-new, .diffContent-lineNumber-orig {
				text-align: right;
				
				background-color: #EEEEEE;
			}
			
			.diffContent-line, .diffContent-line-removed, .diffContent-line-added {
				text-align: left;
				
				background-color: #FAFAFA;
			}
			
			.diffContent-line-removed {
				background-color: #FFCCCC;
			}
			
			.diffContent-line-added {
				background-color: #CCFFCC;
			}
			
		</style>
		<?php
	}
	
}