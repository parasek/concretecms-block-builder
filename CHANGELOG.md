##### 2.1.2
- Bump minimum version of Concrete CMS to 9.1.0

##### 2.1.1
- Fix installing path in composer.json

##### 2.1.0
- You can now install Block Builder using Composer. Check README.md for more information.

##### 2.0.0
- Package updated for version 9.0.0
- Minimum required c5 version is 9.0.0, use previous version of package when using c5.8

#1.3.1
- Fixed svg behaviour in generated image field types

#1.3.0
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

#1.2.1
- Fixed missing escape functions in generated blocks

#1.2.0
- Added optional target="_blank" rel="noopener" to all Link fields
- Fixed json_decode error when copied block is added to page
- Fixed edge case when smart horizontal line was not added
- Fixed missing translations
- Fixed missing 'link_type' variable in view() for Link with Type Selection fields
- Fixed External Link variable typo in generated view.php

#1.1.0
- Added "Date Picker" field
- Added "Link with Type Selection" field ("Link from Sitemap", "Link from File Manager" and "External Link" combined together)
- Added option to have Entries as first/active tab
- Added optional counter to Repeatable entries
- Added "Remove all" and "Scroll down" buttons when creating block
- Added BASE_URL option to available protocols in "External Link" field
- Fixed css of editable field when height of CKEditor is set
- Fixed some missing addslashes() when creating block
- Multiple minor fixes

#1.0.4
- Added more info in README.md

#1.0.3
- Block Builder is now free. License changed to MIT.

#1.0.2	
- Fixed: Now in generated block, when image thumbnail is smaller than constraints, we use original url instead from cache

#1.0.1	
- Fixed: Check if file exists in repeatable entries when editing block
- Fixed: Disappearing entries when changing block template
- Fixed: Removed duplicated .js-entry-title
- Field type "Textarea" has option "Use this field as title in repeatable entries" available now

#1.0.0
- Marketplace release

#0.9.2
- Replaced php array() with [] in all files
- Replaced $_GET with $this->get()
- Added ability to use _ in field handles
- Changed code formatting a little in generated view.php (bigger gaps)
- External link field type - added select field with protocols in generated form.php
- db.xml will now not generate unnecessary fields/rows in repeatable entries, when appropriate options during block creation were not checked
- Few minor bugfixes/code formatting fixes
- Improved documentation

#0.9.1
- Added maxlength to some inputs to prevent errors in STRICT_MODE, when string was too long
- Better ui for blocks put in composer
- Fixes/changes required for marketplace + some class refactoring + remove deprecated/unnecessary code
- Increased min. version to c5.8.2.1

#0.9.0
- Submission to marketplace