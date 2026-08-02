# Block Builder tests

Run the package test suite from the Concrete CMS project root:

```bash
vendor/bin/phpunit -c public/packages/block_builder/phpunit.xml.dist
```

The suite uses the project's existing development dependencies. It does not install the package, connect to the database, or write generated block files.

It checks:

- loading and canonicalizing the real 2.8.1 all-fields configuration;
- the legacy `view.php` variable and repeatable-entry key contract;
- registration and in-memory generation of every field type;
- generated PHP syntax, JSON round trips, and Doctrine XML structure;
- request/config validation, dashboard escaping, icon replacement, locking, and directory recovery.

Before a release, also run a database-backed dashboard smoke test: build the all-fields preset, add and edit the block, rebuild a 2.8.1 block while retaining its old `view.php`, and exercise install, uninstall, and folder deletion. Those Concrete lifecycle and browser-widget flows are intentionally outside this isolated suite.
