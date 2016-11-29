<?php

namespace Synolia\Bundle\OroneoBundle\Controller;

use Oro\Bundle\ImportExportBundle\Form\Model\ImportData;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Oro\Bundle\ImportExportBundle\Handler\HttpImportHandler;
use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;
use Synolia\Bundle\OroneoBundle\Service\ImportService;

/**
 * Class InterfaceController
 */
class InterfaceController extends Controller
{
    /**
     * @Route("/import", name="synolia_Oroneo_import")
     * @Template()
     *
     * @param Request $request
     *
     * @return array
     */
    public function importAction(Request $request)
    {
        $processorsChoices = $this->container->get('synolia.oroneo.import.service')->getProcessorsChoices();

        $form = $this->createForm('akeneo_import_form', null, ['processorsChoices' => $processorsChoices]);
        $validationResult = [];

        if ($request->isMethod('POST')) {
            $form->submit($request);
            $localMapping = $this->container->get('oro_config.global')->get('synolia_oroneo.localization_mapping');
            if (empty($localMapping)) {
                $this->get('session')->getFlashBag()->add(
                    'error',
                    $this->get('translator')->trans(
                        'synolia.oroneo.import_page.error.mapping',
                        [
                            '%url%' => $this->generateUrl(
                                'oro_config_configuration_system',
                                [
                                    'activeGroup' => 'oroneo',
                                    'activeSubGroup' => 'oroneo_global_config_localization',
                                ]
                            ),
                        ]
                    )
                );
            } elseif ($form->isValid()) {
                /** @var ImportData $data */
                $data           = $form->getData();
                $file           = $data->getFile();
                $processorAlias = $data->getProcessorAlias();
                $importJob      = $request->get('importValidateJob', JobExecutor::JOB_VALIDATE_IMPORT_FROM_CSV);
                $inputFormat    = 'csv';

                // Custom job if Attribute import
                if ($processorAlias == ImportService::ATTRIBUTE_PROCESSOR) {
                    $importJob = ImportService::ATTRIBUTE_VALIDATION_JOB;
                }

                // Custom job if Product file import
                if ($processorAlias == ImportService::PRODUCT_FILE_PROCESSOR) {
                    $importJob = ImportService::PRODUCT_FILE_VALIDATION_JOB;
                    $inputFormat = 'zip';
                }

                $this->getImportHandler()->saveImportingFile($file, $processorAlias, $inputFormat);

                $processorRegistry = $this->get('oro_importexport.processor.registry');
                $entityName        = $processorRegistry
                    ->getProcessorEntityName(ProcessorRegistry::TYPE_IMPORT_VALIDATION, $processorAlias);
                $existingAliases   = $processorRegistry
                    ->getProcessorAliasesByEntity(ProcessorRegistry::TYPE_IMPORT_VALIDATION, $entityName);

                $validationResult = $this->getImportHandler()->handleImportValidation(
                    $importJob,
                    $processorAlias,
                    $inputFormat,
                    null,
                    [
                        'delimiter' => $this->container->get('oro_config.global')->get('synolia_oroneo.delimiter'),
                        'enclosure' => $this->container->get('oro_config.global')->get('synolia_oroneo.enclosure'),
                    ]
                );
                $validationResult['showStrategy'] = count($existingAliases) > 1;
                $validationResult['importJob'] = $request->get('importJob');
            }
        }

        return [
            'form'   => $form->createView(),
            'result' => $validationResult,
        ];
    }

    /**
     * @Route("/import/process/{processorAlias}", name="synolia_Oroneo_import_process")
     * @AclAncestor("oro_importexport_export")
     *
     * @param string $processorAlias
     *
     * @return RedirectResponse
     */
    public function importProcessAction($processorAlias)
    {
        $jobName = $this->getRequest()->get('importJob', JobExecutor::JOB_IMPORT_FROM_CSV);
        $inputFormat = 'csv';

        // Disable DataAudit Listener
        if ($processorAlias == ImportService::CATEGORY_PROCESSOR
            || $processorAlias == ImportService::PRODUCT_PROCESSOR
        ) {
            $entityManager = $this->container->get('doctrine')->getManager();
            $eventManager = $entityManager->getEventManager();

            $eventManager->removeEventListener(
                ['onFlush', 'loadClassMetadata', 'postPersist'],
                $this->container->get('oro_dataaudit.listener.entity_listener')
            );

        }

        // Custom job if Attribute import
        if ($processorAlias == ImportService::ATTRIBUTE_PROCESSOR) {
            $jobName = ImportService::ATTRIBUTE_JOB;
        }

        // Custom job if Product file import
        if ($processorAlias == ImportService::PRODUCT_FILE_PROCESSOR) {
            $jobName = ImportService::PRODUCT_FILE_JOB;
            $inputFormat = 'zip';
        }

        // Execute the import
        $result  = $this->getImportHandler()->handleImport(
            $jobName,
            $processorAlias,
            $inputFormat,
            null,
            [
                'delimiter' => $this->container->get('oro_config.global')->get('synolia_oroneo.delimiter'),
                'enclosure' => $this->container->get('oro_config.global')->get('synolia_oroneo.enclosure'),
            ]
        );

        // Update schema if Attribute import
        if ($processorAlias == ImportService::ATTRIBUTE_PROCESSOR) {
            $this->container->get('oro_entity_extend.extend.entity_processor')->updateDatabase(true, true);
        }

        $this->get('session')->getFlashBag()->add(
            ($result['success']) ? 'success':'error',
            $this->get('translator')->trans($result['message'])
        );

        return $this->redirectToRoute('synolia_Oroneo_import');
    }

    /**
     * @Route("/configuration", name="synolia_Oroneo_configuration")
     *
     * @return RedirectResponse
     */
    public function configurationAction()
    {
        return $this->redirectToRoute(
            'oro_config_configuration_system',
            [
                'activeGroup' => 'oroneo',
                'activeSubGroup' => 'oroneo_global_config_settings',
            ]
        );
    }

    /**
     * @return HttpImportHandler
     */
    protected function getImportHandler()
    {
        return $this->get('oro_importexport.handler.import.http');
    }

    /**
     * @return array
     */
    protected function getOptionsFromRequest()
    {
        $options = $this->getRequest()->get('options', []);

        if (!is_array($options)) {
            throw new InvalidArgumentException('Request parameter "options" must be array.');
        }

        return $options;
    }
}
