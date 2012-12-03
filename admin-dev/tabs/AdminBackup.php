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
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

include_once(PS_ADMIN_DIR.'/../classes/AdminTab.php');

class AdminBackup extends AdminTab
{
	public function display()
	{
		if (_PS_MODE_DEMO_)
		{
			$this->_errors[] = Tools::displayError('This functionnality has been disabled.');
			return;
		}
		echo '<fieldset><legend><img src="../img/admin/tab-tools.gif" />'.$this->l('Sypex Dumper').'</legend>';
		echo '<iframe src="sxd/" width="100%" height="462" frameborder="0" style="margin:0;"></iframe>';
		echo '</fieldset>';
	}
}