=== Easy Digital Downloads - Payment Gateway - Nochex ===
Contributors: Nochex 
Tags: Easy Digital Downloads, Credit Cards, Shopping Cart, Nochex Payment Gateway, Nochex, Extension, Gateway
Requires at least: 3.3
Tested up to: 4.9.4
Stable tag: 2.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Accept all major credit cards directly on your Easy Digital Downloads site using the Nochex payment gateway.

Easy Digital Downloads Version Tested up to 2.9 and WordPress 4.9.4

== Description ==
= Nochex Online Payment Services =
Website: http://www.nochex.com

Nochex is the UK's leading independent payment service for start-up, small and medium sized online merchants. We provide a simple and straightforward payment platform that makes 
it easy for your customers to use and for you to get paid, and we back that up with a world-class fraud prevention capability on our safe, secure and reliable PCI-compliant payment pages.

= Key Features =
* Quick and seamless integration into the Easy Digital Downloads checkout page.
* Accept all major credit cards.
* Prevent Fraud - use 3D Secure, 3D Secure is a standard developed by the card schemes - Visa and MasterCard - to improve the security of Internet payments.
* Risk Management - Our gateway is PCI Level 1 compliant.
* Automatically Update Orders - Use APC (Automatic Payment Confirmation) to update orders when they have been paid for.
* Mobile Payments - Mobile friendly interface for customers.
* Customers are sent to the secure Nochex payments pages to make a payment.

== Installation ==

= Installing The Payment Gateway Plugin =

* Download the plugin zip file: https://github.com/NochexDevTeam/Easy-Digital-Downloads
* Login to your WordPress Admin. Click on Plugins | Add New from the left hand menu.
* Click on the "Upload" option, then click "Choose File" to select the zip file from your computer. Once selected, press "OK" and press the "Install Now" button.
* Activate the plugin.
* Open the settings page for Easy Digital Downloads and click the "Payment Gateways" tab.
* Click on the sub tab for "Nochex".
* Configure your Nochex Gateway settings. See below for details.

= Connect to Easy Digital Downloads =
To configure the plugin, go to **Easy Digital Downloads > Settings** from the left hand menu, then the top tab "Payment Gateways". You should see __"Nochex"__ as an option at the top of the screen. 
__*You can select Nochex from the dropdown menu to make it the default gateway, and to enable Nochex select the checkbox next to Nochex and Save Changes.*__

* ** Payment Display Name ** - allows you to determine what your customers will see this payment option as on the checkout page.  
* ** Nochex Merchant ID or Email Address ** - enter your Nochex account email address or Merchant ID. 
* ** Hide Billing Details ** - optional feature, if enabled the billing address details will be hidden when the customer is sent to Nochex.
* ** Detailed Product Information ** - optional feature, if enabled allows your product details to be displayed in a structured format on your Nochex Payment Page.
* ** Test Transaction ** - check to enable Nochex test mode, uncheck to enable LIVE transactions.   
* ** Callback ** - To use the callback functionality, please contact Nochex Support to enable this functionality on your merchant account otherwise this function wont work.
* ** Save Changes.** 

== Changelog ==

= 1.2 =

* Update *

- Bug fixes for billing address / invoice details to be shown on the Payment History details, and APC fixes.
- Extra Validation 
- Pre-fill address fields if user logged in.

= 1.1 =

* Update *

- Updated APC and Callback to ensure it is TLS 1.2 ready
- Removed References to external resources
- Updated internal references.
- Updated and Validated Input sent to Nochex, and data returned.
- Moved Nochex Settings from the Main Payments Gateway to its own tab / tag.

= 1 =

* Update *

- Updated to include new module features 
	+ Callback
	+ Detailed Product Information

- Added extra functionality to include extra comments on orders and updating orders.

= 0.1 =
* First Release.

Support
=====================
Bug fixes and feature patches may be submitted using github pull requests, and bug reports or feature requests as github issues.
Visit our Knowledgebase for support: https://support.nochex.com/ 
