<?php

declare(strict_types=1);

namespace Worldline\Connect\GitHub\Dto\Builder;

use DateTime;
use stdClass;
use Worldline\Connect\GitHub\Dto\Release;
use Worldline\Connect\GitHub\Dto\ReleaseFactory;

use function property_exists;

// phpcs:ignore PSR12.Files.FileHeader.SpacingAfterBlock

class ReleaseBuilder
{
    /** @var ReleaseFactory */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $dtoEntityFactory;

    public function __construct(ReleaseFactory $releaseDtoFactory)
    {
        $this->dtoEntityFactory = $releaseDtoFactory;
    }

    // phpcs:ignore SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
    public function buildFromObject(stdClass $object): Release
    {
        $releaseDto = $this->dtoEntityFactory->create();
        if ($object === null) {
            return $releaseDto;
        }

        if (property_exists($object, 'id')) {
            $releaseDto->setId((int) $object->id);
        }

        if (property_exists($object, 'html_url')) {
            // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
            $releaseDto->setUrl($object->html_url);
        }

        if (property_exists($object, 'tag_name')) {
            // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
            $releaseDto->setTagName($object->tag_name);
        }

        if (property_exists($object, 'name')) {
            $releaseDto->setName($object->name);
        }

        if (property_exists($object, 'body')) {
            $releaseDto->setBody($object->body);
        }

        if (property_exists($object, 'created_at')) {
            // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
            $releaseDto->setCreatedAt(new DateTime($object->created_at));
        }

        if (property_exists($object, 'published_at')) {
            // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
            $releaseDto->setPublishedAt(new DateTime($object->published_at));
        }

        return $releaseDto;
    }
}
