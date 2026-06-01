<?php
/* This file has been prefixed by <PHP-Prefixer> for "XT Social Libraries" */

namespace XTS_BUILD\Telegram\Bot\Commands;

use XTS_BUILD\Telegram\Bot\Api;
use XTS_BUILD\Telegram\Bot\Objects\Update;

/**
 * Interface CommandInterface.
 */
interface CommandInterface
{
    public function getName(): string;

    public function getAliases(): array;

    public function getDescription(): string;

    public function getArguments(): array;

    public function make(Api $telegram, Update $update, array $entity);
}
