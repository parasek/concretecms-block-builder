# 2.6.2
- Fixed marketplace linter errors.

# 2.6.1
- Fixed "Number" field type bug when block is being added through composer.

# 2.6.0
- Added custom config to "WYSIWYG editor" field type.

# 2.5.1
- Image default dimension values are now available in view.

# 2.5.0
- Increased package minimum Concrete version to 9.2.
- Added "Color Picker" field type.
- Added "Icon Picker" field type.
- Added "Number" field type.
- Added new option for generated blocks to highlight multi-element fields (with gray background) instead just dividing them with horizontal line.
- When loading configs, existing blocks are now being sorted by creation date descending.
- Field type "Link with type selection" has been renamed to "Flex Link".
- Option rel="nofollow" has been added to all link type fields.
- Thumbnail/fullscreen dimensions in "Image" field type are now available in view.php, even if no image is selected. They are also being listed as class properties, so they can be accessed in controller.
- Fixed "Enhanced Select Field" in "Single Choice Field" and "Enhanced Multiselect Field" in "Multiple Choice Field" in Concrete 9.2+. New version of this field type will not work in older version of Concrete.
- Fixed bugs when block with none/unknown version is being loaded.
- Fixed tab css styling in generated blocks.

# 2.4.0
- Fixed bug in generated blocks, where you couldn't set empty value in several fields (null was changed to '' in save() method).
- Fixed "trailing slashes" error on self-closing tags (W3C Validation).
- Improved behaviour of "Alt" attribute for "Image" Field type ("Example title - 001.jpg" will be transformed to "Example title" etc.).
- Improved behaviour of "Text" and "Title" for "Link from Sitemap"/"Link from File Manager" (html attributes will stay empty if respective fields were not filled).
- Added new "File Set" Field type.

# 2.3.0
- "Select field" has been renamed to "Single Choice Field".
- "Single Choice Field" has been enhanced and can be added to blocks as:
  - Default Select Field
  - Enhanced Select Field (using select2 for UI)
  - Radio List
- "Multiple Choice Field" has been added and can be added to blocks as:
  - Default Multiselect Field
  - Enhanced Multiselect Field (using select2 for UI)
  - Checkbox List
- "Express" field type has been added  
  Example code (how to display data from Express) will be generated view.php.
- You can now rebuild/refresh existing blocks without uninstalling them - check "Load configuration" page.  
  You can read more information on designated page, go to "Load configuration" page -> Rebuild and refresh -> follow link in the yellow message.  
  This is still an experimental feature, so be sure to backup database/files first.  
- You can load predefined json configs in "Load configuration" page to fast-test/preview different Field types.  
  This is mostly done for my internal development/testing process, but still can be used as "showcase" block.
- Some smaller fixes has been implemented.

# 2.2.0
- Package and generated blocks are now compatible with PHP 8
- Fixed bug when tabs were not displaying under certain conditions
- Fixed "Link with type selection" file field in repeatable entries
- Fixed errors when using generated blocks in Concrete Composer

# 2.1.2
- Bumped minimum version of Concrete CMS to 9.1.0

# 2.1.1
- Fixed installing path in composer.json

# 2.1.0
- You can now install Block Builder using Composer. Check README.md for more information.

# 2.0.0
- Package updated for version 9.0.0
- Minimum required c5 version is 9.0.0, use previous version of package when using c5.8
- Package has been updated to concrete5.9 (minimum version is 9.0.0). When using c5.7 or c5.8 use older version of package.
- "Remove all" button has been added to repeatable entries in generated blocks.
- "Disable smooth scroll" and "Keep added/copied entry collapsed" checkboxes have been added to repeatable entries in generated blocks to smooth editing experience.
- Fixed some small ui/functionality errors from previous versions.

# 1.3.1
- Fixed svg behaviour in generated image field types

# 1.3.0
- Added package version to config-bb.json file
- "Image" field type has been greatly improved. Now, during block creation you can check option that let your site editors enter custom dimensions for every thumbnail/fullscreen image (both single and repeatable)
- Added button in generated block to duplicate entry and place it just after current
- $app is now available in generated view.php by default
- Text fields for all link types are textareas now
- Added CURRENT_PAGE option to external links
- Fixed "Duplicate entry" bug, which was causing revealing hidden options/fields
- Fixed: https://www.concrete5.org/marketplace/addons/block-builder/support/bug-report-errorexception-in-load-config/
- Removed h() function around $new_window variable in generated files
- Fixed count() bug in generated blocks when using higher php versions

# 1.2.1
- Fixed missing escape functions in generated blocks

# 1.2.0
- Added optional target="_blank" rel="noopener" to all Link fields
- Fixed json_decode error when copied block is added to page
- Fixed edge case when smart horizontal line was not added
- Fixed missing translations
- Fixed missing 'link_type' variable in view() for Link with Type Selection fields
- Fixed External Link variable typo in generated view.php

# 1.1.0
- Added "Date Picker" field
- Added "Link with Type Selection" field ("Link from Sitemap", "Link from File Manager" and "External Link" combined together)
- Added option to have Entries as first/active tab
- Added optional counter to Repeatable entries
- Added "Remove all" and "Scroll down" buttons when creating block
- Added BASE_URL option to available protocols in "External Link" field
- Fixed css of editable field when height of CKEditor is set
- Fixed some missing addslashes() when creating block
- Multiple minor fixes

# 1.0.4
- Added more info in README.md

# 1.0.3
- Block Builder is now free. License changed to MIT.

# 1.0.2
- Fixed: Now in generated block, when image thumbnail is smaller than constraints, we use original url instead from cache

# 1.0.1
- Fixed: Check if file exists in repeatable entries when editing block
- Fixed: Disappearing entries when changing block template
- Fixed: Removed duplicated .js-entry-title
- Field type "Textarea" has option "Use this field as title in repeatable entries" available now

# 1.0.0
- Marketplace release

# 0.9.2
- Replaced php array() with [] in all files
- Replaced $_GET with $this->get()
- Added ability to use _ in field handles
- Changed code formatting a little in generated view.php (bigger gaps)
- External link field type - added select field with protocols in generated form.php
- db.xml will now not generate unnecessary fields/rows in repeatable entries, when appropriate options during block creation were not checked
- Few minor bugfixes/code formatting fixes
- Improved documentation

# 0.9.1
- Added maxlength to some inputs to prevent errors in STRICT_MODE, when string was too long
- Better ui for blocks put in composer
- Fixes/changes required for marketplace + some class refactoring + remove deprecated/unnecessary code
- Increased min. version to c5.8.2.1

# 0.9.0
- Submission to marketplace
