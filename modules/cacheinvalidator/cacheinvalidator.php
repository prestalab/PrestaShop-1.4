<?php

/**
* cacheinvalidator module main file.
*
* @author 0RS <admin@prestalab.ru>
* @link http://prestalab.ru/
* @copyright Copyright &copy; 2009-2012 PrestaLab.Ru
* @license    http://www.opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
* @version 0.1
*/

if (!defined('_PS_VERSION_'))
	exit;

class cacheinvalidator extends Module
{

	function __construct()
	{
		$this->name = 'cacheinvalidator';
		$this->tab = 'administration';
		$this->version = '0.1';
		$this->author = 'PrestaLab.Ru';
		$this->need_instance = 0;
		//Ключик из addons.prestashop.com
		$this->module_key='';

		parent::__construct();

		$this->displayName = $this->l('Cache Invalidator');
		$this->description = $this->l('Remove cache after updates');
	}

	public function install()
	{
		return (parent::install()
			&& $this->registerHook('updateQuantity')
			&& $this->registerHook('addproduct')
			&& $this->registerHook('updateproduct')
			&& $this->registerHook('deleteproduct')
			&& $this->registerHook('categoryAddition')
			&& $this->registerHook('categoryUpdate')
			&& $this->registerHook('categoryDeletion')
		);
	}

	private function _processProduct($product)
	{
		$this->_clearCache(__FILE__, null, 'products');
		$this->_clearCache(__FILE__, null, 'id_product_'.$product->id);
		$categories = $product->getCategories();
		foreach ($categories as $category)
			$this->_processCategory((int)$category);
	}

	private function _processCategory($category)
	{
		$this->_clearCache(__FILE__, null, 'categories');
		$this->_clearCache(__FILE__, null, 'id_category_'.(is_int($category)?$category:$category->id));
	}


	public function hookupdateQuantity($params)
	{
		$this->_processProduct($params['product']);
	}

	public function hookaddproduct($params)
	{
		$this->_processProduct($params['product']);
	}

	public function hookupdateproduct($params)
	{
		$this->_processProduct($params['product']);
	}

	public function hookdeleteproduct($params)
	{
		$this->_processProduct($params['product']);
	}

	public function hookcategoryAddition($params)
	{
		$this->_processCategory($params['category']);
	}

	public function hookcategoryUpdate($params)
	{
		$this->_processCategory($params['category']);
	}

	public function hookcategoryDeletion($params)
	{
		$this->_processCategory($params['category']);
	}
}