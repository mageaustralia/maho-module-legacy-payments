<?php

declare(strict_types=1);

/**
 * Mageaustralia_LegacyPayments
 *
 * @copyright  Copyright (c) 2026 Mage Australia (https://mageaustralia.com.au)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Stub payment method for historical orders whose payment module is no longer
 * installed.
 *
 * When DataSync replicates orders from a live OpenMage / Magento install whose
 * payment ecosystem we've since stripped (Braintree, Afterpay legacy iframe,
 * eWAY, M2E Pro, etc.), Mage_Payment_Helper_Data::getMethodInstance() needs
 * the method code to resolve to *some* class. Without a stub it throws
 * "Unknown payment method" and the import row aborts.
 *
 * This class is the "do nothing, but exist" placeholder. Each legacy method
 * code is registered against this class via etc/config.xml. Every "can use"
 * flag is false so checkout never offers it - the order keeps its original
 * method on file (useful for reporting + audit trail) but no NEW order can
 * pick it up.
 */
class Mageaustralia_LegacyPayments_Model_Stub extends Mage_Payment_Model_Method_Abstract
{
    /**
     * Disabled everywhere - no checkout, no admin order create, no multishipping,
     * no recurring profile, no API. Stubs are read-only by definition.
     */
    protected $_isInitializeNeeded      = false;
    protected $_canAuthorize            = false;
    protected $_canCapture              = false;
    protected $_canCapturePartial       = false;
    protected $_canRefund               = false;
    protected $_canRefundInvoicePartial = false;
    protected $_canVoid                 = false;
    protected $_canUseInternal          = false;
    protected $_canUseCheckout          = false;
    protected $_canUseForMultishipping  = false;
    protected $_isGateway               = false;
    protected $_canFetchTransactionInfo = false;
    protected $_canCreateBillingAgreement = false;
    protected $_canReviewPayment        = false;

    /**
     * Stock payment methods declare `protected $_code = 'methodname'` at class
     * level, but the stub serves many codes from one class. Derive the active
     * code at runtime: prefer whatever the InfoInstance (set when an
     * Order_Payment binds to the method) reports, falling back to a sentinel
     * so callers that hit us without context don't blow up.
     */
    #[\Override]
    public function getCode()
    {
        if (!empty($this->_code)) {
            return $this->_code;
        }
        try {
            $info = $this->getInfoInstance();
            if ($info && ($code = $info->getMethod())) {
                return (string) $code;
            }
        } catch (\Throwable) {
            // Fall through.
        }
        return 'legacy_payment_method';
    }

    /**
     * Setter used by the helper rewrite when synthesising a stub for an
     * unknown method code. Writes through to the protected $_code property so
     * the standard getCode()/getConfigData() lookup chain finds it.
     */
    public function setCode(string $code): self
    {
        $this->_code = $code;
        return $this;
    }

    #[\Override]
    public function isAvailable($quote = null)
    {
        // Belt-and-braces. The _can* flags above already prevent checkout
        // selection, but isAvailable() is the canonical gate Maho's payment
        // selector consults. A stub is never available for a new order.
        return false;
    }

    #[\Override]
    public function getTitle()
    {
        // Title comes from each per-method <title> node in config.xml so the
        // order detail page reads "Paid via PayPal Express (legacy)" rather
        // than a generic stub label. Guarded with try/catch because parent::
        // getCode() throws when no InfoInstance is attached (e.g. DataSync's
        // bare validation call to Mage::helper('payment')->getMethodInstance()).
        try {
            $title = (string) $this->getConfigData('title');
            if ($title !== '') {
                return $title;
            }
            return (string) $this->getCode();
        } catch (\Throwable) {
            return 'Legacy payment method';
        }
    }
}
