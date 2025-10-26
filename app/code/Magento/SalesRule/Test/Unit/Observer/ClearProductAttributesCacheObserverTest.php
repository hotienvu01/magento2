<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\SalesRule\Test\Unit\Observer;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\Event\Observer;
use Magento\SalesRule\Observer\ClearProductAttributesCacheObserver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ClearProductAttributesCacheObserverTest extends TestCase
{
    /**
     * @var ClearProductAttributesCacheObserver
     */
    private $observer;

    /**
     * @var CacheInterface|MockObject
     */
    private $cache;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(CacheInterface::class);
        $this->observer = new ClearProductAttributesCacheObserver($this->cache);
    }

    public function testExecuteClearsCache()
    {
        $event = $this->createMock(Observer::class);

        $this->cache->expects($this->once())
            ->method('remove')
            ->with('salesrule_active_product_attributes');

        $this->observer->execute($event);
    }
}
