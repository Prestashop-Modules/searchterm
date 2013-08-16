<?php
class Dispatcher extends  DispatcherCore
{
	protected function loadRoutes()
        {
		parent::loadRoutes();
		$orderby = (isset($_REQUEST['orderby']) && $_REQUEST['orderby']) ? $_REQUEST['orderby'] : 'position' ;
		$orderway= (isset($_REQUEST['orderway']) && $_REQUEST['orderway']) ? $_REQUEST['orderway'] : 'asc' ;
		$results = Db::getInstance()->executeS('
                        SELECT s.id_searchterm, s.term,s.url,s.title,s.description 
                        FROM '._DB_PREFIX_.'searchterm s ');
		foreach($results as $result)
			foreach (Language::getLanguages() as $lang)
				$this->addRoute('search_'.$result['id_searchterm'],$result['url'],'search',$lang['id_lang'],array(), array('search_query' =>$result['term'], 'orderby' => $orderby, 'orderway' => $orderway));
	}
}
