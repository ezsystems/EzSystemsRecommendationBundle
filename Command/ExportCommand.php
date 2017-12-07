<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Command;

use EzSystems\RecommendationBundle\Export\Exporter;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExportCommand extends ContainerAwareCommand
{
    /** @var \EzSystems\RecommendationBundle\Export\Exporter */
    private $exporter;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * @param \EzSystems\RecommendationBundle\Export\Exporter $exporter
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        Exporter $exporter,
        LoggerInterface $logger
    ) {
        parent::__construct();

        $this->exporter = $exporter;
        $this->logger = $logger;
    }

    protected function configure()
    {
        $this
            ->setName('ezreco:runexport')
            ->setDescription('Run export to files.')
            ->addOption('webHook', null, InputOption::VALUE_REQUIRED, 'Guzzle Client base_uri parameter, will be used to send recommendation data')
            ->addOption('transaction', null, InputOption::VALUE_REQUIRED)
            ->addOption('host', null, InputOption::VALUE_REQUIRED, 'Host used in exportDownload url for notifier in export feature')
            ->addOption('customerId', null, InputOption::VALUE_OPTIONAL, 'Your eZ Recommendation customer ID')
            ->addOption('licenseKey', null, InputOption::VALUE_OPTIONAL, 'Your eZ Recommendation license key')
            ->addOption('lang', null, InputOption::VALUE_OPTIONAL, 'List of language codes, eg: eng-GB,fre-FR')
            ->addOption('pageSize', null, InputOption::VALUE_OPTIONAL, '', 1000)
            ->addOption('page', null, InputOption::VALUE_OPTIONAL, '', 1)
            ->addOption('path', null, InputOption::VALUE_OPTIONAL, 'A string of subtree path, eg: /1/2/')
            ->addOption('hidden', null, InputOption::VALUE_OPTIONAL, 'If set to 0 - Criterion Visibility: VISIBLE will be used', 0)
            ->addOption('image', null, InputOption::VALUE_OPTIONAL, 'image_variations used for images')
            ->addOption('sa', null, InputOption::VALUE_OPTIONAL, 'SiteAccess')
            ->addOption('contentTypeIdList', null, InputOption::VALUE_REQUIRED, 'List of Content Types ID')
            ->addOption('fields', null, InputOption::VALUE_OPTIONAL, 'List of the fields, eg: title, description')
            ->addOption(
                'mandatorId',
                null,
                InputOption::VALUE_OPTIONAL,
                'This value will be compared to every customer_id stored in ez_recommendation.system.SITEACCESS_NAME, and all matched siteAccesses will be used.',
                '0'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $input->validate();

            date_default_timezone_set('UTC');

            $this->exporter->runExport($input->getOptions());
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw $e;
        }
    }
}
