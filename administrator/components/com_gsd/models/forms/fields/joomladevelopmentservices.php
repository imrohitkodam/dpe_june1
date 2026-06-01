
<?php

/**
 * @package         Google Structured Data
 * @version         6.0.1 Pro
 * 
 * @author          Tassos Marinos <info@tassos.gr>
 * @link            http://www.tassos.gr
 * @copyright       Copyright © 2025 Tassos Marinos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Form\Field\TextField;

class JFormFieldJoomlaDevelopmentServices extends TextField
{
    private $services = [
        [
            'name' => 'Structured Data Setup & Error Clean Up',
            'description' => 'We will help you setup schema and structured data on your website as per your requirements and as per recommendation by our expert developers.',
            'url' => 'https://www.tassos.gr/services/structured-data-setup'
        ]
    ];

    /**
     * Method to get a list of options for a list input.
     *
     * @return   string
     */
    protected function getInput()
    {
        $output = '';

        foreach ($this->services as $service)
        {
            $output .= '<div class="service">';
            $output .= '<span class="icon-wrench" aria-hidden="true"></span>';
            $output .= '<div><h3>' . $service['name'] . '</h3>';
            $output .= '<p>' . $service['description'] . '</p>';
            $output .= '<a class="btn btn-success" href="' . $service['url'] . '" target="_blank">Get this service</a>';
            $output .= '</div></div>';
        }

        return '<div class="services"> ' . $output . '</div>';
    }
}