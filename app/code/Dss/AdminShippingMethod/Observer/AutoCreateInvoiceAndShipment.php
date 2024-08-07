<?php

declare(strict_types=1);

/**
 * Digit Software Solutions.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 *
 * @category  Dss
 * @package   Dss_AdminShippingMethod
 * @author    Extension Team
 * @copyright Copyright (c) 2024 Digit Software Solutions. ( https://digitsoftsol.com )
 */

namespace Dss\AdminShippingMethod\Observer;

use Dss\AdminShippingMethod\Helper\Data;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\DB\TransactionFactory;
use Magento\Sales\Model\Convert\Order as ConvertOrder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Shipping\Model\ShipmentNotifier;

class AutoCreateInvoiceAndShipment implements ObserverInterface
{
    /**
     * AutoCreateInvoice constructor.
     * @param InvoiceService $invoiceService
     * @param ManagerInterface $messageManager
     * @param TransactionFactory $transaction
     * @param ConvertOrder $convertOrder
     * @param ShipmentNotifier $shipmentNotifier
     * @param Data $helper
     * @param ProductMetadataInterface $productMetadata
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param OrderItemRepositoryInterface $itemRepository
     */
    public function __construct(
        protected InvoiceService $invoiceService,
        protected ManagerInterface $messageManager,
        protected TransactionFactory $transaction,
        protected ConvertOrder $convertOrder,
        protected ShipmentNotifier $shipmentNotifier,
        protected Data $helper,
        protected ProductMetadataInterface $productMetadata,
        private SearchCriteriaBuilder $searchCriteriaBuilder,
        private OrderItemRepositoryInterface $itemRepository
    ) {
    }

    /**
     * Handle the event observer
     *
     * @param Observer $observer
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $shipment = $order->getShippingMethod();
        $storeId = $order->getStoreId();
        // Check code payment method
        if ($shipment == 'adminshippingmethod_adminshippingmethod') {
            // Check option createshipment
            if ($this->helper->getCreatShipment($storeId)) {
                // to check order can ship or not
                if (!$order->canShip()) {
                    throw new LocalizedException(
                        __('You cant create the Shipment of this order.')
                    );
                }
                $orderShipment = $this->convertOrder->toShipment($order);
                if ($this->productMetadata->getVersion() > "2.3.6") {
                    $this->setItemsOrder($order);
                }
                foreach ($order->getAllItems() as $orderItem) {
                    // Check virtual item and item Quantity
                    if (!$orderItem->getQtyToShip() || $orderItem->getIsVirtual()) {
                        continue;
                    }
                    $qty = $orderItem->getQtyToShip();
                    $shipmentItem = $this->convertOrder->itemToShipmentItem($orderItem)->setQty($qty);
                    $orderShipment->addItem($shipmentItem);
                }
                $orderShipment->register();
                $orderShipment->getOrder()->setIsInProcess(true);
                try {
                    // Save created Order Shipment
                    $orderShipment->save();
                    $orderShipment->getOrder()->save();

                    // Send Shipment Email
                    $this->shipmentNotifier->notify($orderShipment);
                    $orderShipment->save();

                    //Show message create shipment
                    $this->messageManager->addSuccessMessage(__("Automatically generated Shipment."));
                } catch (\Exception $e) {
                    throw new LocalizedException(
                        __($e->getMessage())
                    );
                }
            }
            // Check option createinvoice
            $this->checkOptions($storeId, $order);
        }
    }

    /**
     * Check options for creating invoice
     *
     * @param int $storeId
     * @param Order $order
     */
    protected function checkOptions($storeId, $order)
    {
        if ($this->helper->getCreatInvoice($storeId) && $order->canInvoice()) {
            try {
                $invoice = $this->invoiceService->prepareInvoice($order);
                $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
                $invoice->register();
                $invoice->getOrder()->setIsInProcess(true);
                $transaction = $this->transaction->create()->addObject($invoice)->addObject($invoice->getOrder());
                $transaction->save();
                $this->messageManager->addSuccessMessage(__("Automatically generated Invoice."));
            } catch (\Exception $e) {
                $order->addStatusHistoryComment('Exception message: ' . $e->getMessage(), false);
                $order->save();
            }
        }
    }

    /**
     * Set items order when send email shipping
     *
     * @param Order $order
     * @return void
     */
    public function setItemsOrder($order)
    {
        $this->searchCriteriaBuilder->addFilter(OrderItemInterface::ORDER_ID, $order->getId());
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $order->setItems($this->itemRepository->getList($searchCriteria)->getItems());
    }
}
