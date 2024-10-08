<?php declare(strict_types=1);
/**
 * test Magento\Customer\Model\Metadata\Form\Boolean
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Customer\Test\Unit\Model\Metadata\Form;

use Magento\Customer\Model\Metadata\Form\Boolean;

class BooleanTest extends AbstractFormTestCase
{
    /**
     * @param mixed $value to assign to boolean
     * @param mixed $expected text output
     * @dataProvider getOptionTextDataProvider
     */
    public function testGetOptionText($value, $expected)
    {
        // calling outputValue() will cause the protected method getOptionText() to be called
        $boolean = new Boolean(
            $this->localeMock,
            $this->loggerMock,
            $this->attributeMetadataMock,
            $this->localeResolverMock,
            $value,
            0
        );
        $this->assertSame($expected, (string)$boolean->outputValue());
    }

    /**
     * @return array
     */
    public static function getOptionTextDataProvider()
    {
        return [
            '0' => ['0', 'No'],
            '1' => ['1', 'Yes'],
            'int 5' => [5, ''],
            'Null' => [null, ''],
            'Invalid' => ['Invalid', ''],
            'Empty string' => ['', '']
        ];
    }
}
