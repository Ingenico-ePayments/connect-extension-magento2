<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Worldline_Connect',
    // phpcs:ignore Generic.PHP.ForbiddenFunctions.Found
    isset($file) ? dirname($file) : __DIR__
);
