# Navee for CraftCMS
Navigation module for Craft CMS


## Terminology
### Basic
* __Navigation__: A collection of nodes.
* __Node__: A single item in your navigation.
* __Active Node__: The node in your navigation which matches the current uri.
* __Passive Node__: A node which exists in a navigation but will never be marked as active, despite potentially matching the current uri.
* __Root Node__: Top level node; a node with a depth of 1.
* __Branch__: All nodes which are descendants of (and including) a root node.
* __Depth__: The level of a node in the navigation. For example a root node has a depth of 1. The children of a root node have a depth of 2, and so on.

### Lineage
The terminology we are using to describe nodes in relation to the active node.

* __Ancestors__: All nodes above the active node in a branch.
* __Descendants__: All nodes below the active node in a branch.
* __Siblings__: All nodes with the same depth as the active node.
* __Parent__: The node directly above the active node.
* __Children__: The nodes directly below the active node.

## Parameters
### activeClass
The class name you would like to associate with the active node.

<table>
    <tbody>
        <tr>
            <th>Type</th>
            <td>String</td>
        </tr>
        <tr>
            <th>Default</th>
            <td>active</td>
        </tr>
    </tbody>
</table>


### maxDepth
The maximum depth of a navigation from the root node.

    maxDepth : 3

### startDepth : integer
The depth at which to start your navigation.

    startDepth : 2

### startWithAncestorOfActive
Start your navigation with the root node ancestor of the branch in which the active node exists.

> **Type**: Boolean
> **Default**: false

    startWithAncestorOfActive : true

#### Example
    {% set navConfig = {
        'startWithAncestorOfActive' : true,
    } %}
    {{ craft.navee.nav('mainNavigation', navConfig) }}

##### Full Navigation
* Item 1
    * Item 1.1
        * Item 1.1.1
        * Item 1.1.2 (**Active Node**)
            * Item 1.1.2.1
            * Item 1.1.2.2
        * Item 1.1.3
    * Item 1.2
* Item 2
    * Item 2.1
    * Item 2.2

##### Result
* Item 1
    * Item 1.1
        * Item 1.1.1
        * Item 1.1.2 (**Active Node**)
            * Item 1.1.2.1
            * Item 1.1.2.2
        * Item 1.1.3
    * Item 1.2

### startXLevelsAboveActive
Start your navigation x levels above the active node

> **Type**: Integer
> **Default**: 0

    startXLevelsAboveActive : true

#### Example
    {% set navConfig = {
        'startXLevelsAboveActive' : 1,
    } %}
    {{ craft.navee.nav('mainNavigation', navConfig) }}

##### Full Navigation
* Item 1
    * Item 1.1
        * Item 1.1.1
        * Item 1.1.2 (**Active Node**)
            * Item 1.1.2.1
            * Item 1.1.2.2
        * Item 1.1.3
    * Item 1.2
* Item 2
    * Item 2.1
    * Item 2.2

##### Result
    * Item 1.1
        * Item 1.1.1
        * Item 1.1.2 (**Active Node**)
            * Item 1.1.2.1
            * Item 1.1.2.2
        * Item 1.1.3
    * Item 1.2

