<?php
/*
* 2007-2012 PrestaShop
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

include_once(PS_ADMIN_DIR.'/../classes/AdminTab.php');

class AdminCMS extends AdminTab
{	
	private $_category;

	public function __construct()
	{
	 	$this->table = 'cms';
	 	$this->className = 'CMS';
	 	$this->lang = true;
	 	$this->edit = true;
	 	$this->view = true;
	 	$this->delete = true;

		$this->fieldImageSettings = array('name' => 'logo', 'dir' => 'cms');
		
		$this->fieldsDisplay = array(
			'id_cms' => array('title' => $this->l('ID'), 'align' => 'center', 'width' => 25),
			'date_add' => array('title' => $this->l('Date'), 'width' => 100),
			'title' => array('title' => $this->l('Title'), 'width' => 200),
			'description_short' => array('title' => $this->l('Description'), 'width' => 280, 'html' => true),
			'position' => array('title' => $this->l('Position'), 'width' => 40,'filter_key' => 'position', 'align' => 'center', 'position' => 'position'),
			'active' => array('title' => $this->l('Enabled'), 'width' => 25, 'align' => 'center', 'active' => 'status', 'type' => 'bool', 'orderby' => false)
			);
			
		$this->_category = AdminCMSContent::getCurrentCMSCategory();
		$this->_join = '
		LEFT JOIN `'._DB_PREFIX_.'cms_category` c ON (c.`id_cms_category` = a.`id_cms_category`)';
		$this->_select = 'a.position ';
		$this->_filter = 'AND c.id_cms_category = '.(int)($this->_category->id);
		
		parent::__construct();
	}

	public function viewcms()
	{
		global $cookie, $link;
		if (($id_cms = (int)(Tools::getValue('id_cms'))) AND $cms = new CMS($id_cms, (int)($cookie->id_lang)) AND Validate::isLoadedObject($cms))
		{
			$redir = $link->getCMSLink($cms);
			if (!$cms->active)
			{
				$admin_dir = dirname($_SERVER['PHP_SELF']);
				$admin_dir = substr($admin_dir, strrpos($admin_dir,'/') + 1);
				$redir .= '&adtoken='.Tools::encrypt('PreviewCMS'.$cms->id).'&ad='.$admin_dir;
			}
			Tools::redirectAdmin($redir);
		}
	}
	
	public function displayForm($isMainTab = true)
	{
		global $currentIndex, $cookie, $link;
		parent::displayForm();
		
		$obj = $this->loadObject(true);
		$iso = Language::getIsoById((int)($cookie->id_lang));
		$redir = $link->getCMSLink($obj);
		if (!$obj->active)
		{
			$admin_dir = dirname($_SERVER['PHP_SELF']);
			$admin_dir = substr($admin_dir, strrpos($admin_dir,'/') + 1);
			$redir .= '&adtoken='.Tools::encrypt('PreviewCMS'.$obj->id).'&ad='.$admin_dir;
		}
		$divLangName = 'meta_title¤meta_description¤meta_keywords¤ccontent¤clink_rewrite';
		if($obj->id)
		echo '
		<div class="warn draft">
			<p>

			<a href="'.$redir.'" class="button" style="float: right;">'.$this->l('Preview').'</a>
			<br class="clear" />
			</p>
		</div>';
		echo '
		<form action="'.$currentIndex.'&submitAddcms=1&token='.Tools::getAdminTokenLite('AdminCMSContent').'" method="post" name="cms" id="cms" enctype="multipart/form-data">
			'.($obj->id ? '<input type="hidden" name="id_'.$this->table.'" value="'.$obj->id.'" />' : '').'
			<fieldset><legend><img src="../img/admin/cms.gif" />'.$this->l('CMS page').'</legend>';
			
		// META TITLE
		echo '
				<label>'.$this->l('Title').' </label>
				<div class="margin-form">';
		foreach ($this->_languages as $language)
			echo '	<div id="title_'.$language['id_lang'].'" style="display: '.($language['id_lang'] == $this->_defaultFormLanguage ? 'block' : 'none').';float: left;" class="redactor">
						<input size="40" type="text" onkeyup="copyMeta2friendlyURL();" id="name_'.$language['id_lang'].'" name="title_'.$language['id_lang'].'" value="'.htmlentities($this->getFieldValue($obj, 'title', (int)($language['id_lang'])), ENT_COMPAT, 'UTF-8').'" /><sup> *</sup>
					</div>';

		$this->displayFlags($this->_languages, $this->_defaultFormLanguage, $divLangName, 'meta_title');
		echo '	</div><div class="clear space">&nbsp;</div>';

		// Description
		echo '	<label>'.$this->l('Description').' </label>
				<div class="margin-form">';
		foreach ($this->_languages as $language)
			echo '	<div id="cdescription_short_'.$language['id_lang'].'" style="display: '.($language['id_lang'] == $this->_defaultFormLanguage ? 'block' : 'none').';float: left;" class="redactor">
						<textarea class="rte" cols="20" rows="30" id="description_short_'.$language['id_lang'].'" name="description_short_'.$language['id_lang'].'">'.htmlentities(stripslashes($this->getFieldValue($obj, 'description_short', $language['id_lang'])), ENT_COMPAT, 'UTF-8').'</textarea>
					</div>';
		$this->displayFlags($this->_languages, $this->_defaultFormLanguage, $divLangName, 'ccontent');
		echo '	</div><div class="clear space">&nbsp;</div>';
		// CONTENT
		echo '	<label>'.$this->l('Page content').' </label>
				<div class="margin-form">';
		foreach ($this->_languages as $language)
			echo '	<div id="ccontent_'.$language['id_lang'].'" style="display: '.($language['id_lang'] == $this->_defaultFormLanguage ? 'block' : 'none').';float: left;" class="redactor">
						<textarea class="rte" cols="80" rows="30" id="content_'.$language['id_lang'].'" name="content_'.$language['id_lang'].'">'.htmlentities(stripslashes($this->getFieldValue($obj, 'content', $language['id_lang'])), ENT_COMPAT, 'UTF-8').'</textarea>
					</div>';
		$this->displayFlags($this->_languages, $this->_defaultFormLanguage, $divLangName, 'ccontent');
		echo '	</div><div class="clear space">&nbsp;</div>
				<label>'.$this->l('Enable:').' </label>
				<div class="margin-form">
					<input type="radio" name="active" id="active_on" onclick="toggleDraftWarning(false);" value="1" '.($this->getFieldValue($obj, 'active') ? 'checked="checked" ' : '').'/>
					<label class="t" for="active_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Enabled').'" title="'.$this->l('Enabled').'" /></label>
					<input type="radio" name="active" id="active_off" onclick="toggleDraftWarning(true);" value="0" '.(!$this->getFieldValue($obj, 'active') ? 'checked="checked" ' : '').'/>
					<label class="t" for="active_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('Disabled').'" title="'.$this->l('Disabled').'" /></label>
				</div>';

		echo '
				<label>'.$this->l('Comments:').' </label>
				<div class="margin-form">
					<input type="radio" name="comment" id="comment_on" value="1" '.($this->getFieldValue($obj, 'comment') ? 'checked="checked" ' : '').'/>
					<label class="t" for="comment_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Enabled').'" title="'.$this->l('Enabled').'" /></label>
					<input type="radio" name="comment" id="comment_off" value="0" '.(!$this->getFieldValue($obj, 'comment') ? 'checked="checked" ' : '').'/>
					<label class="t" for="comment_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('Disabled').'" title="'.$this->l('Disabled').'" /></label>
				</div>';
		// Date add
		echo '
				<label for="date_add">'.$this->l('Publication date:').' </label>
				<div class="margin-form">
					<input type="text" name="date_add" id="date_add" value="'.$this->getFieldValue($obj, 'date_add').'" class="hasDatepicker" />
				</div>';

		echo '<label>'.$this->l('Image:').' </label>
				<div class="margin-form">';
		echo 		$this->displayImage($obj->id, _PS_IMG_DIR_.'cms/'.$obj->id.'.jpg', 350, NULL, Tools::getAdminToken('AdminCMSContent'.(int)(Tab::getIdFromClassName('AdminCMSContent')).(int)($cookie->id_employee)), true);
		echo '	<input type="file" name="logo" />
					<p>'.$this->l('Upload picture from your computer').'</p>
				</div>';
		echo '</fieldset><br /><fieldset><legend><img src="../img/admin/cms.gif" />'.$this->l('Categories').'</legend>';

		echo '<label>'.$this->l('CMS Category default:').' </label>
				<div class="margin-form">
					<select name="id_cms_category">';
		$categories = CMSCategory::getCategories((int)($cookie->id_lang), false);
		CMSCategory::recurseCMSCategory($categories, $categories[0][1], 1, $this->getFieldValue($obj, 'id_cms_category'));
		echo '
					</select>
				</div>';

		echo '<label>'.$this->l('CMS Categories:').' </label>
				<div class="margin-form">
		<div style="overflow: auto; padding-top: 0.6em;" id="categoryList">
							<table cellspacing="0" cellpadding="0" class="table">
								<tr>
									<th><input type="checkbox" name="checkme" class="noborder" onclick="checkDelBoxes(this.form, \'categoryBox[]\', this.checked)" /></th>
									<th>'.$this->l('ID').'</th>
									<th style="width: 600px">'.$this->l('Name').'</th>
								</tr>';
								$done = array();
								$index = array();

								$categoryBox = Tools::getValue('categoryBox');
								if ($categoryBox != '')
								{
									$categoryBox = @unserialize($categoryBox);
									foreach ($categoryBox as $k => $row)
										$index[] = $row;
								}
								elseif ($obj->id)
									$index = CMS::getIndexedCategories($obj->id);
								$this->recurseCategoryForInclude($obj->id, $index, $categories, $categories[0][1], 1, (Tools::getValue('id_cms_category')?(int)(Tools::getValue('id_cms_category')):$obj->id_cms_category));
		echo '				</table>
							<p style="padding:0px; margin:0px 0px 10px 0px;">'.$this->l('Mark all checkbox(es) of categories in which cms is to appear').'<sup> *</sup></p>
						</div></div>';

		echo '</fieldset><br /><fieldset><legend><img src="../img/admin/cms.gif" />'.$this->l('Products').'</legend>';

		$accessories = (Tools::getValue('id_cms') ? $obj->getProductsLite((int)$cookie->id_lang) : array());

		echo '<div id="divAccessories">';
		foreach ($accessories as $accessory)
			echo $accessory['name'].(!empty($accessory['reference']) ? ' ('.$accessory['reference'].')' : '').' <span onclick="delAccessoryProduct('.$accessory['id_product'].');" style="cursor: pointer;"><img src="../img/admin/delete.gif" class="middle" alt="" /></span><br />';
		echo '</div>';

		echo '<div class="margin-form">

			<input type="hidden" name="inputAccessories" id="inputAccessories" value=" ';
		foreach ($accessories as $accessory) echo $accessory['id_product'].'-';
		echo '" />

			<input type="hidden" name="nameAccessories" id="nameAccessories" value=" ';
		foreach ($accessories as $accessory) echo $accessory['name'].'¤';
		echo '" />';

		echo '<script type="text/javascript">
					var formProduct;
					var accessories = new Array();
				</script>

			<link rel="stylesheet" type="text/css" href="'.__PS_BASE_URI__.'css/jquery.autocomplete.css" />
			<script type="text/javascript" src="'.__PS_BASE_URI__.'js/jquery/jquery.autocomplete.js"></script>
			<script type="text/javascript" src="'.__PS_BASE_URI__.'js/admin-cms.js"></script>';

		echo '<div id="ajax_choose_product" style="padding:6px; padding-top:2px; width:600px;">
							<p class="clear">'.$this->l('Begin typing the first letters of the product name, then select the product from the drop-down list:').'</p>
							<input type="text" value="" id="product_autocomplete_input" />
							<img onclick="$(this).prev().search();" style="cursor: pointer;" src="../img/admin/add.gif" alt="'.$this->l('Add an accessory').'" title="'.$this->l('Add an accessory').'" />
						</div>
						<script type="text/javascript">
							urlToCall = null;
							/* function autocomplete */
							$(function() {
								$(\'#product_autocomplete_input\')
									.autocomplete(\'ajax_products_list.php\', {
										minChars: 1,
										autoFill: true,
										max:20,
										matchContains: true,
										mustMatch:true,
										scroll:false,
										cacheLength:0,
										formatItem: function(item){ return item[1]+\' - \'+item[0]; }
									}).result(addAccessoryProduct);
								$(\'#product_autocomplete_input\').setOptions({
									extraParams: {excludeIds : $(\'#inputAccessories\').val().replace(/\-/g,\',\').replace(/\,$/,\'\')}
								});
							});
						</script>';
		echo '	</div>';


		 echo '</fieldset><br /><fieldset><legend><img src="../img/admin/cms.gif" />'.$this->l('SEO').'</legend>';

		// META TITLE
		echo '	<label>'.$this->l('Meta title').' </label>
				<div class="margin-form">';
		foreach ($this->_languages as $language)
			echo '	<div id="meta_title_'.$language['id_lang'].'" style="display: '.($language['id_lang'] == $this->_defaultFormLanguage ? 'block' : 'none').';float: left;" class="redactor">
						<input size="50" type="text" name="meta_title_'.$language['id_lang'].'" value="'.htmlentities($this->getFieldValue($obj, 'meta_title', (int)($language['id_lang'])), ENT_COMPAT, 'UTF-8').'" />
					</div>';
		$this->displayFlags($this->_languages, $this->_defaultFormLanguage, $divLangName, 'meta_description');
		echo '	</div><div class="clear space">&nbsp;</div>';

		// META DESCRIPTION
		echo '	<label>'.$this->l('Meta description').' </label>
				<div class="margin-form">';
		foreach ($this->_languages as $language)
			echo '	<div id="meta_description_'.$language['id_lang'].'" style="display: '.($language['id_lang'] == $this->_defaultFormLanguage ? 'block' : 'none').';float: left;" class="redactor">
						<input size="50" type="text" name="meta_description_'.$language['id_lang'].'" value="'.htmlentities($this->getFieldValue($obj, 'meta_description', (int)($language['id_lang'])), ENT_COMPAT, 'UTF-8').'" />
					</div>';
		$this->displayFlags($this->_languages, $this->_defaultFormLanguage, $divLangName, 'meta_description');
		echo '	</div><div class="clear space">&nbsp;</div>';

		// META KEYWORDS
		echo '	<label>'.$this->l('Meta keywords').' </label>
				<div class="margin-form">';
		foreach ($this->_languages as $language)
			echo '	<div id="meta_keywords_'.$language['id_lang'].'" style="display: '.($language['id_lang'] == $this->_defaultFormLanguage ? 'block' : 'none').';float: left;" class="redactor">
						<input size="50" type="text" name="meta_keywords_'.$language['id_lang'].'" value="'.htmlentities($this->getFieldValue($obj, 'meta_keywords', (int)($language['id_lang'])), ENT_COMPAT, 'UTF-8').'" />
					</div>';
		$this->displayFlags($this->_languages, $this->_defaultFormLanguage, $divLangName, 'meta_keywords');
		echo '	</div><div class="clear space">&nbsp;</div>';

		// LINK REWRITE
		echo '	<label>'.$this->l('Friendly URL').' </label>
				<div class="margin-form">';
		foreach ($this->_languages as $language)
			echo '	<div id="clink_rewrite_'.$language['id_lang'].'" style="display: '.($language['id_lang'] == $this->_defaultFormLanguage ? 'block' : 'none').';float: left;" class="redactor">
						<input size="30" type="text"  id="input_link_rewrite_'.$language['id_lang'].'" name="link_rewrite_'.$language['id_lang'].'" onkeyup="this.value = str2url(this.value); updateFriendlyURL();" value="'.htmlentities($this->getFieldValue($obj, 'link_rewrite', (int)($language['id_lang'])), ENT_COMPAT, 'UTF-8').'" /><sup> *</sup>
					</div>';
		$this->displayFlags($this->_languages, $this->_defaultFormLanguage, $divLangName, 'clink_rewrite');
		echo '	</div><div class="clear space">&nbsp;</div></fieldset>';
		
		// SUBMIT
		echo '	<div class="margin-form space">
					<input type="submit" value="'.$this->l('   Save   ').'" name="submitAdd'.$this->table.'" class="button" />
					<input type="submit" value="'.$this->l('   Save and Stay  ').'" name="submitAdd'.$this->table.'AndStay" class="button" />
				</div>
				<div class="small"><sup>*</sup> '.$this->l('Required field').'</div>
			<br />
		</form>';
		// TinyMCE
		global $cookie;
		$iso = Language::getIsoById((int)($cookie->id_lang));
		$isoTinyMCE = (file_exists(_PS_ROOT_DIR_.'/js/tiny_mce/langs/'.$iso.'.js') ? $iso : 'en');
		$ad = dirname($_SERVER["PHP_SELF"]);
		echo '
			<script type="text/javascript">	
			var iso = \''.$isoTinyMCE.'\' ;
			var pathCSS = \''._THEME_CSS_DIR_.'\' ;
			var ad = \''.$ad.'\' ;
			</script>
			<script type="text/javascript" src="'.__PS_BASE_URI__.'js/tiny_mce/tiny_mce.js"></script>
			<script type="text/javascript" src="'.__PS_BASE_URI__.'js/tinymce.inc.js"></script>';
		include_once('functions.php');
		includeDatepicker(array('date_add'), true);
	}
	
	public function display($token = NULL)
	{
		global $currentIndex, $cookie;
		
		if (($id_cms_category = (int)Tools::getValue('id_cms_category')))
			$currentIndex .= '&id_cms_category='.$id_cms_category;
		$this->getList((int)($cookie->id_lang), !$cookie->__get($this->table.'Orderby') ? 'position' : NULL, !$cookie->__get($this->table.'Orderway') ? 'ASC' : NULL);
		//$this->getList((int)($cookie->id_lang));
		if (!$id_cms_category)
			$id_cms_category = 1;
		echo '<h3>'.(!$this->_listTotal ? ($this->l('No pages found')) : ($this->_listTotal.' '.($this->_listTotal > 1 ? $this->l('pages') : $this->l('page')))).' '.
		$this->l('in category').' "'.stripslashes(CMSCategory::hideCMSCategoryPosition($this->_category->getName())).'"</h3>';
		echo '<a href="'.$currentIndex.'&id_cms_category='.$id_cms_category.'&add'.$this->table.'&token='.Tools::getAdminTokenLite('AdminCMSContent').'"><img src="../img/admin/add.gif" border="0" /> '.$this->l('Add a new page').'</a>
		<div style="margin:10px;">';
		$this->displayList($token);
		echo '</div>';
	}
	
	public function displayList($token = NULL)
	{
		global $currentIndex;
		
		/* Display list header (filtering, pagination and column names) */
		$this->displayListHeader($token);
		if (!sizeof($this->_list))
			echo '<tr><td class="center" colspan="'.(sizeof($this->fieldsDisplay) + 2).'">'.$this->l('No items found').'</td></tr>';

		/* Show the content of the table */
		$this->displayListContent($token);

		/* Close list table and submit button */
		$this->displayListFooter($token);
	}

	/**
	 * Build a categories tree
	 *
	 * @param array $indexedCategories Array with categories where product is indexed (in order to check checkbox)
	 * @param array $categories Categories to list
	 * @param array $current Current category
	 * @param integer $id_category Current category id
	 */
	public static function recurseCategoryForInclude($id_obj, $indexedCategories, $categories, $current, $id_category = 1, $id_category_default = NULL, $has_suite = array())
	{
		global $done;
		static $irow;

		if (!isset($done[$current['infos']['id_parent']]))
			$done[$current['infos']['id_parent']] = 0;
		$done[$current['infos']['id_parent']] += 1;

		$todo = sizeof($categories[$current['infos']['id_parent']]);
		$doneC = $done[$current['infos']['id_parent']];

		$level = $current['infos']['level_depth'] + 1;

		echo '
		<tr class="'.($irow++ % 2 ? 'alt_row' : '').'">
			<td>
				<input type="checkbox" name="categoryBox[]" class="categoryBox'.($id_category_default == $id_category ? ' id_category_default' : '').'" id="categoryBox_'.$id_category.'" value="'.$id_category.'"'.((in_array($id_category, $indexedCategories) OR ((int)(Tools::getValue('id_cms_category')) == $id_category AND !(int)($id_obj)
		)) ? ' checked="checked"' : '').' />
			</td>
			<td>
				'.$id_category.'
			</td>
			<td>';
		for ($i = 2; $i < $level; $i++)
			echo '<img src="../img/admin/lvl_'.$has_suite[$i - 2].'.gif" alt="" />';
		echo '<img src="../img/admin/'.($level == 1 ? 'lv1.gif' : 'lv2_'.($todo == $doneC ? 'f' : 'b').'.gif').'" alt="" /> &nbsp;
			<label for="categoryBox_'.$id_category.'" class="t">'.stripslashes($current['infos']['name']).'</label></td>
		</tr>';

		if ($level > 1)
			$has_suite[] = ($todo == $doneC ? 0 : 1);
		if (isset($categories[$id_category]))
			foreach ($categories[$id_category] AS $key => $row)
				if ($key != 'infos')
					self::recurseCategoryForInclude($id_obj, $indexedCategories, $categories, $categories[$id_category][$key], $key, $id_category_default, $has_suite);
	}

	function afterUpdate($object)
	{
		$object->cleanProducts();
		if ($accessories = Tools::getValue('inputAccessories'))
		{
			$accessories_id = array_unique(explode('-', $accessories));
			if (sizeof($accessories_id))
			{
				array_pop($accessories_id);
				$object->addProducts($accessories_id);
			}
		}
		$categoryBox = Tools::getValue('categoryBox');
		$object->cleanCategories();
		if (is_array($categoryBox) AND sizeof($categoryBox) > 0)
			$object->addCategories($categoryBox);
	}

	function afterAdd($object)
	{
		$this->afterUpdate($object);
	}
}


