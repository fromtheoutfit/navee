# Navee for Craft CMS
Simple Navigation, Made Simple. Build any kind of navigation you like, without limitation. Rewritten from the ground up for Craft CMS.

![Navee for craft control panel](https://fromtheoutfit.com/lib/media/add-ons/navee/navee-for-craft-cp.gif)

## Features
* Flexible navigations made up of assets, categories, custom urls and entries (singles, channels and structures).
* Output a [simple nested unordered list](https://github.com/fromtheoutfit/navee/wiki/Tags#simple---craftnaveenav) or create [custom html](https://github.com/fromtheoutfit/navee/wiki/Tags#custom---craftnaveegetnav) for your navigation.
* Custom field groups per navigation.
* Limit node visibility to specific User Groups.
* Dynamic navigation based on current active node.
* Easily create [breadcrumbs](https://github.com/fromtheoutfit/navee/wiki/Parameters#breadcrumbs).

## Documentation
Full documentation can be found [in the wiki](https://github.com/fromtheoutfit/navee/wiki).

## Tags
Outputting simple nested unordered lists can be as simple as:

    {{ craft.navee.nav('mainNavigation') }}

Or you can roll your own HTML:

    {% set navConfig = {
            'startwithActive' : true,
            'maxDepth' : 2,
            'activeClassOnAncestors' : true,
            'ancestorActiveClass' : 'activeAncestor',
        } %}

    {% set navigation = craft.navee.getNav('mainNavigation', navConfig) %}

    <ul>
        {% nav node in navigation %}
            <li{% if node.class %} class="{{ node.class }}"{% endif %}>
                <a href="{{ node.link }}">{{ node.title }}</a>
                {% ifchildren %}
                    <ul>{% children %}</ul>
                {% endifchildren %}
            </li>
        {% endnav %}
    </ul>

Read more about your [tag options in the wiki](https://github.com/fromtheoutfit/navee/wiki/Tags).

## Configuring Your Navigation
Navee comes with lots of great options for configuring your navigation. Read about all [available parameters in the wiki](https://github.com/fromtheoutfit/navee/wiki/Parameters).




