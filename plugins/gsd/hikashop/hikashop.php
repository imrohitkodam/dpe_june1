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
use Joomla\Registry\Registry;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

/**
 *   Google Structured Data Plugin
 */
class plgGSDHikaShop extends PluginBaseProduct
{
    /**
     * The product data
     *
     * @var array
     */
    private $product;

    /**
     *  Discover HikaShop Product ID. 
     *  
     *  When the product is attached to a menu item Hikashop uses the product_id parameter to represent the product's ID. 
     *  Otherwise it uses the cid parameter. Why guys? 
     *
     *  @return  string
     */
    protected function getThingID()
    {
        // First check if the cid parameter is available
        if ($cid = $this->app->input->getInt('cid'))
        {
            return $cid;
        }

        // Otherwise return the product_id parameter
        return $this->app->input->getInt('product_id');
    }

    /**
     *  Validate context to decide whether the plugin should run or not.
     *  Disable when we are comparing products.
     *
     *  @return   bool
     */
    protected function passContext()
    {
    	if ($this->app->input->get('layout') == 'compare')
    	{
    		return;
    	}

    	return parent::passContext();
    }

    /**
     *  Product View
     *  
     *  @return  object
     */
    protected function viewProduct()
    {
        if (!$this->product = $this->getProduct($this->getThingID()))
        {
            return;
        }

        $product_title = $this->product->title;

        // If current product has no title, is a variable product and has a parent product, fetch the title from parent product
        if (empty($product_title) && $this->product->parent_id)
        {
            $parent_product = $this->getProduct($this->product->parent_id);
            $product_title = $parent_product->title;
        }

        // Prepare Data
        return [
			'id'          => $this->product->id,
			'alias'       => $this->product->alias,
            'headline'    => $product_title,
            'description' => $this->product->description,
			'introtext'   => $this->product->description, // HikaShop doesn't seem to separate into and full text. 
			'fulltext'    => $this->product->description, // HikaShop doesn't seem to separate into and full text.
            'image'       => Uri::base() . hikashop_config()->get('uploadfolder') . $this->product->image,
            'imagetext'	  => \GSD\Helper::getFirstImageFromString($this->product->description),
            'offerPrice'  => $this->getProductPrice($this->getThingID()),
            'currency'    => $this->getCurrency(),
            'brand'       => $this->product->brandName,
            'ratingValue' => $this->product->ratingValue,
            'reviewCount' => $this->product->reviewCount,
            'sku'         => $this->product->sku,
            'product_condition' => $this->product->product_condition,
            'reviews'     => $this->getReviews(),
            'start_sale_date' => $this->product->start_sale_date,
            'end_sale_date' => $this->product->end_sale_date,
            'bestRating'  => 5,
            'worstRating' => 0
        ];
    }

    /**
     * Indicates whether the product is available based on the amount of product in stock.
     *
     * @return bool
     */
    protected function productIsAvailable()
    {
        if ($this->product->product_quantity == '-1' || (int) $this->product->product_quantity > 0)
        {
            return true;
        }

        return false;
    }

    /**
     * Get the details of a product
     * 
     * @param   int  $id
     * 
     * @return  object
     */
    private function getProduct($id)
    {
        if (!$id)
        {
            return;
        }

        // Fetch data from DB
        $db = $this->db;
        $query = $db->getQuery(true)
            ->select(
                array(
                    'p.product_id as id', 
                    'p.product_alias as alias', 
                    'p.product_name as title', 
                    'p.product_description as description', 
                    'p.product_code as sku',
                    'p.product_average_score as ratingValue',
                    'p.product_total_vote as reviewCount',
                    'f.file_path as image',
                    'c.category_name as brandName',
                    'p.product_msrp as retailPrice',
                    'pr.price_value as price',
                    'p.product_sale_start as start_sale_date',
                    'p.product_sale_end as end_sale_date',
                    'p.product_condition as product_condition',
                    'p.product_quantity'
                ))
            ->from('#__hikashop_product as p')
            ->where('p.product_id = ' . $db->q($id))
            ->join('LEFT', '#__hikashop_file as f on p.product_id = f.file_ref_id AND f.file_type = "product"')
            ->join('LEFT', '#__hikashop_category as c on p.product_manufacturer_id = c.category_id AND c.category_type = "manufacturer"')
            ->join('LEFT', '#__hikashop_price as pr on p.product_id = pr.price_product_id')

            ->setLimit('1');

        $db->setQuery($query);

        return $db->loadObject();
    }

    /**
     * Get the reviews of the product
     * 
     * @return  array
     */
    private function getReviews()
    {
        // Fetch data from DB
        $db = $this->db;
        $query = $db->getQuery(true)
        ->select(
            [
                'u.user_cms_id as user_id',
                'v.vote_pseudo',
                'v.vote_date as datePublished',
                'v.vote_comment as description',
                'v.vote_rating as rating'
            ])
            ->from('#__hikashop_vote as v')
            ->where('v.vote_ref_id = ' . $db->q($this->product->id))
            ->where("v.vote_type = 'product'")
            ->where("v.vote_rating != 0")
            ->join('LEFT', '#__hikashop_user as u on u.user_id = v.vote_user_id');

        $db->setQuery($query);

        $reviews = $db->loadAssocList();

        foreach ($reviews as &$review)
        {
            // Set author name
            $user = Factory::getUser($review['user_id']);
            $review['author'] = $user->name ?: $review['vote_pseudo'];

            unset($review['user_id']);
            unset($review['vote_pseudo']);
        }
        
        return $reviews;
    }

    /**
     * Get product real price with or without tax
     *
     * @param  integer $id  The Product ID
     *
     * @return mixed
     */
    private function getProductPrice($id)
    {
        $productClass = hikashop_get('class.product');
        $product = $productClass->get($id);

        $currencyClass = hikashop_get('class.currency');
        $config = hikashop_config();
        $ids = array($product->product_id);
        $currencyClass->getPrices($product, $ids, hikashop_getCurrency(), $config->get('main_currency'), hikashop_getZone(), $config->get('discount_before_tax'));

        if (!isset($product->prices))
        {
            return $this->product->price > 0 ? $this->product->price : $this->product->retailPrice;
        }

        return ($this->params->get('show_taxed_price', false)) ? $product->prices[0]->price_value_with_tax : $product->prices[0]->price_value;
    }

    /**
     *  Get HikaShop default currency code
     *
     *  @return  string
     */
    private function getCurrency()
    {
        $currencyHelper   = hikashop_get('class.currency');
        $configCurrencyID = hikashop_getCurrency();

        $currencies = null;
        $currencies = $currencyHelper->getCurrencies($configCurrencyID, $currencies);
        $currency   = $currencies[$configCurrencyID];

        return (isset($currency->currency_code)) ? $currency->currency_code : "";
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
			'product_condition' => Text::_('GSD_PRODUCT_CONDITION'),
			'start_sale_date' => Text::_('PLG_GSD_HIKASHOP_SALE_START_DATE'),
			'end_sale_date' => Text::_('PLG_GSD_HIKASHOP_SALE_END_DATE'),
        ];

		MappingOptions::add($options, $new_options, 'GSD_INTEGRATION', 'gsd.item.');
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
            $this->getView() != 'product' ||
            !$this->params->get('remove_hikashop_product_schema', true))
		{
            return;
        }

        // Remove Hikashop Product microdata
        \GSD\SchemaCleaner::remove('Product');
	}
}
