# Mageaustralia_LegacyPayments

[![CI](https://github.com/mageaustralia/maho-module-legacy-payments/actions/workflows/ci.yml/badge.svg)](https://github.com/mageaustralia/maho-module-legacy-payments/actions/workflows/ci.yml)
[![License: OSL-3.0](https://img.shields.io/badge/license-OSL--3.0-blue.svg)](LICENSE)

Catch-all payment-method stub for [Maho](https://mahocommerce.com) (OpenMage / Magento-1 lineage). When a store's order history contains payment codes from modules that are no longer installed (Braintree, legacy eWAY, Afterpay iFrame, M2E Pro, Zip, Amazon Pay, etc.), `Mage_Payment_Helper_Data::getMethodInstance()` throws "Unknown payment method" and breaks order imports, order-detail views, and DataSync replication. This module resolves every such code to a lightweight, no-op stub so historical orders validate, render, and report correctly - without making any of them selectable at checkout.

## How it works

A single mechanism handles everything:

**Dynamic catch-all rewrite** - The module rewrites `Mage_Payment_Helper_Data::getMethodInstance()`. The rewrite calls `parent::getMethodInstance()` first, so any real registered payment module is returned untouched. For any code that has no registered model the rewrite synthesises a `Stub` instance on the fly, stamping the original code onto it. No config stubs are pre-registered; every unknown code is resolved dynamically.

Stubs are read-only by design. Every `_can*` flag is `false`, and `isAvailable()` always returns `false`. No new order can ever select a stub - existing historical orders simply keep their original payment code for reporting and audit purposes.

## Display titles

`Model/Stub.php` contains a built-in `LEGACY_TITLES` map of friendly display titles for well-known historical codes. These titles appear on order-detail pages so the payment column reads something meaningful rather than a raw code string.

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

Any code not in the map receives a humanized fallback: underscores and hyphens are replaced with spaces, each word is title-cased, and " (legacy)" is appended - e.g. `some_obscure_method` renders as "Some Obscure Method (legacy)".

To add a friendly title for an additional historical code, add an entry to the `LEGACY_TITLES` constant in `Model/Stub.php`. No config.xml changes are needed - the dynamic rewrite already handles the method resolution.

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

## Related modules

This module is primarily used alongside **mageaustralia/maho-module-datasync**, which replicates orders from a live OpenMage / Magento store. Without this stub layer, any order whose payment module has been removed from the destination store will fail to import.

## License

[Open Software License 3.0 (OSL-3.0)](LICENSE)
