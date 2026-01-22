<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerTest\Zed\KernelFeature\Business\Transformer;

use Codeception\Test\Unit;
use Spryker\Zed\KernelFeature\Business\Transformer\SprykerFeatureEntityConfigTransformer;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group KernelFeature
 * @group Business
 * @group Transformer
 * @group SprykerFeatureEntityConfigTransformerTest
 * Add your own group annotations below this line
 */
class SprykerFeatureEntityConfigTransformerTest extends Unit
{
    /**
     * @return void
     */
    public function testTransformTransformsDslWithComponentsAndLayoutIntoViewRoot(): void
    {
        // Arrange
        $dsl = [
            'entity' => 'Order',
            'view' => [
                'layout' => [
                    'use' => [
                        'layout.order.page',
                    ],
                ],
                'components' => [
                    'layout.order.page' => [
                        'id' => 'order-page-layout',
                        'virtualRoute' => 'root',
                        'component' => 'LayoutComponent',
                        'slots' => [
                            'content' => [
                                [
                                    'use' => 'table.order.list',
                                ],
                            ],
                        ],
                    ],
                    'table.order.list' => [
                        'component' => 'TableComponent',
                        'inputs' => [
                            'config' => [
                                'dataSource' => [
                                    'type' => 'http',
                                    'url' => '/api-platform/orders',
                                ],
                                'columns' => [
                                    ['id' => 'orderReference', 'title' => 'Order Ref'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $transformer = new SprykerFeatureEntityConfigTransformer();

        // Act
        $result = $transformer->transform($dsl);

        // Assert
        $this->assertArrayHasKey('view', $result);
        $this->assertArrayHasKey('root', $result['view']);

        $root = $result['view']['root'];
        $this->assertIsArray($root);
        $this->assertSame('order-page-layout', $root['id'] ?? null);
        $this->assertSame('LayoutComponent', $root['component'] ?? null);

        $this->assertArrayHasKey('slots', $root);
        $this->assertIsArray($root['slots']);
        $this->assertNotEmpty($root['slots']);

        $contentSlot = null;
        foreach ($root['slots'] as $slotItem) {
            if (!is_array($slotItem)) {
                continue;
            }

            if (($slotItem['slot'] ?? null) === 'content') {
                $contentSlot = $slotItem;

                break;
            }
        }

        $this->assertNotNull($contentSlot, 'Expected a slot item with slot "content".');
        $this->assertSame('TableComponent', $contentSlot['component'] ?? null);

        $this->assertArrayNotHasKey('components', $result['view']);
        $this->assertArrayNotHasKey('layout', $result['view']);
    }

    /**
     * @return void
     */
    public function testTransformReturnsDslUnchangedWhenViewIsMissing(): void
    {
        // Arrange
        $dsl = [
            'entity' => 'Order',
        ];

        $transformer = new SprykerFeatureEntityConfigTransformer();

        // Act
        $result = $transformer->transform($dsl);

        // Assert
        $this->assertSame($dsl, $result);
    }

    /**
     * @return void
     */
    public function testTransformReturnsDslUnchangedWhenViewAlreadyHasRoot(): void
    {
        // Arrange
        $dsl = [
            'entity' => 'Order',
            'view' => [
                'root' => [
                    'component' => 'LayoutComponent',
                ],
            ],
        ];

        $transformer = new SprykerFeatureEntityConfigTransformer();

        // Act
        $result = $transformer->transform($dsl);

        // Assert
        $this->assertSame($dsl, $result);
    }

    /**
     * @return void
     */
    public function testTransformBuildsMultiRouteConfigurationWhenMultipleLayoutsAreProvided(): void
    {
        // Arrange
        $dsl = [
            'entity' => 'Order',
            'view' => [
                'layout' => [
                    'use' => [
                        'layout.order.page',
                        'layout.order.details.page',
                    ],
                ],
                'components' => [
                    'layout.order.page' => [
                        'id' => 'order-page-layout',
                        'virtualRoute' => 'root',
                        'component' => 'LayoutComponent',
                    ],
                    'layout.order.details.page' => [
                        'id' => 'order-details-layout',
                        'virtualRoute' => 'order-details',
                        'component' => 'LayoutComponent',
                    ],
                ],
            ],
        ];

        $transformer = new SprykerFeatureEntityConfigTransformer();

        // Act
        $result = $transformer->transform($dsl);

        // Assert
        $this->assertArrayHasKey('view', $result);
        $this->assertArrayHasKey('root', $result['view']);
        $this->assertArrayHasKey('order-details', $result['view']);

        $rootNode = $result['view']['root'];
        $detailsNode = $result['view']['order-details'];

        $this->assertSame('order-page-layout', $rootNode['id'] ?? null);
        $this->assertSame('order-details-layout', $detailsNode['id'] ?? null);

        $this->assertArrayNotHasKey('components', $result['view']);
        $this->assertArrayNotHasKey('layout', $result['view']);
    }

    /**
     * @return void
     */
    public function testTransformUsesRawConfigurationWhenPresent(): void
    {
        // Arrange
        $rawConfig = [
            'component' => 'TableComponent',
            'inputs' => [
                'config' => [
                    'dataSource' => [
                        'type' => 'http',
                        'url' => '/api-platform/orders',
                    ],
                ],
            ],
        ];

        $dsl = [
            'entity' => 'Order',
            'view' => [
                'layout' => [
                    'use' => [
                        'layout.order.page',
                    ],
                ],
                'components' => [
                    'layout.order.page' => [
                        'raw' => $rawConfig,
                    ],
                ],
            ],
        ];

        $transformer = new SprykerFeatureEntityConfigTransformer();

        // Act
        $result = $transformer->transform($dsl);

        // Assert
        $this->assertArrayHasKey('view', $result);
        $this->assertArrayHasKey('root', $result['view']);

        $rootNode = $result['view']['root'];
        $this->assertSame($rawConfig, $rootNode);

        $this->assertArrayNotHasKey('components', $result['view']);
        $this->assertArrayNotHasKey('layout', $result['view']);
    }
}
