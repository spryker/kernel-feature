<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Zed\KernelFeature\Communication\Router;

use Generated\Shared\Transfer\SprykerFeatureCriteriaTransfer;
use Spryker\Zed\KernelFeature\Business\KernelFeatureFacadeInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

class SprykerFeatureRouter implements RouterInterface
{
    protected RequestContext $context;

    /**
     * @var array<string, array<string, mixed>>|null
     */
    protected ?array $routeCache = null;

    public function __construct(
        protected KernelFeatureFacadeInterface $kernelFeatureFacade,
    ) {
        $this->context = new RequestContext();
    }

    /**
     * @param \Symfony\Component\Routing\RequestContext $context
     *
     * @return void
     */
    public function setContext(RequestContext $context): void
    {
        $this->context = $context;
    }

    /**
     * @return \Symfony\Component\Routing\RequestContext
     */
    public function getContext(): RequestContext
    {
        return $this->context;
    }

    /**
     * @return \Symfony\Component\Routing\RouteCollection
     */
    public function getRouteCollection(): RouteCollection
    {
        $routeCollection = new RouteCollection();
        $routes = $this->getRoutes();

        foreach ($routes as $routeName => $routeData) {
            $route = new Route(
                $routeData['path'],
                [
                    '_controller' => $routeData['controller'],
                    'feature' => $routeData['feature'],
                    'entity' => $routeData['entity'],
                ],
            );
            $routeCollection->add($routeName, $route);
        }

        return $routeCollection;
    }

    /**
     * @param string $name
     * @param array<string, mixed> $parameters
     * @param int $referenceType
     *
     * @throws \Symfony\Component\Routing\Exception\RouteNotFoundException
     *
     * @return string
     */
    public function generate(string $name, array $parameters = [], int $referenceType = self::ABSOLUTE_PATH): string
    {
        $routes = $this->getRoutes();

        if (!isset($routes[$name])) {
            throw new RouteNotFoundException(sprintf('Route "%s" not found.', $name));
        }

        return $routes[$name]['path'];
    }

    /**
     * @param string $pathinfo
     *
     * @throws \Symfony\Component\Routing\Exception\ResourceNotFoundException
     *
     * @return array<string, mixed>
     */
    public function match(string $pathinfo): array
    {
        $routes = $this->getRoutes();

        foreach ($routes as $routeName => $routeData) {
            if ($this->matchPath($pathinfo, $routeData['path'])) {
                return [
                    '_controller' => $routeData['controller'],
                    '_route' => $routeName,
                    'feature' => $routeData['feature'],
                    'entity' => $routeData['entity'],
                    // ACL attributes - required for AccessControlEventDispatcherPlugin
                    'module' => $this->toKebabCase($routeData['feature']),
                    'controller' => $this->toKebabCase($routeData['entity']),
                    'action' => 'index',
                ];
            }
        }

        throw new ResourceNotFoundException(sprintf('No routes found for "%s".', $pathinfo));
    }

    /**
     * @param string $pathinfo
     * @param string $routePath
     *
     * @return bool
     */
    protected function matchPath(string $pathinfo, string $routePath): bool
    {
        $pathinfo = rtrim($pathinfo, '/');
        $routePath = rtrim($routePath, '/');

        return $pathinfo === $routePath;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function getRoutes(): array
    {
        if ($this->routeCache !== null) {
            return $this->routeCache;
        }

        $this->routeCache = [];
        $criteriaTransfer = new SprykerFeatureCriteriaTransfer();
        $featureCollection = $this->kernelFeatureFacade->getFeatureCollection($criteriaTransfer);

        foreach ($featureCollection->getSprykerFeatures() as $feature) {
            /** @var string $featureName */
            $featureName = $feature->getSprykerFeatureName();
            $featurePath = $this->toKebabCase($featureName);
            $entities = $feature->getConfiguration()['entities'] ?? [];

            foreach ($entities as $entityName => $entityConfig) {
                $entityPath = $this->toKebabCase($entityName);
                $routePath = '/' . $featurePath . '/' . $entityPath;
                $routeName = $featurePath . ':' . $entityPath . ':index';

                $this->routeCache[$routeName] = [
                    'path' => $routePath,
                    'controller' => '/falcon-ui/feature/index',
                    'feature' => $featureName,
                    'entity' => $entityName,
                ];
            }
        }

        return $this->routeCache;
    }

    /**
     * @param string $string
     *
     * @return string
     */
    protected function toKebabCase(string $string): string
    {
        return strtolower((string)preg_replace('/([a-z0-9])([A-Z])/', '$1-$2', $string));
    }
}
