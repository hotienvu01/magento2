<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 */

namespace Magento\SalesRule\Model\Plugin;

use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\TotalsCollector;
use Magento\SalesRule\Model\Quote\TotalsCollectionState;

/**
 * Plugin to track when quote totals collection is in progress
 */
class TotalsCollectorPlugin
{
    /**
     * @var TotalsCollectionState
     */
    private $totalsCollectionState;

    /**
     * @param TotalsCollectionState $totalsCollectionState
     */
    public function __construct(TotalsCollectionState $totalsCollectionState)
    {
        $this->totalsCollectionState = $totalsCollectionState;
    }

    /**
     * Set flag before totals collection starts and ensure items are reloaded
     *
     * Forces quote items collection to be reloaded to ensure salesrule attributes
     * are included when totals collection requires them.
     *
     * @param TotalsCollector $subject
     * @param Quote $quote
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeCollect(TotalsCollector $subject, Quote $quote): array
    {
        $this->totalsCollectionState->setIsCollecting(true);
        
        // Force reload of items collection to ensure salesrule attributes are loaded
        if ($quote->hasItemsCollection()) {
            $quote->unsetData('items_collection');
        }

        return [$quote];
    }

    /**
     * Unset flag after totals collection completes
     *
     * @param TotalsCollector $subject
     * @param Total $result
     * @return Total
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterCollect(TotalsCollector $subject, Total $result): Total
    {
        $this->totalsCollectionState->setIsCollecting(false);
        return $result;
    }
}
