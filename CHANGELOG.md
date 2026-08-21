##### 3.0.0 (unreleased)
- Added live block-handle generation from the block name with the same manual override and restart behavior as field handles.
- Moved persistent handle locks and transactional rebuild backups into dedicated `application/files/block_builder` runtime directories.
- Raised the minimum requirements to Concrete CMS 9.5.2 and PHP 8.4.
- Added a User Selector field backed by Concrete's user selector widget.
- Added a Files from a Folder field with direct-child file listing and configurable File Manager, ascending, descending, or random ordering.
- Added an SVG Icon Picker field with sanitized custom icon definitions, generated select controls, and live previews.
- Added an in-memory PHPUnit suite covering the 2.8.1 config schema and view-variable contract, every registered field type, and generated PHP, JSON, and Doctrine XML.
- Expanded the fast suite with boundary, validation, request-security, generated-artifact, lifecycle-service, and filesystem transaction regressions.
- Added fail-closed disposable Concrete integration and browser suites covering installation, generation, persistence, rebuild, duplication, deletion, upgrade, dashboard behavior, CSRF forms, and accessibility smoke checks.
- Added PHP and Concrete version matrices, line/branch/changed-line coverage baseline reports with provisional ratchet targets, scheduled mutation testing, and retained browser diagnostics in CI.
- Hardened transaction recovery against missing, linked, and unsafe backups, destinations, and state markers, and stopped generation when orphaned transaction artifacts require manual recovery.
- Removed non-fragment SVG href and CSS references after sanitization so remote, data, file, and script-like references cannot survive in custom icon definitions.
- Preserved the complete 2.8.1 Flex Link view-variable contract for rebuilt blocks and made `$app` available to every generated `view.php`.
- Prevented field handles from shadowing generated export and edit-mode cache controller properties.
- Serialized generation, uninstallation, and directory removal per block handle, safely replaced retained icons, and made stale-backup cleanup recoverable.
- Restricted rebuild, install, uninstall, and directory-removal actions to matching Block Builder configurations.
- Enforced editor permissions for submitted page, file, image, and Express selectors and removed debug output and server paths from generated Express examples.
- Escaped loaded field values in dashboard templates and rejected unknown or oversized configuration data while retaining the 2.8.1 schema aliases.
- Simplified field-type metadata to one canonical `getFieldType()` method and moved runtime generation values out of the persisted DTO namespace.
- Added optional phone, email, and URL validation to generated Text fields.
- Restored optional visual highlighting for compound Flex Link, link, and Image form fields.
- Added configurable placeholders to generated Text, Textarea, and Number fields.
- Refactored block creation into dedicated request, validation, DTO, service, and generator components for PHP 8.4.
- Added configuration and controller generation for Concrete's `btCacheBlockOutputOnEditMode` cache setting.
- Replaced native repeatable-entry dragging with Concrete's jQuery UI Sortable behavior and a visible vertical placeholder.
- Added explicit generation metadata handling without overwriting metadata when legacy configs are read.
- Added controlled handling for missing, malformed, and invalid block configuration files.
- Added permission, request method, CSRF, handle, folder, and installation-state checks to block installation actions.
- Added safe validation for missing and unsupported field types and missing request fields.
- Fixed validation state reuse, label tab highlighting, the block height validation message, nullable block icons, and missing config dates.
- Changed controller actions to return redirect responses without sending them directly.
- Made pre-lifecycle file generation transactional so rendering, writing, or icon failures restore the previous block folder or remove a partial new folder.
- Centralized initial values and selectable options for the block creation form in its view data provider.
- Added a shared marker interface for immutable field configuration DTOs.
- Renamed the flat persisted configuration model to `BlockConfigDto` to distinguish it from runtime block-creation data.
- Split block generation into directory transactions, generated-file writing, icon generation, and Concrete block lifecycle services.
- Generated text files now replace their targets instead of appending content.
- File generators now declare their own relative destination paths.
- Added an explicit iterable collection as the registration point for generated text files.
- Added server-side allow-list validation for global and field-specific select values.
- Added contextual generation exceptions for directory preparation, file writing, icons, installation, and refresh failures.
- Added safe controller-level handling and logging for block generation failures.
- Restricted file rollback to the pre-lifecycle phase, made backup cleanup best-effort, and added state-aware stale-backup recovery.
- Added distinct configuration-loading failures and accurate, path-safe dashboard feedback.
- Replaced container-based factory lookup in the JSON configuration service with constructor injection.
- Renamed the read-only JSON configuration service to `BlockConfigReader`.
- Added controlled field DTO creation errors for missing or unknown field types, malformed field data, and invalid value types.
- Added reusable server-side integer validation for block limits, cache lifetime, image dimensions, and editor heights.
- Added structural validation for configuration collections, field entries, scalar properties, excluded paths, and choice-option lists before DTO mapping.
- Made validation feedback immutable, removed reusable validator state, and introduced an explicitly ordered create-block validator collection.
- Split create-block validation into short-circuiting request-method, CSRF, authorization, input-shape, and business-validation stages.
- Split block type lookup, permissions, installation, uninstallation, directory lookup/removal, and icon discovery into focused services.
- Split option lists into focused block-settings, field-type, and icon providers.
- Separated reserved-word datasets and handle normalization from reserved-handle policy checks.
- Renamed runtime generation DTOs to `BlockGenerationManifest` and `BlockGenerationResult`, and clarified the text-only generated-file writer name.
- Flattened field implementation directories and moved block type lifecycle orchestration into the generator lifecycle namespace.
- Fixed block creation without basic or repeatable fields by normalizing omitted field collections to empty arrays.
- Replaced the draft controller-only strategy layer with a field contribution plan shared by controller, database, form, view, JavaScript, and CSS generators.
- Added safe, collision-checked text artifact rendering for the complete generated block scaffold, with unsupported field types rejected before the block directory is modified.
- Added generation for basic and repeatable Text fields, including schema columns, controller persistence and search code, escaped edit/view markup, translated validation, and server-side length checks.
- Made repeatable entry replacement, duplication, and deletion transactional and server-rendered existing entry controls before JavaScript enhancement to prevent accidental data loss.
- Hardened configuration loading with canonical source-handle checks, safe application-directory resolution, symlink rejection, and a bounded JSON file size.
- Replaced HTML lifecycle actions in validation errors with escaped plain-text guidance and removed their obsolete AJAX routes and controllers.
- Added path-safe rebuild exclusions and a maximum custom block icon upload size.
- Aligned form context, generation manifest, and reserved-handle class names with their responsibilities and removed one-consumer helper services.
- Removed the pass-through create-block request wrapper, simplified validator aggregation, and derived validation success directly from reported errors.
- Consolidated duplicate JavaScript and CSS plan layers into a shared mutable frontend-asset builder and immutable frontend-asset plan.
- Replaced split field-type contracts and proxy definitions with one explicit contract, a convention-based base class, and a direct field-type registry.
- Grouped concrete field implementations under `FieldType/Type`, separate from shared contracts, factories, validation, enums, and exceptions.
- Centralized build-page URLs, navigation metadata, icon previews, and option lists in its view-data provider.
- Simplified controlled dashboard exceptions to use one administrator-facing message while retaining full exception context in logs.
- Centralized block handle length and format rules across form validation, configuration loading, and lifecycle actions.
- Renamed the create-block validator collection to remove unnecessary business-layer terminology.
- Clarified block lifecycle method names to describe installation, refresh, and lifecycle completion explicitly.
- Changed generated PHP array literals from legacy `array (...)` syntax to modern short `[...]` syntax.
- Simplified text file generators to return explicit file lists instead of lazy generators.
- Scoped generated form-instance identifiers to `form.php` instead of storing them in every block controller.
- Migrated generated database schemas from legacy AXMLS 0.3 to Concrete’s Doctrine XML 0.5 format.
- Excluded repeatable-entry auto-increment values from block export and import data.
- Added database-index generation and indexed repeatable entries by block ID and position.
- Added Composer validation that reuses normal field validation for persisted block data.
- Added controller-plan support for implemented interfaces and content/file-folder export metadata.
- Added opt-in Concrete file-usage tracking with raw repeatable-entry collection and post-save tracker refresh.
- Modernized generated form service imports and hardened text rendering against invalid non-scalar values.
- Removed obsolete and no-op generated controller methods when their block features do not require them.
- Added tabbed generated forms and a plain-JavaScript repeatable-entry interface with matching shared form styles.
- Added basic and repeatable Flex Link generation with page, file, and external destinations, reusable compound-field controls, server-side validation, and compatibility with 2.8.1 configuration, stored JSON data, and generated view variable names.
- Added basic and repeatable Sitemap Link, File Manager Link, and External Link generation while preserving their 2.8.1 configuration aliases, database columns, and generated `view.php` variable names.
- Fixed saved Sitemap Link, File Manager Link, and External Link options being displayed as unchecked when their JSON values are booleans.
- Fixed valid File Manager Link selections being rejected because Concrete file-version methods were checked on the proxying file entity.
- Added generated `view.php` documentation for basic variables, choice-option maps, Flex Link values, and repeatable-entry array keys.
- Added basic and repeatable Image generation with image-only file selection, file tracking, collapsible per-image alt text and dimension overrides, repeatable-field-wide defaults in the Settings tab, original/thumbnail/fullscreen view data, and 2.8.1 configuration and view-variable compatibility.
- Fixed generated Image fields passing a file version instead of the Concrete file entity to the thumbnail helper.
- Added configurable field prefixes and suffixes to generated Text and Number form controls.
- Added add-form and repeatable-entry default values for Text, Textarea, Number, WYSIWYG Editor, HTML Editor, Color Picker, and Icon Picker fields.
- Added configurable Text and Textarea length limits with live character counters in generated forms.

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
