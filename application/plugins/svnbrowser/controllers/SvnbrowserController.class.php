<?php

  /**
  * SVN Browser Controller
  *
  * @bluedream.tv
  */
  class SvnbrowserController extends ApplicationController {
  
    /**
    * Construct the SvnbrowserController
    *
    * @access public
    * @param void
    * @return LinksController
    */
    function __construct() {
      parent::__construct();
      prepare_company_website_controller($this, 'project_website');
    } // __construct
    
    /**
    * Show links for project
    *
    * @access public
    * @param void
    * @return null
    */
    function index() {
      trace(__FILE__,'index()');
      //tpl_assign('joe', 'is funny');
      
      //require_once('svnClasses/svnBrowser.inc.php');
      
      tpl_assign('project_settings', ProjectSvnbrowser::getActiveProjectSettings());
      $activeProject = ProjectSvnbrowser::findByActiveProject();
      tpl_assign('svnArgs', is_null($activeProject) ? NULL : $activeProject->getSvnArgs());
      
      $this->setSidebar(get_template_path('index_sidebar', 'svnbrowser'));
    } // index
    
    function settings(){
      trace(__FILE__,'settings()');
      
      $project_svn = ProjectSvnbrowser::findByActiveProject();
      
      /*
      if (!ProjectSvnbrowse::canEdit(logged_user())) {
        flash_error(lang('no access permissions'));
        $this->redirectTo('svnbrowser','index');
      } // if
      //*/
      
      if (!($project_svn instanceof ProjectSvnbrowse)) {
        //flash_error(lang('project svnbrowser dnx'));
        //$this->redirectTo('svnbrowser');
        
      	//not set
      	$project_svn = new ProjectSvnbrowse();
      	
      } // if
      
      $formData = array_var($_POST, 'project_svnbrowser');
      
      if (!is_array($formData)) {
        $formData = array(
          'transportMethod' => $project_svn->getTransportMethod(),
          'serverPath'      => $project_svn->getServerPath(),
          'authUsername'    => $project_svn->getAuthUsername(),
          'authPassword'    => $project_svn->getAuthPassword(),
        ); // array
      } // if
      
      if (is_array(array_var($_POST, 'project_svnbrowser'))) {
        $project_svn->setFromAttributes($formData);
        $project_svn->setProjectId(active_project()->getId());
        
        try {
          DB::beginWork();
          $project_svn->save();
          ApplicationLogs::createLog($project_svn, active_project(), ApplicationLogs::ACTION_EDIT);
          DB::commit();
          
          flash_success(lang('success edit link'));
          $this->redirectTo('svnbrowser', 'settings');
        } catch(Exception $e) {
          DB::rollback();
          tpl_assign('error', $e);
        } // try
      }
      tpl_assign('project_svn', $project_svn);
      tpl_assign('form_data', $formData);
    } // settings
        
  } // SvnbrowserController

?>