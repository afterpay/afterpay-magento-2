# AGENTS.md

Magento 2 Afterpay Payment Module (`Afterpay\Afterpay`) - PHP 7.4+, Magento 2.4.x.

## Build & Test Commands

Requires full Magento 2 installation. Module path: `app/code/Afterpay/Afterpay/`.

```bash
./vendor/bin/phpcs --standard=Magento2 --warning-severity=7 app/code/Afterpay/Afterpay  # Lint
./vendor/bin/phpunit -c dev/tests/unit/phpunit.xml app/code/Afterpay/Afterpay/Test/Unit  # All tests
./vendor/bin/phpunit -c dev/tests/unit/phpunit.xml app/code/Afterpay/Afterpay/Test/Unit/Path/To/Test.php  # Single test
```

## Architecture

- **Api/**: Service contracts (`CheckoutManagementInterface`) and data interfaces (`Api/Data/`)
- **Model/**: Business logic—Config, Checkout, CBT, Payment, Order, FeatureFlag services
- **Gateway/**: Payment gateway—Command/, Http/, Request/, Response/, Validator/
- **Controller/**, **Block/**, **ViewModel/**: Frontend/admin UI; **view/**: templates, layouts, JS
- **Observer/**, **Plugin/**: Event observers and Magento interceptors
- **etc/**: DI config (`di.xml`), routes, payment gateway XML, `module.xml`

## Code Style

- First line: `<?php declare(strict_types=1);` — PSR-4 namespace: `Afterpay\Afterpay\`
- Typed properties, return types, constructor DI; avoid `ObjectManager`
- Tests extend `\PHPUnit\Framework\TestCase` with `setUp()` method; use `createMock()` for mocks

## .agents/knowledge/

Store plans and working notes in `.agents/knowledge/`. Review for ongoing work context.