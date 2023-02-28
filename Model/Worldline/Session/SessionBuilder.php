<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Model\Worldline\Session;

use Ingenico\Connect\Sdk\Domain\Sessions\SessionResponse;
use Worldline\Connect\Api\Data\SessionInterface;
use Worldline\Connect\Api\Data\SessionInterfaceFactory;

class SessionBuilder
{
    /** @var SessionFactory */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    protected $sessionFactory;

    public function __construct(SessionInterfaceFactory $sessionFactory)
    {
        $this->sessionFactory = $sessionFactory;
    }

    public function build(SessionResponse $sessionResponse): SessionInterface
    {
        $session = $this->sessionFactory->create();
        $session->fromJson($sessionResponse->toJson());

        return $session;
    }
}
