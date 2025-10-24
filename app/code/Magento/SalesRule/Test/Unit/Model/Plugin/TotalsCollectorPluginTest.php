<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\SalesRule\Test\Unit\Model\Plugin;

use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\TotalsCollector;
use Magento\SalesRule\Model\Plugin\TotalsCollectorPlugin;
use Magento\SalesRule\Model\Quote\TotalsCollectionState;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TotalsCollectorPluginTest extends TestCase
{
    /**
     * @var TotalsCollectorPlugin
     */
    private $plugin;

    /**
     * @var TotalsCollectionState|MockObject
     */
    private $totalsCollectionState;

    /**
     * @var TotalsCollector|MockObject
     */
    private $totalsCollector;

    /**
     * @var Quote|MockObject
     */
    private $quote;

    protected function setUp(): void
    {
        $this->totalsCollectionState = $this->createMock(TotalsCollectionState::class);
        $this->totalsCollector = $this->createMock(TotalsCollector::class);
        $this->quote = $this->createMock(Quote::class);

        $this->plugin = new TotalsCollectorPlugin($this->totalsCollectionState);
    }

    public function testBeforeCollectSetsState()
    {
        $this->totalsCollectionState->expects($this->once())
            ->method('setIsCollecting')
            ->with(true);

        $result = $this->plugin->beforeCollect($this->totalsCollector, $this->quote);

        $this->assertEquals([$this->quote], $result);
    }

    public function testAfterCollectUnsetsState()
    {
        $total = $this->createMock(Total::class);

        $this->totalsCollectionState->expects($this->once())
            ->method('setIsCollecting')
            ->with(false);

        $result = $this->plugin->afterCollect($this->totalsCollector, $total);

        $this->assertSame($total, $result);
    }
}
