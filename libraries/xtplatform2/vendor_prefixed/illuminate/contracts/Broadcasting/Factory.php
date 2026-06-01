<?php /* This file has been prefixed by <PHP-Prefixer> for "XT Platform" */

namespace XTP_BUILD\Illuminate\Contracts\Broadcasting;

interface Factory
{
    /**
     * Get a broadcaster implementation by name.
     *
     * @param  string  $name
     * @return void
     */
    public function connection($name = null);
}
