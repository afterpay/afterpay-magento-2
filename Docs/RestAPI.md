# Afterpay Rest API support

The Afterpay module provides the possibility to retrieve an Afterpay checkout token and use Afterpay payment method via REST API.

## Afterpay workflow

The following diagram shows the workflow for placing an order when Afterpay is the selected payment method.
![REST API sequence diagram](img/rest-sequence-diagram.png)

## Usage

Please refer to the [Magento Rest API Order processing tutorial](https://devdocs.magento.com/guides/v2.4/rest/tutorials/orders/order-intro.html) for the general approach.

To be able to pay via Afterpay in your frontend, you are required to create an Afterpay checkout. This needs to happen *after* you set all required cart data.

The endpoint to create an Afterpay checkout looks like this:

**Endpoint:** `POST <host>/rest/<store_code>/V1/afterpay/checkout`<br/>
**Headers:** `Content-Type: application/json`<br/>
**Payload:**
```
{
 "cart_id": "{ CART_ID }",
 "redirectPath":{
   "confirm_path": "some/confirm/path",
   "cancel_path": "some/cancel/path"
 }
}
```
The input is masked cart id (for guest user) or quote id (for logged-in user) and urls to return when Consumer completes the Afterpay screenflow. The successful output will look like this:

```
{
 "afterpay_token": "{ AFTERPAY_TOKEN }",
 "afterpay_expires": "2021-08-03T15:44:28.728Z",
 "afterpay_redirectCheckoutUrl": "https://portal.sandbox.afterpay.com/us/checkout/?token={ AFTERPAY_TOKEN }"
}
```

Retrieved data will be needed for two things (shold be done in following sequence):
1. The Consumer must go through the Afterpay screenflow by `afterpay_redirectCheckoutUrl` *before* order placement operation
    - If the Consumer clicks "confirm", they will be returned to the Merchant website (to `confirm_url` which was passed in `afterpay/checkout` endpoint) with the orderToken and a status of "SUCCESS".
    - If the Consumer cancels, they will be returned to the Merchant website (to `cancel_url` which was passed in `afterpay/checkout` endpoint) with the orderToken and a status of "CANCELLED".
2. The `afterpay_token` must be used when you are setting the payment method on the cart. Hence, the **payment-information** endpoint should look like this:
```
{
 "email": "***",
 "paymentMethod": {
   "method": "afterpay",
   "additional_data": {
     "afterpay_token": "{ AFTERPAY_TOKEN }" 
   }
 },
 "billing_address": { *** }
} 
```

## Error handling

Any errors on the Afterpay side will be exposed in the response, eg:

```
{
    "message": "Unable to place order: Transaction has been declined. Please try again later."
}
```

Any Magento errors will also appear in the same manner, eg:

```
{
    "message": "Could not find a cart with ID { CART_ID }"
}
```
