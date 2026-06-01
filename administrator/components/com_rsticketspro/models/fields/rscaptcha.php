<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */
defined('JPATH_PLATFORM') or die;

require_once JPATH_ADMINISTRATOR.'/components/com_rsticketspro/helpers/rsticketspro.php';

class JFormFieldRSCaptcha extends JFormField
{
	protected $type = 'RSCaptcha';
	
	protected function getInput()
	{
		$captcha = RSTicketsProHelper::getConfig('captcha_enabled');
		
		if ($captcha == 1)
		{
			$route 	= JRoute::_('index.php?option=com_rsticketspro&task=captcha');
			$src 	= JRoute::_('index.php?option=com_rsticketspro&task=captcha&sid='.mt_rand());
			$img = '<img src="'.$src.'" id="submit_captcha_image" alt="'.JText::_('RST_ANTISPAM').'" />';
			
			$refresh = '<span class="'.RSTicketsProHelper::tooltipClass().'" title="'.RSTicketsProHelper::tooltipText(JText::_('RST_REFRESH_CAPTCHA_DESC')).'"><a onclick="return RSTicketsPro.refreshCaptcha(\''.addslashes($route).'\');" href="javascript:void(0);" class="btn btn-small"><i class="icon-refresh icon-rsrefresh"></i></a></span>';
			
			$input = '<div class="rst_captcha"><input type="text" id="'.$this->id.'" name="'.$this->name.'" value="" /></div>';
			
			return $img . $refresh . $input;
		}
		elseif ($captcha > 1 && $captcha < 5)
		{
			// reCAPTCHA NoCAPTCHA
			$doc = JFactory::getDocument();
			if ($doc->getType() == 'html')
			{
				$doc->addScript('https://www.google.com/recaptcha/api.js?'.($captcha == 4 ? 'render=explicit&' : '').'hl='.urlencode(JFactory::getLanguage()->getTag()),array(), array('async' => 'async', 'defer' => 'defer'));
			}

			$sitekey 	= RSTicketsProHelper::getConfig('recaptcha_new_site_key');
			$secretkey  = RSTicketsProHelper::getConfig('recaptcha_new_secret_key');
			
			if (empty($sitekey))
			{
				return JText::_('RST_CAPTCHA_MISSING_SITE_KEY');
			}
			
			if (empty($secretkey))
			{
				return JText::_('RST_CAPTCHA_MISSING_SECRET_KEY');
			}

			if ($captcha == 3) {
				return '<div class="g-recaptcha"
						data-sitekey="'.$this->escape($sitekey).'"
						data-theme="'.$this->escape(RSTicketsProHelper::getConfig('recaptcha_new_theme')).'"
						data-type="'.$this->escape(RSTicketsProHelper::getConfig('recaptcha_new_type')).'"
					></div>';
			} else if ($captcha == 4) {
				$invisible_script = "
					var RSTicketsProReCAPTCHAv2Callback = function() {
						var form =document.getElementById('adminForm');
						if (typeof form.submit != 'function') {
							document.createElement('form').submit.call(form)
						} else {
							form.submit();
						}
					}

					var RSTicketsProReCAPTCHAv2 = function(){
						var rst_rendered_id = grecaptcha.render('g-recaptcha-rst', {
							'sitekey' : '".$this->escape($sitekey)."',
							'theme' : '".$this->escape(RSTicketsProHelper::getConfig('recaptcha_new_theme'))."',
							'type' : '".$this->escape(RSTicketsProHelper::getConfig('recaptcha_new_type'))."',
							'badge' : 'inline',
							'size' : 'invisible',
							'callback' : 'RSTicketsProReCAPTCHAv2Callback'
						 });
						RSTicketsPro.addEvent(document.getElementById('adminForm'), 'submit', function(evt){ evt.preventDefault(); grecaptcha.execute(rst_rendered_id); });
					}

					window.addEventListener('DOMContentLoaded', RSTicketsProReCAPTCHAv2);";
				$doc->addScriptDeclaration($invisible_script);

				return '<div id="g-recaptcha-rst"></div>';
			}
		} else if ($captcha == 5) {
			$jcaptcha = JFactory::getApplication()->get('captcha');
			if (!empty($jcaptcha)) {
				try {
					$jcaptcha = JCaptcha::getInstance($jcaptcha, array('namespace' => 'rscaptcha'));
					if (!is_null($jcaptcha))
					{
						return $jcaptcha->display('rscaptcha', 'rscaptcha');
					}
				} catch (Exception $e) {
					JFactory::getApplication()->enqueueMessage($e->getMessage(), 'error');
				}
			} else {
				JFactory::getApplication()->enqueueMessage(JText::_('RST_CAPTCHA_BUILT_IN_NOT_SELECTED'), 'error');
			}
		}
		
		return '';
	}
	
	protected function escape($string)
	{
		return htmlentities($string, ENT_COMPAT, 'utf-8');
	}
}