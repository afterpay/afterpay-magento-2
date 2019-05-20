# Magento2_Afterpay
Afterpay Payment Method for Magento 2

## New Afterpay Installation
This section outlines the steps to install the Afterpay plugin for the first time.

Note: [MAGENTO] refers to the root folder where Magento is installed.

- Download the Magento-Afterpay plugin - Available as a .zip or tar.gz file from the Afterpay GitHub directory. 
- Unzip the file
- Copy the *'Afterpay'* folder to: *[MAGENTO]/app/code/* 
- Open Command Line Interface
- In CLI, run the below command to enable Afterpay module: *php bin/magento module:enable Afterpay_Afterpay*
- In CLI, run the Magento setup upgrade: *php bin/magento setup:upgrade*
- In CLI, run the Magento Dependencies Injection Compile: *php bin/magento setup:di:compile*
- In CLI, run the Magento Static Content deployment: *php bin/magento setup:static-content:deploy*
- Login to Magento Admin and navigate to System/Cache Management
- Flush the cache storage by selecting Flush Cache Storage


## Afterpay Merchant Setup
Complete the below steps to configure the merchant's Afterpay Merchant Credentials in Magento Admin.
Note: Prerequisite for this section is to obtain an Afterpay Merchant ID and Secret Key from Afterpay.

- Navigate to *Magento Admin/Stores/Configuration/Sales/Payment Methods/Afterpay*
- Enter the *Merchant ID* and *Merchant Key*.
- Enable Afterpay plugin using the *Enabled* checkbox.
- Configure the Afterpay API Mode (*Sandbox Mode* for testing on a staging instance and *Production Mode* for a live website and legitimate transactions).
- Save the configuration.
- Click the *Update Limits* button to retrieve the Minimum and Maximum Afterpay Order values.

## Upgrade Of Afterpay Installation
This section outlines the steps to upgrade the currently installed Afterpay plugin version.
The process of upgrading the Afterpay plugin version involves the complete removal of Afterpay plugin files.
Note: [MAGENTO] refers to the root folder where Magento is installed.

- Remove "Afterpay" folder in: *[MAGENTO]/app/code/*
- Download the Magento-Afterpay plugin - Available as a .zip or tar.gz file from the Afterpay GitHub directory.
- Unzip the file 
- Copy the *'Afterpay'* folder to: *[MAGENTO]/app/code/*
- Open Command Line Interface
- In CLI, run the below command to enable Afterpay module: *php bin/magento module:enable Afterpay_Afterpay*
- In CLI, run the Magento setup upgrade: *php bin/magento setup:upgrade*
- In CLI, run the Magento Dependencies Injection Compile: *php bin/magento setup:di:compile*
- In CLI, run the Magento Static Content deployment: *php bin/magento setup:static-content:deploy*
- Login to Magento Admin and navigate to System/Cache Management
- Flush the cache storage by selecting Flush Cache Storage
