<?php /* This file has been prefixed by <PHP-Prefixer> for "XT Social Libraries" */
/**
 * Copyright 2015 Dirk Groenen 
 *
 * (c) Dirk Groenen <dirk@bitlabs.nl>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XTS_BUILD\DirkGroenen\Pinterest\Models;

class Section extends Model {
        
    /**
     * The available object keys
     * 
     * @see https://developers.pinterest.com/docs/api/sections/?
     * 
     * @var array
     */
    protected $fillable = ["id", "title"];

}
