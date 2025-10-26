<?php
/**
 * Copyright 2015 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\SalesRule\Test\Unit\Model\Plugin;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Model\Quote\Config;
use Magento\SalesRule\Model\Plugin\QuoteConfigProductAttributes;
use Magento\SalesRule\Model\ResourceModel\Rule;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class QuoteConfigProductAttributesTest extends TestCase
{
    /**
     * @var QuoteConfigProductAttributes
     */
    protected $plugin;

    /**
     * @var Rule|MockObject
     */
    protected $ruleResource;

    /**
     * @var CacheInterface|MockObject
     */
    protected $cache;

    /**
     * @var SerializerInterface|MockObject
     */
    protected $serializer;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->ruleResource = $this->createMock(Rule::class);
        $this->cache = $this->createMock(CacheInterface::class);
        $this->serializer = $this->createMock(SerializerInterface::class);

        $this->plugin = $objectManager->getObject(
            QuoteConfigProductAttributes::class,
            [
                'ruleResource' => $this->ruleResource,
                'cache' => $this->cache,
                'serializer' => $this->serializer
            ]
        );
    }

    public function testAfterGetProductAttributesWithCache()
    {
        $subject = $this->createMock(Config::class);
        $attributeCode = 'code of the attribute';
        $expected = [0 => $attributeCode];
        $serializedData = '["' . $attributeCode . '"]';

        $this->cache->expects($this->once())
            ->method('load')
            ->with('salesrule_active_product_attributes')
            ->willReturn($serializedData);

        $this->serializer->expects($this->once())
            ->method('unserialize')
            ->with($serializedData)
            ->willReturn([$attributeCode]);

        $this->ruleResource->expects($this->never())
            ->method('getActiveAttributes');

        $this->assertEquals($expected, $this->plugin->afterGetProductAttributes($subject, []));
    }

    public function testAfterGetProductAttributesWithoutCache()
    {
        $subject = $this->createMock(Config::class);
        $attributeCode = 'code of the attribute';
        $expected = [0 => $attributeCode];
        $serializedData = '["' . $attributeCode . '"]';

        $this->cache->expects($this->once())
            ->method('load')
            ->with('salesrule_active_product_attributes')
            ->willReturn(false);

        $this->ruleResource->expects($this->once())
            ->method('getActiveAttributes')
            ->willReturn(
                [
                    ['attribute_code' => $attributeCode, 'enabled' => true],
                ]
            );

        $this->serializer->expects($this->once())
            ->method('serialize')
            ->with([$attributeCode])
            ->willReturn($serializedData);

        $this->cache->expects($this->once())
            ->method('save')
            ->with($serializedData, 'salesrule_active_product_attributes', ['salesrule']);

        $this->assertEquals($expected, $this->plugin->afterGetProductAttributes($subject, []));
    }
}
