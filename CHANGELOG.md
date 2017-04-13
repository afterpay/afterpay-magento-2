# Afterpay magento extension changelog
Copyright (c) 2017 AfterPay (http://afterpay.com.au/)

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