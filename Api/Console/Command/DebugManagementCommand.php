<?php

namespace Auctane\Api\Console\Command;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DebugManagementCommand
 *
 * @package Auctane\Api\Console\Command
 */
class DebugManagementCommand extends Command
{
    const COMMAND_NAME = 'auctane:api:debug';
    const ARGUMENT_DEBUG_STATUS = 'debug';

    const CONFIG_DEBUG_PATH = 'shipstation_general/shipstation/debug_mode';

    /** @var WriterInterface */
    private $config;


    /**
     * DebugManagementCommand constructor.
     *
     * @param WriterInterface $config
     */
    public function __construct(
        WriterInterface $config
    )
    {
        parent::__construct(self::COMMAND_NAME);

        $this->config = $config;
    }

    /**
     * Command configuration.
     */
    public function configure()
    {
        $this
            ->setDescription("Activates debug mode.")
            ->setDefinition([
                new InputArgument(
                    'debug',
                    InputArgument::REQUIRED,
                    '1 to activate, 0 to deactivate.'
                )
            ]);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->config->save(self::CONFIG_DEBUG_PATH, $input->getArgument(self::ARGUMENT_DEBUG_STATUS));
    }
}
