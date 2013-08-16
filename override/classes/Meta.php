<?php
class Meta extends MetaCore
{
	public static function getMetaTags($id_lang, $page_name, $title = '')
        {
                global $maintenance;
                if (!(isset($maintenance) && (!in_array(Tools::getRemoteAddr(), explode(',', Configuration::get('PS_MAINTENANCE_IP'))))))
                {
                        if ($page_name == 'product' && ($id_product = Tools::getValue('id_product')))
                                return Meta::getProductMetas($id_product, $id_lang, $page_name);
                        elseif ($page_name == 'category' && ($id_category = Tools::getValue('id_category')))
                                return Meta::getCategoryMetas($id_category, $id_lang, $page_name, $title);
                        elseif ($page_name == 'manufacturer' && ($id_manufacturer = Tools::getValue('id_manufacturer')))
                                return Meta::getManufacturerMetas($id_manufacturer, $id_lang, $page_name);
                        elseif ($page_name == 'supplier' && ($id_supplier = Tools::getValue('id_supplier')))
                                return Meta::getSupplierMetas($id_supplier, $id_lang, $page_name);
                        elseif ($page_name == 'cms' && ($id_cms = Tools::getValue('id_cms')))
                                return Meta::getCmsMetas($id_cms, $id_lang, $page_name);
                        elseif ($page_name == 'cms' && ($id_cms_category = Tools::getValue('id_cms_category')))
                                return Meta::getCmsCategoryMetas($id_cms_category, $id_lang, $page_name);
                        elseif ($page_name == 'search')
                                return Meta::getSearchMetas($id_lang, $page_name);
                }

                return Meta::getHomeMetas($id_lang, $page_name);
        }
	public static function getSearchMetas($id_lang, $page_name){
		$sql = 'SELECT term as name, `title` as meta_title, `description` as meta_description, `keywords` as  `meta_keywords`
                        	FROM '._DB_PREFIX_.'searchterm s
                                WHERE term = \''.pSQL($_GET['search_query']).'\'';
                if ($row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql))
                {
                        if (empty($row['meta_description']))
                                $row['meta_description'] = strip_tags($row['meta_description']);
                        if (!empty($row['meta_title']))
                                $row['meta_title'] = $row['meta_title'].' - '.Configuration::get('PS_SHOP_NAME');
                        return Meta::completeMetaTags($row, $row['name']);
                }

                return Meta::getHomeMetas($id_lang, $page_name);
        }
}
