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

include_once _PS_SWIFT4_DIR_. 'classes/Swift.php';
include_once _PS_SWIFT4_DIR_. 'swift_init.php';

class MailCore
{
	public static function Send($id_lang, $template, $subject, $templateVars, $to,
		$toName = null, $from = null, $fromName = null, $fileAttachment = null, $modeSMTP = null, $templatePath = _PS_MAIL_DIR_, $die = false)
	{
		$configuration = Configuration::getMultiple(array('PS_SHOP_EMAIL', 'PS_MAIL_METHOD', 'PS_MAIL_SERVER', 'PS_MAIL_USER', 'PS_MAIL_PASSWD', 'PS_SHOP_NAME', 'PS_MAIL_SMTP_ENCRYPTION', 'PS_MAIL_SMTP_PORT', 'PS_MAIL_METHOD', 'PS_MAIL_TYPE'));

		if (!isset($configuration['PS_MAIL_SMTP_ENCRYPTION']))
			$configuration['PS_MAIL_SMTP_ENCRYPTION'] = 'off';
		if (!isset($configuration['PS_MAIL_SMTP_PORT']))
			$configuration['PS_MAIL_SMTP_PORT'] = 'default';

		// Sending an e-mail can be of vital importance for the merchant, when his password is lost for example, so we must not die but do our best to send the e-mail
		if (!isset($from) || !Validate::isEmail($from))
			$from = $configuration['PS_SHOP_EMAIL'];
		if (!Validate::isEmail($from))
			$from = null;

		// $fromName is not that important, no need to die if it is not valid
		if (!isset($fromName) || !Validate::isMailName($fromName))
			$fromName = $configuration['PS_SHOP_NAME'];
		if (!Validate::isMailName($fromName))
			$fromName = null;

		if (!is_array($to) && !Validate::isEmail($to))
		{
	 		Tools::dieOrLog(Tools::displayError('Error: parameter "to" is corrupted'), $die);
	 		return false;
		}

		if (!is_array($templateVars))
			$templateVars = array();

		// Do not crash for this error, that may be a complicated customer name
		if (is_string($toName) && !empty($toName) && !Validate::isMailName($toName))
	 		$toName = null;

		if (!Validate::isTplName($template))
		{
	 		Tools::dieOrLog(Tools::displayError('Error: invalid email template'), $die);
	 		return false;
		}

		if (!Validate::isMailSubject($subject))
		{
	 		Tools::dieOrLog(Tools::displayError('Error: invalid email subject'), $die);
	 		return false;
		}

		/* Construct multiple recipients list if needed */
		if (isset($to) && is_array($to))
		{
			$to_list = array();
			foreach ($to as $key => $addr)
			{
				$to_name = null;
				$addr = trim($addr);
				if (!Validate::isEmail($addr))
				{
					Tools::dieOrLog(Tools::displayError('Error: invalid email address'), $die);
					return false;
				}
				if (is_array($toName))
				{
					if ($toName && is_array($toName) && Validate::isGenericName($toName[$key]))
						$to_name = $toName[$key];
				}
				if ($to_name == null)
					$to_name = $addr;
                /* Encode accentuated chars */
				$to_list[$addr]=$to_name;
			}
			$to_plugin = $to[0];
			$to = $to_list;
		} else {
			/* Simple recipient, one address */
			$to_plugin = $to;
			if ($toName == null)
				$toName = $to;
            /* Encode accentuated chars */
			$to = array($to=>$toName);
		}
		try {
			/* Connect with the appropriate configuration */
			if ($configuration['PS_MAIL_METHOD'] == 2)
			{
				if (empty($configuration['PS_MAIL_SERVER']) || empty($configuration['PS_MAIL_SMTP_PORT']))
				{
					Tools::dieOrLog(Tools::displayError('Error: invalid SMTP server or SMTP port'), $die);
					return false;
				}
				$transport = Swift_SmtpTransport::newInstance($configuration['PS_MAIL_SERVER'], $configuration['PS_MAIL_SMTP_PORT'], $configuration['PS_MAIL_SMTP_ENCRYPTION'])
				  ->setUsername($configuration['PS_MAIL_USER'])
				  ->setPassword($configuration['PS_MAIL_PASSWD']);
			}
			else
				$transport = Swift_MailTransport::newInstance();

			if (!$transport)
				return false;
			$swift = Swift_Mailer::newInstance($transport);
			/* Get templates content */
			$iso = Language::getIsoById((int)($id_lang));
			if (!$iso)
			{
				Tools::dieOrLog(Tools::displayError('Error - No ISO code for email'), $die);
				return false;
			}
			$template = $iso.'/'.$template;

			$moduleName = false;
			$overrideMail = false;

			// get templatePath
			if (preg_match('#'.__PS_BASE_URI__.'modules/#', $templatePath) && preg_match('#modules/([a-z0-9_-]+)/#ui', $templatePath, $res))
				$moduleName = $res[1];

			if ($moduleName !== false && (file_exists(_PS_THEME_DIR_.'modules/'.$moduleName.'/mails/'.$template.'.txt') ||
				file_exists(_PS_THEME_DIR_.'modules/'.$moduleName.'/mails/'.$template.'.html')))
				$templatePath = _PS_THEME_DIR_.'modules/'.$moduleName.'/mails/';
			elseif (file_exists(_PS_THEME_DIR_.'mails/'.$template.'.txt') || file_exists(_PS_THEME_DIR_.'mails/'.$template.'.html'))
			{
				$templatePath = _PS_THEME_DIR_.'mails/';
				$overrideMail  = true;
			}
			elseif (!file_exists($templatePath.$template.'.html'))
			{
				Tools::dieOrLog(Tools::displayError('Error - The following email template is missing:').' '.$templatePath.$template.'.html', $die);
				return false;
			}
			elseif (!file_exists($templatePath.$template.'.txt'))
			{
				Tools::dieOrLog(Tools::displayError('Error - The following email template is missing:').' '.$templatePath.$template.'.txt', $die);
				return false;
			}

			$templateHtml = file_get_contents($templatePath.$template.'.html');
			$templateTxt = strip_tags(html_entity_decode(file_get_contents($templatePath.$template.'.txt'), null, 'utf-8'));

			if ($overrideMail && file_exists($templatePath.$iso.'/lang.php'))
					include_once($templatePath.$iso.'/lang.php');
			else if ($moduleName && file_exists($templatePath.$iso.'/lang.php'))
				include_once(_PS_THEME_DIR_.'mails/'.$iso.'/lang.php');
			else
				include_once(dirname(__FILE__).'/../mails/'.$iso.'/lang.php');

			/* Create mail && attach differents parts */
			$message = Swift_Message::newInstance('['.Configuration::get('PS_SHOP_NAME').'] '.$subject)
				->setFrom(array($from=>$fromName))
				->setTo($to);
			$templateVars['{shop_logo}'] = (file_exists(_PS_IMG_DIR_.'logo_mail.jpg')) ?
				$message->embed(Swift_Image::fromPath((_PS_IMG_DIR_.'logo_mail.jpg'))) : ((file_exists(_PS_IMG_DIR_.'logo.jpg')) ?
					$message->embed(Swift_Image::fromPath((_PS_IMG_DIR_.'logo.jpg'))) : '');
			$templateVars['{shop_name}'] = Tools::safeOutput(Configuration::get('PS_SHOP_NAME'));
			$templateVars['{shop_url}'] = Tools::getShopDomain(true, true).__PS_BASE_URI__;
			$replacements=array();
			foreach ($to as $addr=>$name)
			{
				$replacements[$addr]=$templateVars;
			}
			$swift->registerPlugin(new Swift_Plugins_DecoratorPlugin($replacements));
			if ($configuration['PS_MAIL_TYPE'] == 3 || $configuration['PS_MAIL_TYPE'] == 2)
				$message->addPart($templateTxt, 'text/plain');
			if ($configuration['PS_MAIL_TYPE'] == 3 || $configuration['PS_MAIL_TYPE'] == 1)
				$message->addPart($templateHtml, 'text/html');
			if ($fileAttachment && isset($fileAttachment['content']) && isset($fileAttachment['name']) && isset($fileAttachment['mime']))
				$message->attach(Swift_Attachment::newInstance($fileAttachment['content'], $fileAttachment['name'], $fileAttachment['mime']));
			/* Send mail */
			return $swift->send($message);
		}
		catch (Swift_TransportException $e){
			return false;
		}
	}

	public static function sendMailTest($smtpChecked, $smtpServer, $content, $subject, $type, $to, $from, $smtpLogin, $smtpPassword, $smtpPort = 25, $smtpEncryption)
	{
		$swift = null;
		$result = false;
		try
		{
			if ($smtpChecked)
			{
				$transport = Swift_SmtpTransport::newInstance($smtpServer, $smtpPort, $smtpEncryption)
				  ->setUsername($smtpLogin)
				  ->setPassword($smtpPassword)
				  ;
			}
			else
				$transport = Swift_MailTransport::newInstance();

			$mailer = Swift_Mailer::newInstance($transport);
			$message = Swift_Message::newInstance($subject, $content, $type)
			  ->setFrom($from)
			  ->setTo($to)
			  ;

			if ($mailer->send($message))
				$result = true;
		}
		catch (Swift_TransportException $e)
		{
			$result = $e->getMessage();
		}

		return $result;
	}

	/**
	 * This method is used to get the translation for email Object.
	 * For an object is forbidden to use htmlentities,
	 * we have to return a sentence with accents.
	 *
	 * @param string $string raw sentence (write directly in file)
	 */
	public static function l($string, $id_lang = null)
	{
		global $_LANGMAIL, $cookie;

		$key = str_replace('\'', '\\\'', $string);

		if ($id_lang == null)
			$id_lang = (!isset($cookie) || !is_object($cookie)) ? (int)_PS_LANG_DEFAULT_ : (int)$cookie->id_lang;

		$file_core = _PS_ROOT_DIR_.'/mails/'.Language::getIsoById((int)$id_lang).'/lang.php';
		if (file_exists($file_core) && empty($_LANGMAIL))
			include_once($file_core);

		$file_theme = _PS_THEME_DIR_.'mails/'.Language::getIsoById((int)$id_lang).'/lang.php';
		if (file_exists($file_theme))
			include_once($file_theme);

		if (!is_array($_LANGMAIL))
			return (str_replace('"', '&quot;', $string));
		if (key_exists($key, $_LANGMAIL))
			$str = $_LANGMAIL[$key];
		else
			$str = $string;

		return str_replace('"', '&quot;', addslashes($str));
	}
}
