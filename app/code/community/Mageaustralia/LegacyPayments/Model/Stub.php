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
 * This class is the "do nothing, but exist" placeholder. The helper rewrite
 * synthesises an instance of this class dynamically for any unknown payment
 * code - no config.xml registrations are required. Every "can use" flag is
 * false so checkout never offers it - the order keeps its original method on
 * file (useful for reporting + audit trail) but no NEW order can pick it up.
 *
 * Friendly display titles for well-known historical codes are stored in
 * LEGACY_TITLES below. Unknown codes receive a humanized "(legacy)" label
 * derived from the raw code string.
 */
class Mageaustralia_LegacyPayments_Model_Stub extends Mage_Payment_Model_Method_Abstract
{
    /**
     * Friendly titles for well-known historical payment codes.
     *
     * These titles were previously stored as <title> nodes in etc/config.xml
     * alongside per-code model registrations. They now live here so the config
     * layer carries no payment stubs (eliminating the config-merge footgun where
     * a stub registration could silently win over a real payment module).
     *
     * Codes not in this map receive a humanized fallback: underscores/hyphens
     * are replaced with spaces, each word is title-cased, and " (legacy)" is
     * appended - e.g. "some_obscure_method" => "Some Obscure Method (legacy)".
     *
     * @var array<string, string>
     */
    private const LEGACY_TITLES = [
        'paypal_express'           => 'PayPal Express (legacy)',
        'MultiplePayment'          => 'Multiple Payment (legacy)',
        'gene_braintree_creditcard' => 'Braintree Credit Card (legacy)',
        'gene_braintree_applepay'  => 'Braintree Apple Pay (legacy)',
        'gene_braintree_paypal'    => 'Braintree PayPal (legacy)',
        'iframe'                   => 'iFrame Hosted Payment (legacy)',
        'ewayau_direct'            => 'eWAY Direct (legacy)',
        'ewayrapid_notsaved'       => 'eWAY Rapid (legacy)',
        'afterpaypayovertime'      => 'Afterpay Pay Over Time (legacy)',
        'catchfeederpayment'       => 'Catch Feeder Payment (legacy)',
        'ebay'                     => 'eBay Marketplace (legacy)',
        'zipmoneypayment'          => 'Zip Money (legacy)',
        'zip_payment'              => 'Zip Payment (legacy)',
        'amazon'                   => 'Amazon Pay (legacy)',
        'ugiftcert'                => 'Gift Certificate (legacy)',
        'purchaseorder'            => 'Purchase Order (legacy)',
        'm2epropayment'            => 'M2E Pro Marketplace (legacy)',
        'polipay_payment'          => 'POLi Pay (legacy)',
        'ig_cashondelivery'        => 'Cash on Delivery (legacy)',
    ];

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
    protected bool $_canUseForMultishipping = false;
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
        // Titles are no longer stored in config.xml. The LEGACY_TITLES map
        // covers well-known historical codes; everything else gets a humanized
        // fallback derived from the raw code string.
        // Guarded with try/catch because getCode() can throw when no
        // InfoInstance is attached (e.g. DataSync's bare validation call to
        // Mage::helper('payment')->getMethodInstance()).
        try {
            $code = $this->getCode();
            if (isset(self::LEGACY_TITLES[$code])) {
                return self::LEGACY_TITLES[$code];
            }
            // Humanize: replace _ and - with spaces, title-case, append "(legacy)".
            $words = preg_replace('/[_\-]+/', ' ', $code) ?? $code;
            return ucwords($words) . ' (legacy)';
        } catch (\Throwable) {
            return 'Legacy payment method';
        }
    }
}
