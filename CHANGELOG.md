Afterpay Magento 2 extension changelog
- Release version: 2.0.2
- Release date: 23 Nov 2017
- Platform: Magento 2
 
Supported Editions and Versions:
- Magento Community Edition (CE) version 2.1.1 and later
- Magento Enterprise Edition (EE) version 2.1.1 and later
- Afterpay-Magento2 plugin v2.0.2 has been verified against a new instance of Magento CE v2.2.0
	- http://devdocs.magento.com/guides/v2.2/release-notes/ReleaseNotes2.2.0EE.html
 
Highlights
Version 2.0.2 of the Afterpay-Magento 2 plugin delivers:
- Single and Multi-Market Afterpay transaction processing.
- Extended support for Magento Order Confirmation emails.
- Implemented Magento default Mini-Cart clearing.
- Magento timeout value override.
- Improved Payment Limits API call.
 
Community & Enterprise Edition enhancements

Single and Multi-market Afterpay transaction processing
- Adapted the API payload to support New Zealand merchants and Afterpay transactions.
- Utilised Magento "State Required" functionality to validate the API payloads based on country requirements.
- Implemented Website Base Currency detection to ensure correct currency is sent to Afterpay API.
- Extends Single-Market use to New Zealand and supports Multi-Market use in Australia and New Zealand.
 
Extended support for Magento Order Confirmation emails.
- Extended support for triggering the Magento Order Confirmation email following an approved Afterpay transaction.
	- Previously Magento triggered the sending of a Order Confirmation email upon order creation.
	- The trigger was altered in Magento 2.1.4 (and subsequent versions).
	- Plugin version 2.0.2 programmatically trigger the Magento Order Confirmation email.

Implemented Magento default Mini-Cart clearing.
- Implemented programmatic removal of the Shopping Cart (Mini-Cart) contents when Afterpay transaction is successful.
	- Previously upon a successful Afterpay transaction, the Mini-Cart presented as retaining the contents of the order despite the Shopping Cart contents being cleared.
 
Magento timeout value override
- Implemented override function for the Magento 2 default timeout value.
- The override function is set to 80 seconds to extend Magento’s 10 default second timeout value.
- This will ensure that Magento processes responses from the Afterpay API up to 80 seconds.
 
Improved Payment Limits API call
- Updated the Payment Limits API call to target Afterpay API V1 Payment Limits Endpoint.
	- Previously the Payment Limits API call targeted Afterpay API V0.
- Added logging on Payment Limits query to monitor incorrect Merchant ID and Key combinations.
	- Following a Payment Limits API call, an entry is created on the afterpay.log file with the Merchant ID and masked Secret Key.
	- The log entry includes both the Payment Limits API request and response.

Miscellaneous
- Implemented Afterpay instalment display on the Magento checkout page.
- Added Afterpay-Magento2 plugin version display with the Payment Method configuration section with Magento Admin.
- Implemented a JavaScript activation delay on the checkout page.
	- Ensures all other checkout page AJAX requests have been completed prior to redirection to Afterpay payment gateway.
- Updated POST request to the Orders API endpoint to display item quantity and price in Afterpay Portal


-------------------------------------------------------------------------------------------------------------------------------

Release version: 2.0.1
Release date: 13.04.2017
 
Platform: Magento 2
Supported Editions and Versions:
-	Magento Community Edition  (CE) version 2.1.1 and later
-	Magento Enterprise Edition (EE)	version 2.1.1 and later
 
Highlights
Version 2.0.1 of the Afterpay-Magento 2 plugin delivers:
-	Magento 2 Multi-Website compatibility
-	Improved API logging mechanism
 
Community & Enterprise Edition enhancements
 
Multi-Store Compatibility
-   Introduction of Afterpay configuration saving and retrieval mechanism to utilise Magento 2 Multi-Websites functionality 
-   Afterpay CRON will perform updates to Afterpay Merchant minimum/maximum limits to all configured Magento Websites.
-   Afterpay "Update Payment Limits" button in Magento Admin to retrieve the details based on the associated Magento Website's Afterpay Merchant ID and Secret Key configuration
-   Order refunding functionality to retrieve the associated Magento Website's Afterpay Merchant ID and Secret Key configuration.
-	Afterpay front-end asset display controlled relative to the associated Magento Website's Afterpay Merchant minimum/maximum limits.
 
Improved API Logging Mechanism
-   Enhanced User Agent detail logging.
-   Additional Afterpay logging on the Transaction Integrity Checking, Payment Capture and Order Creation processes
-   Additional front-end checkout validation related to Billing details required by Afterpay API.
 
Miscellaneous
-   Enhanced Order Item quantity API call to acquire quantities from Magento Quote for display in Afterpay Merchant Portal.
-   Additional Magento Admin error reporting related to invalid Merchant ID and Secret Key configuration.

--------------------------------------------------------------------------------------------------------------------------------

Release version: 2.0.0
Release date: 17.02.2017
 
Platform: Magento 2
Supported Editions and Versions: 
-	Magento Community Edition  (CE)     version 2.1.1 and later
-	Magento Enterprise Edition (EE)		version 2.1.1 and later
 
Highlights
Version 2.0.0 of the Afterpay-Magento 2 plugin delivers:
-	Enhanced order and payment capture processing for Magento 2 Community Edition and Enterprise Edition via Afterpay Merchant API V1
-	Verification of transaction integrity

Community & Enterprise Edition enhancements
 
Order and payment capture processing
-	Access via Afterpay Merchant API V1 has been introduced with this version of the Afterpay-Magento 2 plugin.
-	Following successful payment capture by Afterpay, the following records are created in Magento:
-	Magento order with a status of Processing
-	Magento invoice with a status of Paid
-	Afterpay will no longer create Magento orders with a status of Pending, Cancelled or Failed.

Security
-	To verify the integrity of each transaction, an additional API call has been implemented.
-	This call verifies the transaction integrity by comparing the original value at time of checkout against the value present prior to payment capture, of the following:
		- 	Afterpay token ID
		- 	Magento Quote reserved order ID
		- 	Magento Quote total amount
-	In the instance of a discrepancy between these values, the transaction is cancelled and no payment capture attempts will be made. 

Product-level Configuration enhancements
-	Merchant’s Afterpay transaction limits are now applied at the Product-level as well as at the Checkout-level.
-	Magento Admin Afterpay plugin Enabled / Disabled dropdown now removes Product-level assets when set to ‘Disabled’.

Miscellaneous
-	Validation of merchant credentials (Merchant ID and Merchant Key) extended to exclude non-alphanumeric characters.


### 1.0.0 - 2016-06-23
 - Uploaded the initial development suite