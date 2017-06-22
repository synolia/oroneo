<?php

namespace Synolia\Bundle\OroneoBundle\Manager;

use Oro\Bundle\ImportExportBundle\Handler\HttpImportHandler;
use Oro\Bundle\ImportExportBundle\Handler\CliImportHandler;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Symfony\Component\Translation\TranslatorInterface;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Symfony\Component\HttpFoundation\File\File;
use Oro\Bundle\ImportExportBundle\File\FileSystemOperator;
use Synolia\Bundle\OroneoBundle\Form\Model\ImportData;

/**
 * Class ImportManager
 * Service with actions permitting Import and Validation in the UI.
 * @package   Synolia\Bundle\OroneoBundle\Manager
 * @author    Synolia <contact@synolia.com>
 * @copyright Open Software License v. 3.0 (https://opensource.org/licenses/OSL-3.0)
 */
class ImportManager
{
    const CATEGORY_PROCESSOR             = 'synolia.oroneo.import.processor.category';
    const ATTRIBUTE_PROCESSOR            = 'synolia.oroneo.import.processor.attribute';
    const OPTION_PROCESSOR               = 'synolia.oroneo.import.processor.option';
    const FAMILY_PROCESSOR               = 'synolia.oroneo.import.processor.family';
    const ATTRIBUTE_GROUP_PROCESSOR      = 'synolia.oroneo.import.processor.attribute_group';
    const PRODUCT_PROCESSOR              = 'synolia.oroneo.import.processor.product';
    const PRODUCT_FILE_PROCESSOR         = 'synolia.oroneo.import.processor.product_file';
    const ATTRIBUTE_VALIDATION_JOB       = 'oroneo_attribute_import_validation_csv';
    const ATTRIBUTE_JOB                  = 'oroneo_attribute_import_csv';
    const ATTRIBUTE_GROUP_VALIDATION_JOB = 'oroneo_attribute_group_import_validation_csv';
    const ATTRIBUTE_GROUP_JOB            = 'oroneo_attribute_group_import_csv';
    const PRODUCT_FILE_VALIDATION_JOB    = 'oroneo_product_file_import_validation_zip';
    const PRODUCT_FILE_JOB               = 'oroneo_product_file_import_zip';
    const VALIDATION_IMPORT_TYPE         = 'validation';
    const EXECUTION_IMPORT_TYPE          = 'import';
    const CLI_IMPORT                     = 'cli-import';
    const CSV_FORMAT                     = 'csv';
    const ZIP_FORMAT                     = 'zip';
    const SFTP_CONNECTION                = 'SFTP';
    const FTP_CONNECTION                 = 'FTP';
    const FILE_PREFIX                    = 'oroneo_import';
    const MAX_VALIDATION_FILESIZE        = 500000; // 500kb.

    /** @var TranslatorInterface $translator */
    protected $translator;

    /** @var HttpImportHandler $httpImportHandler */
    protected $httpImportHandler;

    /** @var ProcessorRegistry $processorRegistry */
    protected $processorRegistry;

    /** @var ConfigManager $configManager */
    protected $configManager;

    /** @var DistantConnectionManager $connectionManager*/
    protected $connectionManager;

    /** @var CliImportHandler $cliImportHandler */
    protected $cliImportHandler;

    /** @var FileSystemOperator $fileSystemOperator */
    private $fileSystemOperator;

    /**
     * ImportManager constructor.
     *
     * @param TranslatorInterface      $translator
     * @param HttpImportHandler        $httpImportHandler
     * @param ProcessorRegistry        $processorRegistry
     * @param ConfigManager            $configManager
     * @param DistantConnectionManager $connectionManager
     * @param CliImportHandler         $cliImportHandler
     * @param FileSystemOperator       $fileSystemOperator
     */
    public function __construct(
        TranslatorInterface $translator,
        HttpImportHandler $httpImportHandler,
        ProcessorRegistry $processorRegistry,
        ConfigManager $configManager,
        DistantConnectionManager $connectionManager,
        CliImportHandler $cliImportHandler,
        FileSystemOperator $fileSystemOperator
    ) {
        $this->translator          = $translator;
        $this->httpImportHandler   = $httpImportHandler;
        $this->processorRegistry   = $processorRegistry;
        $this->configManager       = $configManager;
        $this->connectionManager   = $connectionManager;
        $this->cliImportHandler    = $cliImportHandler;
        $this->fileSystemOperator  = $fileSystemOperator;
    }

    /**
     * Return an array for the form import select.
     *
     * @return array
     */
    public function getProcessorsChoices()
    {
        return [
            self::CATEGORY_PROCESSOR        => $this->translator->trans('synolia.oroneo.category.label'),
            self::ATTRIBUTE_PROCESSOR       => $this->translator->trans('synolia.oroneo.attribute.label'),
            self::OPTION_PROCESSOR          => $this->translator->trans('synolia.oroneo.option.label'),
            self::FAMILY_PROCESSOR          => $this->translator->trans('synolia.oroneo.family.label'),
            self::ATTRIBUTE_GROUP_PROCESSOR => $this->translator->trans('synolia.oroneo.attribute_group.label'),
            self::PRODUCT_PROCESSOR         => $this->translator->trans('synolia.oroneo.product.label'),
            self::PRODUCT_FILE_PROCESSOR    => $this->translator->trans('synolia.oroneo.product_file.label'),
        ];
    }

    /**
     * Execute the import validation and return its result.
     *
     * @param ImportData $data Data from the form submit.
     *
     * @return array
     */
    public function importValidation(ImportData $data)
    {
        $file           = $data->getFile();
        $processorAlias = $data->getProcessorAlias();
        $jobName        = $this->getJob($processorAlias, self::VALIDATION_IMPORT_TYPE);

        $options = [
            'delimiter' => $this->configManager->get('synolia_oroneo.delimiter'),
            'enclosure' => $this->configManager->get('synolia_oroneo.enclosure'),
            'filePath'  => $file->getRealPath(),
        ];

        // Avoid endless loading screen in case of huge file imported by the user.
        if ($file->getSize() >= self::MAX_VALIDATION_FILESIZE) {
            $entityName = $this->processorRegistry->getProcessorEntityName(
                ProcessorRegistry::TYPE_IMPORT_VALIDATION,
                $processorAlias
            );

            return [
                'success'        => true,
                'processorAlias' => $processorAlias,
                'counts'         => [],
                'errors'         => [],
                'entityName'     => $entityName,
                'options'        => $options,
                'fileTooBig'     => true,
                'importJob'      => $jobName,
            ];
        }

        $result = $this->httpImportHandler->handleImportValidation(
            $jobName,
            $processorAlias,
            $options
        );
        $result['importJob'] = $jobName;

        return $result;
    }

    /**
     * Retrieve file from distant server.
     *
     * @param string $processorAlias
     *
     * @return File|null
     */
    public function getDistantFile($processorAlias)
    {
        // Retrieve user's config values.
        $connectionInfo = [
            'username'       => $this->configManager->get('synolia_oroneo.distant_username'),
            'password'       => $this->configManager->get('synolia_oroneo.distant_password'),
            'host'           => $this->configManager->get('synolia_oroneo.distant_host'),
            'port'           => $this->configManager->get('synolia_oroneo.distant_port'),
            'connectionType' => $this->configManager->get('synolia_oroneo.distant_connection_type'),
            'filename'       => $this->getDistantFilenameByImportType($processorAlias),
        ];

        // If missing at least one param then return an empty array to trigger an error.
        if (count($connectionInfo) != count(array_diff($connectionInfo, ['']))) {
            return null;
        }

        // Depending on the connection type.
        switch ($connectionInfo['connectionType']) {
            case self::FTP_CONNECTION:
                return $this->connectionManager->ftpImport(
                    $connectionInfo['username'],
                    $connectionInfo['password'],
                    $connectionInfo['host'],
                    $connectionInfo['port'],
                    $connectionInfo['filename']
                );
                break;
            case self::SFTP_CONNECTION:
                return $this->connectionManager->sftpImport(
                    $connectionInfo['username'],
                    $connectionInfo['password'],
                    $connectionInfo['host'],
                    $connectionInfo['port'],
                    $connectionInfo['filename']
                );
                break;
            default:
                return null;
        }
    }

    /**
     * Retrieve the correct job name depending on the processor chosen.
     *
     * @param string $processorAlias
     * @param string $importType
     *
     * @return string
     */
    public function getJob($processorAlias, $importType = self::VALIDATION_IMPORT_TYPE)
    {
        if ($importType == self::VALIDATION_IMPORT_TYPE) {
            // Custom job if Attribute import
            if ($processorAlias == self::ATTRIBUTE_PROCESSOR) {
                return self::ATTRIBUTE_VALIDATION_JOB;
            }

            // Custom job if Attribute group import
            if ($processorAlias == self::ATTRIBUTE_GROUP_PROCESSOR) {
                return self::ATTRIBUTE_GROUP_VALIDATION_JOB;
            }

            // Custom job if Product file import
            if ($processorAlias == self::PRODUCT_FILE_PROCESSOR) {
                return self::PRODUCT_FILE_VALIDATION_JOB;
            }

            return JobExecutor::JOB_IMPORT_VALIDATION_FROM_CSV;
        }

        // Custom job if Attribute import
        if ($processorAlias == self::ATTRIBUTE_PROCESSOR) {
            return self::ATTRIBUTE_JOB;
        }

        // Custom job if Attribute group import
        if ($processorAlias == self::ATTRIBUTE_GROUP_PROCESSOR) {
            return self::ATTRIBUTE_GROUP_JOB;
        }

        // Custom job if Product file import
        if ($processorAlias == self::PRODUCT_FILE_PROCESSOR) {
            return self::PRODUCT_FILE_JOB;
        }

        return JobExecutor::JOB_IMPORT_FROM_CSV;
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
            case ImportManager::FAMILY_PROCESSOR:
                return $this->configManager->get('synolia_oroneo.distant_filepath_family');
            case ImportManager::ATTRIBUTE_GROUP_PROCESSOR:
                return $this->configManager->get('synolia_oroneo.distant_filepath_attribute_group');
            case ImportManager::PRODUCT_PROCESSOR:
                return $this->configManager->get('synolia_oroneo.distant_filepath_product');
            case ImportManager::PRODUCT_FILE_PROCESSOR:
                return $this->configManager->get('synolia_oroneo.distant_filepath_product-file');
            default:
                return null;
        }
    }
}
