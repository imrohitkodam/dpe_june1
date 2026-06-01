<?php
/**
 * @package    DOCman
 * @copyright   Copyright (C) 2011 Timble CVBA (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

$config = array();

if(file_exists(Koowa::getInstance()->getRootPath().'/joomlatools-config/docman.php')) {
    $config = (array) include Koowa::getInstance()->getRootPath().'/joomlatools-config/docman.php';
}

$identifiers = array(
    'identifiers' => array(
        'com://admin/docman.model.behavior.taggable' => array(
            'strict' => isset($config['tags']['strict']) ? $config['tags']['strict'] : false
        ),
        'com:koowa.template.filter.document' => array(
            'strip_assets' => isset($config['filter']['strip_assets']) ? $config['filter']['strip_assets'] : [],
            'file_creation_date' => isset($config['documents']['file_creation_date']) ? $config['documents']['file_creation_date'] : false
        ),

        'com://admin/docman.dispatcher.http' => array(
            'limit' => array(
              'max' => isset($config['paginator']['max_limit']) ? $config['paginator']['max_limit'] : null
            )
        ),

        'com://admin/docman.template.helper.paginator' => array(
            'max_limit' => isset($config['paginator']['max_limit']) ? $config['paginator']['max_limit'] : null
        ),

        'event.subscriber.factory' => array(
            'subscribers' => array(
                'com://admin/docman.event.subscriber.redirect'
            )
        )

    )
);

if (isset($config['csv_export_fields']))
{
    $identifiers['identifiers']['com://admin/docman.view.config.html'] = array(
        'export_fields' => $config['csv_export_fields']
    );
}

return $identifiers;
