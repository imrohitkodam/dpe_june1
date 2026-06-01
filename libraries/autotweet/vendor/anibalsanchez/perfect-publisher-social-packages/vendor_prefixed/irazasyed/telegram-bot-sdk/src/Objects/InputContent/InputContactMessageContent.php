<?php
/* This file has been prefixed by <PHP-Prefixer> for "XT Social Libraries" */

namespace XTS_BUILD\Telegram\Bot\Objects\InputContent;

use XTS_BUILD\Telegram\Bot\Objects\InlineQuery\InlineBaseObject;

/**
 * Class InputContactMessageContent.
 *
 * <code>
 * $params = [
 *   'phone_number'     => '',
 *   'first_name'       => '',
 *   'last_name'        => '',
 * ];
 * </code>
 *
 * @method $this setPhoneNumber($string) Contact's phone number
 * @method $this setFirstName($string)   Contact's first name
 * @method $this setLastName($string)    Optional. Contact's last name
 */
class InputContactMessageContent extends InlineBaseObject
{
}
