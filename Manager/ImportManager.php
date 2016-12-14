<?php

namespace Synolia\Bundle\OroneoBundle\Manager;

use Oro\Bundle\ImportExportBundle\Handler\HttpImportHandler;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Symfony\Component\Translation\TranslatorInterface;
use Oro\Bundle\ImportExportBundle\Form\Model\ImportData;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
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

    /**
     * @var TranslatorInterface $translator
     */
    protected $translator;

    /**
     * @var HttpImportHandler $importHandler
     */
    protected $importHandler;

    /**
     * @var ProcessorRegistry $processorRegistry
     */
    protected $processorRegistry;

    /** @var ConfigManager $configManager */
    protected $configManager;

    /** @var EntityProcessor $entityProcessor */
    protected $entityProcessor;

    /**
     * ImportManager constructor.
     *
     * @param TranslatorInterface $translator
     * @param HttpImportHandler   $importHandler
     * @param ProcessorRegistry   $processorRegistry
     * @param ConfigManager       $configManager
     * @param EntityProcessor     $entityProcessor
     */
    public function __construct(
        TranslatorInterface $translator,
        HttpImportHandler $importHandler,
        ProcessorRegistry $processorRegistry,
        ConfigManager $configManager,
        EntityProcessor $entityProcessor
    ) {
        $this->translator        = $translator;
        $this->importHandler     = $importHandler;
        $this->processorRegistry = $processorRegistry;
        $this->configManager     = $configManager;
        $this->entityProcessor   = $entityProcessor;
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
            $this->entityProcessor->updateDatabase(true, true);
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
}
