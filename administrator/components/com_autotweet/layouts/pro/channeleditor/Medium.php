<?php

/*
* This file is part of the Hope engine - Integration hub.
*
* Copyright (C) 2016 Andrea Gentil & Anibal Sanchez.
*                    Email: team[at]extly.com
*
* The above copyright notice and this permission notice shall be included in all
* copies or substantial portions of the Software.
*
* License  https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
*
*/

?>
{% if title is not empty %}<h1>{{ title }}</h1>{% endif %}

<img src="{{ image_url }}"/>

<p>
    {{ message }}
    {% if params.hashtags is not empty %}{{ params.hashtags }}{% endif %}
    <a href="{{ url }}">{{ url }}</a>
</p>

{% if fulltext is not empty %}
<p>
    {{ fulltext }}
</p>
{% endif %}

<hr/>
<p><a href="{{ org_url }}">{{ org_url }}</a></p>
