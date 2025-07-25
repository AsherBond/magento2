<?php
/**
 * Copyright 2017 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Analytics\ReportXml\DB\Assembler;

use Magento\Analytics\ReportXml\DB\SelectBuilder;

/**
 * Interface AssemblerInterface
 *
 * Introduces family of SQL assemblers
 * Each assembler populates SelectBuilder with config information
 * @see usage examples at \Magento\Analytics\ReportXml\QueryFactory
 *
 * @api
 */
interface AssemblerInterface
{
    /**
     * Assemble SQL statement
     *
     * @param SelectBuilder $selectBuilder
     * @param array $queryConfig
     * @return SelectBuilder
     */
    public function assemble(SelectBuilder $selectBuilder, $queryConfig);
}
