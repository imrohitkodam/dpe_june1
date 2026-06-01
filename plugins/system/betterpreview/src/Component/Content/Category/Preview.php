<?php
/**
 * @package         Better Preview
 * @version         6.7.1PRO
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://regularlabs.com
 * @copyright       Copyright © 2022 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

namespace RegularLabs\Plugin\System\BetterPreview\Component\Content\Category;

defined('_JEXEC') or die;

use RegularLabs\Plugin\System\BetterPreview\Component\Preview as Main_Preview;

class Preview extends Main_Preview
{

    public function renderPreview(&$article, $context)
    {
        if ($context != 'com_content.category' || isset($article->introtext))
        {
            return;
        }

        parent::render($article, $context);
    }

    public function states()
    {
        parent::initStates(
            'categories',
            ['parent' => 'parent_id'],
            'categories',
            ['parent' => 'parent_id']
        );
    }
}
