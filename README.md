# ORONEO by Synolia

**Oroneo** is a **OroCommerce addon/plugin** allowing to import catalogs and product data, from the **Akeneo** standard CSV files.

## How it works
Oroneo reads standard CSV files from the Akeneo exports in order to import data into your OroCommerce database. These data exchanges allow to import:
* **Categories**
* **Attributes, attributes' groups & attributes' options**
* **Families**
* **Products**
* **Media** _(Images, PDF files...)_

## Requirements & Notes
### Requirements
ATM **Oroneo** is designed to work on **OroCommerce v1.1** and based on generated CSV files from **Akeneo 1.3+**
No support for Akeneo Enterprise Edition yet.

If you're working with Akeneo PIM < 1.6, **Synolia** recommends to work with the **[Akeneo Enhanced Connector Bundle](https://github.com/akeneo-labs/EnhancedConnectorBundle)** in order to be more efficient in your interfaces. This bundle allows you to go further to select the right data to export.

### Important notes
There is some limitations that you have to take care of:
* If you plan to import a lot amount of data you may want to use PostgreSQL instead of MySQL. You will encounter some limitation with MySQL and you will have to recompile your MySQL to increase those limitations. So we recommand to use PostgreSQL if you have more than 40 attributes and/or about 1M products.
* ATM, due to a glitch in OroPlatform, we can only support the comma (`,`) delimiter so see to export your akeneo files with this delimiter.
* OroCommerce Products doesn't support multiple categories associations. Only the first category assigned will be taken.
* **Products without families won't be imported !**
* **Products' attributes that are not included in their family won't be imported.**
* Products without a proper label won't be seen on the Product datagrid.

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
And add this line in the `require` array:
```json
"synolia/oroneo": "1.0.0"
```
Then,
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
Useful to define your own delimiter (but currently we support only the comma), enclosure and locales mapping.
There is also the possibility to define your FTP or SFTP connection informations. That will permit to direcly download your CSV files from a remote server.
#### Field mapping
It is possible to map custom fields in those panels. 

### Usage
The import process is available in the UI but also with the CLI.
It uses the [MessageQueueBundle](https://github.com/orocrm/platform/tree/2.1/src/Oro/Bundle/MessageQueueBundle) from OroPlatform.
Refere to this bundle's documentation to learn how it works.
tl;dr
It is necessary to create the oro message queue with this command:
```cli
php app/console oro:message-queue:create-queues
```
And it is preferable to launch the Oro cron:
```cli
php app/console oro:cron
```

Then, to consume message sent, it is needed to launch the message consumer:
```cli
php app/console oro:message-queue:consume
```
Read the documentation to see how to handle this command and which arguments and options you can pass.

### Import order
The imports should be done in the following order :
 * Category
 * Product Attribute
 * Product Attribute Option
 * Product Family
 * Product Attribute group
 * Product
 * Product Files & Images

#### UI import
It is possible to load CSV files directly with the import form.
The import process is devided in two steps : a file validation and the import itself which is sent to the Message Queue Consumer.

Note: if your file is larger than 500kb, the UI validation won't run. But you can still send the message import anyway.
The import itself will check the CSV file you sent and you will receive an email with a link to the log and a summary of the process.

#### FTP/SFTP import (via UI only)
If you leave the checkbox `Manual import` empty, the process will try to connect and remotely download the file from the server specified in the Remote configuration panel.

#### CLI import
```cli
php app/console synolia:akeneo-pim:import importType filePath 
```
Replace **importType** by one of this values:
* category
* attribute
* attribute_group
* family
* option
* product
* product-file

Replace **filePath** by the current path of your CSV file. If you leave this argument, the command will search in this folder: `app/import_export/`
The default mapping can be found [here](https://github.com/synolia/oroneo/blob/master/DependencyInjection/Configuration.php#L56-L89).

It is also possible to make a mass import with the same command but without any argument specified:
```cli
php app/console synolia:akeneo-pim:import
```

It is possible to define an email to be notified and get the import results
```cli
php app/console synolia:akeneo-pim:import --email=test@exemple.com
php app/console synolia:akeneo-pim:import category app/import_export/export_category.csv --email=test@exemple.com
```

#### Notes
**_Attributes Import_**

- This is the list of all Akeneo's attribute types supported by this bundle:
  * pim_catalog_identifier
  * pim_catalog_text
  * pim_catalog_textarea
  * pim_catalog_metric
  * pim_catalog_boolean
  * pim_catalog_simpleselect
  * pim_catalog_number
  * pim_catalog_multiselect
  * pim_catalog_date
  * pim_catalog_image
  * pim_catalog_file

- It is necessary to update the Translations to have the correct attributes' labels diplayed in the UI for newly created attributes.

  To do this you can update the cache with the button in this page _"System > Localization > Translations"_ or by a classic _cache:clear_ :
  ```cli
  php app/console cache:clear --env=prod
  ```
  
  In OroCommerce, attributes have to be in the product family to be displayed. This means that, though you can use any attributes in any of your products in Akeneo, every attribute that is not in the product family will no be rendered in OroCommerce.

**_Products Import_**

The product label is mandatory.
Again, no label results to no product shown in the datagrid.

**_Product files Import_**

Take care of your ZIP file's size when you try to upload it with the form.
We suggest to use this import with the CLI.

**_Attributes Options Import_**

Note that it is not possible to import 2 options with the same label for one attribute even if they do have a different code.

## Contribute
Feel free to open issues and every PR is welcome as any suggestions you might have.

As any project of this kind, you may encounter some untested situations or uncovered behaviors. So any relevant informations may help to make this project better and stronger ;)


## About Synolia

Founded in 2004, **[Synolia](http://www.synolia.com)** is a French e-commerce & CRM company based in Lyon and Paris. With more than 650 projects in both B2B and B2C, Synolia is specialized in designing and delivering the best customer experience.

**Synolia** provides the most innovative solutions and is a certified partner of **Akeneo**, **OroCommerce**, Magento, OroCRM, PrestaShop, Salesfusion, SugarCRM, Qlik, Zendesk. Our ambition is to make each project a new success-story.
