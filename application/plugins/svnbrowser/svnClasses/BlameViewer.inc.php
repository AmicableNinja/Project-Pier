<?php

class BlameViewer{
	
	private $blameOutput	= NULL;
	private $linkFormats	= Array(
										'rev',
									//	'author',
									);
	private $dateFormat	= 'M jS Y, g:i a';
	
	public function __construct($blameOutput, $versionLinkFormat = NULL, $authorLinkFormat = NULL){
		$this->setVariables($blameOutput, $versionLinkFormat, $authorLinkFormat);
	}
	
	public function setVariables($blameOutput, $versionLinkFormat = NULL, $authorLinkFormat = NULL){
		if(count($blameOutput) > 0){
			$this->blameOutput = $blameOutput;
		}
		
		if(strlen($versionLinkFormat) > 0){
			$this->linkFormats['rev'] = $versionLinkFormat;
		}
		
		if(strlen($versionLinkFormat) > 0){
			$this->linkFormats['author'] = $authorLinkFormat;
		}
		
	}
	
	public function buildDisplay($args = NULL){
		
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
		
		//update as is needed
		//@todo build the system to be customizable
		$headerNames = Array(
								'line_no'	=> 'Line #',
								'rev'		=> 'Revision',
								'author'	=> 'Blame',
								'line'		=> 'Content',
								'date'		=> 'Changed',
							);
		
		
		?>
		<table class="blameTable" cellspacing="0px">
			<thead>
				<?php foreach($headerNames as $key => $name) : ?>
				<th class="blameHeader blameHeader-<?php echo($key); ?>"><?php echo($name); ?></th>
				<?php endforeach; ?>
			</thead>
			<tbody>
				<?php foreach($this->blameOutput as $line) : ?>
				<tr>
					<?php foreach(array_keys($headerNames) as $key) : ?>
					<?php $this->_getDisplay_item($line, $key); ?>
					<?php endforeach; ?>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}
	
	private function _getDisplay_item($line, $key){
		?><td class="blameContent blameContent-<?php
		
		//CLASSES AREA
		
		echo(preg_replace('/_/', '-', $key));
		
		switch($key){
			case 'rev': // fall-through
			case 'author':
				echo(' blameContent-rev-'.$line['rev']);
				break;
			case 'date':
			case 'line':
			case 'line_no':
			default:
				break;
		}
		
		?>"><?php
		
		
		// CONTENT AREA
		switch($key){
			case 'rev':
				if(isset($this->linkFormats['rev'])){
					?><a href="<?php echo(sprintf($this->linkFormats['rev'], $line['rev'])); ?>"><?php echo($line['rev'])?></a><?php
				}else{
					echo($line['rev'] . print_r());
				}
				break;
			case 'author':
				if(isset($this->linkFormats['author'])){
					?><a href="<?php echo(sprintf($this->linkFormats['author'], $line['author'])); ?>"><?php echo($line['author'])?></a><?php
				}else{
					echo($line['author']);
				}
				break;
			case 'line':
				echo('<pre>' . htmlspecialchars($line[$key]) . '</pre>');
				break;
			case 'date':
				echo(date($this->dateFormat, strtotime($line['date'])));
				break;
			case 'line_no':
			default:
				echo($line[$key]);
				break;
		}
		
		
		
		// ENDING TAG
		?></td><?php
	}
	
	public function getDefaultStylesheet(){
		?>
		<style>
			.blameTable {
				border: #D7D7D7 thin solid;
				color: #484848;
				font-family: Arial; 
			}
			
			.blameTable th, .blameTable td {
				border-left: #D7D7D7 thin solid;
				border-top:  #D7D7D7 thin solid;
				border-right: none;
				border-bottom: none;
				
				margin: 0px;
			}
			
			.blameHeader {
				text-align: center;
				
				background-color: #EEEEEE;
			}
			
			.blameContent {
				text-align: left;
				
				background-color: #FAFAFA;
			}
			
			.blameContent-line-no {
				font-weight: bold;
			}
			
			.blameContent-rev, .blameContent-author {
				background-color: yellow;
				border-left: none;
				text-align: center;
			}
			<?php

			$users		= Array();
			$revisions	= Array();
			foreach ($this->blameOutput as $line){
				/*
				if(!in_array($line['author'], $users)){
					$users[] = $line['author'];
				}
				//*/
				if(!in_array($line['rev'], $revisions)){
					$revisions[] = $line['rev'];
				}
			}
			
			//$diff = max($revisions) - min($revisions);
			//$delta = $diff  / count($revisions);
			
			foreach($revisions as $rev){
				?>
			.blameContent-rev-<?php echo($rev); ?> {
				background-color: #<?php echo(dechex(rand(0,256)) . dechex(rand(0,256)) . dechex(rand(0,256)) . ''); ?>;
			}
				<?php
			}
			
			?>
		</style>
		<?php
	}
	
}