# wiki-extension-oauth

### Create a new MediaWiki Extension.
- Step 1 - On GitHub, create a new MediaWiki extension repository, using the "wiki-extension-template" as a template.
- Step 2 - Clone the new extenion repository into your MediaWiki installation and rename it.
- Step 3 - Include the extension in your WikiMedia installation by adding "wfLoadExtension('ExtensionDirName')" to "LocalSettings.php".
- Step 4 - Update the extensions.json file.  Change all "BoilerPlate" references to your extensions name.
- Step 5 - Change the hook callback's conditional operator to "true || ..." so that the code executes regardless of whats in the config.
- Step 6 - Refresh the page and you should see the text specified in the callback.


#### Features ???
This automates the recommended code checkers for PHP and JavaScript code in Wikimedia projects
(see https://www.mediawiki.org/wiki/Continuous_integration/Entry_points).
To take advantage of this automation.

1. install nodejs, npm, and PHP composer
2. change to the extension's directory
3. `npm install`
4. `composer install`

Once set up, running `npm test` and `composer test` will run automated code checks.