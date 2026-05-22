<?php

declare(strict_types=1);

/**
 * Mageaustralia_LegacyPayments
 *
 * @copyright  Copyright (c) 2026 Mage Australia (https://mageaustralia.com.au)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Rewrite of Mage_Payment_Helper_Data that turns "Unknown payment method"
 * lookups into a synthetic stub instance instead of a hard failure.
 *
 * The reason this rewrite exists at all: DataSync (and any other caller that
 * resolves payment method by historical code) needs every method observed on
 * a synced order to resolve to *some* class. Hard-coding each legacy method
 * in config.xml is brittle - a single missed code breaks the import for that
 * batch. This rewrite is the catch-all: any code with no registered model
 * gets the Mageaustralia_LegacyPayments_Model_Stub instance and a `_code`
 * stamped on it so getTitle() / getConfigData() resolve cleanly.
 *
 * The explicit per-method blocks in config.xml stay so historical-favourite
 * methods get nice titles (PayPal Express, Braintree CC, etc.); this only
 * kicks in for codes that aren't in that list.
 */
class Mageaustralia_LegacyPayments_Helper_PaymentRewrite extends Mage_Payment_Helper_Data
{
    #[\Override]
    public function getMethodInstance($code)
    {
        $instance = parent::getMethodInstance($code);

        // Parent returned false → method has no model registered. Synthesise
        // a stub from our model so the caller doesn't blow up.
        if (!$instance) {
            $instance = Mage::getModel('mageaustralia_legacypayments/stub');
        }

        // Stamp the code on our own Stub instances. Real third-party method
        // classes declare `protected $_code` statically at class level, but
        // our shared Stub serves any code and can't - so we have to write it
        // explicitly. Done for both branches: explicitly-registered legacy
        // methods (config.xml -> Stub) AND the catch-all path above.
        if ($instance instanceof Mageaustralia_LegacyPayments_Model_Stub) {
            $instance->setCode((string) $code);
            try {
                /** @phpstan-ignore-next-line method.notFound */
                $instance->setStore((int) Mage::app()->getStore()->getId());
            } catch (\Throwable) {
                // Pre-init contexts - safe to ignore.
            }
        }
        return $instance;
    }
}
