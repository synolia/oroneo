<?php

namespace Synolia\Bundle\OroneoBundle\Command;

use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Oro\Bundle\ImportExportBundle\Command\ImportCommand as OroImportCommand;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

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
                InputArgument::OPTIONAL,
                'Type import to be executed. All imports are executed if missing'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $importType = $input->getArgument(self::ARGUMENT_TYPE);

        if (is_null($importType)) {
            return $this->importAll($output, $input->getOption('no-interaction'));
        } else {
            return $this->import($output, $importType, $input->getOption('no-interaction'));
        }
    }

    /**
     * @param OutputInterface $output
     * @param bool            $noInteraction
     *
     * @return int
     */
    protected function importAll(OutputInterface $output, $noInteraction)
    {
        $consolePath = $this->getContainer()->getParameter('kernel.root_dir').DIRECTORY_SEPARATOR.'console';
        $baseCommand = $this->getPhpPath().' '.$consolePath.' '.self::COMMAND_NAME.' ';

        if ($noInteraction) {
            $baseCommand .= '--no-interaction ';
        }

        $config = $this->getConfig();

        foreach ($config as $type => $parameters) {
            $output->writeln('<info>Importing '.$type.'</info>');

            $process = new Process($baseCommand.$type);
            $result = $process->run();

            $output->writeln($process->getOutput());

            $errors = $process->getErrorOutput();
            if (!empty($errors)) {
                $output->writeln('<error>'.$errors.'</error>');
            }

            if ($result != self::STATUS_SUCCESS) {
                return $result;
            }
        }

        return self::STATUS_SUCCESS;
    }

    /**
     * @param OutputInterface $output
     * @param string          $importType
     * @param bool            $noInteraction
     *
     * @return int
     */
    protected function import(OutputInterface $output, $importType, $noInteraction)
    {
        $config = $this->getConfig();

        if (!isset($config[$importType])) {
            throw new \InvalidArgumentException('Import type '.$importType.' does not exist');
        }

        $importConfig = $config[$importType];

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

    /**
     * @return false|string
     * @throws \Exception
     */
    protected function getPhpPath()
    {
        $phpFinder     = new PhpExecutableFinder();
        $phpExecutable = $phpFinder->find();

        if (!$phpExecutable) {
            throw new \Exception('Can\'t find PHP executable');
        }

        return $phpExecutable;
    }

    /**
     * {@inheritdoc}
     */
    protected function renderResult(array $result, OutputInterface $output)
    {
        if ($output instanceof ConsoleOutputInterface && !empty($result['errors'])) {
            $errors           = $result['errors'];
            $result['errors'] = [];
        }

        parent::renderResult($result, $output);

        if (isset($errors)) {
            $errorOutput = $output->getErrorOutput();

            foreach ($errors as $errorMessage) {
                $errorOutput->writeln('<error>'.$errorMessage.'</error>');
            }
        }
    }

    /**
     * Gets the jobs configuration
     *
     * @return array
     */
    protected function getConfig()
    {
        $oroConfig = $this->getContainer()->get('oro_config.global');

        return $oroConfig->get('synolia_oroneo.jobs');
    }
}
