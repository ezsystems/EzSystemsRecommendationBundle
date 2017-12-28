<?php
/**
 * This file is part of the EzSystemRecommendationBundle package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Helper;

use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;
use Psr\Log\LoggerInterface;

class ExportProcessRunner
{
    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /** @var string */
    private $kernelEnvironment;

    /**
     * @param \Psr\Log\LoggerInterface $logger
     * @param string $kernelEnvironment
     */
    public function __construct(
        LoggerInterface $logger,
        $kernelEnvironment
    ) {
        $this->logger = $logger;
        $this->kernelEnvironment = $kernelEnvironment;
    }

    /**
     * @param array $parameters
     */
    public function run(array $parameters = [])
    {
        $documentRoot = $parameters['documentRoot'];
        unset($parameters['documentRoot']);

        $console = file_exists('bin/console') ? 'bin/console' : (file_exists('ezpublish/console') ? 'ezpublish/console' : 'app/console');

        $builder = new ProcessBuilder([
            $documentRoot . '/../' . $console,
            'ezreco:runexport',
            '--env=' . $this->kernelEnvironment,
        ]);
        $builder->setWorkingDirectory($documentRoot . '../');
        $builder->setTimeout(null);
        $builder->setPrefix([
            $this->getPhpPath(),
            '-d',
            'memory_limit=-1',
        ]);

        foreach ($parameters as $key => $option) {
            if (empty($option)) {
                continue;
            }

            $builder->add(sprintf('--%s=%s', $key, $option));
        }

        $command = $builder->getProcess()->getCommandLine();
        $output = sprintf(
            ' > %s 2>&1 & echo $! > %s',
            $documentRoot . '/var/export/.log',
            $documentRoot . '/var/export/.pid'
        );

        $this->logger->info(sprintf('Running command: %s', $command . $output));

        $process = new Process($command . $output);
        $process->disableOutput();
        $process->run();
    }

    /**
     * @return string
     */
    private function getPhpPath()
    {
        static $phpPath;

        if (null !== $phpPath) {
            return $phpPath;
        }
        $phpFinder = new PhpExecutableFinder();
        $phpPath = $phpFinder->find();
        if (!$phpPath) {
            throw new \RuntimeException(
                'The php executable could not be found, it\'s needed for executing parable sub processes, so add it to your PATH environment variable and try again'
            );
        }

        return $phpPath;
    }
}
