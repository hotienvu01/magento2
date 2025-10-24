<?php
/**
 * Copyright 2015 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\SalesRule\Test\Unit\Model\Plugin;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Model\Quote\Config;
use Magento\SalesRule\Model\Plugin\QuoteConfigProductAttributes;
use Magento\SalesRule\Model\Quote\TotalsCollectionState;
use Magento\SalesRule\Model\ResourceModel\Rule;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class QuoteConfigProductAttributesTest extends TestCase
{
    /**
     * @var QuoteConfigProductAttributes|MockObject
     */
    protected $plugin;

    /**
     * @var Rule|MockObject
     */
    protected $ruleResource;

    /**
     * @var TotalsCollectionState|MockObject
     */
    protected $totalsCollectionState;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->ruleResource = $this->createMock(Rule::class);
        $this->totalsCollectionState = $this->createMock(TotalsCollectionState::class);

        $this->plugin = $objectManager->getObject(
            QuoteConfigProductAttributes::class,
            [
                'ruleResource' => $this->ruleResource,
                'totalsCollectionState' => $this->totalsCollectionState
            ]
        );
    }

    public function testAfterGetProductAttributes()
    {
        $subject = $this->createMock(Config::class);
        $attributeCode = 'code of the attribute';
        $expected = [0 => $attributeCode];

        $this->totalsCollectionState->expects($this->once())
            ->method('isCollecting')
            ->willReturn(true);

        $this->ruleResource->expects($this->once())
            ->method('getActiveAttributes')
            ->willReturn(
                [
                    ['attribute_code' => $attributeCode, 'enabled' => true],
                ]
            );

        $this->assertEquals($expected, $this->plugin->afterGetProductAttributes($subject, []));
    }

    public function testAfterGetProductAttributesNotCollecting()
    {
        $subject = $this->createMock(Config::class);
        $inputAttributes = ['existing_attribute'];

        $this->totalsCollectionState->expects($this->once())
            ->method('isCollecting')
            ->willReturn(false);

        $this->ruleResource->expects($this->never())
            ->method('getActiveAttributes');

        $this->assertEquals($inputAttributes, $this->plugin->afterGetProductAttributes($subject, $inputAttributes));
    }
}
