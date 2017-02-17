# Afterpay magento extension changelog
Copyright (c) 2016 AfterPay (http://afterpay.com.au/)


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