<?php

namespace Synolia\Bundle\OroneoBundle\Command;

use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Oro\Bundle\ImportExportBundle\Command\ImportCommand as OroImportCommand;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Synolia\Bundle\OroneoBundle\Manager\ImportManager;

/**
 * Class ImportCommand
 * @package   Synolia\Bundle\OroneoBundle\Command
 * @author    Synolia <contact@synolia.com>
 * @copyright Open Software License v. 3.0 (https://opensource.org/licenses/OSL-3.0)
 */
class ImportCommand extends OroImportCommand
{
    const COMMAND_NAME   = 'synolia:akeneo-pim:import';
    const ARGUMENT_TYPE  = 'import-type';
    const FILE_PATH      = 'file';

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
                'Type import to be executed. All imports are executed if missing.'
            )
            ->addArgument(
                self::FILE_PATH,
                InputArgument::OPTIONAL,
                'Optional: filepath of the CSV to import for one import at a time.'
            )
            ->addOption(
                'email',
                null,
                InputOption::VALUE_REQUIRED,
                'Email to send the log after the import is completed'
            )
            ->addOption(
                'processor',
                null,
                InputOption::VALUE_REQUIRED,
                'Name of the import processor.'
            )
            ->addOption(
                'jobName',
                null,
                InputOption::VALUE_REQUIRED,
                'Name of Import Job.'
            )
            ->addOption(
                'validation',
                null,
                InputOption::VALUE_NONE,
                'If adding this option then validation will be performed instead of import'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $importType = $input->getArgument(self::ARGUMENT_TYPE);
        $config = $this->getConfig();

        if (!isset($config[$importType]) && null !== $importType && 'import_all' !== $importType) {
            throw new \InvalidArgumentException('Import type '.$importType.' does not exist');
        }

        if (null === $importType) {
            throw new \InvalidArgumentException('Import type argument is missing.');
        }

        if ($importType == 'import_all') {
            return $this->importAll($input, $output);
        }
        $input = $this->inputDefinition($input);

        return parent::execute($input, $output);
    }

    /**
     * @param InputInterface $input
     *
     * @return InputInterface
     */
    protected function inputDefinition(InputInterface $input)
    {
        $config = $this->getConfig();

        $fileArg = $input->getArgument(self::FILE_PATH);

        $importConfig = $config[$input->getArgument(self::ARGUMENT_TYPE)];
        $jobName = isset($importConfig['batch_job']) ? $importConfig['batch_job'] : JobExecutor::JOB_IMPORT_FROM_CSV;
        $importPath = $importConfig['import_file'];
        if ($fileArg) {
            $importPath = $fileArg;
        }

        $input->setArgument('file', $importPath);
        $input->setOption('jobName', $jobName);
        $input->setOption('processor', $importConfig['processor']);

        return $input;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function importAll(InputInterface $input, OutputInterface $output)
    {
        $config = $this->getConfig();

        foreach ($config as $type => $parameters) {
            $output->writeln('<info>Importing '.$type.'</info>');
            if (!is_file($parameters['import_file'])) {
                $output->writeln('<error>Missing '.$type.' file.</error>');
            } else {
                $input->setArgument('file', $parameters['import_file']);
                $jobName = JobExecutor::JOB_IMPORT_FROM_CSV;
                if (isset($parameters['batch_job'])) {
                    $jobName = $parameters['batch_job'];
                }
                $input->setOption('jobName', $jobName);
                $input->setOption('processor', $parameters['processor']);
                parent::execute($input, $output);
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
