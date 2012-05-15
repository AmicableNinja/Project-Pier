<?php
  trace(__FILE__,'begin');
  set_page_title(lang('svnbrowser'));
  project_tabbed_navigation('svnbrowser');
  project_crumbs(array(
    array(lang('svnbrowser'), get_url('svnbrowser', 'index')),
    array(lang('index'))
  ));
  $counter = 0;
?>
<?php
$includePath = __DIR__ . '/../svnClasses/svnBrowser.inc.php';
include($includePath);

//@todo set the page title to use the svn directory

if($svnArgs){
	//$conn = new svnConnector($svnArgs);
	//var_dump($conn->ls());
	$browse = new svnBrowser($svnArgs);
	$browse->enableErroring();
	$browse->imagesBaseDir( __DIR__, TRUE );
	$browse->go();
}
?>

<?php trace(__FILE__,'end'); ?>