<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Command;

use EzSystems\RecommendationBundle\Export\Exporter;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Generates and export content to Recommendation Server for a given command options.
 */
class ExportCommand extends Command
{
    /** @var \EzSystems\RecommendationBundle\Export\Exporter */
    private $exporter;

    /** @var \Symfony\Component\HttpFoundation\RequestStack */
    private $requestStack;

    /** @var \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface */
    private $tokenStorage;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * @param \EzSystems\RecommendationBundle\Export\Exporter $exporter
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        Exporter $exporter,
        RequestStack $requestStack,
        TokenStorageInterface $tokenStorage,
        LoggerInterface $logger
    ) {
        parent::__construct();

        $this->exporter = $exporter;
        $this->requestStack = $requestStack;
        $this->tokenStorage = $tokenStorage;
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
            ->addOption('pageSize', null, InputOption::VALUE_OPTIONAL, '', 500)
            ->addOption('page', null, InputOption::VALUE_OPTIONAL, '', 1)
            ->addOption('path', null, InputOption::VALUE_OPTIONAL, 'A string of subtree path, eg: /1/2/', false)
            ->addOption('hidden', null, InputOption::VALUE_OPTIONAL, 'If set to 1 - Criterion Visibility: VISIBLE will be used', 0)
            ->addOption('image', null, InputOption::VALUE_OPTIONAL, 'image_variations used for images')
            ->addOption('siteaccess', null, InputOption::VALUE_OPTIONAL, 'SiteAccess')
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
            $this->prepare();

            date_default_timezone_set('UTC');

            $this->exporter->runExport($input->getOptions(), $output);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw $e;
        }
    }

    /**
     * Prepares Request and Token for CLI environment, required by RichTextConverter to render embeded content.
     * Avoid 'The token storage contains no authentication token'
     * and 'Rendering a fragment can only be done when handling a Request' exceptions.
     */
    private function prepare()
    {
        $session = new Session();
        $session->start();

        $request = Request::createFromGlobals();
        $request->setSession($session);

        $this->requestStack->push($request);
        $this->tokenStorage->setToken(
            new AnonymousToken('anonymous', 'anonymous', ['ROLE_ADMINISTRATOR'])
        );
    }
}
