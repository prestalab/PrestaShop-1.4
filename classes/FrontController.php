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

class FrontControllerCore
{
	public $errors = array();
	protected static $smarty;
	protected static $cookie;
	protected static $link;
	protected static $cart;
	public $iso;

	public $orderBy;
	public $orderWay;
	public $p;
	public $n;

	public $auth = false;
	public $guestAllowed = false;
	public $authRedirection = false;
	public $ssl = false;

	protected $restrictedCountry = false;
	protected $maintenance = false;

	public static $initialized = false;

	protected static $currentCustomerGroups;

	public function __construct()
	{
		global $useSSL;
		$useSSL = $this->ssl;
	}

	public function run()
	{
		$this->init();
		$this->preProcess();
		$this->displayHeader();
		$this->process();
		$this->displayContent();
		$this->displayFooter();
	}

	public function init()
	{
		global $useSSL, $cookie, $smarty, $cart, $iso, $defaultCountry, $protocol_link, $protocol_content, $link, $css_files, $js_files;

		if (self::$initialized)
			return;
		self::$initialized = true;

		// If current URL use SSL, set it true (used a lot for module redirect)
		if (Tools::usingSecureMode())
			$useSSL = $this->ssl = true;

		$css_files = array();
		$js_files = array();

		if ($this->ssl && !Tools::usingSecureMode() && _PS_SSL_ENABLED_)
		{
			header('HTTP/1.1 301 Moved Permanently');
			header('Cache-Control: no-cache');
			header('Location: '.Tools::getShopDomainSsl(true).$_SERVER['REQUEST_URI']);
			exit;
		}
		elseif (_PS_SSL_ENABLED_ && Tools::usingSecureMode() && !($this->ssl))
		{
			header('HTTP/1.1 301 Moved Permanently');
			header('Cache-Control: no-cache');
			header('Location: '.Tools::getShopDomain(true).$_SERVER['REQUEST_URI']);
			exit;
		}

		ob_start();

		/* Loading default country */
		$defaultCountry = new Country((int)_PS_COUNTRY_DEFAULT_, (int)_PS_LANG_DEFAULT_);
		$cookie = new Cookie('ps', '', time() + (((int)Configuration::get('PS_COOKIE_LIFETIME_FO') > 0 ? (int)Configuration::get('PS_COOKIE_LIFETIME_FO') : 1) * 3600));
		$link = new Link();

		if ($this->auth && !$cookie->isLogged($this->guestAllowed))
			Tools::redirect('authentication.php'.($this->authRedirection ? '?back='.$this->authRedirection : ''));

		/* Theme is missing or maintenance */
		if (!file_exists(_PS_THEME_DIR_))
			die(Tools::displayError('Current theme unavailable. Please check your theme directory name and permissions.'));
		elseif (basename($_SERVER['PHP_SELF']) != 'disabled.php' && !(int)Configuration::get('PS_SHOP_ENABLE'))
			$this->maintenance = true;
		elseif (_PS_GEOLOCATION_ENABLED_)
			$this->geolocationManagement();

		// Switch language if needed and init cookie language
		$iso = Tools::getValue('isolang');
		if ($iso && Validate::isLanguageIsoCode($iso))
		{
			$id_lang = (int)Language::getIdByIso($iso);
			if ($id_lang)
				$_GET['id_lang'] = $id_lang;
		}

		Tools::switchLanguage();
		Tools::setCookieLanguage();

		/* attribute id_lang is often needed, so we create a constant for performance reasons */
		if (!defined('_USER_ID_LANG_'))
			define('_USER_ID_LANG_', (int)$cookie->id_lang);

		if (isset($_GET['logout']) || ($cookie->logged && Customer::isBanned((int)$cookie->id_customer)))
		{
			$cookie->logout();
			Tools::redirect(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null);
		}
		elseif (isset($_GET['mylogout']))
		{
			$cookie->mylogout();
			Tools::redirect(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null);
		}

		global $currency;
		$currency = Tools::setCurrency();

		/* Cart already exists */
		if ((int)$cookie->id_cart)
		{
			$cart = new Cart((int)$cookie->id_cart);
			if ($cart->OrderExists())
				unset($cookie->id_cart, $cart, $cookie->checkedTOS);
			/* Delete product of cart, if user can't make an order from his country */
			elseif (_PS_GEOLOCATION_ENABLED_ &&
					!in_array(strtoupper($cookie->iso_code_country), explode(';', Configuration::get('PS_ALLOWED_COUNTRIES'))) &&
				$cart->nbProducts() && (int)Configuration::get('PS_GEOLOCATION_NA_BEHAVIOR') != -1 &&
					!self::isInWhitelistForGeolocation())
				unset($cookie->id_cart, $cart);
			elseif ($cookie->id_customer != $cart->id_customer || $cookie->id_lang != $cart->id_lang || $cookie->id_currency != $cart->id_currency)
			{
				if ($cookie->id_customer)
					$cart->id_customer = (int)$cookie->id_customer;
				$cart->id_lang = (int)$cookie->id_lang;
				$cart->id_currency = (int)$cookie->id_currency;
				$cart->update();
			}
			/* Select an address if not set */
			if (isset($cart) && (!isset($cart->id_address_delivery) || $cart->id_address_delivery == 0 ||
				!isset($cart->id_address_invoice) || $cart->id_address_invoice == 0) && $cookie->id_customer)
			{
				$to_update = false;
				if (!isset($cart->id_address_delivery) || $cart->id_address_delivery == 0)
				{
					$to_update = true;
					$cart->id_address_delivery = (int)Address::getFirstCustomerAddressId($cart->id_customer);
				}
				if (!isset($cart->id_address_invoice) || $cart->id_address_invoice == 0)
				{
					$to_update = true;
					$cart->id_address_invoice = (int)Address::getFirstCustomerAddressId($cart->id_customer);
				}
				if ($to_update)
					$cart->update();
			}
		}

		if (!isset($cart) || !$cart->id)
		{
			$cart = new Cart();
			$cart->id_lang = (int)$cookie->id_lang;
			$cart->id_currency = (int)$cookie->id_currency;
			$cart->id_guest = (int)$cookie->id_guest;
			if ($cookie->id_customer)
			{
				$cart->id_customer = (int)$cookie->id_customer;
				$cart->id_address_delivery = (int)Address::getFirstCustomerAddressId($cart->id_customer);
				$cart->id_address_invoice = $cart->id_address_delivery;
			}
			else
			{
				$cart->id_address_delivery = 0;
				$cart->id_address_invoice = 0;
			}
		}
		if (!$cart->nbProducts())
			$cart->id_carrier = null;

		$locale = strtolower(Configuration::get('PS_LOCALE_LANGUAGE')).'_'.strtoupper(Configuration::get('PS_LOCALE_COUNTRY').'.UTF-8');
		setlocale(LC_COLLATE, $locale);
		setlocale(LC_CTYPE, $locale);
		setlocale(LC_TIME, $locale);
		setlocale(LC_NUMERIC, 'en_US.UTF-8');

		if (Validate::isLoadedObject($currency))
			$smarty->ps_currency = $currency;
		if (Validate::isLoadedObject($ps_language = new Language((int)$cookie->id_lang)))
			$smarty->ps_language = $ps_language;

		/* get page name to display it in body id */
		$page_name = (isset($this->php_self) ? preg_replace('/\.php$/', '', $this->php_self) : '');
		if (preg_match('#^'.__PS_BASE_URI__.'(|'.((int)Configuration::get('PS_REWRITING_SETTINGS') && isset($smarty->ps_language) && !empty($smarty->ps_language) ? $smarty->ps_language->iso_code.'/' : '').')modules/([a-zA-Z0-9_-]+?)/(.*)$#', $_SERVER['REQUEST_URI'], $m))
			$page_name = 'module-'.$m[2].'-'.str_replace(array('.php', '/'), array('', '-'), $m[3]);

		$smarty->assign(Tools::getMetaTags($cookie->id_lang, $page_name));

		$protocol_link = (_PS_SSL_ENABLED_ || Tools::usingSecureMode()) ? 'https://' : 'http://';

		$useSSL = (isset($this->ssl) && $this->ssl && _PS_SSL_ENABLED_) || Tools::usingSecureMode();
		$protocol_content = ($useSSL) ? 'https://' : 'http://';
		if (!defined('_PS_BASE_URL_'))
			define('_PS_BASE_URL_', Tools::getShopDomain(true));
		if (!defined('_PS_BASE_URL_SSL_'))
			define('_PS_BASE_URL_SSL_', Tools::getShopDomainSsl(true));

		$link->preloadPageLinks();
		$this->canonicalRedirection();

		Product::initPricesComputation();

		$display_tax_label = $defaultCountry->display_tax_label;
		if (Validate::isLoadedObject($cart) && $tmp = (int)$cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')})
		{
			$infos = Address::getCountryAndState($tmp);
			$country = new Country((int)$infos['id_country']);
			if (Validate::isLoadedObject($country))
				$display_tax_label = $country->display_tax_label;
		}

		$smarty->assign(array(
			'request_uri' => Tools::safeOutput(urldecode($_SERVER['REQUEST_URI'])),
			'navigationPipe' => (Configuration::get('PS_NAVIGATION_PIPE') ? Configuration::get('PS_NAVIGATION_PIPE') : '>'), /* Breadcrumb */
			'link' => $link,
			'cart' => $cart,
			'currency' => $currency,
			'cookie' => $cookie,
			'page_name' => $page_name,
			'base_dir' => _PS_BASE_URL_.__PS_BASE_URI__,
			'base_dir_ssl' => $protocol_link.Tools::getShopDomainSsl().__PS_BASE_URI__,
			'content_dir' => $protocol_content.Tools::getHttpHost().__PS_BASE_URI__,
			'tpl_dir' => _PS_THEME_DIR_,
			'modules_dir' => _MODULE_DIR_,
			'mail_dir' => _MAIL_DIR_,
			'lang_iso' => $ps_language->iso_code,
			'come_from' => Tools::getHttpHost(true, true).Tools::htmlentitiesUTF8(str_replace('\'', '', urldecode($_SERVER['REQUEST_URI']))),
			'cart_qties' => (int)$cart->nbProducts(),
			'currencies' => Currency::getCurrencies(),
			'languages' => Language::getLanguages(),
			'priceDisplay' => Product::getTaxCalculationMethod(),
			'add_prod_display' => (int)Configuration::get('PS_ATTRIBUTE_CATEGORY_DISPLAY'),
			'shop_name' => Configuration::get('PS_SHOP_NAME'),
			'roundMode' => (int)Configuration::get('PS_PRICE_ROUND_MODE'),
			'use_taxes' => (int)Configuration::get('PS_TAX'),
			'display_tax_label' => (bool)$display_tax_label,
			'vat_management' => (int)Configuration::get('VATNUMBER_MANAGEMENT'),
			'opc' => (bool)Configuration::get('PS_ORDER_PROCESS_TYPE'),
			'PS_CATALOG_MODE' => (bool)Configuration::get('PS_CATALOG_MODE'),
			
			/* Deprecated */
			'id_currency_cookie' => (int)$currency->id,
			'logged' => $cookie->isLogged(),
			'customerName' => ($cookie->logged ? $cookie->customer_firstname.' '.$cookie->customer_lastname : false)
		));

		// TODO for better performances (cache usage), remove these assign and use a smarty function to get the right media server in relation to the full ressource name
		$assignArray = array(
			'img_ps_dir' => _PS_IMG_,
			'img_cat_dir' => _THEME_CAT_DIR_,
			'img_lang_dir' => _THEME_LANG_DIR_,
			'img_prod_dir' => _THEME_PROD_DIR_,
			'img_manu_dir' => _THEME_MANU_DIR_,
			'img_sup_dir' => _THEME_SUP_DIR_,
			'img_ship_dir' => _THEME_SHIP_DIR_,
			'img_store_dir' => _THEME_STORE_DIR_,
			'img_col_dir' => _THEME_COL_DIR_,
			'img_dir' => _THEME_IMG_DIR_,
			'css_dir' => _THEME_CSS_DIR_,
			'js_dir' => _THEME_JS_DIR_,
			'pic_dir' => _THEME_PROD_PIC_DIR_
		);

		foreach ($assignArray as $assignKey => $assignValue)
			if (substr($assignValue, 0, 1) == '/' || $protocol_content == 'https://')
				$smarty->assign($assignKey, $protocol_content.Tools::getMediaServer($assignValue).$assignValue);
			else
				$smarty->assign($assignKey, $assignValue);

		// setting properties from global var
		self::$cookie = $cookie;
		self::$cart = $cart;
		self::$smarty = $smarty;
		self::$link = $link;

		if ($this->maintenance)
			$this->displayMaintenancePage();
		if ($this->restrictedCountry)
			$this->displayRestrictedCountryPage();

		/* Check Live Edit parameters */
		if (Tools::isSubmit('live_edit'))
		{
			$ad = Tools::getValue('ad');
			if (!$ad || Tools::getValue('liveToken') != sha1($ad._COOKIE_KEY_) || !is_dir(_PS_ROOT_DIR_.DIRECTORY_SEPARATOR.$ad))
				die(Tools::displayError());
		}

		$this->iso = $iso;
		$this->setMedia();
	}

	/* Display a maintenance page if shop is closed */
	protected function displayMaintenancePage()
	{
		if (!in_array(Tools::getRemoteAddr(), explode(',', Configuration::get('PS_MAINTENANCE_IP'))))
		{
			header('HTTP/1.1 503 Service Unavailable');
			self::$smarty->display(_PS_THEME_DIR_.'maintenance.tpl');
			exit;
		}
	}

	/* Display a specific page if the user country is not allowed */
	protected function displayRestrictedCountryPage()
	{
		global $smarty;

		header('HTTP/1.1 503 Service Unavailable');
		$smarty->display(_PS_THEME_DIR_.'restricted-country.tpl');
		exit;
	}

	protected function canonicalRedirection()
	{
		global $link, $cookie;

		if (Configuration::get('PS_CANONICAL_REDIRECT') && strtoupper($_SERVER['REQUEST_METHOD']) == 'GET')
		{
			// Automatically redirect to the canonical URL if needed
			if (isset($this->php_self) && !empty($this->php_self))
			{
				// $_SERVER['HTTP_HOST'] must be replaced by the real canonical domain
				$canonicalURL = $link->getPageLink($this->php_self, $this->ssl, $cookie->id_lang);
				if (!Tools::getValue('ajax') && !preg_match('/^'.Tools::pRegexp($canonicalURL, '/').'([&?].*)?$/', (($this->ssl && _PS_SSL_ENABLED_) ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']))
				{
					if ($_SERVER['REQUEST_URI'] == __PS_BASE_URI__)
					{
						header('HTTP/1.0 303 See Other');
						header('Cache-Control: no-cache');
					}
					else
					{
						header('HTTP/1.0 301 Moved Permanently');
						header('Cache-Control: no-cache');
					}

					$params = '';
					$excludedKey = array('isolang', 'id_lang');
					foreach ($_GET as $key => $value)
						if (!in_array($key, $excludedKey))
							$params .= ($params == '' ? '?' : '&').$key.'='.$value;
					Module::hookExec('frontCanonicalRedirect');
					if (defined('_PS_MODE_DEV_') && _PS_MODE_DEV_ && $_SERVER['REQUEST_URI'] != __PS_BASE_URI__)
						die('[Debug] This page has moved<br />Please use the following URL instead: <a href="'.$canonicalURL.$params.'">'.$canonicalURL.$params.'</a>');
					Tools::redirectLink($canonicalURL.$params);
				}
			}
		}
	}

	protected function geolocationManagement()
	{
		global $cookie, $smarty, $defaultCountry;

		if (!in_array($_SERVER['SERVER_NAME'], array('localhost', '127.0.0.1')))
		{
			/* Check if Maxmind Database exists */
			if (file_exists(_PS_GEOIP_DIR_.'GeoLiteCity.dat'))
			{
				if (!isset($cookie->iso_code_country) || (isset($cookie->iso_code_country) && !in_array(strtoupper($cookie->iso_code_country), explode(';', Configuration::get('PS_ALLOWED_COUNTRIES')))))
				{
					include_once(_PS_GEOIP_DIR_.'geoipcity.inc');
					include_once(_PS_GEOIP_DIR_.'geoipregionvars.php');

					$gi = geoip_open(realpath(_PS_GEOIP_DIR_.'GeoLiteCity.dat'), GEOIP_STANDARD);
					$record = geoip_record_by_addr($gi, Tools::getRemoteAddr());

					if (is_object($record) && !in_array(strtoupper($record->country_code), explode(';', Configuration::get('PS_ALLOWED_COUNTRIES'))) && !self::isInWhitelistForGeolocation())
					{
						if (Configuration::get('PS_GEOLOCATION_BEHAVIOR') == _PS_GEOLOCATION_NO_CATALOG_)
							$this->restrictedCountry = true;
						elseif (Configuration::get('PS_GEOLOCATION_BEHAVIOR') == _PS_GEOLOCATION_NO_ORDER_)
							$smarty->assign(array(
								'restricted_country_mode' => true,
								'geolocation_country' => $record->country_name
							));
					}
					elseif (is_object($record))
					{
						$has_been_set = !isset($cookie->iso_code_country);
						$cookie->iso_code_country = strtoupper($record->country_code);
					}
				}

				if (isset($cookie->iso_code_country) && $id_country = (int)Country::getByIso(strtoupper($cookie->iso_code_country)))
				{
					/* Update defaultCountry */
					$defaultCountry = new Country($id_country, _PS_LANG_DEFAULT_);
					if (isset($has_been_set) && $has_been_set)
						$cookie->id_currency = (int)Currency::getCurrencyInstance($defaultCountry->id_currency ? (int)$defaultCountry->id_currency : (int)_PS_CURRENCY_DEFAULT_)->id;
				}
				elseif (Configuration::get('PS_GEOLOCATION_NA_BEHAVIOR') == _PS_GEOLOCATION_NO_CATALOG_)
					$this->restrictedCountry = true;
				elseif (Configuration::get('PS_GEOLOCATION_NA_BEHAVIOR') == _PS_GEOLOCATION_NO_ORDER_)
					$smarty->assign(array(
						'restricted_country_mode' => true,
						'geolocation_country' => 'Undefined'
					));
			}
			/* If not exists we disabled the geolocation feature */
			else
				Configuration::updateValue('PS_GEOLOCATION_ENABLED', 0);
		}
	}

	public function preProcess()
	{
	}

	public function setMedia()
	{
		global $cookie;

		Tools::addCSS(_THEME_CSS_DIR_.'global.css', 'all');

		if(Configuration::get('PL_JQUERY')==1)
			Tools::addJS('http'.($this->ssl?'s':'').'://ajax.googleapis.com/ajax/libs/jquery/1.10.1/jquery.min.js');
		elseif(Configuration::get('PL_JQUERY')==2)
			Tools::addJS('http'.($this->ssl?'s':'').'://yandex.st/jquery/1.10.2/jquery.min.js');
		else
			Tools::addJS(_PS_JS_DIR_.'jquery/jquery.min.js');

		Tools::addJS(array(_PS_JS_DIR_.'jquery/jquery.easing.1.3.js', _PS_JS_DIR_.'tools.js'));
		if (Tools::isSubmit('live_edit') && Tools::getValue('ad') && Tools::getValue('liveToken') == sha1(Tools::getValue('ad')._COOKIE_KEY_))
		{
			Tools::addJS(array(_PS_JS_DIR_.'jquery/jquery-ui-1.8.10.custom.min.js', _PS_JS_DIR_.'jquery/jquery.fancybox-1.3.4.js',
			_PS_JS_DIR_.'hookLiveEdit.js'));
			Tools::addCSS(_PS_CSS_DIR_.'jquery.fancybox-1.3.4.css');
		}
		$language = new Language($cookie->id_lang);
		if ($language->is_rtl)
			Tools::addCSS(_THEME_CSS_DIR_.'rtl.css');
	}

	public function process()
	{
	}

	public function displayContent()
	{
		Tools::safePostVars();
		self::$smarty->assign('errors', $this->errors);
	}

	public function displayHeader()
	{
		global $css_files, $js_files;

		if (!self::$initialized)
			$this->init();

		// P3P Policies (http://www.w3.org/TR/2002/REC-P3P-20020416/#compact_policies)
		header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');

		/* Hooks are volontary out the initialize array (need those variables already assigned) */
		self::$smarty->assign(array(
			'time' => time(),
			'img_update_time' => Configuration::get('PS_IMG_UPDATE_TIME'),
			'static_token' => Tools::getToken(false),
			'token' => Tools::getToken(),
			'logo_image_width' => Configuration::get('SHOP_LOGO_WIDTH'),
			'logo_image_height' => Configuration::get('SHOP_LOGO_HEIGHT'),
			'priceDisplayPrecision' => _PS_PRICE_DISPLAY_PRECISION_,
			'content_only' => (int)Tools::getValue('content_only')
		));
		self::$smarty->assign(array(
			'HOOK_HEADER' => Module::hookExec('header'),
			'HOOK_TOP' => Module::hookExec('top'),
			'HOOK_LEFT_COLUMN' => Module::hookExec('leftColumn')
		));

		if ((Configuration::get('PS_CSS_THEME_CACHE') || Configuration::get('PS_JS_THEME_CACHE')) && is_writable(_PS_THEME_DIR_.'cache'))
		{
			// CSS compressor management
			if (Configuration::get('PS_CSS_THEME_CACHE'))
				Tools::cccCss();

			//JS compressor management
			if (Configuration::get('PS_JS_THEME_CACHE'))
				Tools::cccJs();
		}

		self::$smarty->assign('css_files', $css_files);
		self::$smarty->assign('js_files', array_unique($js_files));
		self::$smarty->display(_PS_THEME_DIR_.'header.tpl');
	}

	public function displayFooter()
	{
		if (!self::$initialized)
			$this->init();

		self::$smarty->assign(array(
			'HOOK_RIGHT_COLUMN' => Module::hookExec('rightColumn', array('cart' => self::$cart)),
			'HOOK_FOOTER' => Module::hookExec('footer'),
			'content_only' => (int)Tools::getValue('content_only')));
		self::$smarty->display(_PS_THEME_DIR_.'footer.tpl');
		
		/* Display Live Edit Template */
		if (Tools::isSubmit('live_edit'))
		{
			$ad = Tools::getValue('ad');
			if (!$ad || Tools::getValue('liveToken') != sha1($ad._COOKIE_KEY_) || !is_dir(_PS_ROOT_DIR_.DIRECTORY_SEPARATOR.$ad))
				die(Tools::displayError());
			else
			{		
				self::$smarty->assign(array('ad' => $ad, 'live_edit' => true));
				self::$smarty->display(_PS_ALL_THEMES_DIR_.'live_edit.tpl');
			}
		}
	}

	public function productSort()
	{
		if (!self::$initialized)
			$this->init();

		$stock_management = (bool)Configuration::get('PS_STOCK_MANAGEMENT'); // no display quantity order if stock management disabled
		$this->orderBy = Tools::getProductsOrder('by', Tools::getValue('orderby'));
		$this->orderWay = Tools::getProductsOrder('way', Tools::getValue('orderway'));

		self::$smarty->assign(array(
			'orderby' => $this->orderBy,
			'orderway' => $this->orderWay,
			'orderbydefault' => Tools::getProductsOrder('by'),
			'orderwayposition' => Tools::getProductsOrder('way'), // Deprecated: orderwayposition
			'orderwaydefault' => Tools::getProductsOrder('way'),
			'stock_management' => (int)$stock_management));
	}

	public function pagination($nbProducts = 10)
	{
		if (!self::$initialized)
			$this->init();

		$nArray = Configuration::get('PS_PRODUCTS_PER_PAGE') != 10 ? array((int)Configuration::get('PS_PRODUCTS_PER_PAGE'), 10, 20, 50) : array(10, 20, 50);
		// Clean duplicate values
		$nArray = array_unique($nArray);
		asort($nArray);
		$this->n = abs((int)Tools::getValue('n', (isset(self::$cookie->nb_item_per_page) && self::$cookie->nb_item_per_page >= 10) ? self::$cookie->nb_item_per_page : (int)Configuration::get('PS_PRODUCTS_PER_PAGE')));
		$this->p = abs((int)Tools::getValue('p', 1));

		if (!is_numeric(Tools::getValue('p', 1)) || Tools::getValue('p', 1) < 0)
			Tools::redirect('404.php');

		$current_url = tools::htmlentitiesUTF8($_SERVER['REQUEST_URI']);
		//delete parameter page
		$current_url = preg_replace('/(\?)?(&amp;)?p=\d+/', '$1', $current_url);

		$range = 2; /* how many pages around page selected */

		if ($this->p < 0)
			$this->p = 0;

		if (isset(self::$cookie->nb_item_per_page) && $this->n != self::$cookie->nb_item_per_page && in_array($this->n, $nArray))
			self::$cookie->nb_item_per_page = $this->n;

		if ($this->p > (($nbProducts / $this->n) + 1))
			Tools::redirect(preg_replace('/[&?]p=\d+/', '', $_SERVER['REQUEST_URI']));

		$pages_nb = ceil($nbProducts / (int)$this->n);

		$start = (int)($this->p - $range);
		if ($start < 1)
			$start = 1;
		$stop = (int)($this->p + $range);
		if ($stop > $pages_nb)
			$stop = (int)($pages_nb);
		self::$smarty->assign('nb_products', $nbProducts);
		$pagination_infos = array(
			'products_per_page' => (int)Configuration::get('PS_PRODUCTS_PER_PAGE'),
			'pages_nb' => $pages_nb,
			'p' => $this->p,
			'n' => $this->n,
			'nArray' => $nArray,
			'range' => $range,
			'start' => $start,
			'stop' => $stop,
			'current_url' => $current_url
		);

		self::$smarty->assign($pagination_infos);
	}

	public static function getCurrentCustomerGroups()
	{
		if (!isset(self::$cookie) || !self::$cookie->id_customer)
			return array();
		if (!is_array(self::$currentCustomerGroups))
		{
			self::$currentCustomerGroups = array();
			$result = Db::getInstance()->ExecuteS('SELECT `id_group` FROM `'._DB_PREFIX_.'customer_group` WHERE `id_customer` = '.(int)self::$cookie->id_customer);
			foreach ($result as $row)
				self::$currentCustomerGroups[] = $row['id_group'];
		}
		return self::$currentCustomerGroups;
	}

	protected static function isInWhitelistForGeolocation()
	{
		$allowed = false;
		$userIp = Tools::getRemoteAddr();
		$ips = explode(';', Configuration::get('PS_GEOLOCATION_WHITELIST'));
		if (is_array($ips) && count($ips))
			foreach ($ips as $ip)
				if (!empty($ip) && strpos($userIp, $ip) === 0)
					$allowed = true;
		return $allowed;
	}
}
