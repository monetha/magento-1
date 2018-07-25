# Magento

Monetha payment gateway integration with Magento 1.9

Detailed install and configuration guide is available at https://help.monetha.io/hc/en-us/articles/360002548452-Magento-1-9-integration

Contact email for your questions: team@monetha.io

# Technical guide
1. Copy `local` folder into `app/code` (in case you have it, just put `local/Monetha` inside it).
2. Copy `template/monetha` folder into `app/design/frontend/base/default/template`.
3. Copy `Monetha_Gateway.xml` into `app/etc/modules`.
4. Flush Magento cache, go to System - Cache Management - click `Flush Magento Cache` and then `Flush Cache Storage`
5. Configure merchant key, merchant secret, and payment method title in `System - Configuration - Sales - Payment Methods - Monetha Gateway`

In order to to try our integration in test mode please make sure to check "TestMode" check mark and use merchant key and secret provided below:

**Merchant Key:** MONETHA_SANDBOX_KEY

**Merchant Secret:** MONETHA_SANDBOX_SECRET

When test mode is switched on all payment transactions will be made in Ropsten testnet. Make sure not to send money from Ropsten testnet wallet address.


### If you have any questions or requests, feel free to ask them via support@monetha.io