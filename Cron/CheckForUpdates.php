<?php

declare(strict_types=1);

namespace Ingenico\Connect\Cron;

use Ingenico\Connect\Model\VersionService;

class CheckForUpdates
{
    /** @var VersionService */
    private $versionService;
    
    public function __construct(VersionService $versionService)
    {
        $this->versionService = $versionService;
    }
    
    public function execute()
    {
        $this->versionService->updateLatestRelease();
    }
}
