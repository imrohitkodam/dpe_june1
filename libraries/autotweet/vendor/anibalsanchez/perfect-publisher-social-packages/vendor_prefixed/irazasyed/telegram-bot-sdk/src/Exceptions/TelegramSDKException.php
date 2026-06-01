<?php
/* This file has been prefixed by <PHP-Prefixer> for "XT Social Libraries" */

namespace XTS_BUILD\Telegram\Bot\Exceptions;

/**
 * Class TelegramSDKException.
 */
class TelegramSDKException extends \Exception
{
    /**
     * Thrown when token is not provided.
     *
     * @param $tokenEnvName
     *
     * @return TelegramSDKException
     */
    public static function tokenNotProvided($tokenEnvName): TelegramSDKException
    {
        return new static('Required "token" not supplied in config and could not find fallback environment variable '.$tokenEnvName.'');
    }
}
