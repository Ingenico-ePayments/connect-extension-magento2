<?php

declare(strict_types=1);

namespace Ingenico\Connect\GitHub\Dto\Builder;

use DateTime;
use Ingenico\Connect\GitHub\Dto\Release;
use Ingenico\Connect\GitHub\Dto\ReleaseFactory;
use function property_exists;
use stdClass;

class ReleaseBuilder
{
    /** @var ReleaseFactory */
    private $dtoEntityFactory;
    
    public function __construct(ReleaseFactory $releaseDtoFactory)
    {
        $this->dtoEntityFactory = $releaseDtoFactory;
    }
    
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
            $releaseDto->setUrl($object->html_url);
        }
        
        if (property_exists($object, 'tag_name')) {
            $releaseDto->setTagName($object->tag_name);
        }
        
        if (property_exists($object, 'name')) {
            $releaseDto->setName($object->name);
        }
        
        if (property_exists($object, 'body')) {
            $releaseDto->setBody($object->body);
        }
        
        if (property_exists($object, 'created_at')) {
            $releaseDto->setCreatedAt(new DateTime($object->created_at));
        }
        
        if (property_exists($object, 'published_at')) {
            $releaseDto->setPublishedAt(new DateTime($object->published_at));
        }
        
        return $releaseDto;
    }
}
