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