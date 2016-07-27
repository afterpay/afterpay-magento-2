<h2> 1.1 New Afterpay Installation </h2>
<p>This section outlines the steps to install the Afterpay plugin for the first time.</p>

<p> Magento can be installed in any folder on your server however for the purposes of this document, [MAGENTO] will refer to the root folder where you have installed your version of Magento. </p>

<ol>
<li> The Afterpay Magento plugin will be provided to you as a zip file or tar file </li>
<li> Unzip the file and follow the instructions to copy across the files Code Directory </li>
<li> Copy folder and all files in /Afterpay folder from the unzipped plugin into [MAGENTO]/app/code </li>
<li>
	<p>There are 2 ways to install Afterpay Plugin</p>
	<p> <strong>Alternative 1</strong> (currently unstable due to various Magento 2 bugs): </p>
	<ul>
		<li> Afterpay Plugin can be copy-pasted directly on the "app/Code" folder by creating the following structure "app/Code/Afterpay/Afterpay"</li>
		<li> Then, go to the Admin and go to "System - Web Setup Wizard - Component Manager"</li>
		<li> You will see Afterpay still disabled, you can enable it by clicking the option on the right hand side</li>
		<li> If the Readiness Check fails and it says CRON not running, go to the Magento CLI.
		 Then, run the 3 CRON operations described above (without using the time declaration, e.g "./bin/magento cron:run". Then try again.
		</li>
	</ul>

	<p><strong>Alternative 2 </strong> (more stable): </p>
	<ul>
		<li> Go to the Magento CLI and use "./bin/magento module:enable Afterpay_Afterpay" </li>
		<li> The system will most likely require us to setup upgrade afterwards: "./bin/magento setup:upgrade" </li>
	</ul>
</li>
<li> Login to Magento admin and go to System > Cache Management </li>
<li> Flush the Magento cache by selecting “Flush Magento Cache” </li>
<li> Check that the correct Afterpay version has been installed and then complete the Configuration steps outlined in this document </li>
</ol>

<hr/>

<h2> 1.2 Afterpay Configuration </h2>
<p> This configuration steps for the plugin are described in the remainder of this document. </p>

<ol>
<li> Check the version of the plugin that has been installed </li>
<li> Obtain a Merchant ID and Secret Key from Afterpay </li>
<li> Configure the Afterpay payment methods API Mode </li>
<li> Place an order on your site using the test details provided </li>
</ol>

<h2> 1.3 Upgrade Of Afterpay Installation </h2>
<p> This section outlines the steps to REMOVE the existing plugin before the upgrade.
Remove the Afterpay plugin by manually deleting the Afterpay folders on . </p>
<ol>
<li> Login to Magento admin and go to System > Cache Management </li>
<li> Flush the Magento cache by selecting "Flush Magento Cache" </li> 
<li> Check that the correct Afterpay version has been installed </li>
</ol>