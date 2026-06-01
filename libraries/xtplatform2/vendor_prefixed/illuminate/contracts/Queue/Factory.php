<?php /* This file has been prefixed by <PHP-Prefixer> for "XT Platform" */

namespace XTP_BUILD\Illuminate\Contracts\Queue;

interface Factory
{
    /**
     * Resolve a queue connection instance.
     *
     * @param  string  $name
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public function connection($name = null);
}
