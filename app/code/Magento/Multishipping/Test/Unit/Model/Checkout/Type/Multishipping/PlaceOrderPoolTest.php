<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Multishipping\Test\Unit\Model\Checkout\Type\Multishipping;

use Magento\Framework\ObjectManager\TMapFactory;
use Magento\Multishipping\Model\Checkout\Type\Multishipping\PlaceOrderInterface;
use Magento\Multishipping\Model\Checkout\Type\Multishipping\PlaceOrderPool;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests Magento\Multishipping\Model\Checkout\Type\Multishipping\PlaceOrderPool.
 */
class PlaceOrderPoolTest extends TestCase
{
    /**
     * @param string $paymentProviderCode
     * @param PlaceOrderInterface[] $placeOrderList
     * @param \Closure $expectedResult
     * @return void
     *
     * @dataProvider getDataProvider
     */
    public function testGet(string $paymentProviderCode, array $placeOrderList, $expectedResult)
    {
        $placeOrderList['payment_code'] = $placeOrderList['payment_code']($this);
        if($expectedResult != null) {
            $expectedResult = $expectedResult($this);
        }
        /** @var TMapFactory|MockObject $tMapFactory */
        $tMapFactory = $this->getMockBuilder(TMapFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $tMapFactory->method('createSharedObjectsMap')->willReturn($placeOrderList);

        $placeOrderPool = new PlaceOrderPool($tMapFactory);
        $result = $placeOrderPool->get($paymentProviderCode);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return array
     */
    public static function getDataProvider(): array
    {
        $placeOrder = static fn (self $testCase) => $testCase->getMockForAbstractClass(PlaceOrderInterface::class);
        $placeOrderList = ['payment_code' => $placeOrder];

        return [
            'code exists in pool' => ['payment_code', $placeOrderList, $placeOrder],
            'no code in pool' => ['some_code', $placeOrderList, null],
        ];
    }
}
