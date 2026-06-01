<?php

/*
 * @package     Perfect Publisher
 *
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2022 Extly, CB. All rights reserved.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *
 * @see         https://www.extly.com
 */

defined('_JEXEC') || exit;

?>
{% if generatedAuthor is defined and params.author_article == 'top' %}
{{ generatedAuthor | raw }}
{% endif %}
{% if generatedImage is defined %}
{{ generatedImage | raw }}
{% endif %}
{{ introtext | raw }}
{% if fulltext %}
<hr id="system-readmore" />
{{ fulltext | raw }}
{% endif %}
{% if generatedAuthor is defined and params.author_article == 'bottom' %}
{{ generatedAuthor | raw }}
{% endif %}
{% if generatedReadonLink  is defined %}
{{ generatedReadonLink | raw }}
{% endif %}
