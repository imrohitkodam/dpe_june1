<?php
/* This file has been prefixed by <PHP-Prefixer> for "XT Social Libraries" */

namespace XTS_BUILD\Telegram\Bot\Objects\Payments;

use XTS_BUILD\Telegram\Bot\Objects\BaseObject;

/**
 * @property string         $id        Shipping option identifier.
 * @property string         $title     Option title.
 * @property LabeledPrice[] $prices    List of price portions.
 *
 * @link https://core.telegram.org/bots/api#shippingoption
 */
class ShippingOption extends BaseObject
{
    /**
     * {@inheritdoc}
     */
    public function relations()
    {
        return [
            'prices' => LabeledPrice::class,
        ];
    }
}
