<?php
/**
 * @package         Modals
 * @version         12.3.5PRO
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://regularlabs.com
 * @copyright       Copyright © 2023 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

namespace RegularLabs\Plugin\System\Modals;

defined('_JEXEC') or die;


use RegularLabs\Library\ObjectHelper;

class Thumbnail
{
    private $image;

    public function __construct(Image $main_image, $settings)
    {
        $this->image = ObjectHelper::clone($main_image)
            ->setEnableResize($settings->{'create-thumbnails'} ?? true)
            ->setDimensions($settings->{'thumbnail-width'} ?? 0, $settings->{'thumbnail-height'} ?? 0)
            ->setItemProp($settings->{'thumbnail-add-itemprop'} ?? false);
    }

    public function render()
    {
        return $this->image->render();
    }
}
