<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 * Not sure why this should differ from the above and I would prefer my code to
 * be released under GPL v2+ but am deferring to http://civicrm.org/what/licensing
 * Copyright (C) 2014 Christian Wach <needle@haystack.co.uk>
 * Licensed to CiviCRM under the Academic Free License version 3.0.
 */
 
/**
 * This class provides the functionality to save a search
 * Saved Searches are used for saving frequently used queries
 */
class CRM_Contact_Form_Task_CreateWordPressUsers extends CRM_Contact_Form_Task {

  /**
   * build all the data structures needed to build the form
   *
   * @return void
   * @access public
   */
  function preProcess() {
    parent::preProcess();
    
    // get selected contact IDs
    $contactIDs = implode( ',', $this->_contactIds );
    
    // construct query to get name and email of selected contact ids
    $query = "
		SELECT c.id as contact_id, 
		       c.first_name as first_name, 
		       c.last_name as last_name,
			   c.contact_type as contact_type, 
			   e.email as email
		FROM   civicrm_contact c, 
		       civicrm_email e
		WHERE  e.contact_id = c.id
		AND    e.is_primary = 1
		AND    c.id IN ( $contactIDs )";

    $rows = array();
    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $rows[] = array(
        'id' => $dao->contact_id,
        'first_name' => $dao->first_name,
        'last_name' => $dao->last_name,
        'contact_type' => $dao->contact_type,
        'email' => $dao->email,
      );
    }
    
    // our array now contains all contacts who can be synced to WordPress
    $this->assign('rows', $rows);
    
    /*
    //$this->assign('rows', $rows);
    print_r( array(
    	'contactIDs' => $contactIDs,
    	'rows' => $rows,
    ) ); die();
    */
    
  }

  /**
   * Build the form - it consists of
   *
   * @access public
   *
   * @return void
   */
  function buildQuickForm() {
    $this->addDefaultButtons(ts('Back to Search'), 'done');
  }

  /**
   * process the form after the input has been submitted and validated
   *
   * @access public
   *
   * @return void
   */
  public function postProcess() {}
}

