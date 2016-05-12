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

### Ancestors
