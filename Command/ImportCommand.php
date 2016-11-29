<?php

namespace Synolia\Bundle\OroneoBundle\Command;

use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Oro\Bundle\ImportExportBundle\Command\ImportCommand as OroImportCommand;

/**
 * Class ImportOptionCommand
 */
class ImportCommand extends OroImportCommand
{
    const COMMAND_NAME  = 'synolia:akeneo-pim:import';
    const ARGUMENT_TYPE = 'import-type';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(static::COMMAND_NAME)
            ->setDescription('Akeneo import')
            ->addArgument(
                self::ARGUMENT_TYPE,
                InputArgument::REQUIRED,
                'Type import to be executed'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $oroConfig  = $this->getContainer()->get('oro_config.global');
        $importType = $input->getArgument(self::ARGUMENT_TYPE);
        $config     = $oroConfig->get('synolia_oroneo.jobs');

        if (!isset($config[$importType])) {
            throw new \InvalidArgumentException('Import type '.$importType.' does not exist');
        }

        $importConfig  = $config[$importType];
        $noInteraction = $input->getOption('no-interaction');

        $this->getImportHandler()->setImportingFileName($importConfig['import_file']);

        $batchJob    = isset($importConfig['batch_job']) ? $importConfig['batch_job'] : JobExecutor::JOB_IMPORT_FROM_CSV;
        $inputFormat = isset($importConfig['input_format']) ? $importConfig['input_format'] : 'csv';

        $importInfo = $this->getImportHandler()->handleImport(
            $batchJob,
            $importConfig['processor'],
            $inputFormat,
            null,
            [
                'delimiter' => ';',
            ]
        );

        if (!$noInteraction) {
            $this->renderResult($importInfo, $output);
        }

        $output->writeln('<info>'.$importInfo['message'].'</info>');

        return self::STATUS_SUCCESS;
    }
}
