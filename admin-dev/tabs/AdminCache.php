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
*  @copyright  2007-2012 PrestaShop SA
*  @version  Release: $Revision$
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

class AdminCache extends AdminTab
{
	public function __construct()
	{
		$this->table = 'hook_module';
		//$this->className = 'hook_module';
		$this->identifier = 'id_module_hook';
		$this->lang = false;
		$this->edit = true;
		$this->delete = false;
		$this->view = false;
		$this->noAdd = true;
		$this->_defaultOrderBy = 'module_name';

		$this->fieldsDisplay = array(
			'module_name' => array('title' => $this->l('Module'), 'width' => 120),
			'hook_name' => array('title' => $this->l('Hook'), 'width' => 120),
			'time' => array('title' => $this->l('Cache time'), 'align' => 'center', 'width' => 50),
		);

		$this->_join = '
		LEFT JOIN `' . _DB_PREFIX_ . 'hook` h ON (h.`id_hook` = a.`id_hook`)
		LEFT JOIN `' . _DB_PREFIX_ . 'module` m ON (m.`id_module` = a.`id_module`)';
		$this->_select = 'h.`name` as `hook_name`, m.`name` as `module_name`, CONCAT(m.`id_module`,\'-\', h.`id_hook`) as `id_module_hook`';
		$this->_filter = "AND h.`position` = 1 AND m.`name` NOT IN (
		'blockadvertising',
		'blockcart',
		'blockcategories',
		'blockcms',
		'blockcurrencies',
		'blocklanguages',
		'blockmanufacturer',
		'blocknewproducts',
		 'blockpermanentlinks',
		 'blocksearch',
		 'blockspecials',
		 'blockstore',
		 'blocktags',
		 'blockuserinfo',
		 'editorial',
		 'homefeatured',
		 'blockbestsellers'
		 ) AND h.`name` NOT LIKE 'Admin%'";

		$this->optionTitle = $this->l('Cache Setup');
		$this->_fieldsOptions = array(
			'PL_CACHE_LONG' => array('title' => $this->l('Long cache time'), 'desc' => $this->l('Long cache time in seconds.'), 'type' => 'text', 'size' => 30, 'default' => '31536000'),
			'PL_CACHE_LIST' => array('title' => $this->l('Product list cache time'), 'desc' => $this->l('Product list cache time in seconds.'), 'type' => 'text', 'size' => 30, 'default' => '86400'),
			'PL_CACHE_SHORT' => array('title' => $this->l('Short cache time'), 'desc' => $this->l('Product list cache time in seconds.'), 'type' => 'text', 'size' => 30, 'default' => '86400'),
		);

		parent::__construct();
	}

	public function displayForm($isMainTab = true)
	{
		global $currentIndex;
		parent::displayForm();

		if (!($id = (Tools::getValue($this->identifier))))
			return;
		$data = explode('-', $id);

		$time = Db::getInstance()->getValue('SELECT `time`  FROM ' . pSQL(_DB_PREFIX_ . $this->table) . ' WHERE id_module=' . (int)$data[0] . ' AND id_hook=' . (int)$data[1]);

		echo '
		<form action="' . $currentIndex . '&token=' . $this->token . '&submitAdd' . $this->table . '=1" method="post">
		' . ($id ? '<input type="hidden" name="' . $this->identifier . '" value="' . $id . '" />' : '') . '
			<fieldset><legend><img src="../img/admin/metatags.gif" />' . $this->l('Module cache time') . '</legend>
				<label>' . $this->l('Cache time:') . ' </label>
				<div class="margin-form">
					<input type="input" name="time" value="' . $time . '" />
					<p>' . $this->l('Cache time in seconds. 0 is no cached.') . '</p>
				</div>
				<div class="margin-form">
					<input type="submit" value="' . $this->l('   Save   ') . '" name="submitAdd' . $this->table . '" class="button" />
				</div>
			</fieldset>
		</form>';
	}

	public function postProcess()
	{
		/* PrestaShop demo mode */
		if (_PS_MODE_DEMO_) {
			$this->_errors[] = Tools::displayError('This functionnality has been disabled.');
			return;
		}
		/* PrestaShop demo mode*/

		if (Tools::isSubmit('submitAdd' . $this->table)) {
			if (!($id = Tools::getValue($this->identifier)))
				return;

			$data = explode('-', $id);

			if (!Db::getInstance()->Execute('UPDATE ' . pSQL(_DB_PREFIX_ . $this->table) . ' SET `time`=' . (int)Tools::getValue('time') . ' WHERE `id_module`=' . (int)$data[0] . ' AND `id_hook`=' . (int)$data[1])) {
				$this->_errors[] = Tools::displayError('Update error.');
				return false;
			}
			global $currentIndex;
			$token = Tools::getValue('token') ? Tools::getValue('token') : $this->token;
			Tools::redirectAdmin($currentIndex . '&token=' . $token);
		} elseif (Tools::isSubmit('submitOptions' . $this->table)) {
			global $smarty;
			$smarty->clearAllCache();
			Cache::getInstance()->flush();
		}

		return parent::postProcess();
	}
}


