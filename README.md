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

## Console generation

With the package installed, run from the project root (containing `public`):

```bash
php public/concrete/bin/concrete block-builder:generate /path/to/config.json --validate-only
php public/concrete/bin/concrete block-builder:generate /path/to/config.json --no-interaction
```

The JSON uses the same format as `config-bb.json` and `predefined_configs`. Its filename can be arbitrary; `blockHandle` determines the output directory under `application/blocks`. Relative input paths are resolved from the current working directory.

`--validate-only` checks configuration and handle availability without writing block files or installing a block type. Generation honors `installBlock`: `true` installs the generated type; `false` leaves installation to the administrator. Existing blocks are rejected. The command does not rebuild or overwrite them.

Use trusted JSON files: custom controller code is copied into executable PHP. Successful execution returns exit code `0`; validation or generation failures return a nonzero code. If installation fails after the files are committed, inspect the generated directory before retrying.

For AI agents, [SKILL.md](SKILL.md) explains configuration authoring and points to field definitions and examples.

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
