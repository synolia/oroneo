<?php

namespace Synolia\Bundle\OroneoBundle\Controller;

use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Synolia\Bundle\OroneoBundle\Manager\ImportManager;

/**
 * Class ImportController
 * Controller to handle the UI import validation and import process.
 */
class ImportController extends Controller
{
    /**
     * Action to display the import form and import validation result.
     *
     * @Route("/import", name="synolia_oroneo_import")
     * @Template()
     *
     * @param Request $request
     *
     * @return array
     */
    public function importAction(Request $request)
    {
        $validationResult = [];
        $processorsChoices = $this->container->get('synolia.oroneo.import.manager')->getProcessorsChoices();

        $form = $this->createForm('akeneo_import_form', null, ['processorsChoices' => $processorsChoices]);

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
                $importJob      = $request->get('importValidateJob', JobExecutor::JOB_VALIDATE_IMPORT_FROM_CSV);
                $validationResult = $this->container->get('synolia.oroneo.import.manager')->importValidation($form->getData(), $importJob);
                $validationResult['importJob'] = $request->get('importJob');
            }
        }

        return [
            'form'   => $form->createView(),
            'result' => $validationResult,
        ];
    }

    /**
     * Action to execute the import itself.
     *
     * @Route("/import/process/{processorAlias}", name="synolia_oroneo_import_process")
     * @AclAncestor("oro_importexport_export")
     *
     * @param string  $processorAlias
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function importProcessAction($processorAlias, Request $request)
    {
        $jobName = $request->get('importJob', JobExecutor::JOB_IMPORT_FROM_CSV);

        // Disable DataAudit Listener
        if ($processorAlias == ImportManager::CATEGORY_PROCESSOR
            || $processorAlias == ImportManager::PRODUCT_PROCESSOR
        ) {
            $entityManager = $this->container->get('doctrine')->getManager();
            $eventManager = $entityManager->getEventManager();

            $eventManager->removeEventListener(
                ['onFlush', 'loadClassMetadata', 'postPersist'],
                $this->container->get('oro_dataaudit.listener.entity_listener')
            );

        }

        $imporResult = $this->container->get('synolia.oroneo.import.manager')->importExecution($processorAlias, $jobName);

        $this->get('session')->getFlashBag()->add(
            ($imporResult['success']) ? 'success':'error',
            $this->get('translator')->trans($imporResult['message'])
        );

        return $this->redirectToRoute('synolia_oroneo_import');
    }

    /**
     * Action to redirect a link to the configuration page.
     *
     * @todo Find a way to remove this action and create a direct link to the configuration page in the navigation menu. ATM can't find a way to create such link with params in it.
     *
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
}
