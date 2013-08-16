<?php

if (!defined('_PS_VERSION_'))
	exit;
	
class SearchTerm extends Module
{
	private $_html = '';
	private $_postErrors = array();

	public function __construct()
	{
		$this->name = 'searchterm';
		$this->tab = 'administration';
		$this->version = '1.1';
		$this->author = 'dh42';
		$this->need_instance = 0;
		
		$this->tabClassName = 'AdminTools';
		$this->subtabClass[1] = 'AdminSearchTerms';
		$this->TabName[1] = ' SEO Search pages';

		parent::__construct();
		
		$this->displayName = $this->l('Search Term');
		$this->description = $this->l('Add Search  term');
		 if (!defined('GSITEMAP_FILE'))
                        define('GSITEMAP_FILE', dirname(__FILE__).'/../../sitemap.xml');

	}
	
	public function install()
	{	
		if (!parent::install() ||
                        !$this->installDB() ||
                        !$this->installTab())
                        return false;
                return true;
	}
	public function installDB()
        {
                return (Db::getInstance()->execute('CREATE TABLE '._DB_PREFIX_.'searchterm (
                        `id_searchterm` int(10) NOT NULL AUTO_INCREMENT, 
                        `term` varchar(255) NOT NULL,
                        `url` varchar(255) NOT NULL,
                        `title` varchar(255) NOT NULL,
                        `description` varchar(255) NOT NULL,
                        `keywords` varchar(255) NOT NULL,
                        PRIMARY KEY(`id_searchterm`))
                        ENGINE='._MYSQL_ENGINE_.' default CHARSET=utf8') 
                        );

        }
	private function installTab(){
		$id_tab = Tab::getIdFromClassName($this->tabClassName);
                if ($id_tab) {
                foreach ( $this->subtabClass as $k =>$subTab){
                     $tab = new Tab();
                     $tab->class_name = $subTab ;
                     $tab->id_parent = $id_tab ;
                     $tab->module = $this->name;
                     $languages = Language::getLanguages();
                     foreach ($languages as $language)
                        $tab->name[$language['id_lang']] = $this->TabName[$k];
                     $tab->add();
                }
                     return true ;
                }
                return false;
        }
	public function uninstall()
        {
                if (!parent::uninstall() ||
                        !$this->uninstallDB())
                        return false;
                return true;
        }

        private function uninstallDB()
        {
		foreach ( $this->subtabClass as $subTab){
                        $id_tab = Tab::getIdFromClassName($subTab);
                        if ($id_tab) {
                              $tab = new Tab($id_tab);
                              $tab->delete();
                        }
                }
                Db::getInstance()->execute('DROP TABLE `'._DB_PREFIX_.'searchterm`');
                return true;
        }
		

	public function getContent()
	{		
		$this->_html .= '<h2>'.$this->l('SEO search landing pages').'</h2>';
		$this->_postProcess();
		if (Tools::isSubmit('addSearchTerm') OR (Tools::isSubmit('editSearchTerm') AND Tools::isSubmit('id_searchterm')))
			$this->_setSearchTermForm();
		else
			$this->_setConfigurationForm();
		
		return $this->_html;
	}
	 private function _postGsValidation()
        {
                file_put_contents(GSITEMAP_FILE, '');
                if (!($fp = fopen(GSITEMAP_FILE, 'w')))
                        $this->_postErrors[] = sprintf($this->l('Cannot create %ssitemap.xml file..'), realpath(dirname(__FILE__.'/../..')).'/');
                else
                        fclose($fp);
        }
	private function _postGsProcess()
        {
                Configuration::updateValue('GSITEMAP_ALL_CMS', (int)Tools::getValue('GSITEMAP_ALL_CMS'));
                Configuration::updateValue('GSITEMAP_ALL_PRODUCTS', (int)Tools::getValue('GSITEMAP_ALL_PRODUCTS'));

                if (Shop::isFeatureActive())
                        $res = $this->generateSitemapIndex();
                else
                        $res = $this->generateSitemap(Configuration::get('PS_SHOP_DEFAULT'), GSITEMAP_FILE);

                $this->_html .= '<h3 class="'. ($res ? 'conf confirm' : 'alert error') .'" style="margin-bottom: 20px">';
                $this->_html .= $res ? $this->l('Sitemap file generated.') : $this->l('Error while creating sitemap file.');
                $this->_html .= '</h3>';
        }
	private function _postProcess()
	{
		$errors = array();
		if (Tools::isSubmit('btnSubmit'))
                {
                        $this->_postGsValidation();
                        if (!count($this->_postErrors))
                                $this->_postGsProcess();
                        else
                                foreach ($this->_postErrors as $err)
                                        $this->_html .= '<div class="alert error">'.$err.'</div>';
                }
		if (Tools::isSubmit('submitSearchTerm'))
		{	
			if(!Tools::getValue('term'))
				$errors[] = $this->l('Please add a search Term');
			if(!Tools::getValue('url'))
				$errors[] = $this->l('Please add a Re-written url');
			if(!Tools::getValue('title'))
				$errors[] = $this->l('Please add a Meta Title');
			if(!Tools::getValue('description'))
				$errors[] = $this->l('Please add a Meta Description');
			
			if (!sizeof($errors))
			{
				if (Tools::isSubmit('addSearchTerm'))
				{
					if (Db::getInstance()->execute('
					INSERT INTO `'._DB_PREFIX_.'searchterm`(`term`, `url`, `title`, `description`, `keywords`) 
					VALUES (\''.pSQL(Tools::getValue('term')).'\',\''.pSQL(Tools::getValue('url')).'\',\''.pSQL(Tools::getValue('title')).'\',\''.pSQL(Tools::getValue('description')).'\',\''.pSQL(Tools::getValue('keywords')).'\')
					'))
						Tools::redirectAdmin(AdminController::$currentIndex.'&configure='.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules').'&confirmAddSearchTerm');
					else
						$this->_html .= $this->displayError($this->l('An error occurred on adding of search term.'));
				}
				else
				{
					if (Db::getInstance()->execute('
					UPDATE `'._DB_PREFIX_.'searchterm`  
					SET `term` = \''.pSQL(Tools::getValue('term')).'\' ,`url` = \''.pSQL(Tools::getValue('url')).'\',`title` = \''.pSQL(Tools::getValue('title')).'\',`description` = \''.pSQL(Tools::getValue('description')).'\' ,`keywords` = \''.pSQL(Tools::getValue('keywords')).'\'
					WHERE `id_searchterm` = '.(int)(Tools::getValue('id_searchterm'))
					))
						Tools::redirectAdmin(AdminController::$currentIndex.'&configure='.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules').'&confirmEditSearchTerm');
					else
						$this->_html .= $this->displayError($this->l('An error occurred on updating of search term.'));
				}
				
			}
			else
				$this->_html .= $this->displayError(implode('<br />', $errors));
		}
		
		if (Tools::isSubmit('deleteSearchTerm') AND Tools::isSubmit('id_searchterm') AND (int)(Tools::getValue('id_searchterm')) AND $this->_isSearchTermExists((int)(Tools::getValue('id_searchterm'))))
		{
			$this->_deleteByIdSearchTerm((int)(Tools::getValue('id_searchterm')));
			$this->_html .= $this->displayConfirmation($this->l('Search term deleted successfully'));
		}
		if (Tools::isSubmit('confirmAddSearchTerm'))
			$this->_html = $this->displayConfirmation($this->l('Search Term added successfully'));
		
		if (Tools::isSubmit('confirmEditSearchTerm'))
			$this->_html = $this->displayConfirmation($this->l('Search Term updated successfully'));
	}
	private function _setConfigurationForm()
	{
		$this->_html .= '
		<fieldset>
			<legend><img src="'._PS_BASE_URL_.__PS_BASE_URI__.'modules/'.$this->name.'/img/meta100.png" alt="" /> '.$this->l('Search Term configuration').'</legend>
			
			<p><a href="'.AdminController::$currentIndex.'&configure='.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules').'&addSearchTerm"><img src="'._PS_BASE_URL_.__PS_BASE_URI__.'modules/'.$this->name.'/img/add_2.gif" alt="" /> '.$this->l('Add a new search term').'</a></p>
			
			<h3>'.$this->l('List of search terms').'</h3>';
		$searchterms = $this->_getSearchTermList();
		if (sizeof($searchterms))
		{
			$this->_html .= '<table width="100%" class="table" cellspacing="0" cellpadding="0">
			<thead>
			<tr>
				<th width="15%"><b>'.$this->l('Search Term').'</b></th>
				<th width="15%"><b>'.$this->l('Re-written url').'</b></th>
				<th width="20%"><b>'.$this->l('Meta Title').'</b></th>
				<th width="20%"><b>'.$this->l('Meta Description').'</b></th>
				<th width="20%"><b>'.$this->l('Meta Keywords').'</b></th>
				<th width="10%"><b>'.$this->l('Actions').'</b></th>
			</tr>
			</thead>
			<tbody>
			';
			
			foreach ($searchterms as $rule)
			{
				$this->_html .= '
				<tr>
					<td width="15%">'.(!preg_match('/^0$/Ui', $rule['term']) ? htmlentities($rule['term'], ENT_QUOTES, 'UTF-8') : Configuration::get('PS_SHOP_NAME')).'</td>
					<td width="15%">'.(!preg_match('/^0$/Ui', $rule['url']) ? htmlentities($rule['url'], ENT_QUOTES, 'UTF-8') : Configuration::get('PS_SHOP_NAME')).'</td>
					<td width="20%">'.(!preg_match('/^0$/Ui', $rule['title']) ? htmlentities($rule['title'], ENT_QUOTES, 'UTF-8') : Configuration::get('PS_SHOP_NAME')).'</td>
					<td width="20%">'.(!preg_match('/^0$/Ui', $rule['description']) ? htmlentities($rule['description'], ENT_QUOTES, 'UTF-8') : Configuration::get('PS_SHOP_NAME')).'</td>
					<td width="20%">'.(!preg_match('/^0$/Ui', $rule['keywords']) ? htmlentities($rule['keywords'], ENT_QUOTES, 'UTF-8') : Configuration::get('PS_SHOP_NAME')).'</td>
					</td>
					<td width="10%" class="center">
						<a href="'.AdminController::$currentIndex.'&configure='.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules').'&editSearchTerm&id_searchterm='.(int)($rule['id_searchterm']).'" title="'.$this->l('Edit').'"><img src="'._PS_ADMIN_IMG_.'edit.gif" alt="" /></a> 
						<a href="'.AdminController::$currentIndex.'&configure='.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules').'&deleteSearchTerm&id_searchterm='.(int)($rule['id_searchterm']).'" title="'.$this->l('Delete').'"><img src="'._PS_ADMIN_IMG_.'delete.gif" alt="" /></a>
					</td>
				</tr>';
			}
			$this->_html .= '
			</tbody>
			</table>';
		}
		else
			$this->_html .= '<p class="center">'.$this->l('No Search Term').'</p>';
		
		$this->_html .= '
		</fieldset>
		<br />
		';
		if (Tools::usingSecureMode())
                        $domain = Tools::getShopDomainSsl(true);
                else
                        $domain = Tools::getShopDomain(true);

		$this->_html .= '
		<fieldset>
			<legend><img src="'._PS_BASE_URL_.__PS_BASE_URI__.'modules/'.$this->name.'/img/sitemap.png" alt="" /> '.$this->l('Sitemap').'</legend>
                        <form action="'.Tools::htmlentitiesUTF8($_SERVER['REQUEST_URI']).'" method="post">
				<div style="margin:0 0 20px 0;">
                                        <input type="checkbox" name="GSITEMAP_ALL_PRODUCTS" id="GSITEMAP_ALL_PRODUCTS" style="vertical-align: middle;" value="1" '.(Configuration::get('GSITEMAP_ALL_PRODUCTS') ? 'checked="checked"' : '').' /> <label class="t" for="GSITEMAP_ALL_PRODUCTS">'.$this->l('Sitemap also includes products from inactive categories.').'</label>
                                </div>
                                <div style="margin:0 0 20px 0;">
                                        <input type="checkbox" name="GSITEMAP_ALL_CMS" id="GSITEMAP_ALL_CMS" style="vertical-align: middle;" value="1" '.(Configuration::get('GSITEMAP_ALL_CMS') ? 'checked="checked"' : '').' /> <label class="t" for="GSITEMAP_ALL_CMS">'.$this->l('Sitemap also includes CMS pages not found in a CMS block.').'</label>
                                </div>
                                <input name="btnSubmit" class="button" type="submit"
                                value="'.((!file_exists(GSITEMAP_FILE)) ? $this->l('Generate a sitemap file.') : $this->l('Update the sitemap file.')).'" />
                        </form><br />
			<h2>'.$this->l('Use cron job to re-build the sitemap:').'</h2>
                        <p>
                                <b>'.$domain.__PS_BASE_URI__.'modules/'.$this->name.'/gsitemap-cron.php?&token='.substr(Tools::encrypt('gsitemap/cron'),0,10).'&GSITEMAP_ALL_CMS='.((int)Configuration::get('GSITEMAP_ALL_CMS')).'&GSITEMAP_ALL_PRODUCTS='.((int)Configuration::get('GSITEMAP_ALL_PRODUCTS')).'</b>
                        </p>	
		</fieldset>' ;

	}
	
	private function _setSearchTermForm()
	{
		if (Tools::isSubmit('editSearchTerm') AND $this->_isSearchTermExists(Tools::getValue('id_searchterm')))
			$search_term= $this->_getSearchTerm(Tools::getValue('id_searchterm'));
		$this->_html .= '
		<form method="post" action="'.$_SERVER['REQUEST_URI'].'">
		';
		if (isset($search_term) AND $search_term['id_searchterm'])
			$this->_html .= '<input type="hidden" name="id_searchterm" value="'.(int)($search_term['id_searchterm']).'" />';
		$this->_html .= '
		<fieldset>
		';
		if (Tools::isSubmit('addSearchTerm'))
			$this->_html .= '<legend><img src="'._PS_BASE_URL_.__PS_BASE_URI__.'modules/'.$this->name.'/img/add.gif" alt="" /> '.$this->l('New Search rule').'</legend>';
		elseif (Tools::isSubmit('editSearchTerm'))
			$this->_html .= '<legend><img src="'._PS_BASE_URL_.__PS_BASE_URI__.'modules/'.$this->name.'/img/edit.gif" alt="" /> '.$this->l('Edit Search rule').'</legend>';
		
			$this->_html .= '<label style="clear: both;">'.$this->l('Search Term').'</label>
                                <div class="margin-form">
                                        <input style="clear: both;" type="text" name="term" value="'.htmlentities(Tools::getValue('term', ((isset($search_term) AND $search_term['term']) ? $search_term['term'] : '')), ENT_QUOTES, 'UTF-8').'" />
                                </div>
                                <label style="clear: both;">'.$this->l('Re-written url').'</label>
                                <div class="margin-form">
                                        <input style="width: 500px;clear: both;" type="text"  name="url" value="'.htmlentities(Tools::getValue('url', ((isset($search_term) AND $search_term['url']) ? $search_term['url'] : '')), ENT_QUOTES, 'UTF-8').'" />
                                </div>
                                <label style="clear: both;">'.$this->l('Meta Title').'</label>
                                <div class="margin-form">
                                        <input style="width: 500px;clear: both;" type="text" name="title" value="'.htmlentities(Tools::getValue('title', ((isset($search_term) AND $search_term['title']) ? $search_term['title'] : '')), ENT_QUOTES, 'UTF-8').'" />
                                </div>
                                <label style="clear: both;">'.$this->l('Meta Description').'</label>
                                <div class="margin-form">
                                        <input style="width: 500px;clear: both;" type="text" name="description" value="'.htmlentities(Tools::getValue('description', ((isset($search_term) AND $search_term['description']) ? $search_term['description'] : '')), ENT_QUOTES, 'UTF-8').'" />
                                </div>
                                <label style="clear: both;">'.$this->l('Meta Keywords').'</label>
                                <div class="margin-form">
                                        <input style="width: 500px;clear: both;" type="text" name="keywords" value="'.htmlentities(Tools::getValue('keywords', ((isset($search_term) AND $search_term['keywords']) ? $search_term['keywords'] : '')), ENT_QUOTES, 'UTF-8').'" />
                                </div>
			<p class="center"><input type="submit" class="button" name="submitSearchTerm" value="'.$this->l('Save').'" /></p>
			<p class="center"><a href="'.AdminController::$currentIndex.'&configure='.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules').'">'.$this->l('Cancel').'</a></p>
		';
		$this->_html .= '
		</fieldset>
		</form>
		';
	}
	private function _getSearchTermList()
	{
		return Db::getInstance()->executeS('
                        SELECT s.id_searchterm, s.term,s.url,s.title,s.description, s.keywords 
                        FROM '._DB_PREFIX_.'searchterm s ');
	}
	private function _getSearchTerm($id_searchterm)
	{
		if (!(int)($id_searchterm))
			return false;
		return Db::getInstance()->getRow('
		SELECT * 
		FROM `'._DB_PREFIX_.'searchterm` s 
		WHERE `id_searchterm` = '.(int)($id_searchterm)
		);
	}
	private function _isSearchTermExists($id_searchterm)
	{
		if (!(int)($id_searchterm))
			return false;
		return (bool)Db::getInstance()->getValue('
		SELECT COUNT(*) 
		FROM `'._DB_PREFIX_.'searchterm` 
		WHERE `id_searchterm` = '.(int)($id_searchterm)
		);
	}
	private function _deleteByIdSearchTerm($id_searchterm)
	{
		if (!(int)($id_searchterm))
			return false;
		return Db::getInstance()->execute('
		DELETE FROM `'._DB_PREFIX_.'searchterm` 
		WHERE `id_searchterm` = '.(int)($id_searchterm)
		);
	}

	/**
	 * Generate sitemap index to reference the sitemap of each shop
	 *
	 * @return bool
	 */
	public function generateSitemapIndex()
	{
		$xmlString = <<<XML
<?xml version="1.0" encoding="UTF-8" ?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
</sitemapindex>
XML;
		$xml = new SimpleXMLElement($xmlString);

		$sql = 'SELECT s.id_shop, su.domain, su.domain_ssl, CONCAT(su.physical_uri, su.virtual_uri) as uri
				FROM '._DB_PREFIX_.'shop s
				INNER JOIN '._DB_PREFIX_.'shop_url su ON s.id_shop = su.id_shop AND su.main = 1
				WHERE s.active = 1
					AND s.deleted = 0
					AND su.active = 1';
		if (!$result = Db::getInstance()->executeS($sql))
			return false;

		$res = true;
		foreach ($result as $row)
		{
			$info = pathinfo(GSITEMAP_FILE);
			$filename = $info['filename'].'-'.$row['id_shop'].'.'.$info['extension'];

			$replaceUrl = array('http://'.$row['domain'].$row['uri'], ((Configuration::get('PS_SSL_ENABLED')) ? 'https://' : 'http://').$row['domain_ssl'].$row['uri']);

			$last = $this->generateSitemap($row['id_shop'], $info['dirname'].'/'.$filename, $replaceUrl);
			if ($last)
			{
				$this->_addSitemapIndexNode($xml, 'http://'.$row['domain'].(($row['uri']) ? $row['uri'] : '/').$filename, date('Y-m-d'));
			}
			$res &= $last;
		}

		$fp = fopen(GSITEMAP_FILE, 'w');
		fwrite($fp, $xml->asXML());
		fclose($fp);

		return $res && file_exists(GSITEMAP_FILE);
	}

	/**
	 * Generate a sitemap for a shop
	 *
	 * @param int $id_shop
	 * @param string $filename
	 * @return bool
	 */
	private function generateSitemap($id_shop, $filename = '', $replace_url = array())
	{
		$langs = Language::getLanguages();
		$shop = new Shop($id_shop);
		if (!$shop->id)
			return false;

		$xmlString = <<<XML
<?xml version="1.0" encoding="UTF-8" ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
</urlset>
XML;

		$xml = new SimpleXMLElement($xmlString);

		if (Configuration::get('PS_REWRITING_SETTINGS') && count($langs) > 1)
			foreach($langs as $lang)
			{
				$this->_addSitemapNode($xml, Tools::getShopDomain(true, true).__PS_BASE_URI__.$lang['iso_code'].'/', '1.00', 'daily', date('Y-m-d'));
			}
		else
			$this->_addSitemapNode($xml, Tools::getShopDomain(true, true).__PS_BASE_URI__, '1.00', 'daily', date('Y-m-d'));

		/* Product Generator */
		$sql = 'SELECT p.id_product, pl.link_rewrite, DATE_FORMAT(IF(ps.date_upd,ps.date_upd,ps.date_add), \'%Y-%m-%d\') date_upd, pl.id_lang, cl.`link_rewrite` category, ean13, i.id_image, il.legend legend_image, (
					SELECT MIN(level_depth)
					FROM '._DB_PREFIX_.'product p2
					'.Shop::addSqlAssociation('product', 'p2').'
					LEFT JOIN '._DB_PREFIX_.'category_product cp2 ON p2.id_product = cp2.id_product
					LEFT JOIN '._DB_PREFIX_.'category c2 ON cp2.id_category = c2.id_category
					WHERE p2.id_product = p.id_product AND product_shop.`active` = 1 AND c2.`active` = 1) AS level_depth
				FROM '._DB_PREFIX_.'product p
				LEFT JOIN '._DB_PREFIX_.'product_shop ps ON (ps.id_product = p.id_product AND ps.id_shop = '.(int)$id_shop.')
				LEFT JOIN '._DB_PREFIX_.'product_lang pl ON (p.id_product = pl.id_product)
				LEFT JOIN '._DB_PREFIX_.'category_lang cl ON (ps.id_category_default = cl.id_category AND pl.id_lang = cl.id_lang AND cl.id_shop = '.(int)$id_shop.')
				LEFT JOIN '._DB_PREFIX_.'image i ON p.id_product = i.id_product
				LEFT JOIN '._DB_PREFIX_.'image_lang il ON (i.id_image = il.id_image)
				LEFT JOIN '._DB_PREFIX_.'lang l ON (pl.id_lang = l.id_lang)
				WHERE l.`active` = 1
					AND ps.`active` = 1
					AND ps.id_shop = '.(int)$id_shop.'
				'.(Configuration::get('GSITEMAP_ALL_PRODUCTS') ? '' : 'HAVING level_depth IS NOT NULL').'
				ORDER BY pl.id_product, pl.id_lang ASC';

		$resource = Db::getInstance(_PS_USE_SQL_SLAVE_)->query($sql);

		// array used to know which product/image was already added (blacklist)
		$done = null;
		$sitemap = null;

		// iterates on the products, to gather the image ids
		while ($product = Db::getInstance()->nextRow($resource))
		{
			// if the product has not been added
			$id_product = $product['id_product'];
			if (!isset($done[$id_product]['added']))
			{
				// priority
				if (($priority = 0.7 - ($product['level_depth'] / 10)) < 0.1)
					$priority = 0.1;

				// adds the product
				$tmpLink = $this->context->link->getProductLink((int)($product['id_product']), $product['link_rewrite'], $product['category'], $product['ean13'], (int)($product['id_lang']));
				$sitemap = $this->_addSitemapNode($xml, $tmpLink, $priority, 'weekly', substr($product['date_upd'], 0, 10));

				// considers the product has added
				$done[$id_product]['added'] = true;
			}

			// if the image has not been added
			$id_image = $product['id_image'];
			if (!isset($done[$id_product][$id_image]) && $id_image)
			{
				// adds the image
				$this->_addSitemapNodeImage($sitemap, $product);

				// considers the image as added
				$done[$id_product][$id_image] = true;
			}
		}

		/* Categories Generator */
		if (Configuration::get('PS_REWRITING_SETTINGS'))
			$categories = Db::getInstance()->executeS('
			SELECT c.id_category, c.level_depth, link_rewrite, DATE_FORMAT(IF(date_upd,date_upd,date_add), \'%Y-%m-%d\') AS date_upd, cl.id_lang
			FROM '._DB_PREFIX_.'category c
			LEFT JOIN '._DB_PREFIX_.'category_lang cl ON c.id_category = cl.id_category
			LEFT JOIN '._DB_PREFIX_.'lang l ON cl.id_lang = l.id_lang
			WHERE l.`active` = 1 AND c.`active` = 1 AND c.id_category != 1
			ORDER BY cl.id_category, cl.id_lang ASC');
		else
			$categories = Db::getInstance()->executeS(
			'SELECT c.id_category, c.level_depth, DATE_FORMAT(IF(date_upd,date_upd,date_add), \'%Y-%m-%d\') AS date_upd
			FROM '._DB_PREFIX_.'category c
			ORDER BY c.id_category ASC');


		foreach($categories as $category)
		{
			if (($priority = 0.9 - ($category['level_depth'] / 10)) < 0.1)
				$priority = 0.1;

			$tmpLink = Configuration::get('PS_REWRITING_SETTINGS') ?
				$this->context->link->getCategoryLink((int)$category['id_category'], $category['link_rewrite'], (int)$category['id_lang'])
				: $this->context->link->getCategoryLink((int)$category['id_category']);
			$this->_addSitemapNode($xml, htmlspecialchars($tmpLink), $priority, 'weekly', substr($category['date_upd'], 0, 10));
		}

		/* CMS Generator */
		if (Configuration::get('GSITEMAP_ALL_CMS') || !Module::isInstalled('blockcms'))
			$sql_cms = '
			SELECT DISTINCT '.(Configuration::get('PS_REWRITING_SETTINGS') ? 'cl.id_cms, cl.link_rewrite, cl.id_lang' : 'cl.id_cms').
			' FROM '._DB_PREFIX_.'cms_lang cl
			LEFT JOIN '._DB_PREFIX_.'lang l ON (cl.id_lang = l.id_lang)
			WHERE l.`active` = 1
			ORDER BY cl.id_cms, cl.id_lang ASC';
		else if (Module::isInstalled('blockcms'))
			$sql_cms = '
			SELECT DISTINCT '.(Configuration::get('PS_REWRITING_SETTINGS') ? 'cl.id_cms, cl.link_rewrite, cl.id_lang' : 'cl.id_cms').
			' FROM '._DB_PREFIX_.'cms_block_page b
			LEFT JOIN '._DB_PREFIX_.'cms_lang cl ON (b.id_cms = cl.id_cms)
			LEFT JOIN '._DB_PREFIX_.'lang l ON (cl.id_lang = l.id_lang)
			WHERE l.`active` = 1
			ORDER BY cl.id_cms, cl.id_lang ASC';

		$cmss = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql_cms);
		foreach($cmss as $cms)
		{
			$tmpLink = Configuration::get('PS_REWRITING_SETTINGS') ?
				$this->context->link->getCMSLink((int)$cms['id_cms'], $cms['link_rewrite'], false, (int)$cms['id_lang'])
				: $this->context->link->getCMSLink((int)$cms['id_cms']);
			$this->_addSitemapNode($xml, $tmpLink, '0.8', 'daily');
		}

		/* Search Term Generator */
		if (Module::isInstalled('searchterm')){
			$sql_sts = '
			SELECT DISTINCT s.term, s.url, s.title, s.description, s.keywords
			FROM '._DB_PREFIX_.'searchterm s
			ORDER BY s.term ASC';
		$sts = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql_sts);
		foreach($sts as $st)
		{
			$tmpLink = Tools::getShopDomain(true, true).__PS_BASE_URI__.$st['url'] ;
			$this->_addSitemapNode($xml, $tmpLink, '0.5', 'weekly');
		}
		}
		/* End Search Term Generator */
		/* Add classic pages (contact, best sales, new products...) */
		$pages = array(
			'supplier' => false,
			'manufacturer' => false,
			'new-products' => false,
			'prices-drop' => false,
			'stores' => false,
			'authentication' => true,
			'best-sales' => false,
			'contact-form' => true);

		// Don't show suppliers and manufacturers if they are disallowed
		if (!Module::getInstanceByName('blockmanufacturer')->id && !Configuration::get('PS_DISPLAY_SUPPLIERS'))
			unset($pages['manufacturer']);

		if (!Module::getInstanceByName('blocksupplier')->id && !Configuration::get('PS_DISPLAY_SUPPLIERS'))
			unset($pages['supplier']);

		// Generate nodes for pages
		if(Configuration::get('PS_REWRITING_SETTINGS'))
			foreach ($pages as $page => $ssl)
				foreach($langs as $lang)
					$this->_addSitemapNode($xml, $this->context->link->getPageLink($page, $ssl, $lang['id_lang']), '0.5', 'monthly');
		else
			foreach($pages as $page => $ssl)
				$this->_addSitemapNode($xml, $this->context->link->getPageLink($page, $ssl), '0.5', 'monthly');

		$xml_string = $xml->asXML();

		// Replace URL in XML strings by real shops URL
		if ($replace_url)
			$xml_string = str_replace(array(Tools::getShopDomain(true).__PS_BASE_URI__, Tools::getShopDomainSsl(true).__PS_BASE_URI__), $replace_url, $xml_string);

		$fp = fopen($filename, 'w');
		fwrite($fp, $xml_string);
		fclose($fp);

		return file_exists($filename);
	}

	private function _addSitemapIndexNode($xml, $loc, $last_mod)
	{
		$sitemap = $xml->addChild('sitemap');
		$sitemap->addChild('loc', htmlspecialchars($loc));
		$sitemap->addChild('lastmod', $last_mod);
		return $sitemap;
	}

	private function _addSitemapNode($xml, $loc, $priority, $change_freq, $last_mod = NULL)
	{
		$sitemap = $xml->addChild('url');
		$sitemap->addChild('loc', htmlspecialchars($loc));
		$sitemap->addChild('priority',  number_format($priority,1,'.',''));
		if ($last_mod)
			$sitemap->addChild('lastmod', $last_mod);
		$sitemap->addChild('changefreq', $change_freq);
		return $sitemap;
	}

	private function _addSitemapNodeImage($xml, $product)
	{
		$image = $xml->addChild('image', null, 'http://www.google.com/schemas/sitemap-image/1.1');
		$image->addChild('loc', htmlspecialchars($this->context->link->getImageLink($product['link_rewrite'], (int)$product['id_product'].'-'.(int)$product['id_image'])), 'http://www.google.com/schemas/sitemap-image/1.1');

		$legend_image = preg_replace('/(&+)/i', '&amp;', $product['legend_image']);
		$image->addChild('caption', $legend_image, 'http://www.google.com/schemas/sitemap-image/1.1');
		$image->addChild('title', $legend_image, 'http://www.google.com/schemas/sitemap-image/1.1');
	}

	private function _displaySitemap()
	{
		if (Shop::isFeatureActive())
		{
			$sql = 'SELECT s.id_shop, su.domain, su.domain_ssl, CONCAT(su.physical_uri, su.virtual_uri) as uri
					FROM '._DB_PREFIX_.'shop s
					INNER JOIN '._DB_PREFIX_.'shop_url su ON s.id_shop = su.id_shop AND su.main = 1
					WHERE s.active = 1
						AND s.deleted = 0
						AND su.active = 1';
			if (!$result = Db::getInstance()->executeS($sql))
				return '';

			$this->_html .= '<h2>'.$this->l('Sitemap index').'</h2>';
			$this->_html .= '<p>'.$this->l('Your Google sitemap file is online at the following address:').'<br />
				<a href="'.Tools::getShopDomain(true, true).__PS_BASE_URI__.'sitemap.xml" target="_blank"><b>'.Tools::getShopDomain(true, true).__PS_BASE_URI__.'sitemap.xml</b></a></p><br />';

			$info = pathinfo(GSITEMAP_FILE);
			foreach ($result as $shop)
			{
				$filename = $info['dirname'].'/'.$info['filename'].'-'.$shop['id_shop'].'.'.$info['extension'];
				if (file_exists($filename) && filesize($filename))
				{
					$fp = fopen($filename, 'r');
					$fstat = fstat($fp);
					fclose($fp);
					$xml = simplexml_load_file($filename);

					$nbPages = count($xml->url);
					$sitemap_uri = 'http://'.$shop['domain'].$shop['uri'].$info['filename'].'-'.$shop['id_shop'].'.'.$info['extension'];

					$this->_html .= '<h2>'.$this->l('Sitemap for: ').$shop['domain'].$shop['uri'].'</h2>';
					$this->_html .= '<p>'.$this->l('Your Google sitemap file is online at the following address:').'<br />
					<a href="'.$sitemap_uri.'" target="_blank"><b>'.$sitemap_uri.'</b></a></p><br />';

					$this->_html .= $this->l('Update:').' <b>'.utf8_encode(strftime('%A %d %B %Y %H:%M:%S',$fstat['mtime'])).'</b><br />';
					$this->_html .= $this->l('Filesize:').' <b>'.number_format(($fstat['size']*.000001), 3).'MB</b><br />';
					$this->_html .= $this->l('Indexed pages:').' <b>'.$nbPages.'</b><br /><br />';
				}
			}
		}
		elseif (file_exists(GSITEMAP_FILE) && filesize(GSITEMAP_FILE))
		{
			$fp = fopen(GSITEMAP_FILE, 'r');
			$fstat = fstat($fp);
			fclose($fp);
			$xml = simplexml_load_file(GSITEMAP_FILE);

			$nbPages = count($xml->url);

			$this->_html .= '<p>'.$this->l('Your Google sitemap file is online at the following address:').'<br />
			<a href="'.Tools::getShopDomain(true, true).__PS_BASE_URI__.'sitemap.xml" target="_blank"><b>'.Tools::getShopDomain(true, true).__PS_BASE_URI__.'sitemap.xml</b></a></p><br />';

			$this->_html .= $this->l('Update:').' <b>'.utf8_encode(strftime('%A %d %B %Y %H:%M:%S',$fstat['mtime'])).'</b><br />';
			$this->_html .= $this->l('Filesize:').' <b>'.number_format(($fstat['size']*.000001), 3).'MB</b><br />';
			$this->_html .= $this->l('Indexed pages:').' <b>'.$nbPages.'</b><br /><br />';
		}
	}

}
