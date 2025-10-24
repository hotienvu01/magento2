<?php
/**
 * Copyright 2013 Adobe
 * All Rights Reserved.
 */

namespace Magento\SalesRule\Model\Plugin;

use Magento\Quote\Model\Quote\Config;
use Magento\SalesRule\Model\ResourceModel\Rule as RuleResource;
use Magento\SalesRule\Model\Quote\TotalsCollectionState;

class QuoteConfigProductAttributes
{
    /**
     * @var RuleResource
     */
    private $ruleResource;

    /**
     * @var array|null
     */
    private $activeAttributeCodes;

    /**
     * @var TotalsCollectionState
     */
    private $totalsCollectionState;

    /**
     * @param RuleResource $ruleResource
     * @param TotalsCollectionState $totalsCollectionState
     */
    public function __construct(
        RuleResource $ruleResource,
        TotalsCollectionState $totalsCollectionState
    ) {
        $this->ruleResource = $ruleResource;
        $this->totalsCollectionState = $totalsCollectionState;
    }

    /**
     * Append sales rule product attribute keys to select by quote item collection
     *
     * Only loads attributes when totals collection is in progress to avoid unnecessary
     * database queries and EAV attribute loading on every cart view.
     *
     * @param Config $subject
     * @param array $attributeKeys
     *
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetProductAttributes(Config $subject, array $attributeKeys): array
    {
        // Only load salesrule attributes when actually collecting totals
        if (!$this->totalsCollectionState->isCollecting()) {
            return $attributeKeys;
        }

        if ($this->activeAttributeCodes === null) {
            $this->activeAttributeCodes = array_column($this->ruleResource->getActiveAttributes(), 'attribute_code');
        }

        return array_merge($attributeKeys, $this->activeAttributeCodes);
    }
}
