# Afterpay GraphQl api functional tests

## Preconditions
Your phpunit_graphql.xml needs to contain your shop URL:

```xml
<!-- Webserver URL -->
<const name="TESTS_BASE_URL" value="https://shop-with-afterpay.com"/>
```

## Run
```
vendor/bin/phpunit -c dev/tests/api-functional/phpunit_graphql.xml app/code/Afterpay/Afterpay/Test/ApiFunctional/
```

Please also refer to the documentation:
 
https://devdocs.magento.com/guides/v2.4/graphql/functional-testing.html
