<?php
/**
 * Copyright 2011 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\Framework\Config\Test\Unit;

use Magento\Framework\Config\Dom;
use PHPUnit\Framework\TestCase;

class XsdTest extends TestCase
{
    /**
     * @param string $xsdFile
     * @param string $invalidXmlFile
     * @param int $expectedErrorsQty
     * @dataProvider invalidXmlFileDataProvider
     * @throws \Exception
     */
    public function testInvalidXmlFile($xsdFile, $invalidXmlFile, $expectedErrorsQty)
    {
        if (!function_exists('libxml_set_external_entity_loader')) {
            $this->markTestSkipped('Skipped on HHVM. Will be fixed in MAGETWO-45033');
        }
        $dom = new \DOMDocument();
        $dom->load(__DIR__ . "/_files/{$invalidXmlFile}");
        $schema = __DIR__ . "/../../etc/{$xsdFile}";

        libxml_use_internal_errors(true);
        $result = Dom::validateDomDocument($dom, $schema);
        $errorsQty = count($result);
        libxml_use_internal_errors(false);

        if ($expectedErrorsQty > 0) {
            $this->assertNotEmpty($result);
        }
        $this->assertEquals($expectedErrorsQty, $errorsQty);
    }

    /**
     * @return array
     */
    public static function invalidXmlFileDataProvider()
    {
        return [['view.xsd', 'view_invalid.xml', 10], ['theme.xsd', 'theme_invalid.xml', 1]];
    }
}
