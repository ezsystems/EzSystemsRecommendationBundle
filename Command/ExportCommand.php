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
            ->setName('ezreco:runexport')
            ->setDescription('Run export to files.')
            ->addOption('documentRoot', null, InputOption::VALUE_REQUIRED, 'Root directory, /path/to/web/ for symfony app / apache DOCUMENT_ROOT')
            ->addOption('schemeAndHttpHost', null, InputOption::VALUE_REQUIRED, 'Host used in exported content')
            ->addOption('webHook', null, InputOption::VALUE_REQUIRED, 'Guzzle Client base_uri parameter, will be used to send YC data')
            ->addOption('transaction', null, InputOption::VALUE_REQUIRED)
            ->addOption('host', null, InputOption::VALUE_REQUIRED, 'Host for exportDownload feature')
            ->addOption('responseType', null, InputOption::VALUE_REQUIRED, '', 'export')
            ->addOption('customerId', null, InputOption::VALUE_REQUIRED, 'Your YOOCHOOSE customer ID')
            ->addOption('licenseKey', null, InputOption::VALUE_REQUIRED, 'Your YOOCHOOSE license key')
            ->addOption('lang', null, InputOption::VALUE_OPTIONAL, 'List of languages, eg: eng-GB,fre-FR')
            ->addOption('pageSize', null, InputOption::VALUE_OPTIONAL, '', 1000)
            ->addOption('page', null, InputOption::VALUE_OPTIONAL, '', 1)
            ->addOption('path', null, InputOption::VALUE_OPTIONAL, 'An string of subtree path strings, eg: /1/2/')
            ->addOption('hidden', null, InputOption::VALUE_OPTIONAL, 'If set to 0 - Criterion Visibility: VISIBLE will be used', 0)
            ->addOption('image', null, InputOption::VALUE_OPTIONAL, 'image_variations used for images')
            ->addOption('siteAccess', null, InputOption::VALUE_OPTIONAL, '')
            ->addOption('contentTypeIdList', null, InputOption::VALUE_OPTIONAL, 'List of content Types Id, eg: 16,38')
            ->addOption('contentIdList', null, InputOption::VALUE_OPTIONAL, 'List of content Id, eg: 5,6')
            ->addOption('fields', null, InputOption::VALUE_OPTIONAL, 'list of the fields in content, eg: field1,field2')
            ->addOption(
                'requestContentType',
                null,
                InputOption::VALUE_OPTIONAL,
                'Name of generator, ezpublish_rest.output.generator.NAME, where NAME will be replaces with passed value', 'json'
            )
            ->addOption(
                'mandatorId',
                null,
                InputOption::VALUE_OPTIONAL,
                'This value will be compared to every customer_id stored in ez_recommendation.system.SITEACCESS_NAME, and all matched siteAccess will be used.',
                '0'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = $this->getContainer()->get('logger');

        try {
            if (function_exists('dump')) {
                dump($input->getOptions());
            }

            $input->getOption('documentRoot');
            $input->getOption('contentTypeIdList');

            $contentType = $this->getContainer()->get('ez_recommendation.rest.contenttype');
            date_default_timezone_set('UTC');

            $contentType->runExport($input->getOptions());
        } catch (\Exception $e) {
            $logger->error($e->getMessage());
            throw $e;
        }
    }
}
