<?php
/* This file has been prefixed by <PHP-Prefixer> for "XT Social Libraries" */

namespace XTS_BUILD\Telegram\Bot\Objects;

/**
 * Class Contact.
 *
 *
 * @property string   $phoneNumber    Contact's phone number.
 * @property string   $firstName      Contact's first name.
 * @property string   $lastName       (Optional). Contact's last name.
 * @property int      $userId         (Optional). Contact's user identifier in Telegram.
 */
class Contact extends BaseObject
{
    /**
     * {@inheritdoc}
     */
    public function relations()
    {
        return [];
    }
}
