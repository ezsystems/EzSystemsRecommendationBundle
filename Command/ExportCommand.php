<?php

namespace EzSystems\RecommendationBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExportCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->addOption('pageSize', null, InputOption::VALUE_OPTIONAL, '', 1000)
            ->addOption('page', null, InputOption::VALUE_OPTIONAL, '', 1)
            ->addOption('path', null, InputOption::VALUE_OPTIONAL)
            ->addOption('hidden', null, InputOption::VALUE_OPTIONAL, '', 0)
            ->addOption('image', null, InputOption::VALUE_OPTIONAL)
            ->addOption('siteAccess', null, InputOption::VALUE_OPTIONAL)
            ->addOption('documentRoot', null, InputOption::VALUE_REQUIRED)
            ->addOption('schemeAndHttpHost', null, InputOption::VALUE_REQUIRED)
            ->addOption('webHook', null, InputOption::VALUE_REQUIRED)
            ->addOption('transaction', null, InputOption::VALUE_REQUIRED)
            ->addOption('lang', null, InputOption::VALUE_OPTIONAL)
            ->addOption('host', null, InputOption::VALUE_REQUIRED)
            ->addOption('responseType', null, InputOption::VALUE_REQUIRED, '', 'export')
            ->addOption('customerId', null, InputOption::VALUE_REQUIRED)
            ->addOption('licenseKey', null, InputOption::VALUE_REQUIRED)
            ->addOption('contentTypeIdList', null, InputOption::VALUE_OPTIONAL)
            ->addOption('contentIdList', null, InputOption::VALUE_OPTIONAL)
            ->addOption('requestedFields', null, InputOption::VALUE_OPTIONAL)
            ->addOption('requestContentType', null, InputOption::VALUE_OPTIONAL, '', 'json')
            ->addOption('requestedMandatorId', null, InputOption::VALUE_OPTIONAL, '', '0')

            ->setName('ezreco:runexport')
            ->setDescription('Run export to files.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $contentType = $this->getContainer()->get('ez_recommendation.rest.contenttype');
        date_default_timezone_set('UTC');

        $contentType->runExport($input->getOptions());
    }
}
