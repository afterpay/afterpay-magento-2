<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Test\Unit\Service\Payment\Auth;

use Afterpay\Afterpay\Model\Order\Payment\Auth\ExpiryDate;

class ExpiryDateTest extends \PHPUnit\Framework\TestCase
{
    protected ExpiryDate $expiryDate;

    /** @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface|mixed|\PHPUnit\Framework\MockObject\MockObject */
    protected $timezone;

    protected function setUp(): void
    {
        $this->timezone = $this->getMockBuilder(\Magento\Framework\Stdlib\DateTime\TimezoneInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->expiryDate = new ExpiryDate($this->timezone);
    }

    /**
     * @dataProvider datesProvider
     */
    public function testIsExpired(string $expireDate, ?string $dateToCheck = null, bool $result)
    {
        if (!$dateToCheck) {
            $this->timezone->expects($this->once())->method("date")->willReturn(new \DateTime());
        }
        $this->assertEquals($this->expiryDate->isExpired($expireDate, $dateToCheck), $result);
    }

    public function datesProvider(): array
    {
        return [
            ["2021-08-10 09:31 CDT", "2021-08-10 09:31 CDT", false],
            ["2021-08-10 09:31 CDT", "2021-08-10 09:32 CDT", true],
            ["2021-08-10 09:31 CDT", "2024-11-11 09:32 CDT", true],
            ["2021-08-10 09:31 CDT", "2024-01-11 09:32 CDT", true],
            ["2021-07-05 09:31 CDT", "2020-01-11 09:32 CDT", false],
            [(new \DateTime())->format('Y-m-d H:i T'), null, false],
            [(new \DateTime())->modify('-1 minute')->format(ExpiryDate::FORMAT), null, true],
            [(new \DateTime())->modify('-1 days')->format(ExpiryDate::FORMAT), null, true],
            [(new \DateTime())->modify('-1 week')->format(ExpiryDate::FORMAT), null, true],
            [(new \DateTime())->modify('-1 year +2 days -3 minutes')->format(ExpiryDate::FORMAT), null, true],
            [(new \DateTime())->modify('+1 year +2 days -3 minutes')->format(ExpiryDate::FORMAT), null, false],
            [(new \DateTime())->modify('+1 day')->format(ExpiryDate::FORMAT), null, false],
        ];
    }
}
