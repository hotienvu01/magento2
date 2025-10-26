<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 */

namespace Magento\SalesRule\Observer;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Clear cached salesrule product attributes when rules are saved
 */
class ClearProductAttributesCacheObserver implements ObserverInterface
{
    /**
     * Cache key for active salesrule attributes
     */
    private const CACHE_KEY = 'salesrule_active_product_attributes';

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @param CacheInterface $cache
     */
    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Clear salesrule product attributes cache
     *
     * @param Observer $observer
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(Observer $observer): void
    {
        $this->cache->remove(self::CACHE_KEY);
    }
}
