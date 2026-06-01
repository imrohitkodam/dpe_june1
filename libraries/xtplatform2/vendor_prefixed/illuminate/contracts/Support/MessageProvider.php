<?php /* This file has been prefixed by <PHP-Prefixer> for "XT Platform" */

namespace XTP_BUILD\Illuminate\Contracts\Support;

interface MessageProvider
{
    /**
     * Get the messages for the instance.
     *
     * @return \Illuminate\Contracts\Support\MessageBag
     */
    public function getMessageBag();
}
