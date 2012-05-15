<?php
  
  /**
  * All functions here are in the global scope so keep names unique by using
  *   the following pattern:
  *
  *   <name_of_plugin>_<pp_function_name>
  *   i.e. for the hook in 'add_dashboard_tab' use 'svnbrowser_add_dashboard_tab'
  */
  
  // add project tab
  add_action('add_project_tab', 'svnbrowser_add_project_tab');
  function svnbrowser_add_project_tab() {
    add_tabbed_navigation_item(
      'svnbrowser',
      'svnbrowser',
      get_url('svnbrowser', 'index')
    );
  }

  /**
  * If you need an activation routine run from the admin panel
  *   use the following pattern for the function:
  *
  *   <name_of_plugin>_activate
  *
  *  This is good for creation of database tables etc.
  */
  function svnbrowser_activate() {
    $sql = "CREATE TABLE IF NOT EXISTS `".TABLE_PREFIX."plugin_svnbrowser` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `project_id` int(10) unsigned NOT NULL,
            `transportMethod` varchar(10) NOT NULL,
            `serverPath` varchar(255) NOT NULL,
            `authUsername` varchar(50) NOT NULL,
            `authPassword` varchar(50) NOT NULL,
            PRIMARY KEY (`id`)
          ) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4 ;";
    // create table
    DB::execute($sql);
  }

  /**
  * If you need an de-activation routine run from the admin panel
  *   use the following pattern for the function:
  *
  *   <name_of_plugin>_deactivate
  *
  *  This is good for deletion of database tables etc.
  */
  function svnbrowser_deactivate($purge=false) {
    // sample drop table
    if ($purge) {
      DB::execute("DROP TABLE IF EXISTS `".TABLE_PREFIX."plugin_svnbrowser`");
    }
  }
?>