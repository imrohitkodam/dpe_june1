<?php /* This file has been prefixed by <PHP-Prefixer> for "XT Social Libraries" */
/**
 * Copyright 2015 Dirk Groenen
 *
 * (c) Dirk Groenen <dirk@bitlabs.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XTS_BUILD\DirkGroenen\Pinterest\Endpoints;

use XTS_BUILD\DirkGroenen\Pinterest\Transport\Request;
use XTS_BUILD\DirkGroenen\Pinterest\Pinterest;


class Endpoint {

    /**
     * Instance of the request class
     *
     * @var Request
     */
    protected $request;

    /**
     * Instance of the master class
     *
     * @var Pinterest
     */
    protected $master;

    /**
     * Create a new model instance
     *
     * @param  Request              $request
     * @param  Pinterest            $master
     */
    public function __construct(Request $request, Pinterest $master)
    {
        $this->request = $request;
        $this->master = $master;
    }

}