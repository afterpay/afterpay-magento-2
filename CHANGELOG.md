# Afterpay Magento 2 Extension Changelog

## Version 3.3.0

_Wed 13 Jan 2021_

### Supported Editions & Versions

Tested and verified in clean installations of Magento 2:

- Magento Enterprise Edition (EE) version 2.4.1
- Magento Enterprise Edition (EE) version 2.3.5-p1 

### Highlights

- Implemented the JS Library for asset placement on PDP and Cart page.
- Added new admin options to enable/disable display of Afterpay/Clearpay assets on PDP and Cart page.
- Moved the Afterpay/Clearpay PDP assets for “Bundle” products.
- Improved Cross Border Trade (CBT) configuration to update automatically in sync with the nominated Merchant account.
- Improved support for multi-currency configurations.
- Improved compatibility with PHP 7.4.
- Improved compatibility with third-party "Fraud Prevention" modules while saving admin configuration.
- Improved visual quality of UI assets.
- Improved support for refunding unshipped items in Deferred Payment Flow.

---

## Version 3.2.0

_Wed 19 Aug 2020_

### Supported Editions & Versions

Tested and verified in clean installations of Magento 2:

- Magento Enterprise Edition (EE) version 2.3.5-p1

### Highlights

- Added live support for transactions in Canadian Dollars (CAD).
- Improved Checkout button on cart page as part of re-brand.
- Improved Payment method display on checkout page as part of re-brand.
- Added a workaround to resolve the "Email has a wrong format" error, which prevented creation of the Magento order, and sometimes resulted in an automated refund of the Afterpay payment.
- Improved compatibility with third party "custom order number" modules while saving admin configuration.
- Added plugin assets in Content Security Policy (CSP) whitelist.

---

## Version 3.1.4

_Fri 26 Jun 2020_

### Supported Editions & Versions

Tested and verified in clean installations of Magento 2:

- Magento Community Edition (CE) version 2.3.5-p1
- Magento Enterprise Edition (EE) version 2.3.5-p1

### Highlights

- Improved reliability of logo display on cart page for high resolution mobile screens.
- Improved billing and shipping address validation at checkout.
- Improved Restricted Categories feature to better support multiple store views.
- Further improved compatibility with offline refunds.
- Added preliminary Sandbox support for transactions in CAD.
- Added a fix to prevent duplicate order emails.

---

## Version 3.1.3

_Wed 20 May 2020_

### Supported Editions & Versions

Tested and verified in clean installations of Magento 2:

- Magento Community Edition (CE) version 2.3.5-p1
- Magento Enterprise Edition (EE) version 2.3.5-p1

### Highlights

- Added instalment calculations within cart page assets.
- Added an automatic void/refund of an Afterpay Order where Magento throws an unrecoverable exception during submitQuote.
- Refined language presented to US consumers.
- Improved cart page logic relating to Afterpay availability, where the total exactly matches the merchant minimum.
- Improved handling of unexpected/corrupted network data.
- Improved compatibility with offline refunds.
- Adjusted the implementation of the Afterpay checkout JavaScript.

---

## Version 3.1.2

_Fri 24 Jan 2020 (AEDT)_
 
### Supported Editions & Versions

- Magento Community Edition (CE) version 2.0.2 and later
- Magento Enterprise Edition (EE) version 2.0.2 and later

### Highlights

- Improved handling of Store Credit and Gift Cards in Magento Enterprise.
- Improved handling of unusual AJAX behaviour at the checkout.
- Replaced legacy internal assets with latest artwork hosted on the Afterpay CDN.
- Added a cron job for Deferred Payment Flow to create Credit Memos in Magento if a payment Auth is allowed to expire.
- Added a new field on the Magento Invoice to show when a payment Auth will expire.

---

## Version 3.1.1

_Tue 24 Dec 2019 (AEDT)_
 
### Supported Editions & Versions

- Magento Community Edition (CE) version 2.0.2 and later
- Magento Enterprise Edition (EE) version 2.0.2 and later

### Highlights

- Added support for installation and updates via Composer.
- Improved support for running the Magento code compiler in Community Edition.

---

## Version 3.1.0

_Wed 18 Dec 2019 (AEDT)_
 
### Supported Editions & Versions

- Magento Community Edition (CE) version 2.0.2 and later
- Magento Enterprise Edition (EE) version 2.0.2 and later

### Highlights

- API upgrade from v1 to v2, including the introduction of "Deferred Payment Flow".
- Improvements to quote validation prior to payment capture.
- Improvements to the "Exclude Category" feature.

---

## Version 3.0.6

_Wed 25 Sep 2019 (AEST)_
 
### Supported Editions & Versions

- Magento Community Edition (CE) version 2.0.2 and later
- Magento Enterprise Edition (EE) version 2.0.2 and later

### Highlights

- Added a new feature to allow Afterpay to be disabled for a specified set of product categories.
- Added a new feature to allow Cross Border Trade (CBT) editions of modal assets to be presented on PDP and cart pages (for AU & NZ only).
- Improved default labelling of Afterpay at the checkout.
- Improved compatibility between Afterpay and Clearpay modules in multi-regional Magento installations.
- Improved support for Credit Memos used in conjunction with Afterpay orders in Magento Enterprise installations.
- Improved support for Product Detail Pages (PDP) where the main price element is missing the "data-price-type" attribute.
- Upgraded regional assets for modal popups.
- Removed potentially sensitive information from log files.

---

## Version 3.0.5

_04 Sep 2019 (AEST)_
 
### Supported Editions & Versions

- Magento Community Edition (CE) version 2.0.2 and later
- Magento Enterprise Edition (EE) version 2.0.2 and later

### Highlights

- Added a new feature to notify admin if an exception occurs while finalising the Magento order.
- Improved handling of SQL errors during the order finalisation process.
- Improved handling of variant selection, where the default variant was ineligible for purchase with Afterpay.
- Improved handling of virtual product orders.
- Improved handling of orders from logged-in customers where no billing address was selected.
- Improved formatting and cross-browser compatibility for PDP elements and extended support for uncommon product types.
- Improved checkout field validation.
- Improved checkout information and assets for US merchants.
- Improved logging during cron tasks.
- Extended debug logging.

---

## Version 3.0.4

_25 Jan 2019 (AEDT)_
 
### Supported Editions & Versions

- Magento Community Edition (CE) version 2.0.2 and later
- Magento Enterprise Edition (EE) version 2.0.2 and later

### Highlights

- Improved support for HTTP/2.

---

## Version 3.0.3

_28 Nov 2018 (AEDT)_
 
### Supported Editions & Versions

- Magento Community Edition (CE) version 2.1.1 and later
- Magento Enterprise Edition (EE) version 2.1.1 and later

### Highlights

- Revised Portal and API URLs for Afterpay US.
- Improved handling of front-end JS.
- Removed deprecated "Payment Display" configuration options.
- Removed deprecated cron job configuration.

---

## Version 3.0.2

_23 Oct 2018 (AEDT)_
 
### Supported Editions & Versions

- Magento Community Edition (CE) version 2.1.1 and later
- Magento Enterprise Edition (EE) version 2.1.1 and later

### Highlights

- Improved handling of unsupported store currencies.

---

## Version 3.0.1

_21 Nov 2018 (AEDT)_
 
### Supported Editions & Versions

- Magento Community Edition (CE) version 2.1.1 and later
- Magento Enterprise Edition (EE) version 2.1.1 and later

### Highlights

- Compliance with MEQP2

---

## Version 3.0.0

_Wed 10 Oct 2018 (AEDT)_
 
### Supported Editions & Versions

- Magento Community Edition (CE) version 2.1.1 and later
- Magento Enterprise Edition (EE) version 2.1.1 and later
- Afterpay-Magento2 plugin v3.0.0 has been verified against a new instance of Magento CE v2.2.3
  - https://magento.com/tech-resources/download#download2173

### Highlights

Version 3.0.0 of the Afterpay Magento 2 Extension includes:

- United States Afterpay transaction processing.
- Region specific front-end display.
- Removal of Afterpay Merchant API v0 connectivity.
 
### Community & Enterprise Edition Enhancements

**United States Afterpay transaction processing**

- Introduction of connectivity to the Afterpay Merchant US API endpoints.
- Adapted the API payload to support United States merchants and Afterpay transactions.
- Extended Currency detection to ensure USD currency is sent to Afterpay Merchant US API endpoints.
- Allows Single-Market use to Australia, New Zealand or US; and supports Multi-Market use in Australia, New Zealand & US.
 
**Region specific front-end display**

- Introduction of region specific front-end display to align with market language.
- This functionality is present on the Magento 2 product, cart and checkout pages.

**Removal of Afterpay Merchant API v0 connectivity**

- Removal of configuration method and functional to transact via Afterpay Merchant API v0.
- Version 3.0.0 will exclusively allow configuration for Afterpay Merchant API v1, ensuring a simplified transaction process.

**Miscellaneous**

- Extended support for Afterpay instalment amount updates on configurable products.
- Implemented email address validation on checkout form to remove illegal characters prior to initial Afterpay API call.
- Extended support for Afterpay instalment amount display on checkout page to display total order value, including shipping and tax.
- Updated checkout instalment calculations for total order values not evenly divisible by 4.
- Enhanced Payment Limits update function to accommodate empty/null merchant account minimum order value.
- Expanded Afterpay logging entries of refund exceptions.
- Updated PHPUnit namespace to accommodate PHPUnit 6.0.0+.

---

## Version 2.0.2

_Thu 23 Nov 2017 (AEDT)_
 
### Supported Editions & Versions

- Magento Community Edition (CE) version 2.1.1 and later
- Magento Enterprise Edition (EE) version 2.1.1 and later
- Afterpay-Magento2 plugin v2.0.2 has been verified against a new instance of Magento CE v2.2.0
  - http://devdocs.magento.com/guides/v2.2/release-notes/ReleaseNotes2.2.0EE.html
 
### Highlights

Version 2.0.2 of the Afterpay Magento 2 Extension delivers:

- Single and Multi-Market Afterpay transaction processing.
- Extended support for Magento Order Confirmation emails.
- Implemented Magento default Mini-Cart clearing.
- Magento timeout value override.
- Improved Payment Limits API call.
 
### Community & Enterprise Edition Enhancements

**Single and Multi-market Afterpay transaction processing**

- Adapted the API payload to support New Zealand merchants and Afterpay transactions.
- Utilised Magento "State Required" functionality to validate the API payloads based on country requirements.
- Implemented Website Base Currency detection to ensure correct currency is sent to Afterpay API.
- Extends Single-Market use to New Zealand and supports Multi-Market use in Australia and New Zealand.
 
**Extended support for Magento Order Confirmation emails**

- Extended support for triggering the Magento Order Confirmation email following an approved Afterpay transaction.
  - Previously Magento triggered the sending of a Order Confirmation email upon order creation.
  - The trigger was altered in Magento 2.1.4 (and subsequent versions).
  - Plugin version 2.0.2 programmatically trigger the Magento Order Confirmation email.

**Implemented Magento default Mini-Cart clearing**

- Implemented programmatic removal of the Shopping Cart (Mini-Cart) contents when Afterpay transaction is successful.
  - Previously upon a successful Afterpay transaction, the Mini-Cart presented as retaining the contents of the order despite the Shopping Cart contents being cleared.
 
**Magento timeout value override**

- Implemented override function for the Magento 2 default timeout value.
- The override function is set to 80 seconds to extend Magento's 10 default second timeout value.
- This will ensure that Magento processes responses from the Afterpay API up to 80 seconds.
 
**Improved Payment Limits API call**

- Updated the Payment Limits API call to target Afterpay API V1 Payment Limits Endpoint.
  - Previously the Payment Limits API call targeted Afterpay API V0.
- Added logging on Payment Limits query to monitor incorrect Merchant ID and Key combinations.
  - Following a Payment Limits API call, an entry is created on the afterpay.log file with the Merchant ID and masked Secret Key.
  - The log entry includes both the Payment Limits API request and response.

**Miscellaneous**

- Implemented Afterpay instalment display on the Magento checkout page.
- Added Afterpay-Magento2 plugin version display with the Payment Method configuration section with Magento Admin.
- Implemented a JavaScript activation delay on the checkout page.
  - Ensures all other checkout page AJAX requests have been completed prior to redirection to Afterpay payment gateway.
- Updated POST request to the Orders API endpoint to display item quantity and price in Afterpay Portal

---

## Version 2.0.1

_Thu 13 Apr 2017 (AEST)_
 
### Supported Editions & Versions

- Magento Community Edition (CE) version 2.1.1 and later
- Magento Enterprise Edition (EE) version 2.1.1 and later
 
### Highlights

Version 2.0.1 of the Afterpay Magento 2 Extension delivers:

- Magento 2 Multi-Website compatibility
- Improved API logging mechanism
 
### Community & Enterprise Edition Enhancements
 
**Multi-Store Compatibility**

- Introduction of Afterpay configuration saving and retrieval mechanism to utilise Magento 2 Multi-Websites functionality 
- Afterpay CRON will perform updates to Afterpay Merchant minimum/maximum limits to all configured Magento Websites.
- Afterpay "Update Payment Limits" button in Magento Admin to retrieve the details based on the associated Magento Website's Afterpay Merchant ID and Secret Key configuration
- Order refunding functionality to retrieve the associated Magento Website's Afterpay Merchant ID and Secret Key configuration.
- Afterpay front-end asset display controlled relative to the associated Magento Website's Afterpay Merchant minimum/maximum limits.
 
**Improved API Logging Mechanism**

- Enhanced User Agent detail logging.
- Additional Afterpay logging on the Transaction Integrity Checking, Payment Capture and Order Creation processes
- Additional front-end checkout validation related to Billing details required by Afterpay API.
 
**Miscellaneous**

- Enhanced Order Item quantity API call to acquire quantities from Magento Quote for display in Afterpay Merchant Portal.
- Additional Magento Admin error reporting related to invalid Merchant ID and Secret Key configuration.

---

## Version 2.0.0

_Fri 17 Feb 2017 (AEDT)_
 
### Supported Editions & Versions

- Magento Community Edition (CE) version 2.1.1 and later
- Magento Enterprise Edition (EE) version 2.1.1 and later
 
### Highlights

Version 2.0.0 of the Afterpay Magento 2 Extension delivers:

- Enhanced order and payment capture processing for Magento 2 Community Edition and Enterprise Edition via Afterpay Merchant API V1
- Verification of transaction integrity

### Community & Enterprise Edition Enhancements
 
**Order and payment capture processing**

- Access via Afterpay Merchant API V1 has been introduced with this version of the Afterpay-Magento 2 plugin.
- Following successful payment capture by Afterpay, the following records are created in Magento:
- Magento order with a status of Processing
- Magento invoice with a status of Paid
- Afterpay will no longer create Magento orders with a status of Pending, Cancelled or Failed.

**Security**

- To verify the integrity of each transaction, an additional API call has been implemented.
- This call verifies the transaction integrity by comparing the original value at time of checkout against the value present prior to payment capture, of the following:
  - Afterpay token ID
  - Magento Quote reserved order ID
  - Magento Quote total amount
- In the instance of a discrepancy between these values, the transaction is cancelled and no payment capture attempts will be made. 

**Product-level Configuration enhancements**

- Merchant's Afterpay transaction limits are now applied at the Product-level as well as at the Checkout-level.
- Magento Admin Afterpay plugin Enabled / Disabled dropdown now removes Product-level assets when set to 'Disabled'.

**Miscellaneous**

- Validation of merchant credentials (Merchant ID and Merchant Key) extended to exclude non-alphanumeric characters.

---

## Version 1.0.0

_Thu 23 Jun 2016 (AEST)_

### Highlights

- Uploaded the initial development suite
