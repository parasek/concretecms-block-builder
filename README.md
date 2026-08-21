# Block Builder

Design, configure, and generate custom Concrete CMS blocks through a user-friendly Dashboard interface.

Block Builder creates the files and field-handling code required by a working block. You can then customize the generated templates, styles, and behavior for your project.

## Features

- Generate complete Concrete CMS blocks through an intuitive Dashboard interface.
- Use fields as standalone block fields or as part of a repeatable set.
- Generate block and field handles automatically while retaining full control over manual edits.
- Load an existing Block Builder configuration as the starting point for a new block.
- Rebuild existing blocks when their configuration changes.

### Available field types

- Text
- Number
- Textarea
- WYSIWYG Editor
- HTML Editor
- Single Choice
- Multiple Choice
- Flex Link
- Link from Sitemap
- Link from File Manager
- External Link
- Image
- Express
- File Set
- Files from Folder
- Date Picker
- Color Picker
- Icon Picker
- SVG Icon Picker
- User Selector

All field types can be used as standalone fields or in repeatable entries.

## Requirements

| Block Builder version | Concrete CMS version | PHP version |
| --- | --- | --- |
| 3.0.0 or later | 9.5.2 or later | 8.4 or later |
| 2.5.0–2.8.1 (EOL) | 9.2.0 or later | 7.2–8.4 |
| 2.2.0–2.4.0 (EOL) | 9.1.x | 7.2–8.4 |
| 2.0.0–2.1.2 (EOL) | 9.0.x | 7.2–7.4 |
| 1.0.3–1.4.0 (EOL) | 8.2.1 or later | 5.5–7.4 |
| 1.2.1-legacy (EOL) | 5.7.5 or later | 5.3–7.1 |

## Installation

Block Builder is available from the [Concrete CMS Marketplace](https://market.concretecms.com/products/block-builder/49b29b3c-d119-11ee-b9df-0a97d4ce16b9).

To install the latest version with Composer, run:

```shell
composer require parasek/block_builder
```

After adding the package, install Block Builder from the Concrete CMS Dashboard if it has not already been installed.

## Security

Block Builder can place custom PHP code in generated block controllers. Grant access to its Dashboard pages and the `install_packages` permission only to fully trusted administrators who are authorized to execute server-side code.

## Support the project

If Block Builder is useful to you, you can support its development by:

- starring the [GitHub repository](https://github.com/parasek/concretecms-block-builder);
- leaving a five-star rating on the [Concrete CMS Marketplace](https://market.concretecms.com/products/block-builder/49b29b3c-d119-11ee-b9df-0a97d4ce16b9);
- reporting issues or suggesting improvements through [GitHub Issues](https://github.com/parasek/concretecms-block-builder/issues);
- sending feedback through the [contact form](https://c5center.com/contact).

## Project links

- [Concrete CMS Marketplace](https://market.concretecms.com/products/block-builder/49b29b3c-d119-11ee-b9df-0a97d4ce16b9)
- [GitHub repository](https://github.com/parasek/concretecms-block-builder)
- [Legacy GitHub repository](https://github.com/parasek/concretecms-block-builder-legacy)

## Tests

See the [testing documentation](tests/README.md) for PHPUnit commands, coverage information, and the release smoke-test checklist.
