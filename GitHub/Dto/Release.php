<?php

declare(strict_types=1);

namespace Ingenico\Connect\GitHub\Dto;

use DateTime;

class Release
{
    /** @var int|null */
    protected $id;
    
    /** @var string|null */
    protected $url;
    
    /** @var string|null */
    protected $tagName;
    
    /** @var string|null */
    protected $name;
    
    /** @var string|null */
    protected $body;
    
    /** @var DateTime|null */
    protected $createdAt;
    
    /** @var DateTime|null */
    protected $publishedAt;
    
    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }
    
    /**
     * @param int|null $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
    
    /**
     * @return string|null
     */
    public function getUrl()
    {
        return $this->url;
    }
    
    /**
     * @param string $url
     */
    public function setUrl(string $url)
    {
        $this->url = $url;
    }
    
    /**
     * @return string|null
     */
    public function getTagName()
    {
        return $this->tagName;
    }
    
    /**
     * @param string $tagName
     */
    public function setTagName(string $tagName)
    {
        $this->tagName = $tagName;
    }
    
    /**
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }
    
    /**
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }
    
    /**
     * @return string|null
     */
    public function getBody()
    {
        return $this->body;
    }
    
    /**
     * @param string $body
     */
    public function setBody(string $body)
    {
        $this->body = $body;
    }
    
    /**
     * @return DateTime|null
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }
    
    /**
     * @param DateTime $createdAt
     */
    public function setCreatedAt(DateTime $createdAt)
    {
        $this->createdAt = $createdAt;
    }
    
    /**
     * @return DateTime|null
     */
    public function getPublishedAt()
    {
        return $this->publishedAt;
    }
    
    /**
     * @param DateTime $publishedAt
     */
    public function setPublishedAt(DateTime $publishedAt)
    {
        $this->publishedAt = $publishedAt;
    }
}
