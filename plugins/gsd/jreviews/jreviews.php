<?php

/**
 * @package         Google Structured Data
 * @version         6.0.1 Pro
 *
 * @author          Tassos Marinos <info@tassos.gr>
 * @link            http://www.tassos.gr
 * @copyright       Copyright © 2022 Tassos Marinos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die('Restricted access');

use GSD\Helper;
use GSD\PluginBase;
use GSD\MappingOptions;
use GSD\Helper\JReviews;
use NRFramework\Cache;
use NRFramework\Functions;
use Joomla\Registry\Registry;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class plgGSDJReviews extends PluginBase
{
    private $item = null;

    private $article = null;

	private $listingModel = null;

    private $listing = null;
    
    public function getView()
    {
        return 'article';
    }
    
    protected function passContext()
    {
        if (!$option = $this->app->input->get('option'))
        {
            return;
        }

        if ($option !== 'com_content')
        {
            return;
        }
        
		if ($this->app->input->get('view') !== $this->getView())
		{
			return;
		}
		
        if (!$id = $this->app->input->get('id'))
        {
            return;
        }

        if (!$this->article = JReviews::getListing($id))
        {
            return;
        }

        return true;
    }

    /**
     *  Product View
     *  
     *  @return  object
     */
    protected function viewArticle()
    {
        // Load current item via model
		$model = new Joomla\Component\Content\Site\Model\ArticleModel(['ignore_request' => true]);
		$model->setState('article.id', $this->getThingID());
		$model->setState('params', $this->app->getParams());

		// Make sure we have a valid item data
		if (!is_object($model) || !$this->item = $model->getItem())
		{
			return;
		}

		// Image
		$image = new Registry($this->item->images);

		// Set text property required by the Content Prepare Event
		$this->item->text = isset($this->item->introtext) && !empty($this->item->introtext) ? $this->item->introtext : $this->item->fulltext;

		if (class_exists('\ClassRegistry'))
		{
			$this->listingModel = \ClassRegistry::getClass('EverywhereComContentModel');
			$this->listing = $this->listingModel ? $this->listingModel->getListingById($this->item->id) : null;
		}

		// Array data
		$payload = [
			'id'           => $this->item->id,
			'alias'        => $this->item->alias,
			'headline'     => $this->item->title,
			'description'  => $this->item->text,
			'introtext'    => $this->item->introtext,
			'fulltext'     => $this->item->fulltext,
			'image_intro'  => $image->get('image_intro'),
			'image_full'   => $image->get('image_fulltext'),
			'image'        => $image->get('image_intro') ?: $image->get('image_fulltext'),
			'imagetext'	   => \GSD\Helper::getFirstImageFromString($this->item->introtext . $this->item->fulltext),
			'created_by'   => $this->item->created_by,
			'created_by_alias' => $this->item->created_by_alias,
			'created'      => $this->item->created,
			'modified'     => $this->item->modified,
			'publish_up'   => $this->item->publish_up,
			'publish_down' => $this->item->publish_down,
			'ratingValue'  => isset($this->listing['Review']['user_rating']) ? $this->listing['Review']['user_rating'] : 0,
        	'reviewCount'  => isset($this->listing['Review']['user_review_count']) ? $this->listing['Review']['user_review_count'] : 0,
        	'metakey'	   => $this->item->metakey,
            'metadesc'	   => $this->item->metadesc,
            
            // Category Info
            'category.id'     => $this->item->catid,
            'category.title'  => $this->item->category_title,
            'category.alias'  => $this->item->category_alias
		];

        // Joomla Core custom fields
        $this->attachCustomFields($payload);

        // JReviews custom fields
        $this->attachJReviewsCustomFields($payload);

        return $payload;
    }

	/**
	 * Append Custom Fields to payload
	 *
	 * @param	array	$payload
	 * @param   string 	$prefix
	 *
	 * @return	void
	 */
	private function attachCustomFields(&$payload, $prefix = 'cf.')
	{
		$fields = $this->getCustomFields($this->item);
		
		if (!is_array($fields) || count($fields) == 0)
		{
			return;
		}

		foreach ($fields as $key => $field)
		{
			$field_path = $prefix . strtolower($field->name);
			$value = $field->value;

			if ($field->rawvalue && $field->value != $field->rawvalue)
			{
				$value = $field->rawvalue;
			}

			if ($field->type === 'media')
			{
				$value_decoded = json_decode($value, true);
				$value = $value_decoded && isset($value_decoded['imagefile']) ? $value_decoded['imagefile'] : $value;
			}

			$payload[$field_path] = is_array($value) ? implode(', ', $value) : $value;
		}
	}

    private function attachJReviewsCustomFields(&$payload, $prefix = 'jrcf.')
    {
		$fields = $this->getJReviewsCustomFields();
		
		if (!is_array($fields) || count($fields) == 0)
		{
			return;
		}

        foreach ($fields as $field)
        {
			if ($field->name === 'jreviews_directory_slug')
			{
				$payload[$prefix . 'jreviews_directory_slug'] = isset($this->listing['Directory']['slug']) ? $this->listing['Directory']['slug'] : '';
				continue;
			}
			else if ($field->name === 'jreviews_directory_title')
			{
				$payload[$prefix . 'jreviews_directory_title'] = isset($this->listing['Directory']['title']) ? $this->listing['Directory']['title'] : '';
				continue;
			}
			
            $field_path = $prefix . strtolower($field->name);
            $value = isset($this->article[$field->name]) ? $this->article[$field->name] : null;

            if (!$value)
            {
                continue;
            }

            if (in_array($field->type, ['checkboxes', 'select', 'selectmultiple', 'radiobuttons', 'relatedlisting']))
            {
                $value = ltrim($value, '*');
                $value = rtrim($value, '*');
                $value = array_map(function($item) {
                    return trim($item);
                }, explode('*', $value));

				// Get the listing title for each selected listing
				if ($field->type === 'relatedlisting')
				{
					foreach ($value as &$val)
					{
						if ($this->listingModel && $listing = $this->listingModel->getListingById($val))
						{
							$val = isset($listing['Listing']['title']) ? $listing['Listing']['title'] : '';
						}
					}
					$value = array_filter($value);
				}
				else if ($field->type === 'selectmultiple')
				{
					$options = $this->getCustomFieldOptions($field->fieldid);

					if ($options && is_array($options))
					{
						$value = array_map(function($item) use ($options) {
							return isset($options[$item]) ? $options[$item] : $item;
						}, $value);
					}
				}

                $value = implode(', ', $value);
            }

            $payload[$field_path] = $value;
        }
    }

	private function getCustomFieldOptions($field_id)
	{
		$hash = md5($this->_name . 'jreviews_cf_options_' . $field_id);
		if (Cache::has($hash))
		{
			return Cache::get($hash);
		}

		$db = Factory::getDbo();

		$query = $db->getQuery(true)
			->select('text, value')
			->from('#__jreviews_fieldoptions')
			->where('fieldid = ' . (int) $field_id);
		$db->setQuery($query);
		$options = $db->loadObjectList();

		if (!$options)
		{
			return [];
		}

		// Convert options to associative array
		$options = array_reduce($options, function($carry, $item) {
			$carry[$item->value] = $item->text;
			return $carry;
		}, []);
		
		Cache::set($hash, $options);

		return $options;
	}

    /**
	 * The MapOptions Backend Event. Triggered by the mappingoptions fields to help each integration add its own map options.
	 *  
	 * @param	string	$plugin
	 * @param	array	$options
	 *
	 * @return	void
	 */
    public function onMapOptions($plugin, &$options)
    {
		if ($plugin != $this->_name)
        {
			return;
		}

		// Custom mapping options
		$options_ = [
			'image_intro' => 'NR_INTRO_IMAGE',
			'image_full'  => 'NR_FULL_IMAGE',
            'category.id'     => 'GSD_MAPPING_OPTION_CAT_ID',
            'category.alias'  => 'GSD_MAPPING_OPTION_CAT_ALIAS',
            'category.title'  => 'GSD_MAPPING_OPTION_CAT_TITLE'
		];
		MappingOptions::add($options, $options_, 'GSD_INTEGRATION', 'gsd.item.');

		// Add Author Alias option
		$offset = array_search('user.name', array_keys($options['GSD_INTEGRATION']));
		$options['GSD_INTEGRATION'] = Functions::array_splice_assoc($options['GSD_INTEGRATION'], ['gsd.item.created_by_alias' => 'Author Alias'], $offset);
    
        // Add Custom Fields
        if ($custom_fields = $this->getCustomFields())
        {
            $custom_fields_options = [];
        
            foreach ($custom_fields as $key => $field)
            {
                $custom_fields_options[$field->name] = $field->title;
            }
    
            MappingOptions::add($options, $custom_fields_options);
        }

        if ($custom_fields = $this->getJReviewsCustomFields())
        {
            $custom_fields_options = [];
        
            foreach ($custom_fields as $key => $field)
            {
                $custom_fields_options[$field->name] = $field->title;
            }
    
            MappingOptions::add($options, $custom_fields_options, 'PLG_GSD_JREVIEWS_CUSTOM_FIELDS', 'gsd.item.jrcf.');
        }
    }
	
	/**
	 * Load Joomla Articles Custom Fields
	 *
	 * @param  mixed $article
	 *
	 * @return void
	 */
	private function getCustomFields($article = null)
	{
		$hash = md5($this->_name . 'cf');

		if (Cache::has($hash))
		{
			return Cache::get($hash);
		}

		JLoader::register('FieldsHelper', JPATH_ADMINISTRATOR . '/components/com_fields/helpers/fields.php');

		if (!class_exists('FieldsHelper'))
		{
			return;
		}

		$fields = FieldsHelper::getFields('com_content.article', $article, true);

		return Cache::set($hash, $fields);
	}

    private function getJReviewsCustomFields()
    {
        $hash = md5($this->_name . 'jreviews_cf');

		if (Cache::has($hash))
		{
			return Cache::get($hash);
		}

		$jr_custom_fields = [
			// Directory Slug
			(object) [
				'name' => 'jreviews_directory_slug',
				'title' => Text::_('PLG_GSD_JREVIEWS_DIRECTORY_SLUG')
			],
			// Directory Title
			(object) [
				'name' => 'jreviews_directory_title',
				'title' => Text::_('PLG_GSD_JREVIEWS_DIRECTORY_TITLE')
			],
		];

        $db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->select('f.fieldid, f.name, f.type, f.title')
            ->from('#__jreviews_groups as g')
            ->join('LEFT', '#__jreviews_fields as f ON f.groupid = g.groupid')
            ->where('g.type = ' . $db->q('content'));

        $db->setQuery($query);

        $fields = array_merge($jr_custom_fields, $db->loadObjectList());

        return Cache::set($hash, $fields);
    }
}