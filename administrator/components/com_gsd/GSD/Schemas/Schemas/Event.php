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

namespace GSD\Schemas\Schemas;

// No direct access
defined('_JEXEC') or die;

use GSD\Helper;

class Event extends \GSD\Schemas\Base
{
    /**
     * A key => value array with schema properties that needs to be renamed.
     * 
     * The left value represents the name of the property as defined in the schema's XML file.
     * The right value represents the name of the property as it's expected in JSON class.
     *  
     * @Todo - We should rename all properties directly in each schema XML file and then get rid of this property.
     * 
     * @var array
     */
    protected $rename_properties = [
        'locationAddress' => 'streetAddress'
    ];

    /**
     * Return all the schema properties
     *
     * @return void
     */
    protected function initProps()
    {
        $props = [
            'startDate'     => Helper::date($this->data['startDate'], true),
            'endDate'       => Helper::date($this->data['endDate'], true),
            'startDateTime' => Helper::date($this->data['offerStartDate'], true),
            'price'         => $this->getPrice()
        ];

        $this->data->loadArray($props);

        parent::initProps();
    }
}