<?php
/**
 * Copyright 2015 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Catalog\Test\Unit\Model\ProductOptions\Config;

use Magento\Framework\Config\Dom\UrnResolver;
use Magento\Framework\TestFramework\Unit\Utility\XsdValidator;
use PHPUnit\Framework\TestCase;

class XsdTest extends TestCase
{
    /**
     * Path to xsd file
     * @var string
     */
    protected $_xsdSchemaPath;

    /**
     * @var XsdValidator
     */
    protected $_xsdValidator;

    protected function setUp(): void
    {
        if (!function_exists('libxml_set_external_entity_loader')) {
            $this->markTestSkipped('Skipped on HHVM. Will be fixed in MAGETWO-45033');
        }
        $urnResolver = new UrnResolver();
        $this->_xsdSchemaPath = $urnResolver->getRealPath('urn:magento:module:Magento_Catalog:etc/');
        $this->_xsdValidator = new XsdValidator();
    }

    /**
     * @param string $schemaName
     * @param string $xmlString
     * @param array $expectedError
     * @param bool $isRegex
     */
    protected function _loadDataForTest($schemaName, $xmlString, $expectedError, $isRegex = false)
    {
        $actualErrors = $this->_xsdValidator->validate($this->_xsdSchemaPath . $schemaName, $xmlString);
        $this->assertNotEmpty($actualErrors);

        foreach ($expectedError as $error) {
            if ($isRegex) {
                foreach ($actualErrors as $actualError) {
                    $this->assertMatchesRegularExpression($error, $actualError);
                }
            } else {
                $this->assertContains($error, $actualErrors);
            }
        }
    }

    /**
     * @param string $xmlString
     * @param array $expectedError
     * @param $isRegex
     * @dataProvider schemaCorrectlyIdentifiesInvalidProductOptionsDataProvider
     */
    public function testSchemaCorrectlyIdentifiesInvalidProductOptionsXml($xmlString, $expectedError, $isRegex)
    {
        $this->_loadDataForTest('product_options.xsd', $xmlString, $expectedError, $isRegex);
    }

    /**
     * @param string $xmlString
     * @param array $expectedError
     * @dataProvider schemaCorrectlyIdentifiesInvalidProductOptionsMergedXmlDataProvider
     */
    public function testSchemaCorrectlyIdentifiesInvalidProductOptionsMergedXml($xmlString, $expectedError)
    {
        $this->_loadDataForTest('product_options_merged.xsd', $xmlString, $expectedError);
    }

    /**
     * @param string $schemaName
     * @param string $validFileName
     * @dataProvider schemaCorrectlyIdentifiesValidXmlDataProvider
     */
    public function testSchemaCorrectlyIdentifiesValidXml($schemaName, $validFileName)
    {
        $xmlString = file_get_contents(__DIR__ . '/_files/' . $validFileName);
        $schemaPath = $this->_xsdSchemaPath . $schemaName;
        $actualResult = $this->_xsdValidator->validate($schemaPath, $xmlString);
        $this->assertEquals([], $actualResult);
    }

    /**
     * Data provider with valid xml array according to schema
     */
    public static function schemaCorrectlyIdentifiesValidXmlDataProvider()
    {
        return [
            'product_options' => ['product_options.xsd', 'product_options_valid.xml'],
            'product_options_merged' => ['product_options_merged.xsd', 'product_options_merged_valid.xml']
        ];
    }

    /**
     * Data provider with invalid xml array according to schema
     */
    public static function schemaCorrectlyIdentifiesInvalidProductOptionsDataProvider()
    {
        return include __DIR__ . '/_files/invalidProductOptionsXmlArray.php';
    }

    /**
     * Data provider with invalid xml array according to schema
     */
    public static function schemaCorrectlyIdentifiesInvalidProductOptionsMergedXmlDataProvider()
    {
        return include __DIR__ . '/_files/invalidProductOptionsMergedXmlArray.php';
    }
}
