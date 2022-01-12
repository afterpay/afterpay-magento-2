<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Test\Unit\Model\Log\Method;

use Magento\Payment\Model\Method\Logger;

class LoggerTest extends \PHPUnit\Framework\TestCase
{
    protected $logger;

    /** @var \Magento\Framework\Logger\Monolog|\PHPUnit\Framework\MockObject\MockObject */
    protected $monolog;

    protected function setUp(): void
    {
        $this->monolog = $this->getMockBuilder(\Magento\Framework\Logger\Monolog::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger = new \Afterpay\Afterpay\Model\Log\Method\Logger($this->monolog);
    }

    /**
     * @dataProvider dataToLogProvider
     */
    public function testDebug(array $keys, array $data, string $expectedMessage)
    {
        $this->monolog->expects($this->once())->method("debug")->with($expectedMessage);
        $this->logger->debug($data, $keys, true);
    }

    public function dataToLogProvider(): array
    {
        return [
            $this->provideSingleNestingData(),
            $this->provideNoObfuscationData(),
            $this->provideReadData(),
            $this->provideDoubleNestingData(),
            $this->provideAnotherKeysData()
        ];
    }

    private function provideSingleNestingData(): array
    {
        $keys = ["shipping","billing","consumer"];
        $data = [
            "shipping" => "obfuscate me",
            "billing" => "obfuscate me",
            "consumer" => "obfuscate me",
        ];
        $expectedMessage = var_export([
            "shipping" => Logger::DEBUG_KEYS_MASK,
            "billing" => Logger::DEBUG_KEYS_MASK,
            "consumer" => Logger::DEBUG_KEYS_MASK,
        ], true);

        return [$keys, $data, $expectedMessage];
    }

    private function provideNoObfuscationData(): array
    {
        $keys = ["shipping","billing","consumer"];
        $data = [
            "not_shipping" => "not obfuscate me",
            "not_billing" => "not obfuscate me",
            "not_consumer" => "not obfuscate me",
        ];
        $expectedMessage = var_export($data, true);

        return [$keys, $data, $expectedMessage];
    }

    private function provideDoubleNestingData(): array
    {
        $keys = ["shipping","billing","consumer"];
        $data = [
            "shipping" => [
                "billing" => [
                    "consumer" => [
                        "shipping" => "obfuscate me"
                    ],
                    "dummy" => "obfuscate me"
                ]
            ],
            "dummy" => "not obfuscate me"
        ];
        $expectedMessage = var_export([
            "shipping" => [
                "billing" => [
                    "consumer" => [
                        "shipping" =>  Logger::DEBUG_KEYS_MASK
                    ],
                    "dummy" =>  Logger::DEBUG_KEYS_MASK
                ]
            ],
            "dummy" => "not obfuscate me"
        ], true);

        return [$keys, $data, $expectedMessage];
    }

    private function provideAnotherKeysData(): array
    {
        $keys = ["name", "surname", "address"];
        $data = [
            "name" => "Bohdan",
            "surname" => "Khmelnytskyi",
            "address" => [
                "village" => "Subotiv",
                "Voivodeship" => "Kyiv"
            ],
            "items" => [
                "horse" => [
                    "qty" => "2",
                    "amount" => "4 denarius"
                ]
            ]
        ];
        $expectedMessage = var_export([
            "name" => Logger::DEBUG_KEYS_MASK,
            "surname" => Logger::DEBUG_KEYS_MASK,
            "address" => [
                "village" => Logger::DEBUG_KEYS_MASK,
                "Voivodeship" => Logger::DEBUG_KEYS_MASK,
            ],
            "items" => [
                "horse" => [
                    "qty" => "2",
                    "amount" => "4 denarius"
                ]
            ]
        ], true);

        return [$keys, $data, $expectedMessage];
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function provideReadData(): array
    {
        $keys = ["shipping","billing","consumer"];
        $data = [
            'request_body' => [
                'orderId' => '100101665793',
            ],
            'client' => 'Afterpay\\Afterpay\\Gateway\\Http\\Client\\Client',
            'response' => [
                'id' => '100101665793',
                'token' => '002.5vjpvsmrlgdfpg988fpj5qvle36m188f62qvv25qmjjobokn',
                'status' => 'APPROVED',
                'created' => '2021-07-29T15:28:08.054Z',
                'originalAmount' => [
                    'amount' => '915.00',
                    'currency' => 'USD',
                ],
                'openToCaptureAmount' => [
                    'amount' => '0.00',
                    'currency' => 'USD',
                ],
                'paymentState' => 'CAPTURED',
                'merchantReference' => '000000033',
                'refunds' => [
                    0 => [
                        'amount' => [
                            'amount' => '600.00',
                            'currency' => 'USD',
                        ],
                        'refundId' => '1830870',
                        'refundedAt' => '2021-07-29T15:32:43.758Z',
                    ],
                ],
                'orderDetails' => [
                    'consumer' => [
                        'phoneNumber' => 'obfuscate me',
                        'givenNames' => 'obfuscate me',
                        'surname' => 'obfuscate me',
                        'email' => 'obfuscate me',
                    ],
                    'billing' => [
                        'name' => 'obfuscate me',
                        'line1' => 'obfuscate me',
                        'line2' => 'obfuscate me',
                        'area1' => 'obfuscate me',
                        'region' => 'obfuscate me',
                        'postcode' => 'obfuscate me',
                        'countryCode' => 'obfuscate me',
                    ],
                    'shipping' => [
                        'name' => 'obfuscate me',
                        'line1' => 'obfuscate me',
                        'line2' => 'obfuscate me',
                        'area1' => 'obfuscate me',
                        'region' => 'obfuscate me',
                        'postcode' => 'obfuscate me',
                        'countryCode' => 'obfuscate me',
                    ],
                    'courier' => [
                    ],
                    'items' => [
                        0 => [
                            'name' => 'product',
                            'quantity' => 3,
                            'price' => [
                                'amount' => '300.00',
                                'currency' => 'USD',
                            ],
                        ],
                    ],
                    'discounts' => [
                    ],
                    'shippingAmount' => [
                        'amount' => '15.00',
                        'currency' => 'USD',
                    ],
                    'taxAmount' => [
                        'amount' => '0.00',
                        'currency' => 'USD',
                    ],
                ],
                'events' => [
                    0 => [
                        'id' => '1vzktQ3rJWlIxpt5LdUCHHF6VRN',
                        'created' => '2021-07-29T15:28:38.019Z',
                        'expires' => '2021-08-05T15:28:38.018Z',
                        'type' => 'AUTH_APPROVED',
                        'amount' => [
                            'amount' => '915.00',
                            'currency' => 'USD',
                        ],
                        'paymentEventMerchantReference' => null,
                    ],
                    1 => [
                        'id' => '1vzlJknAwuhZd9yM496qHlHVG4m',
                        'created' => '2021-07-29T15:32:08.278Z',
                        'expires' => null,
                        'type' => 'CAPTURED',
                        'amount' => [
                            'amount' => '315.00',
                            'currency' => 'USD',
                        ],
                        'paymentEventMerchantReference' => null,
                    ],
                    2 => [
                        'id' => '1vzlO8tJjHQTz7ujvMRejQrbqeh',
                        'created' => '2021-07-29T15:32:43.772Z',
                        'expires' => null,
                        'type' => 'VOIDED',
                        'amount' => [
                            'amount' => '600.00',
                            'currency' => 'USD',
                        ],
                        'paymentEventMerchantReference' => null,
                    ],
                ],
            ],
        ];
        $expectedMessage = var_export([
            'request_body' => [
                'orderId' => '100101665793',
            ],
            'client' => 'Afterpay\\Afterpay\\Gateway\\Http\\Client\\Client',
            'response' => [
                'id' => '100101665793',
                'token' => '002.5vjpvsmrlgdfpg988fpj5qvle36m188f62qvv25qmjjobokn',
                'status' => 'APPROVED',
                'created' => '2021-07-29T15:28:08.054Z',
                'originalAmount' => [
                    'amount' => '915.00',
                    'currency' => 'USD',
                ],
                'openToCaptureAmount' => [
                    'amount' => '0.00',
                    'currency' => 'USD',
                ],
                'paymentState' => 'CAPTURED',
                'merchantReference' => '000000033',
                'refunds' => [
                    0 => [
                        'amount' => [
                            'amount' => '600.00',
                            'currency' => 'USD',
                        ],
                        'refundId' => '1830870',
                        'refundedAt' => '2021-07-29T15:32:43.758Z',
                    ],
                ],
                'orderDetails' => [
                    'consumer' => [
                        'phoneNumber' =>  Logger::DEBUG_KEYS_MASK,
                        'givenNames' =>  Logger::DEBUG_KEYS_MASK,
                        'surname' =>  Logger::DEBUG_KEYS_MASK,
                        'email' =>  Logger::DEBUG_KEYS_MASK,
                    ],
                    'billing' => [
                        'name' =>  Logger::DEBUG_KEYS_MASK,
                        'line1' =>  Logger::DEBUG_KEYS_MASK,
                        'line2' =>  Logger::DEBUG_KEYS_MASK,
                        'area1' =>  Logger::DEBUG_KEYS_MASK,
                        'region' =>  Logger::DEBUG_KEYS_MASK,
                        'postcode' =>  Logger::DEBUG_KEYS_MASK,
                        'countryCode' =>  Logger::DEBUG_KEYS_MASK,
                    ],
                    'shipping' => [
                        'name' =>  Logger::DEBUG_KEYS_MASK,
                        'line1' =>  Logger::DEBUG_KEYS_MASK,
                        'line2' =>  Logger::DEBUG_KEYS_MASK,
                        'area1' =>  Logger::DEBUG_KEYS_MASK,
                        'region' =>  Logger::DEBUG_KEYS_MASK,
                        'postcode' =>  Logger::DEBUG_KEYS_MASK,
                        'countryCode' =>  Logger::DEBUG_KEYS_MASK,
                    ],
                    'courier' => [
                    ],
                    'items' => [
                        0 => [
                            'name' => 'product',
                            'quantity' => 3,
                            'price' => [
                                'amount' => '300.00',
                                'currency' => 'USD',
                            ],
                        ],
                    ],
                    'discounts' => [
                    ],
                    'shippingAmount' => [
                        'amount' => '15.00',
                        'currency' => 'USD',
                    ],
                    'taxAmount' => [
                        'amount' => '0.00',
                        'currency' => 'USD',
                    ],
                ],
                'events' => [
                    0 => [
                        'id' => '1vzktQ3rJWlIxpt5LdUCHHF6VRN',
                        'created' => '2021-07-29T15:28:38.019Z',
                        'expires' => '2021-08-05T15:28:38.018Z',
                        'type' => 'AUTH_APPROVED',
                        'amount' => [
                            'amount' => '915.00',
                            'currency' => 'USD',
                        ],
                        'paymentEventMerchantReference' => null,
                    ],
                    1 => [
                        'id' => '1vzlJknAwuhZd9yM496qHlHVG4m',
                        'created' => '2021-07-29T15:32:08.278Z',
                        'expires' => null,
                        'type' => 'CAPTURED',
                        'amount' => [
                            'amount' => '315.00',
                            'currency' => 'USD',
                        ],
                        'paymentEventMerchantReference' => null,
                    ],
                    2 => [
                        'id' => '1vzlO8tJjHQTz7ujvMRejQrbqeh',
                        'created' => '2021-07-29T15:32:43.772Z',
                        'expires' => null,
                        'type' => 'VOIDED',
                        'amount' => [
                            'amount' => '600.00',
                            'currency' => 'USD',
                        ],
                        'paymentEventMerchantReference' => null,
                    ],
                ],
            ],
        ], true);

        return [$keys, $data, $expectedMessage];
    }
}
