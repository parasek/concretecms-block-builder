---
name: block-builder
description: Create Concrete CMS blocks from Block Builder JSON configurations using the console generator. Use for choosing block fields, defining repeatable entries, and generating application block files.
---

# Generate a Concrete CMS block

Paths below are relative to this package. The package must be installed in Concrete CMS for its console command to be available.

## Prepare the configuration

- Start with [predefined_configs/all_fields.json](predefined_configs/all_fields.json). Keep its block settings and UI labels, replace `blockName`, `blockHandle`, and `blockDescription`, and retain only the fields the requested block needs.
- `basic` contains standalone fields; `entries` contains the fields of each repeatable entry. Include both collections, using `[]` for an unused collection. Fields can use JSON arrays or numeric object keys, as the examples do.
- Set `installBlock` explicitly: `false` creates files only; `true` also installs the block type in Concrete. Installation does not add a block to a page.
- A block handle has 3–50 lowercase letters and underscores, no leading/trailing underscore or consecutive underscores. Choose an unused handle; the command rejects existing application folders, installed block types, and core block handles.
- Field handles are PHP identifiers and must be unique within their collection. Use descriptive English names and avoid reserved controller properties. Only one repeatable field may have `titleSource` enabled.
- For canonical field type identifiers, consult [FieldTypeEnum.php](src/BlockBuilder/FieldType/Enum/FieldTypeEnum.php). For a selected type, inspect its `*FieldType.php` and `*FieldTypeDto.php` under `src/BlockBuilder/FieldType/Type/`: `getDefaultValues()`, `getProperties()`, `createDtoFromArray()`, and `validate()` describe defaults, accepted properties, and constraints. Some properties are inherited from `AbstractFieldType`.
- Use [BlockConfigDto.php](src/BlockBuilder/Block/Dto/BlockConfigDto.php) for top-level properties. Prefer native JSON booleans and numbers. The single/multiple-choice examples include older property names; use the current classes when authoring new configurations.
- Preserve labels needed by the chosen tabs and at least one of `addAtTheTopLabel` / `addAtTheBottomLabel`. Do not put command flags such as `rebuildBlock` or icon upload fields in the JSON.

## Choose field types

Use these authoring defaults in both `basic` and `entries`. Set choice fields' `displayType` explicitly when adapting examples or class defaults.

| Requested input | `fieldType` | Configuration guidance |
| --- | --- | --- |
| Short text, heading, or caption | `text_field` | Plain text on one line. |
| Multiline plain text | `textarea` | Use when formatting controls are unnecessary. |
| Formatted content | `wysiwyg_editor` | Use for editor-authored rich text; `html_editor` is for editing HTML source. |
| Image | `image` | Use the image field for image selection, alt text, and image variants. |
| Single checkbox / on-off setting | `select_multiple_field` | Set `displayType: "checkbox_list"` and define one option. Leave `required: false` when unchecked is valid. |
| Multiple independent choices | `select_multiple_field` | Use `displayType: "checkbox_list"` for fewer than 10 options; use `"enhanced_multiselect"` for **10 or more** options. Count selectable options, not selected values or repeatable entries. |
| One choice from a list, including an explicit Yes/No choice | `select_field` | Default to `displayType: "default_select"`, the standard HTML select field. |
| Any link, button destination, page link, external URL, or download link | `link` | Always use Flex Link, including when only one destination type is currently needed. |
| Numeric input | `number` | Use for quantities and numeric settings. |

### Choice fields and common ambiguities

- There is no standalone `checkbox` field type. A single checkbox uses Multiple Choice with one option; its selection represents presence/absence of that option, not a scalar boolean. Inspect the generated view variables before writing conditions.
- For fixed choices, set `listGenerationMethod: "basic_list"` and supply `options` as a newline-separated string, preferably with stable keys: `"small :: Small\nlarge :: Large"`. A Single Choice `defaultValue` is one option key; Multiple Choice defaults use pipe-separated keys such as `"small|large"`, or `""` for no selection.
- For options produced by `custom_code`, apply the same Multiple Choice threshold when the available count is known. If it cannot be determined while authoring, keep the `checkbox_list` default. This is a configuration choice; the generator does not switch display types dynamically.
- Use `link` for all links, including links attached to images. The specific types `link_from_sitemap`, `link_from_file_manager`, and `external_link` are not used for new configurations under this skill.
- An image with a clickable destination needs an `image` field plus a separate Flex Link field. A gallery or list of cards typically puts its image, text, and optional Flex Link fields in `entries`.

For accepted choice display values, see [FieldTypeOptionProvider.php](src/BlockBuilder/Service/Option/FieldTypeOptionProvider.php). For Single Choice option parsing and generated controls, see [SingleChoiceFieldGenerationContributor.php](src/BlockBuilder/FieldType/Type/SingleChoice/Generation/SingleChoiceFieldGenerationContributor.php).

## Validate and generate

From the project root (the directory containing `public`):

```bash
php public/concrete/bin/concrete block-builder:generate /absolute/path/to/config.json --validate-only
php public/concrete/bin/concrete block-builder:generate /absolute/path/to/config.json --no-interaction
```

Adjust the executable path if the site's web root has a different layout. Relative JSON paths are resolved from the shell's working directory; the filename need not match `blockHandle`.

Validation checks the configuration and current handle availability without writing block files or installing anything. It does not execute custom PHP or prove that a subsequent installation will succeed. Follow the project's command approval rules before running generation.

Generation writes to `application/blocks/<blockHandle>` under the site's web root and saves `config-bb.json` there. A zero exit status indicates success; nonzero means failure. Read the error output before retrying. If installation fails after files have been written, inspect the retained folder and Concrete's state before taking another action.

## Customize the result

Treat the generated `config-bb.json` as the source of truth. Keep custom controller methods in `customControllerMethods`, view setup in `viewCustomCode`, and asset setup in `registerViewAssetsCustomCode`, synchronized with the generated controller. Use fully qualified class names in these snippets; do not add `<?php` tags. Configuration code becomes executable PHP, so inspect supplied snippets before generation.

Customize templates and styles as required by the task. Escape user-controlled values when rendering. Keep generated field handling, persistence, and database metadata managed by Block Builder. This console command creates new blocks only; it has no rebuild or overwrite option.
