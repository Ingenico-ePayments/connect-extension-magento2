<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Command;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Worldline\Connect\Model\Event\Processor;

class ProcessEvents extends Command
{
    public const AMOUNT_OPTION = 'amount';

    /**
     * @var Processor
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $processor;

    /**
     * @var State
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $appState;

    /**
     * ProcessEvents constructor.
     *
     * @param Processor $processor
     * @param State $appState
     * @param string|null $name
     */
    public function __construct(Processor $processor, State $appState, $name = null)
    {
        $this->processor = $processor;
        $this->appState = $appState;

        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setName('worldline:process-events');
        $this->setDescription('Batch processes webhook events');
        $this->setDefinition(
            [
                new InputOption(
                    self::AMOUNT_OPTION,
                    '-a',
                    InputOption::VALUE_OPTIONAL,
                    'Amount of events that should get processed.',
                    20
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
        $amount = $input->getOption(self::AMOUNT_OPTION);
        $output->writeln('Start processing events...');
        $this->processor->processBatch($amount);
        $output->writeln('Finished processing events');
    }
}
