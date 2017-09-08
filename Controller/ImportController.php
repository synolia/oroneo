<?php

namespace Synolia\Bundle\OroneoBundle\Controller;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Oro\Bundle\ImportExportBundle\Async\Topics;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;
use Synolia\Bundle\OroneoBundle\Manager\ImportManager;

/**
 * Class ImportController
 * Controller to handle the UI import validation and import process.
 * @package   Synolia\Bundle\OroneoBundle\Controller
 * @author    Synolia <contact@synolia.com>
 * @copyright Open Software License v. 3.0 (https://opensource.org/licenses/OSL-3.0)
 */
class ImportController extends Controller
{
    /**
     * Action to display the import form and import validation result.
     * It's about VALIDATION here; not the effective import.
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
        $form = $this->createForm('synolia_oroneo_import_form', null, ['processorsChoices' => $processorsChoices]);

        if ($request->isMethod('POST')) {
            $form->submit($request);
            $localMapping = $this->container->get('oro_config.global')->get('synolia_oroneo.localization_mapping');
            $file = $form->getData()->getFile();
            if (empty($localMapping)) {
                // No locale mapping in config: Flash an error.
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
            } elseif ($form->get('isManualImport')->getData() === false && null !== $form->getData()->getProcessorAlias()) {
                // FTP/SFTP import : file downloaded from a remote server.
                $file = null;
                try {
                    $file = $this->container->get('synolia.oroneo.import.manager')->getDistantFile($form->getData()->getProcessorAlias());
                } catch (\Exception $e) {
                    $this->get('session')->getFlashBag()->add('error', $e->getMessage());
                }
                $form->getData()->setFile($file);
                if (null === $file) {
                    // Flash an error if the distant import failed.
                    $this->get('session')->getFlashBag()->add(
                        'error',
                        $this->get('translator')->trans(
                            'synolia.oroneo.import_page.error.distant.missing_config',
                            [
                                '%url%' => $this->generateUrl(
                                    'oro_config_configuration_system',
                                    [
                                        'activeGroup' => 'oroneo',
                                        'activeSubGroup' => 'oroneo_config_distant',
                                    ]
                                ),
                            ]
                        )
                    );

                    $form->addError(new FormError('missing file'));
                }
            }

            if ($form->isValid()) {
                /** @var UploadedFile $file */
                if ($file) {
                    $fileName = $this->get('oro_importexport.file.file_manager')->saveImportingFile($file);
                    $this->get('session')->set('fileName', $fileName);
                    $this->get('session')->set('originFileName', $file->getClientOriginalName());
                }
                $validationResult = $this->container->get('synolia.oroneo.import.manager')->importValidation($form->getData());
            } else {
                if ($form->get('isManualImport')->getData() === true) {
                    $this->get('session')->getFlashBag()->add(
                        'error',
                        $this->get('translator')->trans('synolia.oroneo.import_page.error.default_message')
                    );
                    throw new NotFoundHttpException('Import file not found.');
                }
            }
        }

        return [
            'form'    => $form->createView(),
            'result'  => $validationResult,
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
        $jobName  = $this->container->get('synolia.oroneo.import.manager')->getJob($processorAlias, ImportManager::EXECUTION_IMPORT_TYPE);
        $fileName = $this->get('session')->get('fileName');
        $originFileName = $this->get('session')->get('originFileName');
        $token = $this->get('security.token_storage')->getToken();

        $this->get('oro_message_queue.client.message_producer')->send(
            Topics::PRE_HTTP_IMPORT,
            [
                'fileName' => $fileName,
                'process' => ProcessorRegistry::TYPE_IMPORT,
                'originFileName' => $originFileName,
                'userId' => $this->getUser()->getId(),
                'securityToken' => $this->get('oro_security.token_serializer')->serialize($token),
                'jobName' => $jobName,
                'processorAlias' => $processorAlias,
                'options' => $this->getOptionsFromRequest($request),
            ]
        );

        $this->get('session')->getFlashBag()->add(
            'success',
            $this->get('translator')->trans('synolia.oroneo.import_page.success.message')
        );

        if ($processorAlias == ImportManager::ATTRIBUTE_PROCESSOR) {
            $message = $this->get('translator')->trans(
                'oro.translation.translation.rebuild_cache_required',
                [
                    '%path%' => $this->generateUrl('oro_translation_translation_index'),
                ]
            );
            $this->get('session')->getFlashBag()->add('warning', $message);
        }

        return $this->redirectToRoute('synolia_oroneo_import');
    }

    /**
     * Tests the remote connection
     *
     * @Route("/testConnection", name="synolia_oroneo_test_configuration", options={"expose"=true})
     *
     * @return JsonResponse
     */
    public function testConnectionAction()
    {
        $connectionManager = $this->get('synolia.oroneo.distant_connection.manager');

        try {
            $connectionManager->testConnection();
            $type = 'success';
            $message = $this->get('translator')->trans('synolia.oroneo.import_page.test_connection.success');
        } catch (\Exception $e) {
            $type = 'error';
            $message = $e->getMessage();
        }

        return new JsonResponse([
            'type' => $type,
            'message' => $message,
        ]);
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    protected function getOptionsFromRequest(Request $request)
    {
        $options = $request->get('options', []);

        if (!is_array($options)) {
            throw new InvalidArgumentException('Request parameter "options" must be array.');
        }

        return $options;
    }
}
