##### 3.0.0 (unreleased)
- Raised the minimum requirements to Concrete CMS 9.5.2 and PHP 8.4.
- Extensively reworked Block Builder while maintaining compatibility with 2.8.1 configuration files and generated view variables wherever possible.
- Redesigned and polished the Dashboard interface for creating and managing blocks.
- Added dark mode support to the Block Builder Dashboard and generated block forms.
- Added support for custom block icons, including uploaded PNG icons and icons selected from Concrete CMS.
- Block and field handles are now generated automatically from their corresponding names and labels, while still allowing manual edits.
- Added controls for installing and uninstalling custom block types and deleting uninstalled block folders directly from the configuration list.
- Improved configuration loading, validation, and error feedback, and preserved loaded configuration details after unsuccessful build attempts.
- Modernized generated block data import and export, including file and folder references and cleaner repeatable-entry exports.
- Generated blocks now use Concrete CMS's current Doctrine XML database schema format.
- Added Concrete CMS file-usage tracking to generated blocks that use supported file fields.
- Added settings for caching block output in edit mode and for registered users.
- Advanced block settings now show their corresponding generated controller property names.
- Added configurable messages for the "Basic information" and "Repeatable entries" tabs.
- Added an option to display zero values in generated Text, Textarea, and Number field output.
- Removed the deprecated horizontal-divider settings (`fieldsDivider` and `entryFieldsDivider`) from generated block configurations.
- Textarea fields now resize automatically and support configurable minimum and maximum heights instead of a fixed height.
- WYSIWYG Editor fields now support configurable minimum and maximum heights instead of a fixed height.
- Date Picker fields now use native HTML date controls and support optional time selection, minute intervals, minimum and maximum dates, and custom PHP date formats.
- Added configurable prefixes and suffixes to generated Text and Number controls.
- Added configurable placeholders to generated Text, Textarea, and Number fields.
- Added configurable default values for newly added basic and repeatable Text, Textarea, Number, WYSIWYG Editor, HTML Editor, Color Picker, and Icon Picker fields.
- Added configurable length limits and live character counters to generated Text and Textarea fields.
- Added optional phone, email, and URL validation to generated Text fields.
- Added a User Selector field type using Concrete CMS's user selector.
- Added an SVG Icon Picker field type for defining and selecting custom SVG icons.
- Added a Files from Folder field type with ordering that can match File Manager or use ascending, descending, or random order.
- Added "Required" and "Use this field as the title in repeatable entries" indicators to field headers in the Block Builder editor.
- Generated `view.php` files now document available values with `@var` annotations.
- Rebuilding a block now correctly synchronizes its block type set.
- Updated and completed the German, Swiss German, French, and Polish translations.

##### 2.8.1
- Fixed undefined PHP 8 errors for the Image field type.
- Removed unnecessary asset loads.
- Fixed an error when copying a single checkbox field ("Multiple Choice Field" in repeatable entries).

##### 2.8.0
- You can now add custom code inside the view() method.
- You can now add an empty option when custom code is selected in Single Choice Field.
- You can now add default value(s) in Single/Multiple Choice Fields.
- Fixed the wrong variable name generation in Single/Multiple Choice Fields.

##### 2.7.0
- You can now modify various block variables (e.g., $btCacheBlockRecord).
- You can now add custom code to Single/Multiple Choice Fields (for example, to fetch a list from Express).
- You can now add/remove the empty option in Single Choice Fields.
- You can now add custom methods at the bottom of the block controller.
- You can now add custom code inside registerViewAssets() (to load JavaScript/CSS assets).
- You can now exclude custom files/folders when rebuilding (refreshing) a block.
- The config-bb.json file is now generated in a human-readable format.
- Miscellaneous changes.

##### 2.6.2
- Fixed marketplace linter errors.

##### 2.6.1
- Fixed the "Number" field type bug when a block is added through Composer.

##### 2.6.0
- Added custom config support to the "WYSIWYG Editor" field type.

##### 2.5.1
- Image default dimension values are now available in the view.

##### 2.5.0
- Increased Concrete requirement to version to 9.2.
- Added a "Color Picker" field type.
- Added an "Icon Picker" field type.
- Added "Number" field type.
- Added an option for generated blocks to highlight multi-element fields (gray background) instead of just dividing them with a horizontal line.
- When loading configs, existing blocks are now sorted by creation date (descending).
- The field type "Link with Type Selection" is called "Flex Link" now.
- Added the rel="nofollow" option to all link-type fields.
- Thumbnail/fullscreen dimensions in the "Image" field type are now available in view.php even if no image is selected. They are also listed as class properties, so they can be accessed in the controller.
- Fixed "Enhanced Select Field" issues in Concrete 9.2+ for Single Choice Field and Multiple Choice Field. Note: the new version will not work on older Concrete releases.
- Fixed bugs when loading blocks with no/unknown version.
- Fixed tab CSS styling in generated blocks.

##### 2.4.0
- Fixed a bug in generated blocks where empty values could not be set in several fields (null was changed to '' in save()).
- Fixed the trailing-slashes error on self-closing tags (W3C validation).
- Improved behavior of the Alt attribute for the Image field ("Example title – 001.jpg" → "Example title").
- Improved behavior of Text and Title for "Link from Sitemap" / "Link from File Manager" (HTML attributes remain empty if respective fields were not filled).
- Added a new "File Set" field type.

##### 2.3.0
- "Select field" is called "Single Choice Field" now.
- "Single Choice Field" has been enhanced and can now be added as:
    - Default Select Field
    - Enhanced Select Field (using select2 for UI)
    - Radio List
- "Multiple Choice Field" has been added and can now be added as:
    - Default Multiselect Field
    - Enhanced Multiselect Field (using select2 for UI)
    - Checkbox List
- "Express" field type has been added. Example code (how to display data from Express) will be generated in view.php.
- You can now rebuild/refresh existing blocks without uninstalling them — see the "Load configuration" page. This is still experimental; back up your database and files first.
- You can load predefined JSON configs on the "Load configuration" page to quickly test/preview different field types (useful for development/testing and as a showcase).
- Various smaller fixes.

##### 2.2.0
- Package and generated blocks are now compatible with PHP 8.
- Fixed a bug when tabs were not displaying under certain conditions.
- Fixed the "Link with Type Selection" file field in repeatable entries.
- Fixed errors when using generated blocks in Composer.

##### 2.1.2
- Bumped a minimum Concrete CMS version to 9.1.0.

##### 2.1.1
- Fixed the installation path in composer.json.

##### 2.1.0
- Block Builder can now be installed using Composer. See README.md for details.

##### 2.0.0
- Package updated for Concrete 9.0.0.
- The minimum required Concrete version is 9.0.0 — use an earlier package version for c5.8.
- "Remove all" button is added to repeatable entries in generated blocks.
- "Disable smooth scroll" and "Keep added/copied entries collapsed" checkboxes are added to repeatable entries to improve editing.
- Fixed several small UI/functionality issues from previous versions.

##### 1.3.1
- Fixed SVG behavior in generated Image field types.

##### 1.3.0
- Added a package version to config-bb.json.
- Improved the Image field: editors can now enter custom dimensions for every thumbnail/fullscreen image (single and repeatable).
- Added a button in generated blocks to duplicate an entry and place it immediately after the current one.
- $app is now available in generated view.php by default.
- Text fields for all link types are now text areas.
- Added the CURRENT_PAGE option to External Link.
- Fixed the "Duplicate entry" bug that revealed hidden options/fields.
- Fixed a marketplace bug (see a linked report).
- Removed h() around $new_window variable in generated files.
- Fixed the count() bug in generated blocks on newer PHP versions.

##### 1.2.1
- Fixed missing escape functions in generated blocks.

##### 1.2.0
- Added optional target="_blank" rel="noopener" to all Link fields.
- Fixed the json_decode error when a copied block is added to a page.
- Fixed an edge case where the smart horizontal line was not added.
- Fixed missing translations.
- Fixed the missing 'link_type' variable in view() for Link with Type Selection fields.
- Fixed External Link variable typo in generated view.php.

##### 1.1.0
- Added a "Date Picker" field.
- Added a "Link with Type Selection" field ("Link from Sitemap" / "Link from File Manager" / "External Link").
- Added an option to show Entries as the first/active tab.
- Added optional counter to repeatable entries.
- Added "Remove all" and "Scroll down" buttons when creating blocks.
- Added a BASE_URL option to protocols in the External Link field.
- Fixed CSS of editable fields when CKEditor height is set.
- Fixed missing addslashes() during block creation.
- Multiple minor fixes.

##### 1.0.4
- Added more info to README.md.

##### 1.0.3
- Block Builder is now free (MIT license).

##### 1.0.2
- Fixed: when an image thumbnail is smaller than constraints, the generated block uses the original URL instead of cache.

##### 1.0.1
- Fixed: check if a file exists in repeatable entries when editing a block.
- Fixed: disappearing entries when changing the block template.
- Fixed: removed duplicated .js-entry-title.
- Textarea field now has the "Use this field as title in repeatable entries" option.

##### 1.0.0
- Marketplace release.

##### 0.9.2
- Replaced php array() with [] in all files.
- Replaced $_GET with $this->get().
- Added the ability to use _ in field handles.
- Adjusted formatting in generated view.php.
- Added protocol select to the External Link field in generated form.php.
- db.xml no longer generates unnecessary fields/rows in repeatable entries when options are unchecked.
- Minor bugfixes and documentation improvements.

##### 0.9.1
- Added maxlength to some inputs to prevent STRICT_MODE errors when strings were too long.
- Improved UI for blocks added to Composer.
- Marketplace fixes and class refactoring; removed deprecated/unnecessary code.
- Increased the minimum Concrete version to c5.8.2.1.

##### 0.9.0
- Submitted to the marketplace.
