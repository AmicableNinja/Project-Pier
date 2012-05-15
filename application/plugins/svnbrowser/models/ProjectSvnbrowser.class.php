<?php

  /**
  * ProjectLinks
  *
  * @http://www.activeingredient.com.au
  */
  class ProjectSvnbrowser extends BaseProjectSvnbrowser {
  
    /**
    * Return array of all links for project
    *
    * @param Project
    * @return ProjectLinks
    */
    static function getAllProjectLinks(Project $project) {
      trace(__FILE__,'getAllProjectLinks():begin');
      
      $conditions = array('`project_id` = ?', $project->getId());
      
      return self::findAll(array(
        'conditions' => $conditions,
        'order' => '`created_on` DESC',
      )); // findAll
      trace(__FILE__,'getAllProjectLinks():end');
    } // getAllProjectLinks
    
  static function getProjectSettings(Project $project){
      trace(__FILE__,'getProjectSettings():begin');
      
      $conditions = array('`project_id` = ?', $project->getId());
      $conditions = array('TRUE', TRUE);
      
      return self::findAll(array(
        'conditions' => $conditions,
        //'order' => '`created_on` DESC',
      )); // findAll
      trace(__FILE__,'getProjectSettings():end');
    } // getProjectSettings
    
    static function getActiveProjectSettings(){
    	return self::getProjectSettings(active_project());
    }
    
    static function getFormData(){
    	//@todo
    	return NULL;
    }

  } // ProjectLinks 

?>