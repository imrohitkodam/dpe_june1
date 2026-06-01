<?php

/**
 * @package         Google Structured Data
 * @version         6.0.1 Pro
 *
 * @author          Tassos Marinos <info@tassos.gr>
 * @link            http://www.tassos.gr
 * @copyright       Copyright © 2021 Tassos Marinos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die('Restricted access');

use GSD\Helper;
use GSD\MappingOptions;
use GSD\PluginBaseProduct;
use NRFramework\Cache;
use Joomla\CMS\Factory;

/**
 *  VirtueMart Google Structured Data Plugin
 */
class plgGSDVirtueMart extends PluginBaseProduct
{
    /**
     *  Indicates the query string parameter name that is used by the front-end component
     *
     *  @var  string
     */
    protected $thingRequestIDName = 'virtuemart_product_id';

    /**
     * The VirtueMart product we are manipulating.
     * 
     * @var  object
     */
    private $product;

    /**
     * The currency of the product.
     * 
     * @var  object
     */
    private $currency;

	/**
     *  Product View
     *  
     *  @return  object
     */
	protected function viewProductDetails()
	{
        $id       = $this->getThingID();
        $product  = VmModel::getModel('Product')->getProduct($id);
        $this->currency = VmModel::getModel('currency')->getCurrency($this->app->getUserState('virtuemart_currency_id', null));
        $rating   = $this->getRating($id);

        if (!is_object($product) || !isset($product->product_name))
        {
            return;
        }

		if (!class_exists('ShopFunctions'))
        {
            require(VMPATH_ADMIN . '/' . 'helpers' . '/' . 'shopfunctions.php');
        }

        // Prepare Data
        $data = [
            'id'          => $product->virtuemart_product_id,
            'alias'       => $product->slug,
            'headline'    => $product->product_name,
            'description' => empty($product->product_s_desc) ? $product->product_desc : $product->product_s_desc,
			'introtext'   => $product->product_s_desc,
            'fulltext'    => $product->product_desc,
            
            // B/C: The description_short property is deprecated. Use introtext instead.
            'description_short' => $product->product_s_desc, 

            'currency'    => $this->currency->currency_code_3,

            // On some sites, when the user is logged-in as Super User, the image property is not available for unknown reason.
            'image'       => isset($product->images) ? $this->getImage($product->images) : null,

            'imagetext'	  => Helper::getFirstImageFromString($product->product_s_desc . $product->product_desc),
            'brand'       => $product->mf_name,
            'ratingValue' => $rating['value'],
            'reviewCount' => $rating['count'],
            'sku'         => $product->product_sku,
            'mpn'         => $product->product_mpn,
            'gtin'        => isset($product->product_gtin) ? $product->product_gtin : null,
            'metakey'     => $product->metakey,
            'metadesc'    => $product->metadesc,
            'reviews'     => $this->getReviews($id),
            'bestRating'  => 5,
            'worstRating' => 0,
            'category_id' => $product->virtuemart_category_id,
            'category_title' => $product->category_name,
            'weight'      => $product->product_weight,
            'weight_unit' => strtolower($product->product_weight_uom)
        ];

        // store product
        $this->product = $product;

        // attach custom fields values
        $this->attachCustomFieldsValues($data);
        
        // set product price
        $this->setProductPrice($data);

        return $data;
    }

    /**
     * Add the custom fields values of the product into the payload
     * 
     * @param   array  $data
     * 
     * @return  void
     */
    private function attachCustomFieldsValues(&$data)
    {
        // Add Custom Fields to payload
        if (isset($this->product->customfieldsSorted) && is_array($this->product->customfieldsSorted))
        {
            foreach ($this->product->customfieldsSorted as $custom_field_group)
            {
                if (!is_array($custom_field_group))
                {
                    continue;
                }

                foreach ($custom_field_group as $custom_field)
                {
                    if (!isset($custom_field->virtuemart_custom_id) || 
                        !isset($custom_field->customfield_value) || 
                        !is_string($custom_field->customfield_value))
                    {
                        continue;
                    }

                    // If a product uses a custom field more than once, the last value will be used only.
                    $key = 'cf.' . $custom_field->virtuemart_custom_id;
                    $data[$key] = $custom_field->customfield_value;
                }
            }
        }
    }

    /**
     * Finds the price of the product.
     * Also finds the min and max prices of all child produces when viewing a parent product that does not have a price set.
     * 
     * @param   array   $data
     * 
     * @return  void
     */
    private function setProductPrice(&$data)
    {
        // Check if product has a price and use it
        if (!empty($this->product->prices['salesPrice']))
        {
            $data['offerPrice'] = $this->getPrice($this->product->prices['salesPrice'], $this->currency->virtuemart_currency_id);
            return;
        }

        // If the product does not have a price and has child products, get the product childs
        $childProducts = $this->getChildProducts($this->product->virtuemart_product_id);

        $prices = [];

        // get their prices
        foreach ($childProducts as $key => $priceData)
        {
            $product = VmModel::getModel('Product')->getProduct($priceData->virtuemart_product_id);
            $prices[] = $this->getPrice($product->prices['salesPrice'], $this->currency->virtuemart_currency_id);
        }

        // remove zero value prices
        $prices = array_filter($prices);

        // no prices found
        if (!count($prices))
        {
            return;
        }

        $data['offerPrice'] = [min($prices), max($prices)];
    }

    /**
     * Returns all child products
     * 
     * @param   string   $product_id
     * 
     * @return  object
     */
    public function getChildProducts($product_id)
    {
        if (empty($product_id))
        {
			return [];
        }

        $db = Factory::getDbo();

        $query = $db->getQuery(true)
            ->select(['virtuemart_product_id'])
            ->from($db->quoteName('#__virtuemart_products'))
            ->where($db->quoteName('product_parent_id') . '=' . $product_id)
            ->where($db->quoteName('published') . '=1');

        $db->setQuery($query);

		return $db->loadObjectList();
	}

    /**
     * Indicates whether the product is available based on the amount of product in stock.
     *
     * @return bool
     */
    protected function productIsAvailable()
    {
        if (!isset($this->product->product_in_stock))
        {
            return;
        }

        return (int) $this->product->product_in_stock > 0;
    }

    /**
     *  Re-calculates and formats given price with a currency
     *
     *  @param   String     The product price
     *  @param   Integer    The currency id
     *
     *  @return  void
     */
    private function getPrice($price, $currency_id)
    {
        @include_once JPATH_ADMINISTRATOR . '/components/com_virtuemart/helpers/currencydisplay.php';

        // Try to re-calculate the price using currency
        if (!class_exists('CurrencyDisplay'))
        {
            return 0;
        }

        $currencyHelper = CurrencyDisplay::getInstance($currency_id);
        return $currencyHelper->roundForDisplay($price);
    }

    /**
     *  Gets Virtuemart product rating
     *
     *  @param   Integer  $id  Product ID
     *
     *  @return  Array
     */
    private function getRating($id)
    {
        $ratingModel = VmModel::getModel('ratings');
        $rating = $ratingModel->getRatingByProduct($id);

        if (!is_object($rating) || !isset($rating->rating))
        {
            return [
                'value' => 0,
                'count' => 0
            ];
        }

        return [
            'value' => $rating->rating,
            'count' => $this->getCountReviews($id)
        ];
    }

    /**
     * Get the reviews of the given product ID
     * 
     * @return  array
     */
    protected function getReviews($id)
    {
        $reviews = $this->getReviewsByProduct($id);

        $data = [];

        foreach ($reviews as $review)
        {
            $data[] = [
                'author' => $review->name,
                'datePublished' => $review->created_on,
                'description' => $review->comment,
                'rating' => $review->review_rating
            ];
        }
        
        return $data;
    }

    /**
     * Gets reviews by a product ID
     *
     * @param   int  $product_id
     * 
     * @return  object
     */
    private function getReviewsByProduct($product_id)
    {
        // Return cached result if it does exist.
        $hash = 'gsdVMProductReviews' . $product_id;

        if (Cache::has($hash))
        {
            return Cache::get($hash);
        }

        $db = Factory::getDbo();

        $query = $db->getQuery(true)
            ->select(['a.created_on', 'a.comment', 'a.review_rating', 'b.name'])
            ->from($db->quoteName('#__virtuemart_rating_reviews', 'a'))
            ->join('LEFT', $db->quoteName('#__users', 'b') . ' ON ' . $db->quoteName('b.id') . ' = ' . $db->quoteName('a.created_by'))
            ->where($db->quoteName('a.created_by') . '=' . $db->quoteName('b.id'))
            ->where($db->quoteName('a.virtuemart_product_id') . '=' . $product_id)
            ->where($db->quoteName('a.published') . '= 1');

        $db->setQuery($query);

        // Cache result
        return Cache::set($hash, $db->loadObjectList());
    }
    
    /**
     * Get the total number of product reviews
     *
     * @param  integer $id
     *
     * @return void
     */
    private function getCountReviews($id)
    {
        return count($this->getReviewsByProduct($id));
    }

    /**
     *  Returns VirtueMart product image
     *
     *  @param   array  $images   Product Images
     *
     *  @return  string
     */
    private function getImage($images)
    {
        if (!is_array($images) || count($images) == 0 || !isset($images[0]) || !isset($images[0]->file_url))
        {
            return;
        }

        return Helper::absURL($images[0]->file_url);
    }

	/**
	 * Listening to the onAfterRender Joomla event
	 *
	 * @return void
	 */
	public function onAfterRender()
	{
        // Make sure we are on the right context
        if ($this->app->isClient('administrator') || 
            !$this->passContext() || 
            $this->getView() != 'productdetails' ||
            !$this->params->get('remove_virtuemart_product_schema', true))
		{
            return;
        }

        // Remove the Product Structured Data item added by Virtuemart
        \GSD\SchemaCleaner::remove('Product');
    }

    /**
     * Return an array of Virtuemart Custom Fields
     *
     * @return  array
     */
    private function getCustomFields()
    {
		// load language
        NRFramework\Functions::loadLanguage('com_virtuemart');
        
        $db = Factory::getDbo();

        $query = $db->getQuery(true);

        // Get all SobiPro Fields
        $query
            ->select($db->quoteName(['virtuemart_custom_id', 'custom_title'], ['name', 'title']))
            ->from($db->quoteName('#__virtuemart_customs'))
            ->where($db->quoteName('custom_parent_id') . ' = 0')
            ->where($db->quoteName('published') . ' = 1');

        $db->setQuery($query);

        return $db->loadObjectList();
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
        parent::onMapOptions($plugin, $options);
        
		if ($plugin != $this->_name)
        {
			return;
		}
        
		$new_options = [
			'gtin' => 'GTIN'
        ];

		MappingOptions::add($options, $new_options, 'GSD_INTEGRATION', 'gsd.item.');
        
		// Add Custom Fields
		if (!$custom_fields = $this->getCustomFields())
		{
			return;
        }
        
		$custom_fields_options = [];
	
		foreach ($custom_fields as $key => $field)
		{
			$custom_fields_options[$field->name] = $field->title;
		}

		MappingOptions::add($options, $custom_fields_options);
	}
}