# Mageaustralia_LegacyPayments

[![CI](https://github.com/mageaustralia/maho-module-legacy-payments/actions/workflows/ci.yml/badge.svg)](https://github.com/mageaustralia/maho-module-legacy-payments/actions/workflows/ci.yml)
[![License: OSL-3.0](https://img.shields.io/badge/license-OSL--3.0-blue.svg)](LICENSE)

Catch-all payment-method stub for [Maho](https://mahocommerce.com) (OpenMage / Magento-1 lineage). When a store's order history contains payment codes from modules that are no longer installed (Braintree, legacy eWAY, Afterpay iFrame, M2E Pro, Zip, Amazon Pay, etc.), `Mage_Payment_Helper_Data::getMethodInstance()` throws "Unknown payment method" and breaks order imports, order-detail views, and DataSync replication. This module resolves every such code to a lightweight, no-op stub so historical orders validate, render, and report correctly - without making any of them selectable at checkout.

## How it works

Two layers work together:

1. **Explicit stubs** - `etc/config.xml` registers a curated list of known legacy codes (see below), each pointing to the shared `Stub` model. Every stub carries a friendly title (e.g. "Braintree Credit Card (legacy)") so the order-detail page shows something meaningful.

2. **Catch-all rewrite** - A rewrite of `Mage_Payment_Helper_Data::getMethodInstance()` catches any code that still has no registered model after the config merge and synthesises a `Stub` instance on the fly. This means a single missed code in the config list can no longer break an import batch.

Stubs are read-only by design. Every `_can*` flag is `false`, and `isAvailable()` always returns `false`. No new order can ever select a stub - existing historical orders simply keep their original payment code for reporting and audit purposes.

## Pre-registered legacy codes

| Code | Title |
|------|-------|
| `paypal_express` | PayPal Express (legacy) |
| `MultiplePayment` | Multiple Payment (legacy) |
| `gene_braintree_creditcard` | Braintree Credit Card (legacy) |
| `gene_braintree_applepay` | Braintree Apple Pay (legacy) |
| `gene_braintree_paypal` | Braintree PayPal (legacy) |
| `iframe` | iFrame Hosted Payment (legacy) |
| `ewayau_direct` | eWAY Direct (legacy) |
| `ewayrapid_notsaved` | eWAY Rapid (legacy) |
| `afterpaypayovertime` | Afterpay Pay Over Time (legacy) |
| `catchfeederpayment` | Catch Feeder Payment (legacy) |
| `ebay` | eBay Marketplace (legacy) |
| `zipmoneypayment` | Zip Money (legacy) |
| `zip_payment` | Zip Payment (legacy) |
| `amazon` | Amazon Pay (legacy) |
| `ugiftcert` | Gift Certificate (legacy) |
| `purchaseorder` | Purchase Order (legacy) |
| `m2epropayment` | M2E Pro Marketplace (legacy) |
| `polipay_payment` | POLi Pay (legacy) |
| `ig_cashondelivery` | Cash on Delivery (legacy) |

To register an additional code, add a block to the `<default><payment>` section of `etc/config.xml`:

```xml
<my_old_method_code>
    <active>0</active>
    <model>mageaustralia_legacypayments/stub</model>
    <title>My Old Method (legacy)</title>
</my_old_method_code>
```

The catch-all rewrite handles any code not in the list, so adding explicit entries is optional - it only affects the title shown on order pages.

## Requirements

- Maho 26.3+ (`mahocommerce/maho ^26.3`)
- PHP 8.3+

## Installation

```bash
composer require mageaustralia/maho-module-legacy-payments
composer dump-autoload -o
php maho cache:flush
```

`composer dump-autoload -o` regenerates the optimized classmap that Maho uses; it is required after installing any Maho module.

No database migrations are included - this module adds no tables.

## Configuration

No admin configuration is needed. The module is active as soon as it is installed and the cache is flushed.

If you need to verify it is loaded, check **System > Configuration > Advanced > Advanced > Disable Modules Output** - `Mageaustralia_LegacyPayments` should appear in the list.

To inspect which payment codes are currently stubbed, review `app/code/community/Mageaustralia/LegacyPayments/etc/config.xml`.

## Related modules

This module is primarily used alongside **mageaustralia/maho-module-datasync**, which replicates orders from a live OpenMage / Magento store. Without this stub layer, any order whose payment module has been removed from the destination store will fail to import.

## License

[Open Software License 3.0 (OSL-3.0)](LICENSE)
