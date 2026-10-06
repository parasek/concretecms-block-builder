---
name: block-builder
description: Create or rebuild Concrete CMS blocks from Block Builder JSON configurations using console commands. Use for choosing block fields, defining repeatable entries, and generating application block files.
---

# Create or rebuild a Concrete CMS block

Paths below are relative to this package. The package must be installed in Concrete CMS for its console command to be available.

## Prepare the configuration

- For a new block, use [predefined_configs/all_fields.json](predefined_configs/all_fields.json) as a reference for block settings, UI labels, and field examples. Create a minimal configuration containing only the requested fields; review settings rather than copying them blindly, and set `blockName`, `blockHandle`, and `blockDescription` for the new block. For rebuilding, use the existing block's configuration and retain its handle.
- `basic` contains standalone fields; `entries` contains the fields of each repeatable entry. Include both collections, using `[]` for an unused collection. Fields can use JSON arrays or numeric object keys, as the examples do.
- For generation, set `installBlock` explicitly: `false` creates files only; `true` also installs the block type in Concrete. Installation does not add a block to a page.
- A block handle has 3–50 lowercase letters and underscores, no leading/trailing underscore or consecutive underscores. For a new block, choose an unused handle; `generate` rejects existing application folders, installed block types, and core block handles.
- Field handles are PHP identifiers and must be unique within their collection. Use descriptive English names and avoid reserved controller properties. Only one repeatable field may have `titleSource` enabled.
- For canonical field type identifiers, consult [FieldTypeEnum.php](src/BlockBuilder/FieldType/Enum/FieldTypeEnum.php). For a selected type, inspect its `*FieldType.php` and `*FieldTypeDto.php` under `src/BlockBuilder/FieldType/Type/`: `getDefaultValues()`, `getProperties()`, `createDtoFromArray()`, and `validate()` describe defaults, accepted properties, and constraints. Some properties are inherited from `AbstractFieldType`.
- Use [BlockConfigDto.php](src/BlockBuilder/Block/Dto/BlockConfigDto.php) for top-level properties. Prefer native JSON booleans and numbers.
- Preserve labels needed by the chosen tabs and at least one of `addAtTheTopLabel` / `addAtTheBottomLabel`. Do not put command flags such as `rebuildBlock` or icon upload fields in the JSON.

## Choose field types

Apply the field recommendations below to both standalone fields (`basic`) and repeatable-entry fields (`entries`). For most text content, default to one of the first two `wysiwyg_editor` configurations. Use plain-text fields only for the specific cases listed below.

Review the available options for each selected field type and configure those relevant to the requested content and editing experience. Do not rely solely on defaults or introduce constraints the request does not imply.

| Requested input | `fieldType` | Configuration |
| --- | --- | --- |
| Headings, slider captions, labels, or similar display text, including content that may need basic formatting or line breaks later | `wysiwyg_editor` | Apply `basic_editor`: allows inline formatting and `<br>`, removes `<p>`. |
| Longer text, content with paragraphs, or other editor-authored rich text | `wysiwyg_editor` | Use a configuration that allows paragraph tags. |
| Very simple single-line values that should remain plain text and will not need formatting | `text_field` | — |
| Explicitly requested plain textarea, or a simple line-separated list such as phone numbers to split and display individually | `textarea` | — |
| Editable HTML source | `html_editor` | — |
| Image with alt text and thumbnail settings | `image` | — |
| Single checkbox / on-off setting | `select_multiple_field` | Set `displayType: "checkbox_list"` and define one option. Leave `required: false` when unchecked is valid. |
| Multiple independent choices | `select_multiple_field` | Use `displayType: "checkbox_list"` for fewer than 10 options; use `"enhanced_multiselect"` for **10 or more** options. Count selectable options, not selected values or repeatable entries. |
| One choice from a list, including an explicit Yes/No choice | `select_field` | Default to `displayType: "default_select"`, the standard HTML select field. |
| Any link, button destination, page link, external URL, or download link | `link` | Always use Flex Link, including when only one destination type is currently needed. |
| Number, quantity, or numeric setting | `number` | — |

For WYSIWYG presets, copy `allowedTags` and `customConfig` from the selected entry in [WysiwygEditorPresets.php](src/BlockBuilder/FieldType/Type/WysiwygEditor/WysiwygEditorPresets.php). There is no persisted preset property; `customConfig` must be a JSON-encoded string, not a JSON object.

### Choice fields and common ambiguities

- There is no standalone `checkbox` field type. A single checkbox uses Multiple Choice with one option; its selection represents presence/absence of that option, not a scalar boolean. Inspect the generated view variables before writing conditions.
- For fixed choices, set `listGenerationMethod: "basic_list"` and supply `options` as a newline-separated string, preferably with stable keys: `"small :: Small\nlarge :: Large"`. A Single Choice `defaultValue` is one option key; Multiple Choice defaults use pipe-separated keys such as `"small|large"`, or `""` for no selection.
- For dynamic options in `select_field` or `select_multiple_field`, set `listGenerationMethod: "custom_code"` and store the PHP in the field's `customCode` property, for example to populate choices from existing Express entries or a Concrete page list. Inspect the corresponding generation contributor or custom-code example for the expected options variable and key/label format. Use stable option keys and fully qualified class names; do not manually edit the generated options method.
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

Treat `config-bb.json` as the source of truth. Add custom controller methods through `customControllerMethods`, view setup through `viewCustomCode`, and asset setup through `registerViewAssetsCustomCode`; let Block Builder generate the controller from these properties. Choice fields also support their own `customCode` as described above. In all configuration snippets, use fully qualified class names such as `\Full\Path\To\Class`; do not add custom `use` imports to the controller or `<?php` tags to snippets. Configuration code becomes executable PHP, so inspect supplied snippets before generation.

Do not manually edit generated block files, including `controller.php`, `form.php`, `add.php`, `edit.php`, and `db.xml`. Direct edits are allowed only in `view.php` and files or directories listed in `excludedFromRemoval` (by default, `templates/`); make other changes through `config-bb.json` and regeneration. `view.php` is also regenerated: preserve it before rebuilding, then restore or adapt it afterward. Excluding a generated file does not prevent the generator from overwriting it. Escape user-controlled values when rendering, and keep generated field handling, persistence, and database metadata managed by Block Builder. The `generate` command creates new blocks only. Use `rebuild` for existing installed Block Builder blocks.

## Rebuild an existing block

Before editing, inspect `application/blocks/<handle>/config-bb.json`, generated PHP, and `db.xml`. Save the original configuration, controller, and other customized files outside the block directory, including `view.php`, `templates/`, CSS, JavaScript, and other assets. Update the existing configuration without changing `blockHandle`; the command reads this file directly and rejects a mismatched handle, unsafe directory, invalid configuration, or uninstalled block.

Proceed in this order: save originals, edit and compare fields, validate, rebuild, then restore or adapt custom files and verify the result. Store custom controller changes in the configuration as described in **Customize the result**.

### Preserve existing manual customizations

Check `controller.php` and other regenerated files for user-added code not represented in `config-bb.json`. Compare with the configuration, generator templates/contributors, and available version history; do not mistake generated code for custom additions. Move custom behavior into the supported JSON properties or field-level `customCode`, and presentation-only code into `view.php` or excluded templates, preserving execution order and behavior. Replace custom class imports with fully qualified names in configuration snippets.

If a customization cannot be safely preserved in these locations, identify the affected file and behavior and tell the user that rebuilding will overwrite it. Keep a backup and obtain approval before proceeding with a rebuild that would lose that behavior; do not silently discard the code or restore the old controller over the regenerated one.

### Check existing field compatibility

Compare old and proposed fields in `basic` and `entries` against the existing `db.xml` and controller, including type or storage-option changes, renames, removals, and moves between collections. For every conversion, check storage format, save/render behavior, existing values, empty/null handling, size, range, precision, and serialization. The same handle does not guarantee compatibility, and `--validate-only` does not check stored data. Discover Concrete CMS MCP tools first for live data checks; unavailable data is not evidence of compatibility.

For incompatible or uncertain changes, prepare a migration and database recovery plan before rebuilding. Obtain approval for unresolved conversion choices or destructive changes before executing them. Do not silently cast, truncate, replace invalid values with zero, discard data, or rename the field to bypass the issue.

Examples (not an exhaustive list):

- Text to `number` changes storage to `decimal`: check numeric validity, empty/null handling, range, and precision.
- `textarea` to `wysiwyg_editor` keeps `text` storage, including with a simple toolbar. Check HTML interpretation and line breaks, but do not require a database migration solely because the field type changed.

### Restore customized views and assets

Keep presentation customizations in `view.php` and `templates/`. Use the saved files as the basis for the final result, preserving their PHP logic, HTML wrappers, classes, and styling. Restore custom CSS, JavaScript, and other assets, adapting them only where the changes require it.

- For a Block Builder/Concrete upgrade without field changes, restore the original `view.php` and `templates/` after generation. Compare the new generated controller and view contract first; make only compatibility changes required by the new version, preserving the layout and behavior.
- When fields change, adapt the saved views and every affected template using the new generated variables: update renamed references, remove code specific to deleted fields without removing shared wrappers, and place new fields where they fit the existing layout and conventions. Preserve unrelated PHP and markup; do not replace customized views with the generated default.
- Review the final diff, validate `config-bb.json`, and check PHP syntax and field references in the controller and restored or adapted templates. Verify affected rendering and behavior where possible. Keep the saved copies until verification is complete.

### Run the rebuild

From the project root:

```bash
php public/concrete/bin/concrete block-builder:rebuild example_block --validate-only
php public/concrete/bin/concrete block-builder:rebuild example_block --no-interaction
```

Follow the project's approval rules before running the rebuild. It replaces generated files and refreshes the installed block type (including its database schema), regardless of `installBlock`. It does not add blocks to pages. Validation alone does not write files or refresh the block.

Review `excludedFromRemoval` before rebuilding. Files outside the exclusions are removed before generation; exclusions prevent removal but do not prevent generated files from overwriting the same paths. The existing block icon is preserved.

On failure, read the error and inspect the block state before retrying. The generator can restore the directory for failures before generated files are committed; a refresh failure retains the new files and recovery backup and does not roll back database changes.
