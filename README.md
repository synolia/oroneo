# ORONEO by Synolia

**Oroneo** is a **OroCommerce addon** allowing to import catalogs and product data, from the **Akeneo** standard CSV files.

## How it works
Oroneo reads standard CSV files from the Akeneo exports in order to import data into your OroCommerce database. These data exchanges allow to import:
* **Categories**
* **Attributes & attributes' options**
* **Products**
* **Media** (Images, PDF files...)

## Requirements
ATM **Oroneo** is designed to work only on **OroCommerce beta-3** and based on generated CSV files from **Akeneo 1.3+**

PHP : **PHP 5.6+**

**Synolia** recommends to work with the **[Akeneo Enhanced Connector Bundle](https://github.com/akeneo-labs/EnhancedConnectorBundle)** in order to be more efficient in your interfaces. This bundle allows you to go further to select the right data to export.

## How to install
Oroneo's installation is rather simple ! You only need to use composer:
Add those lines to the repositories array in `composer.json` :
```json
"repositories": [
  {
    "type": "vcs",
    "url":  "https://github.com/synolia/oroneo.git"
  }
]
```
And execute this command
```cli
composer require synolia/oroneo
```
### Bundle installation in a working OroCommerce environment
If you already have an OroCommerce application and you want to add this bundle, you need an extra step:
```cli
php app/console oro:platform:update
```

## Configuration and usage
### Configuration
A new navigation tab is available with a link to the settings page.
There is a default configuration but you can adjust it to match your requirements and fields mappings.
#### Global
Useful to define your own delimiter, enclosure and locales mapping.
#### Field mapping
Categories & Product fields mapping are defined is this panel.

### Usage
The import process is available in the UI but also with the CLI.
#### UI import
It is possible to load CSV files directly with the import form.
The import process is devided in two steps : a file validation and the import itself.
#### CLI import
```cli
php app/console synolia:akeneo-pim:import type
```
Replace **type** by one of this values:
* category
* attribute
* option
* product
* product-file

CSV & ZIP files should be stored in the folder `app/Resources/imports/`

It is also possible to import them all with the same command but without any argument specified:
```cli
php app/console synolia:akeneo-pim:import
```

## About Synolia

Founded in 2004, **[Synolia](http://www.synolia.com)** is a French e-commerce & CRM company based in Lyon and Paris. With more than 650 projects in both B2B and B2C, Synolia is specialized in designing and delivering the best customer experience.

**Synolia** provides the most innovative solutions and is a certified partner of **Akeneo**, **OroCommerce**, Magento, OroCRM, PrestaShop, Salesfusion, SugarCRM, Qlik, Zendesk. Our ambition is to make each project a new success-story.
