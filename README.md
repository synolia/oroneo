# ORONEO by Synolia

**Oroneo** is a **OroCommerce addon** allowing to import catalogs and product data, from the **Akeneo** standard CSV files.

## How it works
Oroneo reads standard CSV files from the Akeneo exports in order to import data into your OroCommerce database. These data exchanges allow to import:
* **Categories**
* **Attributes, attributes' groups & attributes' options**
* **Families**
* **Products**
* **_Media_** _(Images, PDF files...)_ (not yet functional)

## Requirements & Notes
### Requirements
ATM **Oroneo** is designed to work on **OroCommerce v1.1** and based on generated CSV files from **Akeneo 1.3+**

If you're working with Akeneo PIM < 1.6, **Synolia** recommends to work with the **[Akeneo Enhanced Connector Bundle](https://github.com/akeneo-labs/EnhancedConnectorBundle)** in order to be more efficient in your interfaces. This bundle allows you to go further to select the right data to export.

### Important notes
There is some limitations that you have to take care of:
* If you plan to import a lot amount of data you may want to use PostgreSQL instead of MySQL. You will encounter some limitation with MySQL and you will have to recompile your MySQL to increase those limitations. So we recommand to use PostgreSQL if you have more than 40 attributes and/or about 1M products.
* ATM, due to a glitch in OroPlatform, we can only support the comma (`,`) delimiter so see to export your akeneo files with this delimiter.
* Product Files & Images import is not yet available. We are currently working on it and it might be available soon. 
* OroCommerce Products doesn't support multiple categories associations. Only the first assigned category will be taken.
 

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
"synolia/oroneo": "dev-master"
```
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
ATM, it is necessary to map the correct label's attribute column with the locale suffix because we do not set this column to a translatable one yet.

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

#### UI import
It is possible to load CSV files directly with the import form.
The import process is devided in two steps : a file validation and the import itself which is sent to the Message Queue Consumer.

Note: if your file is larger than 500kb, the UI validation won't run. But you can still send the import anyway.
The import itself will check the CSV file you sent and if there is any issue with it, you will receive an email with a link to the log.

#### FTP/SFTP import (via UI only)
If you leave the checkbox `Manual import` empty, the process will try to connect and remotly download the file from the server specified in the Remote configuration panel.

#### CLI import
```cli
php app/console synolia:akeneo-pim:import importType filePath 
```
Replace **type** by one of this values:
* category
* attribute
* attribute_groupe
* family
* option
* product
* _product-file_ (not yet functional)

Replace **filePath** by the current path of your CSV file. If you leave this argument, the command will search in this folder: `app/import_export/`
The default mapping can be found [here](https://github.com/synolia/oroneo/blob/master/DependencyInjection/Configuration.php#L56-L89).

It is also possible to make a mass import with the same command but without any argument specified:
```cli
php app/console synolia:akeneo-pim:import
```

It is possible to define an email to be notified and get the import result
```cli
php app/console synolia:akeneo-pim:import --email=test@exemple.com
php app/console synolia:akeneo-pim:import category app/import_export/export_category.csv --email=test@exemple.com
```

## About Synolia

Founded in 2004, **[Synolia](http://www.synolia.com)** is a French e-commerce & CRM company based in Lyon and Paris. With more than 650 projects in both B2B and B2C, Synolia is specialized in designing and delivering the best customer experience.

**Synolia** provides the most innovative solutions and is a certified partner of **Akeneo**, **OroCommerce**, Magento, OroCRM, PrestaShop, Salesfusion, SugarCRM, Qlik, Zendesk. Our ambition is to make each project a new success-story.
