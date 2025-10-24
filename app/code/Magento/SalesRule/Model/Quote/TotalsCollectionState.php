<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 */

namespace Magento\SalesRule\Model\Quote;

/**
 * Tracks whether quote totals collection is currently in progress
 *
 * This class is used to optimize performance by only loading salesrule product attributes
 * when they're actually needed during totals collection, rather than on every quote load.
 */
class TotalsCollectionState
{
    /**
     * @var bool
     */
    private $isCollecting = false;

    /**
     * Set totals collection state
     *
     * @param bool $state
     * @return void
     */
    public function setIsCollecting(bool $state): void
    {
        $this->isCollecting = $state;
    }

    /**
     * Check if totals collection is in progress
     *
     * @return bool
     */
    public function isCollecting(): bool
    {
        return $this->isCollecting;
    }
}
