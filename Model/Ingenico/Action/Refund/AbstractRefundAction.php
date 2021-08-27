<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Ingenico\Action\Refund;

use Ingenico\Connect\Model\ConfigProvider;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Creditmemo;

/**
 * Abstract class to handle creditmemo and order persistence for
 * refund actions
 *
 * @package Ingenico\Connect\Model\Ingenico\Action\Refund
 */
abstract class AbstractRefundAction implements RefundActionInterface
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var CreditmemoRepositoryInterface
     */
    private $creditmemoRepository;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        CreditmemoRepositoryInterface $creditmemoRepository
    ) {
        $this->orderRepository = $orderRepository;
        $this->creditmemoRepository = $creditmemoRepository;
    }

    /**
     * @param CreditmemoInterface $creditMemo
     * @return void
     * @throws LocalizedException
     */
    final public function process(CreditmemoInterface $creditMemo)
    {
        $order = $creditMemo instanceof Creditmemo
            ? $creditMemo->getOrder()
            : $this->orderRepository->get($creditMemo->getOrderId());

        $this->validatePaymentMethod($order);
        $this->performRefundAction($order, $creditMemo);
        $this->persist($order, $creditMemo);

        $this->performPostRefundAction($order, $creditMemo);
    }

    /**
     * @param OrderInterface $order
     * @param CreditmemoInterface $creditMemo
     * @return void
     * @throws LocalizedException
     */
    abstract protected function performRefundAction(
        OrderInterface $order,
        CreditmemoInterface $creditMemo
    );

    /**
     * @param OrderInterface $order
     * @param Creditmemo $creditMemo
     */
    protected function performPostRefundAction(OrderInterface $order, Creditmemo $creditMemo)
    {
        // Stub that can be used to perform post-save actions. These might
        // be helpful for new credit memo's where an ID might be required.
    }

    final protected function persist(OrderInterface $order, Creditmemo $creditMemo)
    {
        $this->creditmemoRepository->save($creditMemo);
        $this->orderRepository->save($order);
    }

    /**
     * @param OrderInterface $order
     * @throws LocalizedException
     */
    private function validatePaymentMethod(OrderInterface $order)
    {
        if (!$order->getPayment()->getMethod() === ConfigProvider::CODE) {
            throw new LocalizedException(__('Order is not paid with Ingenico'));
        }
    }
}
