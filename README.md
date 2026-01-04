# Block Builder

Build custom Concrete CMS blocks with an optional set of repeatable entries.

# How to support this project

If you like Block Builder and want to support development of this addon, feel free to star [GitHub page](https://github.com/parasek/concretecms-block-builder) or rate addon on [Concrete CMS Marketplace](https://market.concretecms.com/products/block-builder/49b29b3c-d119-11ee-b9df-0a97d4ce16b9).

Reporting errors via [GitHub Issues](https://github.com/parasek/concretecms-block-builder/issues) or [website](https://c5center.com/contact) is always appreciated.

# Requirements

Latest version of Block Builder requires Concrete version 9.2.0 or higher and is compatible with PHP 8.

For Concrete 8.2.1+ use the highest version of 1.x.x branch.

Concrete CMS Marketplace: [https://market.concretecms.com/products/block-builder/49b29b3c-d119-11ee-b9df-0a97d4ce16b9](https://market.concretecms.com/products/block-builder/49b29b3c-d119-11ee-b9df-0a97d4ce16b9)

GitHub: [https://github.com/parasek/concretecms-block-builder](https://github.com/parasek/concretecms-block-builder)

If you are looking for version suited for Concrete5.7, check [Block Builder Legacy](https://github.com/parasek/concretecms-block-builder-legacy).


# Install the latest version with Composer

`composer require parasek/block_builder`

When using Concrete5.8

`composer require parasek/block_builder:^1.4.0`

# Features

This addon is made for developers and site builders. It allows you to quickly create skeleton for your block, but you still need to manually add some custom css/html code in view.php.

- Build custom Concrete CMS blocks in your dashboard using user-friendly interface
- Generated blocks can have up to 2 tabs:
  - Basic information (non-repeatable fields)
  - Repeatable entries (one set of repeatable fields)
- Multiple field types available:
  - Text
  - Number
  - Textarea
  - WYSIWYG Editor
  - Single Choice Field
  - Multiple Choice Field
  - Flex Link
  - Link from Sitemap
  - Link from File Manager
  - External Link
  - Image
  - Express
  - File Set
  - HTML Editor
  - Date Picker
  - Color Picker
  - Icon Picker
- You can create blocks based on configuration of previously created blocks
