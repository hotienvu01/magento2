<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\SalesRule\Test\Unit\Model\Quote;

use Magento\SalesRule\Model\Quote\TotalsCollectionState;
use PHPUnit\Framework\TestCase;

class TotalsCollectionStateTest extends TestCase
{
    /**
     * @var TotalsCollectionState
     */
    private $state;

    protected function setUp(): void
    {
        $this->state = new TotalsCollectionState();
    }

    public function testDefaultStateIsFalse()
    {
        $this->assertFalse($this->state->isCollecting());
    }

    public function testSetIsCollectingTrue()
    {
        $this->state->setIsCollecting(true);
        $this->assertTrue($this->state->isCollecting());
    }

    public function testSetIsCollectingFalse()
    {
        $this->state->setIsCollecting(true);
        $this->state->setIsCollecting(false);
        $this->assertFalse($this->state->isCollecting());
    }

    public function testMultipleToggle()
    {
        $this->assertFalse($this->state->isCollecting());
        
        $this->state->setIsCollecting(true);
        $this->assertTrue($this->state->isCollecting());
        
        $this->state->setIsCollecting(false);
        $this->assertFalse($this->state->isCollecting());
        
        $this->state->setIsCollecting(true);
        $this->assertTrue($this->state->isCollecting());
    }
}
