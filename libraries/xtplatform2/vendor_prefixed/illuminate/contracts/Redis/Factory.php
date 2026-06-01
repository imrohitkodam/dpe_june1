<?php /* This file has been prefixed by <PHP-Prefixer> for "XT Platform" */

namespace XTP_BUILD\Illuminate\Contracts\Redis;

interface Factory
{
    /**
     * Get a Redis connection by name.
     *
     * @param  string  $name
     * @return \Illuminate\Redis\Connections\Connection
     */
    public function connection($name = null);
}
