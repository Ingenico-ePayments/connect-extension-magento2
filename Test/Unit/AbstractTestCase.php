<?php

namespace Ingenico\Connect\Test\Unit;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

abstract class AbstractTestCase extends TestCase
{
    protected function getObjectManager(): ObjectManager
    {
        return new ObjectManager($this);
    }

    protected function getMockForFactory($instanceName)
    {
        $objectManager = $this->getObjectManager();
        $factory = $this->getMockBuilder($instanceName . 'Factory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $factory->expects($this->any())
            ->method('create')
            ->will($this->returnCallback(function ($args) use ($instanceName, $objectManager) {
                return $objectManager->getObject($instanceName, $args);
            }));
        return $factory;
    }
}
