# Navee for CraftCMS
Navigation module for Craft CMS


## Terminology
### Basic
* **Navigation**: A collection of nodes.
* **Node**: A single item in your navigation.
* **Active Node**: The node in your navigation which matches the current uri.
* **Passive Node**: A node which exists in a navigation but will never be marked as active, despite potentially matching the current uri.
* **Root Node**: Top level node; a node with a depth of 1.
* **Branch**: All nodes which are descendants of (and including) a root node.
* **Depth**: The level of a node in the navigation. For example a root node has a depth of 1. The children of a root node have a depth of 2, and so on.

### Lineage
The terminology we are using to describe nodes in relation to the active node.

* **Ancestors**: All nodes above the active node in a branch.
* **Descendants**: All nodes below the active node in a branch.
* **Siblings**: All nodes with the same depth as the active node.
* **Parent**: The node directly above the active node.
* **Children**: The nodes directly below the active node.

## Parameters
### maxDepth : integer
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

