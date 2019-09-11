<?php

namespace Ingenico\Connect\Model\Ingenico\Client;

use Ingenico\Connect\Sdk\Domain\MetaData\ShoppingCartExtension;

class ShoppingCartExtensionFactory
{
    /**
     * Create instance of Ingenico ShoppingCartExtensionFactory
     *
     * @param string $creator
     * @param string $name
     * @param string $version
     * @return ShoppingCartExtension
     */
    public function create($creator, $name, $version)
    {
        return new ShoppingCartExtension($creator, $name, $version);
    }
}
