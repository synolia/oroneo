# 0.3. (2017-01-13) 
**Fix:**
- Issue [#9](https://github.com/synolia/oroneo/issues/9) : Revert Mapping Migrations to the default locale at the install.
- Issue [#11](https://github.com/synolia/oroneo/issues/11) : Fix multi-enum import.
- Added some unit tests (still in progress for functional tests).
- Rework on PR [#7](https://github.com/synolia/oroneo/pull/7) to fix attribute import.

**Implemented enhancements:**
- Added the possibility to import directly from an FTP or SFTP connection.
- Added a CLI command to import all CSV files at once.

# 0.2.2 (2016-12-14) 
**Fix:**
- PR [#3](https://github.com/synolia/oroneo/pull/3) : Fixed a fatal error when the default localization isn't set up.
- PR [#7](https://github.com/synolia/oroneo/pull/7) : Fix [#6](https://github.com/synolia/oroneo/issues/6) the system attributes config were erased by the import system.
- PR [#8](https://github.com/synolia/oroneo/pull/8) : Refactored attribute types list to a container parameter.
- PR [#10](https://github.com/synolia/oroneo/pull/10) : Precision about EnhancedConnector bundle.
- Rewrite ZipFileReader to make it more simple.
- Some renames to fit with the bundle's name `oroneo`.

# 0.2.1 (2016-12-07) 
**Implemented enhancements:**
- Categories import under a "master category" defined in the Category Configuration panel
- Category Configuration panel : added the possibility to choose the default parent category.

**Fix:**
- Removed unecessary injections
- Fix beta5 upgrade : getAll() function replaced with getLocalizations()

# 0.2.0 (2016-12-05) 
**Upgrade to support OroCommerce-beta5 version** 

# 0.1.1 (2016-11-29)
**Cleaning:** 
- Removed useless files
- Renamed Classes and files to match the name **Oroneo**
- `composer.json` adjusted
- [README](https://github.com/synolia/oroneo/blob/master/README.md) updated

# 0.1.0 (2016-11-25)
- Initial release : **It's just you and me OroCommerce!**
