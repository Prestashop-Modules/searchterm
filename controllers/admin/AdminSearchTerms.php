<?php
class AdminSearchTermsController extends AdminController
{
	public function initContent()
        {
                //$this->display = 'view';
                //parent::initContent();
		$index = AdminController::$currentIndex ;
		$new_index = preg_replace('/AdminSearchTerms/', 'AdminModules', $index);		
		Tools::redirectAdmin($new_index.'&configure=searchterm&token='.Tools::getAdminTokenLite('AdminModules'));
        }
/*
	public function renderView()
        {
include(dirname(__FILE__).'/../../searchterm.php');
		$searchterm = new SearchTerm();
		return $searchterm->getContent($_GET['token']);
	}	
*/
}
