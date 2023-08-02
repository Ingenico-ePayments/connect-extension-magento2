<?php

declare(strict_types=1);

namespace Worldline\Connect\Gateway\Command;

use Magento\Payment\Gateway\CommandInterface;

class VaultCommand implements CommandInterface
{
    public function __construct(
        private readonly AuthorizeCommand $initializeCommand,
        private readonly string $paymentAction
    ) {
    }

    public function execute(array $commandSubject)
    {
        $commandSubject['paymentAction'] = $this->paymentAction;
        $this->initializeCommand->execute($commandSubject);
    }
}
