<?php

namespace Synolia\Bundle\OroneoBundle\Manager;

use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\ImportExportBundle\Handler\HttpImportHandler;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Translation\TranslatorInterface;
use Oro\Bundle\ImportExportBundle\Form\Model\ImportData;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager as EntityConfigManager;
use Oro\Bundle\EntityExtendBundle\Extend\EntityProcessor;

/**
 * Class ImportManager
 * Service with actions permitting Import and Validation in the UI.
 */
class ImportManager
{
    const CATEGORY_PROCESSOR          = 'synolia.oroneo.import.processor.category';
    const ATTRIBUTE_PROCESSOR         = 'synolia.oroneo.import.processor.attribute';
    const OPTION_PROCESSOR            = 'synolia.oroneo.import.processor.option';
    const PRODUCT_PROCESSOR           = 'synolia.oroneo.import.processor.product';
    const PRODUCT_FILE_PROCESSOR      = 'synolia.oroneo.import.processor.product_file';
    const ATTRIBUTE_VALIDATION_JOB    = 'synolia_oroneo_attribute_import_validation_from_csv';
    const ATTRIBUTE_JOB               = 'synolia_oroneo_attribute_import_from_csv';
    const PRODUCT_FILE_VALIDATION_JOB = 'synolia_oroneo_product_file_import_validation_from_zip';
    const PRODUCT_FILE_JOB            = 'synolia_oroneo_product_file_import_from_zip';
    const VALIDATION_IMPORT_TYPE      = 'validation';
    const EXECUTION_IMPORT_TYPE       = 'import';
    const CSV_FORMAT                  = 'csv';
    const ZIP_FORMAT                  = 'zip';
    const SFTP_CONNECTION             = 'SFTP';
    const FTP_CONNECTION              = 'FTP';

    /** @var TranslatorInterface $translator */
    protected $translator;

    /** @var HttpImportHandler $importHandler */
    protected $importHandler;

    /** @var ProcessorRegistry $processorRegistry */
    protected $processorRegistry;

    /** @var ConfigManager $configManager */
    protected $configManager;

    /** @var EntityProcessor $entityProcessor */
    protected $entityProcessor;

    /** @var EntityConfigManager $entityConfigManager */
    protected $entityConfigManager;

    /** @var  DistantConnectionManager $connectionManager*/
    protected $connectionManager;

    /**
     * ImportManager constructor.
     *
     * @param TranslatorInterface      $translator
     * @param HttpImportHandler        $importHandler
     * @param ProcessorRegistry        $processorRegistry
     * @param ConfigManager            $configManager
     * @param EntityProcessor          $entityProcessor
     * @param EntityConfigManager      $entityConfigManager
     * @param DistantConnectionManager $connectionManager
     */
    public function __construct(
        TranslatorInterface $translator,
        HttpImportHandler $importHandler,
        ProcessorRegistry $processorRegistry,
        ConfigManager $configManager,
        EntityProcessor $entityProcessor,
        EntityConfigManager $entityConfigManager,
        DistantConnectionManager $connectionManager
    ) {
        $this->translator          = $translator;
        $this->importHandler       = $importHandler;
        $this->processorRegistry   = $processorRegistry;
        $this->configManager       = $configManager;
        $this->entityProcessor     = $entityProcessor;
        $this->entityConfigManager = $entityConfigManager;
        $this->connectionManager   = $connectionManager;
    }

    /**
     * Return an array for the form import select.
     *
     * @return array
     */
    public function getProcessorsChoices()
    {
        return [
            self::CATEGORY_PROCESSOR     => $this->translator->trans('synolia.oroneo.category.label'),
            self::ATTRIBUTE_PROCESSOR    => $this->translator->trans('synolia.oroneo.attribute.label'),
            self::OPTION_PROCESSOR       => $this->translator->trans('synolia.oroneo.option.label'),
            self::PRODUCT_PROCESSOR      => $this->translator->trans('synolia.oroneo.product.label'),
            self::PRODUCT_FILE_PROCESSOR => $this->translator->trans('synolia.oroneo.product_file.label'),
        ];
    }

    /**
     * Execute the import validation and return its result.
     *
     * @param ImportData $data    Data from the form submit.
     * @param string     $jobName Validation job's name from the controller request.
     *
     * @return array
     */
    public function importValidation(ImportData $data, $jobName)
    {
        $file           = $data->getFile();
        $processorAlias = $data->getProcessorAlias();
        $inputFormat    = $this->getFormat($processorAlias);
        $importJob      = $this->getJob($processorAlias, $jobName, self::VALIDATION_IMPORT_TYPE);

        $this->importHandler->saveImportingFile($file, $processorAlias, $inputFormat);

        $entityName = $this->processorRegistry
            ->getProcessorEntityName(ProcessorRegistry::TYPE_IMPORT_VALIDATION, $processorAlias);
        $existingAliases = $this->processorRegistry
            ->getProcessorAliasesByEntity(ProcessorRegistry::TYPE_IMPORT_VALIDATION, $entityName);

        $validationResult  = $this->getImportResult($importJob, $processorAlias, $inputFormat, self::VALIDATION_IMPORT_TYPE);
        $validationResult['showStrategy'] = count($existingAliases) > 1;

        return $validationResult;
    }

    /**
     * Retrieve file from distant server and use it to run the import job.
     *
     * @param ImportData $data    Data from the front form.
     * @param string     $jobName Validation job's name from the controller request.
     *
     * @return array
     * @throws \Exception
     */
    public function distantImportValidation(ImportData $data, $jobName)
    {
        // Retrieve user's config values.
        $connectionInfo = [
            'username'       => $this->configManager->get('synolia_oroneo.distant_username'),
            'password'       => $this->configManager->get('synolia_oroneo.distant_password'),
            'host'           => $this->configManager->get('synolia_oroneo.distant_host'),
            'port'           => $this->configManager->get('synolia_oroneo.distant_port'),
            'connectionType' => $this->configManager->get('synolia_oroneo.distant_connection_type'),
            'filename'       => $this->getDistantFilenameByImportType($data->getProcessorAlias()),
        ];

        // If missing at least one param then return an empty array to trigger an error.
        if (count($connectionInfo) != count(array_diff($connectionInfo, ['']))) {
            return [];
        }

        // Depending on the connection type.
        $file = null;
        switch ($connectionInfo['connectionType']) {
            case self::FTP_CONNECTION:
                $file = $this->connectionManager->ftpImport(
                    $connectionInfo['username'],
                    $connectionInfo['password'],
                    $connectionInfo['host'],
                    $connectionInfo['port'],
                    $connectionInfo['filename']
                );
                break;
            case self::SFTP_CONNECTION:
                $file = $this->connectionManager->sftpImport(
                    $connectionInfo['username'],
                    $connectionInfo['password'],
                    $connectionInfo['host'],
                    $connectionInfo['port'],
                    $connectionInfo['filename']
                );
                break;
            default:
                return [];
        }

        if (null === $file) {
            return [];
        }

        // Setting a File instead of an UploadedFile to avoid upload validation error.
        $data->setFile($file);

        return $this->importValidation($data, $jobName);
    }

    /**
     * Execution of the import itself.
     *
     * @param string $processorAlias
     * @param string $jobName
     *
     * @return array
     */
    public function importExecution($processorAlias, $jobName)
    {
        $jobName = $this->getJob($processorAlias, $jobName, self::EXECUTION_IMPORT_TYPE);
        $inputFormat = $this->getFormat($processorAlias);

        // Execute the import
        $result  = $this->getImportResult($jobName, $processorAlias, $inputFormat, self::EXECUTION_IMPORT_TYPE);

        // Update schema if Attribute import
        if ($processorAlias == self::ATTRIBUTE_PROCESSOR) {
            $product = $this->entityConfigManager->getConfigEntityModel(Product::class);
            $config  = $product->toArray('extend');

            if ($config['state'] == ExtendScope::STATE_UPDATE) {
                $this->entityProcessor->updateDatabase(true, true);
            }
        }

        return $result;
    }

    /**
     * Retrieve the correct job name depending on the processor chosen.
     *
     * @param string $processorAlias
     * @param string $jobName
     * @param string $importType
     *
     * @return string
     */
    protected function getJob($processorAlias, $jobName, $importType = self::VALIDATION_IMPORT_TYPE)
    {
        if ($importType == self::VALIDATION_IMPORT_TYPE) {
            // Custom job if Attribute import
            if ($processorAlias == self::ATTRIBUTE_PROCESSOR) {
                return self::ATTRIBUTE_VALIDATION_JOB;
            }

            // Custom job if Product file import
            if ($processorAlias == self::PRODUCT_FILE_PROCESSOR) {
                return self::PRODUCT_FILE_VALIDATION_JOB;
            }
        }

        // Custom job if Attribute import
        if ($processorAlias == self::ATTRIBUTE_PROCESSOR) {
            return self::ATTRIBUTE_JOB;
        }

        // Custom job if Product file import
        if ($processorAlias == self::PRODUCT_FILE_PROCESSOR) {
            return self::PRODUCT_FILE_JOB;
        }

        return $jobName;
    }

    /**
     * Return the file format depending on the processor.
     *
     * @param string $processorAlias
     *
     * @return string
     */
    protected function getFormat($processorAlias)
    {
        // Different file format if Product file import
        if ($processorAlias == self::PRODUCT_FILE_PROCESSOR) {
            return self::ZIP_FORMAT;
        }

        return self::CSV_FORMAT;
    }

    /**
     * Execute the import process depending on the import type.
     *
     * @param string $jobName
     * @param string $processorAlias
     * @param string $inputFormat
     * @param string $importType
     *
     * @return array
     */
    protected function getImportResult($jobName, $processorAlias, $inputFormat, $importType = self::VALIDATION_IMPORT_TYPE)
    {
        $options = [
            'delimiter' => $this->configManager->get('synolia_oroneo.delimiter'),
            'enclosure' => $this->configManager->get('synolia_oroneo.enclosure'),
        ];

        if ($importType == self::VALIDATION_IMPORT_TYPE) {
            return $this->importHandler->handleImportValidation(
                $jobName,
                $processorAlias,
                $inputFormat,
                null,
                $options
            );
        }

        return $this->importHandler->handleImport(
            $jobName,
            $processorAlias,
            $inputFormat,
            null,
            $options
        );
    }

    /**
     * @param string $importType
     *
     * @return string|null
     */
    protected function getDistantFilenameByImportType($importType)
    {
        switch ($importType) {
            case ImportManager::CATEGORY_PROCESSOR:
                return $this->configManager->get('synolia_oroneo.distant_filepath_category');
            case ImportManager::ATTRIBUTE_PROCESSOR:
                return $this->configManager->get('synolia_oroneo.distant_filepath_attribute');
            case ImportManager::OPTION_PROCESSOR:
                return $this->configManager->get('synolia_oroneo.distant_filepath_option');
            case ImportManager::PRODUCT_PROCESSOR:
                return $this->configManager->get('synolia_oroneo.distant_filepath_product');
            case ImportManager::PRODUCT_FILE_PROCESSOR:
                return $this->configManager->get('synolia_oroneo.distant_filepath_product-file');
            default:
                return null;
        }
    }
}
