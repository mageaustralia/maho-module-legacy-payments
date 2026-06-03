<?php

declare(strict_types=1);

/**
 * Mageaustralia_LegacyPayments
 *
 * @copyright  Copyright (c) 2026 Mage Australia (https://mageaustralia.com.au)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Renders a stubbed legacy method's synced additional_information in the admin
 * order view. The stub has no live gateway, but the historical payment data is
 * on file (payer ids, transaction ids, gateway response codes) and worth
 * showing for support and audit.
 */
class Mageaustralia_LegacyPayments_Block_Info extends Mage_Payment_Block_Info
{
    #[\Override]
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('mageaustralia/legacypayments/info.phtml');
    }
}
