<?php
  trace(__FILE__,'begin');
  set_page_title(lang('svnbrowser'));
  project_tabbed_navigation('svnbrowser');
  project_crumbs(array(
    array(lang('svnbrowser'), get_url('svnbrowser', 'index')),
    array(lang('settings'))
  ));
  $counter = 0;
?>

<?php tpl_display(get_template_path('form_errors')) ?>

<form action="<?php echo(get_url('svnbrowser', 'settings')); ?>" method="post">
	<?php echo label_tag(lang('transportMethod'), 'svnbrowserSettings-transportMethod', true) ?>
	<select name="project_svnbrowser[transportMethod]" id="svnbrowserSettings-transportMethod">
		<option<?php if($form_data['transportMethod'] == 'file'){?> selected="selected"<?php } ?>>file</option>
		<option<?php if($form_data['transportMethod'] == 'SVN'){?> selected="selected"<?php } ?>>SVN</option>
		<option<?php if($form_data['transportMethod'] == 'SVN+SSH'){?> selected="selected"<?php } ?>>SVN+SSH</option>
	</select>
	
	<?php echo label_tag(lang('serverPath'), 'svnbrowserSettings-serverPath', true) ?>
	<?php echo text_field('project_svnbrowser[serverPath]', array_var($form_data, 'serverPath'), array('id' => 'svnbrowserSettings-serverPath', 'class' => 'medium')) ?> (ex: /var/svn/localRepo/)
	
	<?php echo label_tag(lang('auth_username'), 'svnbrowserSettings-authUsername') ?>
	<?php echo text_field('project_svnbrowser[authUsername]', array_var($form_data, 'authUsername'), array('id' => 'svnbrowserSettings-authUsername', 'class' => 'medium')) ?> (blank if none) <!-- use the lang() function!! -->
	
	<?php echo label_tag(lang('auth_password'), 'svnbrowserSettings-authPassword') ?>
	<?php echo text_field('project_svnbrowser[authPassword]', array_var($form_data, 'authPassword'), array('id' => 'svnbrowserSettings-authPassword', 'class' => 'medium', 'type' => 'password' )) ?>

	<p>
		Note: This data (including username, password) is stored in cleartext in the database. For the sake of security, the combination given should have only browsing permissions.<br>
		Future revisions include the prospect of securing this information.
	</p>
	<br>
	<input type="submit" value="Save" />
</form>