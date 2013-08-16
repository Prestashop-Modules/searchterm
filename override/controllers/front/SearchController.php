<?php
class SearchController extends SearchControllerCore
{
	public function canonicalRedirection($canonicalURL = '')
        {
                if(isset($_REQUEST['search_query']) && $_REQUEST['search_query'])
                        $result = Db::getInstance()->getRow('
                                SELECT id_searchterm, url 
                                FROM '._DB_PREFIX_.'searchterm Where term = \''.pSQL($_REQUEST['search_query']).'\'' );
                if($result) {
			$params = array();
                        $str_params = '';
			$excluded_key = array('controller','submit_search', 'search_query');
			if(isset($_GET['orderby']) && strtolower($_GET['orderby']) == 'position') unset($_GET['orderby']);
			if(isset($_GET['orderway']) && strtolower($_GET['orderway']) == 'asc') unset($_GET['orderway']);
                        foreach ($_GET as $key => $value)
                                if (!in_array($key, $excluded_key) && Validate::isUrl($key) && Validate::isUrl($value))
                                        $params[Tools::safeOutput($key)] = Tools::safeOutput($value);

                        $str_params = http_build_query($params, '', '&');
			$canonical_url = Tools::getShopDomain(true, true).__PS_BASE_URI__.$result['url'] ;
			if (!empty($str_params))
                                $final_url = $canonical_url.'?'.$str_params;
                        else
                                $final_url = $canonical_url;

			header('HTTP/1.0 301 Moved');
                        header('Cache-Control: no-cache');
			Tools::redirectLink($final_url);
		}
                //if($result) parent::canonicalRedirection(Tools::getShopDomain(true, true).__PS_BASE_URI__.$result['url']);

        }
}
