<?php
/*
* 2007-2013 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2013 PrestaShop SA
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

class CMSCore extends ObjectModel
{
	public $title;
	public $description_short;
	public $comment;
	public $date_add;
	public $date_upd;

	public $meta_title;
	public $meta_description;
	public $meta_keywords;
	public $content;
	public $link_rewrite;
	public $id_cms_category;
	public $position;
	public $active;

	public  $image_dir;

 	protected $fieldsValidate = array('id_cms_category' => 'isUnsignedInt');
	protected $fieldsRequiredLang = array('title', 'link_rewrite');
	protected $fieldsSizeLang = array('title' => 255,'description_short' => 255,'meta_description' => 255, 'meta_keywords' => 255, 'meta_title' => 128, 'link_rewrite' => 128, 'content' => 3999999999999);
	protected $fieldsValidateLang = array('meta_description' => 'isGenericName', 'meta_keywords' => 'isGenericName', 'meta_title' => 'isGenericName', 'link_rewrite' => 'isLinkRewrite', 'content' => 'isString', 'title' => 'isGenericName', 'description_short' => 'isString');

	protected $table = 'cms';
	protected $identifier = 'id_cms';
	public		$id_image = 'default';
	
	protected	$webserviceParameters = array(
		'objectNodeName' => 'content',
		'objectsNodeName' => 'content_management_system',
	);

	public function __construct($id = NULL, $id_lang = NULL){
		$this->image_dir=_PS_IMG_DIR_.'cms/';
		parent::__construct($id, $id_lang);
	}

	public function getFields() 
	{ 
		parent::validateFields();
		$fields['id_cms'] = (int)$this->id;
		$fields['id_cms_category'] = (int)$this->id_cms_category;
		$fields['position'] = (int)$this->position;
		$fields['active'] = (int)$this->active;
		$fields['date_add'] = pSQL($this->date_add);
		$fields['date_upd'] = pSQL($this->date_upd);
		$fields['comment'] = (int)$this->comment;
		return $fields;	 
	}
	
	public function getTranslationsFieldsChild()
	{
		parent::validateFieldsLang();

		$fieldsArray = array('meta_title', 'meta_description', 'meta_keywords', 'link_rewrite', 'title');
		$fields = array();
		$languages = Language::getLanguages(false);
		$defaultLanguage = (int)(_PS_LANG_DEFAULT_);
		foreach ($languages as $language)
		{
			$fields[$language['id_lang']]['id_lang'] = (int)($language['id_lang']);
			$fields[$language['id_lang']][$this->identifier] = (int)($this->id);
			$fields[$language['id_lang']]['content'] = (isset($this->content[$language['id_lang']])) ? pSQL($this->content[$language['id_lang']], true) : '';
			$fields[$language['id_lang']]['description_short'] = (isset($this->description_short[$language['id_lang']])) ? pSQL($this->description_short[$language['id_lang']], true) : '';
			foreach ($fieldsArray as $field)
			{
				if (!Validate::isTableOrIdentifier($field))
					die(Tools::displayError());
				if (isset($this->{$field}[$language['id_lang']]) AND !empty($this->{$field}[$language['id_lang']]))
					$fields[$language['id_lang']][$field] = pSQL($this->{$field}[$language['id_lang']]);
				elseif (in_array($field, $this->fieldsRequiredLang))
					$fields[$language['id_lang']][$field] = pSQL($this->{$field}[$defaultLanguage]);
				else
					$fields[$language['id_lang']][$field] = '';
			}
		}
		return $fields;
	}
	
	public function add($autodate = true, $nullValues = false)
	{ 
		$this->position = CMS::getLastPosition((int)$this->id_cms_category);
		return parent::add($autodate, true); 
	}

	public function update($nullValues = false)
	{
		if (parent::update($nullValues))
			return $this->cleanPositions($this->id_cms_category);
		return false;
	}
	
	public function delete()
	{
	 	if (parent::delete())
		 {
			if ($this->id == Configuration::get('PS_CONDITIONS_CMS_ID'))
			{
				 Configuration::updateValue('PS_CONDITIONS', 0);
				 Configuration::updateValue('PS_CONDITIONS_CMS_ID', 0);
			}
			$this->cleanCategories();
			$this->cleanProducts();
			return $this->cleanPositions($this->id_cms_category);
		 }
		return false;
	}

	public static function getLinks($id_lang, $selection = null, $active = true, $ssl = false)
	{
		$results = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS('
		SELECT c.id_cms, cl.link_rewrite, cl.meta_title
		FROM '._DB_PREFIX_.'cms c
		LEFT JOIN '._DB_PREFIX_.'cms_lang cl ON (c.id_cms = cl.id_cms AND cl.id_lang = '.(int)$id_lang.')
		WHERE 1
		'.(!empty($selection) ? ' AND c.id_cms IN ('.implode(',', array_map('intval', $selection)).')' : '').
		($active ? ' AND c.`active` = 1 ' : '').
		'ORDER BY c.`position`');

		if ($results)
		{
			$link = new Link();
			foreach ($results as &$row)
				$row['link'] = $link->getCMSLink((int)$row['id_cms'], $row['link_rewrite'], (bool)$ssl, (int)$id_lang);
		}

		return $results;
	}
	
	public static function listCms($id_lang = null, $id_block = false, $active = true)
	{
		if (empty($id_lang))
			$id_lang = (int)_PS_LANG_DEFAULT_;

		return Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS('
		SELECT c.id_cms, l.meta_title
		FROM  '._DB_PREFIX_.'cms c
		JOIN '._DB_PREFIX_.'cms_lang l ON (c.id_cms = l.id_cms)
		'.($id_block ? 'JOIN '._DB_PREFIX_.'block_cms b ON (c.id_cms = b.id_cms)' : '').'
		WHERE l.id_lang = '.(int)$id_lang.($id_block ? ' AND b.id_block = '.(int)$id_block : '').($active ? ' AND c.`active` = 1 ' : '').'
		ORDER BY c.`position`');
	}
	
	/**
	 * @deprecated
	 */
	public static function isInBlock($id_cms, $id_block)
	{
		Tools::displayAsDeprecated();
		Db::getInstance()->getRow('
		SELECT id_cms FROM '._DB_PREFIX_.'block_cms
		WHERE id_block = '.(int)$id_block.' AND id_cms = '.(int)$id_cms);
		
		return (Db::getInstance()->NumRows());
	}
	
	/**
	 * @deprecated
	 */
	public static function updateCmsToBlock($cms, $id_block)
	{
		Tools::displayAsDeprecated();
		Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'block_cms` WHERE `id_block` = '.(int)$id_block);

		$list = '';
		foreach ($cms as $id_cms)
			$list .= '('.(int)($id_block).', '.(int)($id_cms).'),';
		$list = rtrim($list, ',');
		
		if (!empty($list))
			Db::getInstance()->Execute('INSERT INTO '._DB_PREFIX_.'block_cms (id_block, id_cms) VALUES '.pSQL($list));
			
		return true;
	}
	
	public function updatePosition($way, $position)
	{
		if (!$res = Db::getInstance()->ExecuteS('
		SELECT cp.`id_cms`, cp.`position`, cp.`id_cms_category` 
		FROM `'._DB_PREFIX_.'cms` cp
		WHERE cp.`id_cms_category` = '.(int)$this->id_cms_category.' 
		ORDER BY cp.`position` ASC'))
			return false;
		
		foreach ($res as $cms)
			if ((int)$cms['id_cms'] == (int)$this->id)
				$movedCms = $cms;
		
		if (!isset($movedCms) || !isset($position))
			return false;
		
		// < and > statements rather than BETWEEN operator
		// since BETWEEN is treated differently according to databases
		return (Db::getInstance()->Execute('
			UPDATE `'._DB_PREFIX_.'cms`
			SET `position`= `position` '.($way ? '- 1' : '+ 1').'
			WHERE `position` 
			'.($way 
				? '> '.(int)$movedCms['position'].' AND `position` <= '.(int)$position
				: '< '.(int)$movedCms['position'].' AND `position` >= '.(int)$position).'
			AND `id_cms_category`='.(int)$movedCms['id_cms_category'])
		&& Db::getInstance()->Execute('
			UPDATE `'._DB_PREFIX_.'cms`
			SET `position` = '.(int)$position.'
			WHERE `id_cms` = '.(int)$movedCms['id_cms'].'
			AND `id_cms_category`='.(int)$movedCms['id_cms_category']));
	}
	
	public static function cleanPositions($id_category)
	{
		$result = Db::getInstance()->ExecuteS('
		SELECT `id_cms`
		FROM `'._DB_PREFIX_.'cms`
		WHERE `id_cms_category` = '.(int)$id_category.'
		ORDER BY `position`');

		$sizeof = count($result);
		for ($i = 0; $i < $sizeof; ++$i)
			Db::getInstance()->Execute('
			UPDATE `'._DB_PREFIX_.'cms`
			SET `position` = '.(int)$i.'
			WHERE `id_cms_category` = '.(int)$id_category.' AND `id_cms` = '.(int)$result[$i]['id_cms'].'
			LIMIT 1');

		return true;
	}
	
	public static function getLastPosition($id_category)
	{
		return (Db::getInstance()->getValue('SELECT MAX(position)+1 FROM `'._DB_PREFIX_.'cms` WHERE `id_cms_category` = '.(int)($id_category)));
	}
	
	public static function getCMSPages($id_lang = null, $id_cms_category = null, $active = true)
	{
		return Db::getInstance()->ExecuteS('
		SELECT *
		FROM `'._DB_PREFIX_.'cms` c
		JOIN `'._DB_PREFIX_.'cms_lang` l ON (c.id_cms = l.id_cms)'.
		(isset($id_cms_category) ? 'WHERE `id_cms_category` = '.(int)($id_cms_category) : '').
		($active ? ' AND c.`active` = 1 ' : '').' AND l.id_lang = '.(int)($id_lang).'
		ORDER BY `position`');
	}

    public static function getUrlRewriteInformations($id_cms)
	{
		return Db::getInstance()->ExecuteS('
		SELECT l.`id_lang`, c.`link_rewrite`
		FROM `'._DB_PREFIX_.'cms_lang` c
		LEFT JOIN  `'._DB_PREFIX_.'lang` l ON (c.`id_lang` = l.`id_lang`)
		WHERE c.`id_cms` = '.(int)$id_cms.'	AND l.`active` = 1');
	}

	/**
	 * Get categories where product is indexed
	 *
	 * @param integer $id_product Product id
	 * @return array Categories where product is indexed
	 */
	public static function getIndexedCategories($id_cms)
	{
		$result = Db::getInstance()->ExecuteS('
		SELECT `id_cms_category`
		FROM `'._DB_PREFIX_.'cms_category_cms`
		WHERE `id_cms` = '.(int)$id_cms);
		$return = array();
		foreach($result as $val)
			$return[] = $val['id_cms_category'];
		return $return;
	}

	public function getCategories($id_lang)
	{
		$result = Db::getInstance()->ExecuteS('
		SELECT ccc.`id_cms_category`, ccl.*
		FROM `'._DB_PREFIX_.'cms_category_cms` ccc
		LEFT JOIN `'._DB_PREFIX_.'cms_category_lang` ccl ON (ccc.`id_cms_category` = ccl.`id_cms_category` AND ccl.`id_lang`='.(int)$id_lang.')
		WHERE `id_cms` = '.(int)$this->id);
		global $link;
		foreach($result as &$row)
			$row['link'] = $link->getCMSCategoryLink((int)$row['id_cms_category'], $row['link_rewrite'], (int)$id_lang);
		unset($row);
		return $result;
	}

	public function cleanCategories()
	{
		Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'cms_category_cms` WHERE `id_cms` = '.(int)$this->id);
	}

	public function addCategories($groups)
	{
		foreach ($groups as $group)
		{
			$row = array('id_cms' => (int)$this->id, 'id_cms_category' => (int)$group);
			Db::getInstance()->AutoExecute(_DB_PREFIX_.'cms_category_cms', $row, 'INSERT');
		}
	}

	public function addProducts($groups)
	{
		foreach ($groups as $group)
		{
			$row = array('id_cms' => (int)$this->id, 'id_product' => (int)$group);
			Db::getInstance()->AutoExecute(_DB_PREFIX_.'cms_product', $row, 'INSERT');
		}
	}

	public function getProductsLite($id_lang)
	{
		$result = Db::getInstance()->ExecuteS('
			SELECT cp.`id_product`, pl.`name`
			FROM '._DB_PREFIX_.'cms_product cp
		 	LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON cp.`id_product` = pl.`id_product` AND pl.`id_lang` = '.(int)$id_lang.'
		 WHERE cp.`id_cms` = '.(int)$this->id);
		return $result;
	}

	public function getProducts($id_lang, $active = true)
	{
		$sql = '
		SELECT p.*, pa.`id_product_attribute`, pl.`description`, pl.`description_short`, pl.`available_now`, pl.`available_later`, pl.`link_rewrite`,
		pl.`meta_description`, pl.`meta_keywords`, pl.`meta_title`, pl.`name`, i.`id_image`, il.`legend`, m.`name` manufacturer_name,
		tl.`name` tax_name, t.`rate`, cl.`name` category_default, DATEDIFF(p.`date_add`, DATE_SUB(NOW(),
		INTERVAL '.(Validate::isUnsignedInt(Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) ? Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20).' DAY)) > 0 new
		FROM `'._DB_PREFIX_.'cms_product` cp
		LEFT JOIN `'._DB_PREFIX_.'product` p ON (p.`id_product` = cp.`id_product`)
		LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa ON (p.`id_product` = pa.`id_product` AND default_on = 1)
		LEFT JOIN `'._DB_PREFIX_.'category_lang` cl ON (p.`id_category_default` = cl.`id_category` AND cl.`id_lang` = '.(int)$id_lang.')
		LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (p.`id_product` = pl.`id_product` AND pl.`id_lang` = '.(int)$id_lang.')
		LEFT JOIN `'._DB_PREFIX_.'image` i ON (i.`id_product` = p.`id_product` AND i.`cover` = 1)
		LEFT JOIN `'._DB_PREFIX_.'image_lang` il ON (i.`id_image` = il.`id_image` AND il.`id_lang` = '.(int)$id_lang.')
		LEFT JOIN `'._DB_PREFIX_.'tax_rule` tr ON (p.`id_tax_rules_group` = tr.`id_tax_rules_group` AND tr.`id_country` = '.(int)Country::getDefaultCountryId().' AND tr.`id_state` = 0)
		LEFT JOIN `'._DB_PREFIX_.'tax` t ON (t.`id_tax` = tr.`id_tax`)
		LEFT JOIN `'._DB_PREFIX_.'tax_lang` tl ON (t.`id_tax` = tl.`id_tax` AND tl.`id_lang` = '.(int)$id_lang.')
		LEFT JOIN `'._DB_PREFIX_.'manufacturer` m ON m.`id_manufacturer` = p.`id_manufacturer`
		WHERE cp.`id_cms` = '.(int)$this->id.($active ? ' AND p.`active` = 1' : '');

		$result = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS($sql);

		if (!$result)
			return false;

		/* Modify SQL result */
		return Product::getProductsProperties((int)$id_lang, $result);
	}

	public function cleanProducts($id_product = false)
	{
		return Db::getInstance()->Execute('
		DELETE FROM `'._DB_PREFIX_.'cms_product`
		WHERE `id_cms` = '.(int)$this->id).($id_product?' AND `id_product`='.(int)$id_product:'');
	}
}