<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;

class RSSeoStructuredData {
	
	protected static $loaded = false;
	
	/*
	 *	This variable will hold all the json-ld scripts
	 */
	protected static $json = array();
	
	/*
	 *	JSON options variable
	 */
	protected static $options = 0;
	
	/*
	 *	Class constructor
	 */
	public function __construct($object) {
		$data = $this->prepareData($object->data);
		
		// JSON $options compatibility 5.4.0
		if (version_compare(phpversion(), '5.4.0', '>=')) {
			self::$options = JSON_PRETTY_PRINT;
		}
		
		if (method_exists($this, $object->type)) {
			$this->{$object->type}($data);
		} else {
			if (!self::$loaded) {
				PluginHelper::importPlugin('rsseo');
				self::$loaded = true;
			}
			
			Factory::getApplication()->triggerEvent('onrsseo_generate_'.$object->type, array(array('data' => $data, 'json' => &self::$json)));
		}
	}
	
	/*
	 *	Create an instance of this class
	 */
	public static function getInstance($object) {
		return new RSSeoStructuredData($object);
	}
	
	/*
	 *	Method to generate the JSON-LD output
	 */
	public static function generate() {
		if (self::$json) {
			if (Factory::getDocument()->getType() == 'html') {
				Factory::getDocument()->addCustomTag(implode("\n", self::$json));
			}
		}
	}
	
	/*
	 *	Create the SITE structured data
	 */
	protected function site($data) {
		if ($this->getValue($data, 'enable')) {
			$json	= array();
			$array	= array();
			$name	= $this->getValue($data, 'name', Factory::getConfig()->get('sitename'));
			$url	= $this->getValue($data, 'url', Uri::root());
			$social	= $this->getValue($data, 'social');
			
			$array['@context'] = 'https://schema.org';
			$array['@type'] = 'WebSite';
			$array['name'] = $name;
			$array['url'] = $url;
			
			if ($alternate = $this->getValue($data, 'alternate')) {
				$array['alternateName'] = $alternate;
			}
			
			if ($social) {
				$social = str_replace("\r", '', $social);
				$array['sameAs'] = explode("\n",$social);
			}
			
			$json[] = '<script type="application/ld+json">';
			$json[] = json_encode($array, self::$options);
			$json[] = '</script>';
			
			self::$json[] = implode("\n",$json);
		}
	}
	
	/*
	 *	Create the sitelinks searchbox
	 */
	protected function search($data) {
		if ($this->getValue($data, 'enable')) {
			$json	= array();
			$array	= array();
			$url	= $this->getValue($data, 'url', Uri::root());
			$type	= $this->getValue($data, 'search_type', 'search');
			$custom	= $this->getValue($data, 'search_custom', '');
			
			if ($type == 'search') {
				$search = Uri::root().'index.php?option=com_search&searchphrase=all&searchword={search_term}';
			} elseif($type == 'finder') {
				$search = Uri::root().'index.php?option=com_finder&q={search_term}';
			} else {
				$search = $custom;
			}
			
			$array['@context'] = 'https://schema.org';
			$array['@type'] = 'WebSite';
			$array['url'] = $url;
			$array['potentialAction']['@type'] = 'SearchAction';
			$array['potentialAction']['target'] = $search;
			$array['potentialAction']['query-input'] = 'required name=search_term';
			
			$json[] = '<script type="application/ld+json">';
			$json[] = json_encode($array, self::$options);
			$json[] = '</script>';
			
			self::$json[] = implode("\n",$json);
		}
	}
	
	/*
	 *	Create the breadcrumbs JSON-LD
	 */
	protected function breadcrumbs($data) {
		if ($this->getValue($data, 'enable')) {
			$items	= Factory::getApplication()->getPathway()->getPathWay();
			$json	= array();
			$array	= array();
			$root	= Uri::getInstance()->toString(array('host','scheme','port'));
			$homeID	= Multilanguage::isEnabled() ? Factory::getApplication()->getMenu()->getDefault(Factory::getLanguage()->getTag()) : Factory::getApplication()->getMenu()->getDefault();
			$homeID = $homeID->id;
			$home	= $root.Route::_('index.php?Itemid='.$homeID,false);
			$count	= count($items);
			$pos	= 1;
			
			if ($items) {
				$array['@context'] = 'https://schema.org';
				$array['@type'] = 'BreadcrumbList';
				$array['itemListElement'] = array();
				
				if ($this->getValue($data, 'home')) {
					$array['itemListElement'][] = array('@type' => 'ListItem', 'position' => $pos, 'item' => array('@id' => $home.'home', 'name' => Text::_('COM_RSSEO_BREADCRUMBS_HOME')));
				} else {
					$pos = 0;
				}
			
				foreach ($items as $key => $item) {
					$pos++;
					
					$url = ($pos - 1) == $count ? (string) Uri::getInstance() : $root.Route::_($item->link);
					$array['itemListElement'][] = array('@type' => 'ListItem', 'position' => $pos, 'item' => array('@id' => $url.'/'.$this->safeString($item->name), 'name' => $item->name));
				}
			
				$json[] = '<script type="application/ld+json">';
				$json[] = json_encode($array, self::$options);
				$json[] = '</script>';
				
				self::$json[] = implode("\n",$json);
			}
		}
	}
	
	/*
	 *	Create the article JSON-LD
	 */
	protected function article($data) {
		if ($this->getValue($data, 'enable')) {
			$input	= Factory::getApplication()->input;
			$option	= $input->get('option');
			$view	= $input->get('view');
			$id		= $input->getInt('id');
			
			if ($option == 'com_content' && $view == 'article' && $id) {
				if ($article = $this->getArticle($id)) {
					$json	= array();
					$array	= array();
					$text	= mb_substr(strip_tags($article->introtext.$article->fulltext), 0, 500);
					$lang	= $article->language === '*' ? Factory::getConfig()->get('language') : $article->language;
					$pName	= $this->getValue($data, 'name');
					$pLogo	= $this->getValue($data, 'logo');
					$dImage	= $this->getValue($data, 'article_image');
					$modified = $article->modified && $article->modified != '0000-00-00 00:00:00' ? $article->modified : $article->publish_up;
					
					if (empty($article->image) && !empty($dImage)) {
						$article->image = $dImage;
					}
					
					$array['@context'] = 'https://schema.org';
					$array['@type'] = 'Article';
					$array['mainEntityOfPage']['@type'] = 'WebPage';
					$array['mainEntityOfPage']['@id'] = Uri::root();
					$array['headline'] = $article->title;
					$array['description'] = $text;
					$array['inLanguage'] = $lang;
					$array['interactionCount'] = $article->hits;
					$array['datePublished'] = HTMLHelper::_('date', $article->publish_up, 'c');
					$array['dateCreated'] = HTMLHelper::_('date', $article->created, 'c');
					$array['dateModified'] = HTMLHelper::_('date', $modified, 'c');
					
					if ($article->image) {
						$array['image']['@type'] = 'ImageObject';
						$array['image']['url'] = Uri::root().$article->image;
						$array['image']['height'] = '800';
						$array['image']['width'] = '800';
					}
					
					if ($article->name) {
						$array['author']['@type'] = 'Person';
						$array['author']['name'] = $article->name;
					}
					
					if ($pName && $pLogo) {
						$array['publisher']['@type'] = 'Organization';
						$array['publisher']['name'] = $pName;
						$array['publisher']['logo']['@type'] = 'ImageObject';
						$array['publisher']['logo']['url'] = Uri::root().$pLogo;
						$array['publisher']['logo']['width'] = '600';
						$array['publisher']['logo']['height'] = '600';
					}
					
					
					$json[] = '<script type="application/ld+json">';
					$json[] = json_encode($array, self::$options);
					$json[] = '</script>';
					
					self::$json[] = implode("\n",$json);
				}
			}
		}
	}
	
	/*
	 *	Create the contact's JSON-LD
	 */
	protected function contact($data) {
		if ($this->getValue($data, 'enable')) {
			$input	= Factory::getApplication()->input;
			$option	= $input->get('option');
			$view	= $input->get('view');
			$id		= $input->getInt('id');
			
			if ($option == 'com_contact' && $view == 'contact' && $id) {
				if ($contact = $this->getContact($id)) {
					$json	= array();
					$array	= array();
					
					$array['@context'] = 'https://schema.org';
					$array['@type'] = 'Person';
					$array['name'] = $contact->name;
					$array['jobTitle'] = $contact->con_position;
					$array['address']['@type'] = 'PostalAddress';
					$array['address']['streetAddress'] = $contact->address;
					$array['address']['addressLocality'] = $contact->suburb;
					$array['address']['addressRegion'] = $contact->state;
					$array['address']['postalCode'] = $contact->postcode;
					$array['address']['faxNumber'] = $contact->fax;
					$array['address']['telephone'] = $contact->telephone;
					$array['address']['url'] = $contact->webpage;
					$array['address']['addressCountry'] = $contact->country;
					
					if ($contact->image) {
						$array['image']['@type'] = 'ImageObject';
						$array['image']['url'] = Uri::root().$contact->image;
						$array['image']['width'] = '800';
						$array['image']['height'] = '800';
					}
					
					$json[] = '<script type="application/ld+json">';
					$json[] = json_encode($array, self::$options);
					$json[] = '</script>';
					
					self::$json[] = implode("\n",$json);
				}
			}
		}
	}
	
	/*
	 *	Create the business JSON-LD
	 */
	protected function business($data) {
		if ($this->getValue($data, 'enable')) {
			$json	 = array();
			$array	 = array();
			$custom  = array();
			$url	 = $this->getValue($data, 'url', Uri::root());
			$name	 = $this->getValue($data, 'name', Factory::getConfig()->get('sitename'));
			$type	 = $this->getValue($data, 'type');
			$lat	 = $this->getValue($data, 'lat');
			$lon	 = $this->getValue($data, 'lon');
			$address = $this->getValue($data, 'address');
			$city	 = $this->getValue($data, 'locality');
			$state	 = $this->getValue($data, 'region');
			$code	 = $this->getValue($data, 'code');
			$country = $this->getValue($data, 'country');
			$hours	 = $this->getValue($data, 'hours', 'none');
			$weekdays= $this->getValue($data, 'available');
			$cuisines= $this->getValue($data, 'servesCuisine');
			
			
			$array['@context'] = 'https://schema.org';
			$array['@type'] = $type;
			$array['@id'] = rtrim($url, '/').'/'.$this->safeString($type);
			$array['name'] = $name;
			$array['url'] = $url;
			
			if ($image = $this->getValue($data, 'logo')) {
				$array['image'] = Uri::root().$image;
			}
			
			if ($phone = $this->getValue($data, 'telephone')) {
				$array['telephone'] = $phone;
			}
			
			if ($priceRange = $this->getValue($data, 'range')) {
				$array['priceRange'] = $priceRange;
			}
			
			if ($cuisines) {
				$array['servesCuisine'] = explode(',', $cuisines);
			}
			
			if ($lat && $lon) {
				$array['geo']['@type'] = 'GeoCoordinates';
				$array['geo']['latitude'] = $lat;
				$array['geo']['longitude'] = $lon;
			}
			
			if ($address || $city || $state || $code || $country) {
				$array['address']['@type'] = 'PostalAddress';
				$array['address']['streetAddress'] = $address;
				$array['address']['addressLocality'] = $city;
				$array['address']['addressRegion'] = $state;
				$array['address']['postalCode'] = $code;
				$array['address']['addressCountry'] = $country;
			}
			
			if ($hours == 'none') {
				$array['openingHoursSpecification'] = array();
			} elseif ($hours == 'always') {
				$array['openingHoursSpecification']['@type'] = 'OpeningHoursSpecification';
				$array['openingHoursSpecification']['dayOfWeek'] = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
				$array['openingHoursSpecification']['opens'] = '00:00';
				$array['openingHoursSpecification']['closes'] = '23:59';
			} elseif ($hours == 'custom') {
				if ($weekdays) {
					foreach ($weekdays as $weekday => $day) {
						if (isset($day['enabled'])) {
							$custom = array();
							$custom['@type'] = 'OpeningHoursSpecification';
							$custom['dayOfWeek'] = array($weekday);
							$custom['opens'] = $day['opens'];
							$custom['closes'] = $day['closes'];
							
							$array['openingHoursSpecification'][] = $custom;
						}
					}
				}
			}
			
			$json[] = '<script type="application/ld+json">';
			$json[] = json_encode($array, self::$options);
			$json[] = '</script>';
			
			self::$json[] = implode("\n",$json);
		}
	}
	
	/*
	 *	Method to get article details
	 */
	protected function getArticle($id) {
		$db		= Factory::getDbo();
		$query	= $db->getQuery(true);
		
		$query->select($db->qn('a.title'))->select($db->qn('a.images'))
			->select($db->qn('a.introtext'))->select($db->qn('a.fulltext'))
			->select($db->qn('a.publish_up'))->select($db->qn('a.modified'))
			->select($db->qn('a.language'))->select($db->qn('u.name'))
			->select($db->qn('a.created'))->select($db->qn('a.hits'))
			->select('ROUND(v.rating_sum / v.rating_count, 0) AS rating, v.rating_count as rating_count')
			->from($db->qn('#__content','a'))
			->join('LEFT', $db->qn('#__content_rating','v').' ON '.$db->qn('a.id').' = '.$db->qn('v.content_id'))
			->join('LEFT', $db->qn('#__users','u').' ON '.$db->qn('u.id').' = '.$db->qn('a.created_by'))
			->where($db->qn('a.id').' = '.$db->q($id));
		$db->setQuery($query);
		if ($article = $db->loadObject()) {
			try {
				$reg = new Registry;
				$reg->loadString($article->images);
				$images = $reg->toArray();
				$article->image = isset($images['image_fulltext']) ? $images['image_fulltext'] : (isset($images['image_intro']) ? $images['image_intro'] : '');
			} catch (Exception $e) {
				$article->image = '';
			}
			
			return $article;
		}
		
		return false;
	}
	
	/*
	 *	Method to get contact details
	 */
	protected function getContact($id) {
		$db		= Factory::getDbo();
		$query	= $db->getQuery(true);
		
		$query->select('*')
			->from($db->qn('#__contact_details','a'))
			->where($db->qn('id').' = '.$db->q($id));
		$db->setQuery($query);
		return $db->loadObject();
	}
	
	/*
	 *	Method to get the value of a constant
	 */
	protected function getValue($data, $name, $default = null) {
		if (isset($data[$name]) && !empty($data[$name])) {
			return $data[$name];
		}
		
		return $default;
	}
	
	/*
	 *	Prepare data to be used by internal functions
	 */
	protected function prepareData($data) {
		try {
			$registry = new Registry;
			$registry->loadString($data);
			return $registry->toArray();
		} catch (Exception $e) {
			return array();
		}
	}
	
	/*
	 *	Method to create a sanitize a string
	 */
	protected function safeString($string) {
		if (Factory::getConfig()->get('unicodeslugs') == 1) {
			 return OutputFilter::stringURLUnicodeSlug($string);
		} else {
			return OutputFilter::stringURLSafe($string);
		}
	}
}