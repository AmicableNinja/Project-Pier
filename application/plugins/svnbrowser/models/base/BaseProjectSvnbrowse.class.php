<?php

  /**
  * BaseProjectLink class
  *
  * @http://www.activeingredient.com.au
  */
  abstract class BaseProjectSvnbrowse extends ProjectDataObject {
  
    /**
    * Return value of 'id' field
    *
    * @access public
    * @param void
    * @return integer 
    */
    function getObjectId() {
      return $this->getColumnValue('id');
    } // getObjectId()
    
    // -----------------------------------------------------
    //  Magic access method
    //  NB: this replaces the need for other setters/getters
    // -----------------------------------------------------
    function __call($method, $args) {
      if( preg_match('/(set|get)(_)?/', $method) ) {
        if(substr($method, 0, 3) == "get") {
          $col = substr(strtolower(preg_replace('([A-Z])', '_$0', $method)), 4);
          if( $col ) {
            return $this->getColumnValue($col);
          }
        } elseif(substr($method, 0, 3) == "set" && count($args)) {
          $col = substr(strtolower(preg_replace('([A-Z])', '_$0', $method)), 4);
          if( $col ) {
            return $this->setColumnValue($col, $args[0]);
          }
        }
      }
      // me no understand!
      return false;
    }
    
    /**
    * Return manager instance
    *
    * @access protected
    * @param void
    * @return ProjectSvnbrowser 
    */
    function manager() {
      if (!($this->manager instanceof ProjectSvnbrowser)) {
        $this->manager = ProjectSvnbrowser::instance();
      }
      return $this->manager;
    } // manager
    
    //'id','project_id','transportMethod','serverPath','authUsername','authPassword'
    
    /**
    * Return value of 'id' field
    *
    * @access public
    * @param void
    * @return integer 
    */
    function getId() {
      return $this->getColumnValue('id');
    } // getId()
    
    /**
    * Return value of 'project_id' field
    *
    * @access public
    * @param void
    * @return integer 
    */
    function getProjectId() {
      return $this->getColumnValue('project_id');
    } // getProjectId()
    
    /**
    * Set value of 'transportMethod' field
    *
    * @access public   
    * @param integer string
    * @return integer
    */
    function setProjectId($value) {
      return $this->setColumnValue('project_id', $value);
    } // setTransportMethod()
    
    /**
    * Return value of 'transportMethod' field
    *
    * @access public
    * @param void
    * @return string 
    */
    function getTransportMethod() {
      return $this->getColumnValue('transportMethod');
    } // getTransportMethod()
    
    /**
    * Set value of 'transportMethod' field
    *
    * @access public   
    * @param integer string
    * @return string
    */
    function setTransportMethod($value) {
      return $this->setColumnValue('transportMethod', $value);
    } // setTransportMethod()
    
    /**
    * Return value of 'serverPath' field
    *
    * @access public
    * @param void
    * @return string 
    */
    function getServerPath() {
      return $this->getColumnValue('serverPath');
    } // getServerPath()
    
    /**
    * Set value of 'serverPath' field
    *
    * @access public   
    * @param string $value
    * @return string
    */
    function setServerPath($value) {
      return $this->setColumnValue('serverPath', $value);
    } // setServerPath()
    
    /**
    * Return value of 'authUsername' field
    *
    * @access public
    * @param void
    * @return string 
    */
    function getAuthUsername() {
      return $this->getColumnValue('authUsername');
    } // getAuthUsername()
    
    /**
    * Set value of 'authUsername' field
    *
    * @access public   
    * @param string $value
    * @return string
    */
    function setAuthUsername($value) {
      return $this->setColumnValue('authUsername', $value);
    } // setAuthUsername()
    
    /**
    * Return value of 'authPassword' field
    *
    * @access public
    * @param void
    * @return string 
    */
    function getAuthPassword() {
      return $this->getColumnValue('authPassword');
    } // getAuthPassword()
    
    /**
    * Set value of 'authPassword' field
    *
    * @access public   
    * @param string $value
    * @return string
    */
    function setAuthPassword($value) {
      return $this->setColumnValue('authPassword', $value);
    } // setAuthPassword()
    
    function getSvnArgs(){
    	$args = Array();
    	$args['transportMethod'] = self::getTransportMethod();
    	$args['baseLocation']    = self::getServerPath();
    	
    	if(strlen(self::getAuthUsername()) > 0){
    		$args['authorization'] = Array();
    		
    		$args['authorization']['username'] = self::getAuthUsername();
    		$args['authorization']['password'] = self::getAuthPassword();
    	}
    	
    	return $args;
    }

    
  
  } // BaseProjectLink 

?>