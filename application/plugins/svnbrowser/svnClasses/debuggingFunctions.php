<?php 

function vname(&$var, $scope=false, $prefix='unique', $suffix='value')
  {
    if($scope) $vals = $scope;
    else      $vals = $GLOBALS;
    $old = $var;
    $var = $new = $prefix.rand().$suffix;
    $vname = FALSE;
    foreach($vals as $key => $val) {
      if($val === $new) $vname = $key;
    }
    $var = $old;
    return $vname;
  }
  
function writeln($line, $pre = FALSE){
	if($pre){
		$tag = 'pre';
	}else{
		$tag = 'p';
	}
	echo("\n<$tag>".$line.'</'.$tag.'>');
}



function writelnvar($var, $name){
	writeln( '<pre>' . $name. ' = ' . print_r($var, TRUE) . '</pre>' );
}

?>