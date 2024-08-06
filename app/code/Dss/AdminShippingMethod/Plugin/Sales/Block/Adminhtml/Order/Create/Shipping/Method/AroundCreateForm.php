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

namespace Dss\AdminShippingMethod\Plugin\Sales\Block\Adminhtml\Order\Create\Shipping\Method;

use Magento\Sales\Block\Adminhtml\Order\Create\Shipping\Method\Form;
use Dss\AdminShippingMethod\Helper\Data;

class AroundCreateForm
{
    /**
     * AfterCreateForm constructor.
     *
     * @param Data $helper
     */
    public function __construct(
        protected Data $helper
    ) {
    }

    /**
     * Around plugin for isMethodActive method.
     *
     * @param Form $subject
     * @param callable $proceed
     * @param string $code
     * @return bool
     */
    public function aroundIsMethodActive(Form $subject, callable $proceed, $code)
    {
        $storeId = $subject->getAddress()->getQuote()->getStoreId();
        $selectStore = $this->helper->getPreSelect($storeId);
        $getActive = $subject->getActiveMethodRate();
        if (!$getActive) {
            if ($selectStore) {
                if ($code == "adminshippingmethod_adminshippingmethod") {
                    return true;
                }
            }
        }
        return $proceed($code);
    }
}
