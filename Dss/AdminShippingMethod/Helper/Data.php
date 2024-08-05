<?php
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
declare(strict_types=1);

namespace Dss\AdminShippingMethod\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    /**
     * Data constructor.
     *
     * @param Context $context
     * @param \Magento\Sales\Api\Data\OrderInterface $orderInterface
     */
    public function __construct(
        Context $context,
        protected OrderInterface $orderInterface
    ) {
        parent::__construct($context);
    }

    /**
     * Get order function
     *
     * @return \Magento\Sales\Api\Data\OrderInterface
     */
    public function getOrder(): OrderInterface
    {
        return $this->orderInterface;
    }

    /**
     * Check if pre-select is enabled for the store.
     *
     * @param int $storeId
     * @return bool
     */
    public function getPreSelect($storeId): bool
    {
        return $this->scopeConfig->isSetFlag(
            'carriers/adminshippingmethod/pre_select',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if invoice creation is enabled for the store.
     *
     * @param int $storeId
     * @return bool
     */
    public function getCreatInvoice($storeId): bool
    {
        return $this->scopeConfig->isSetFlag(
            'carriers/adminshippingmethod/createinvoice',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if shipment creation is enabled for the store.
     *
     * @param int $storeId
     * @return bool
     */
    public function getCreatShipment($storeId): bool
    {
        return $this->scopeConfig->isSetFlag(
            'carriers/adminshippingmethod/createshipment',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get the name of the shipping method for the store.
     *
     * @param int $storeId
     * @return string
     */
    public function getName($storeId): string
    {
        return $this->scopeConfig->getValue(
            'carriers/adminshippingmethod/name',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get the title of the shipping method for the store.
     *
     * @param int $storeId
     * @return string
     */
    public function getTitle($storeId): string
    {
        return $this->scopeConfig->getValue(
            'carriers/adminshippingmethod/title',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get the error message for the store.
     *
     * @param int $storeId
     * @return string
     */
    public function getError($storeId): string
    {
        return $this->scopeConfig->getValue(
            'carriers/adminshippingmethod/specificerrmsg',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
