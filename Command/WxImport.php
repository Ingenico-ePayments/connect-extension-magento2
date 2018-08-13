<?php

namespace Netresearch\Epayments\Command;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Netresearch\Epayments\Cron\FetchWxFiles\ProcessorInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WxImport extends Command
{
    const DATE_OPTION = 'date';

    /** @var ProcessorInterface */
    private $processor;

    /**
     * @var State
     */
    private $appState;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * WxImport constructor.
     * @param ProcessorInterface $processor
     * @param State $appState
     * @param StoreManagerInterface $storeManager
     * @param string|null $name
     */
    public function __construct(
        ProcessorInterface $processor,
        State $appState,
        StoreManagerInterface $storeManager,
        $name = null
    ) {
        $this->processor = $processor;
        $this->appState = $appState;
        $this->storeManager = $storeManager;

        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setName('ingenico:wx-import');
        $this->setDescription('Imports WX transaction file for yesterday or a given date');
        $this->setDefinition(
            [
                new InputOption(
                    self::DATE_OPTION,
                    '-d',
                    InputOption::VALUE_OPTIONAL,
                    'Specific date for importing - must be compatible to PHPs strtotime() function.',
                    'yesterday'
                ),
            ]
        );

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws LocalizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->appState->getAreaCode();
        } catch (LocalizedException $exception) {
            $this->appState->setAreaCode(Area::AREA_CRONTAB);
        }
        $date = $input->getOption(self::DATE_OPTION);
        $output->writeln('Starting import for ' . $date);
        foreach ($this->storeManager->getWebsites() as $website) {
            $group = $this->storeManager->getGroup($website->getDefaultGroupId());
            $this->processor->process($group->getDefaultStoreId(), $date);
        }
    }
}
