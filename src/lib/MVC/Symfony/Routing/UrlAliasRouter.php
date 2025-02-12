<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Core\MVC\Symfony\Routing;

use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\Repository\URLAliasService;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Contracts\Core\Repository\Values\Content\URLAlias;
use Ibexa\Core\MVC\Symfony\Routing\Generator\UrlAliasGenerator;
use Ibexa\Core\MVC\Symfony\View\Manager as ViewManager;
use InvalidArgumentException;
use LogicException;
use Psr\Log\LoggerInterface;
use Symfony\Cmf\Component\Routing\ChainedRouterInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route as SymfonyRoute;
use Symfony\Component\Routing\RouteCollection;

class UrlAliasRouter implements ChainedRouterInterface, RequestMatcherInterface
{
    public const URL_ALIAS_ROUTE_NAME = 'ibexa.url.alias';

    public const VIEW_ACTION = 'ibexa_content:viewAction';

    /** @var \Symfony\Component\Routing\RequestContext */
    protected $requestContext;

    /** @var \Ibexa\Contracts\Core\Repository\LocationService */
    protected $locationService;

    /** @var \Ibexa\Contracts\Core\Repository\URLAliasService */
    protected $urlAliasService;

    /** @var \Ibexa\Contracts\Core\Repository\ContentService */
    protected $contentService;

    /** @var \Ibexa\Core\MVC\Symfony\Routing\Generator\UrlAliasGenerator */
    protected $generator;

    /**
     * Holds current root Location id.
     *
     * @var int|string
     */
    protected $rootLocationId;

    /** @var \Psr\Log\LoggerInterface */
    protected $logger;

    public function __construct(
        LocationService $locationService,
        URLAliasService $urlAliasService,
        ContentService $contentService,
        UrlAliasGenerator $generator,
        RequestContext $requestContext,
        LoggerInterface $logger = null
    ) {
        $this->locationService = $locationService;
        $this->urlAliasService = $urlAliasService;
        $this->contentService = $contentService;
        $this->generator = $generator;
        $this->requestContext = $requestContext !== null ? $requestContext : new RequestContext();
        $this->logger = $logger;
    }

    /**
     * Injects current root Location id.
     *
     * @param int|string $rootLocationId
     */
    public function setRootLocationId($rootLocationId)
    {
        $this->rootLocationId = $rootLocationId;
    }

    /**
     * Tries to match a request with a set of routes.
     *
     * If the matcher can not find information, it must throw one of the exceptions documented
     * below.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request The request to match
     *
     * @return array An array of parameters
     *
     * @throws \Symfony\Component\Routing\Exception\ResourceNotFoundException If no matching resource could be found
     */
    public function matchRequest(Request $request)
    {
        try {
            $requestedPath = $request->attributes->get('semanticPathinfo', $request->getPathInfo());
            $urlAlias = $this->getUrlAlias($requestedPath);
            if ($this->rootLocationId === null) {
                $pathPrefix = '/';
            } else {
                $pathPrefix = $this->generator->getPathPrefixByRootLocationId($this->rootLocationId);
            }

            $params = [
                '_route' => self::URL_ALIAS_ROUTE_NAME,
            ];
            switch ($urlAlias->type) {
                case URLAlias::LOCATION:
                    $location = $this->generator->loadLocation($urlAlias->destination);
                    $params += [
                        '_controller' => static::VIEW_ACTION,
                        'contentId' => $location->contentId,
                        'locationId' => $urlAlias->destination,
                        'viewType' => ViewManager::VIEW_TYPE_FULL,
                        'layout' => true,
                    ];

                    // For Location alias setup 301 redirect to Location's current URL when:
                    // 1. alias is history
                    // 2. alias is custom with forward flag true
                    // 3. requested URL is not case-sensitive equal with the one loaded
                    if ($urlAlias->isHistory === true || ($urlAlias->isCustom === true && $urlAlias->forward === true)) {
                        $params += [
                            'semanticPathinfo' => $this->generator->generate($location, []),
                            'needsRedirect' => true,
                            // Specify not to prepend siteaccess while redirecting when applicable since it would be already present (see UrlAliasGenerator::doGenerate())
                            'prependSiteaccessOnRedirect' => false,
                        ];
                    } elseif ($this->needsCaseRedirect($urlAlias, $requestedPath, $pathPrefix)) {
                        $params += [
                            'semanticPathinfo' => $this->removePathPrefix($urlAlias->path, $pathPrefix),
                            'needsRedirect' => true,
                        ];

                        if ($urlAlias->destination instanceof Location) {
                            $params += ['locationId' => $urlAlias->destination->id];
                        }
                    }

                    if (isset($this->logger)) {
                        $this->logger->info("UrlAlias matched location #{$urlAlias->destination}. Forwarding to ViewController");
                    }

                    break;

                case URLAlias::RESOURCE:
                    // In URLAlias terms, "forward" means "redirect".
                    if ($urlAlias->forward) {
                        $params += [
                            'semanticPathinfo' => '/' . trim($urlAlias->destination, '/'),
                            'needsRedirect' => true,
                        ];
                    } elseif ($this->needsCaseRedirect($urlAlias, $requestedPath, $pathPrefix)) {
                        // Handle case-correction redirect
                        $params += [
                            'semanticPathinfo' => $this->removePathPrefix($urlAlias->path, $pathPrefix),
                            'needsRedirect' => true,
                        ];
                    } else {
                        $params += [
                            'semanticPathinfo' => '/' . trim($urlAlias->destination, '/'),
                            'needsForward' => true,
                        ];
                    }

                    break;

                case URLAlias::VIRTUAL:
                    // Handle case-correction redirect
                    if ($this->needsCaseRedirect($urlAlias, $requestedPath, $pathPrefix)) {
                        $params += [
                            'semanticPathinfo' => $this->removePathPrefix($urlAlias->path, $pathPrefix),
                            'needsRedirect' => true,
                        ];
                    } else {
                        // Virtual aliases should load the Content at homepage URL
                        $params += [
                            'semanticPathinfo' => '/',
                            'needsForward' => true,
                        ];
                    }

                    break;
            }

            return $params;
        } catch (NotFoundException $e) {
            throw new ResourceNotFoundException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Removes prefix from path.
     *
     * Checks for presence of $prefix and removes it from $path if found.
     *
     * @param string $path
     * @param string $prefix
     *
     * @return string
     */
    protected function removePathPrefix($path, $prefix)
    {
        if ($prefix !== '/' && mb_stripos($path, $prefix) === 0) {
            $path = mb_substr($path, mb_strlen($prefix));
        }

        return $path;
    }

    /**
     * Returns true of false on comparing $urlAlias->path and $path with case sensitivity.
     *
     * Used to determine if redirect is needed because requested path is case-different
     * from the stored one.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\URLAlias $loadedUrlAlias
     * @param string $requestedPath
     * @param string $pathPrefix
     *
     * @return bool
     */
    protected function needsCaseRedirect(URLAlias $loadedUrlAlias, $requestedPath, $pathPrefix)
    {
        // If requested path is excluded from tree root jail, compare it to loaded UrlAlias directly.
        if ($this->generator->isUriPrefixExcluded($requestedPath)) {
            return strcmp($loadedUrlAlias->path, $requestedPath) !== 0;
        }

        // Compare loaded UrlAlias with requested path, prefixed with configured path prefix.
        return
            strcmp(
                $loadedUrlAlias->path,
                $pathPrefix . ($pathPrefix === '/' ? trim($requestedPath, '/') : rtrim($requestedPath, '/'))
            ) !== 0
        ;
    }

    /**
     * Returns the UrlAlias object to use, starting from the request.
     *
     * @param $pathinfo
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException if the path does not exist or is not valid for the given language
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\URLAlias
     */
    protected function getUrlAlias($pathinfo)
    {
        return $this->urlAliasService->lookup($pathinfo);
    }

    /**
     * Gets the RouteCollection instance associated with this Router.
     *
     * @return \Symfony\Component\Routing\RouteCollection A RouteCollection instance
     */
    public function getRouteCollection()
    {
        return new RouteCollection();
    }

    /**
     * Generates a URL for a location, from the given parameters.
     *
     * It is possible to directly pass a Location object as the route name, as the ChainRouter allows it through ChainedRouterInterface.
     *
     * If $name is a route name, the "location" key in $parameters must be set to a valid {@see \Ibexa\Contracts\Core\Repository\Values\Content\Location} object.
     * "locationId" can also be provided.
     *
     * If the generator is not able to generate the url, it must throw the RouteNotFoundException
     * as documented below.
     *
     * @see UrlAliasRouter::supports()
     *
     * @param string $name The name of the route or a Location instance
     * @param array $parameters An array of parameters
     * @param int $referenceType The type of reference to be generated (one of the constants)
     *
     * @throws \LogicException
     * @throws \Symfony\Component\Routing\Exception\RouteNotFoundException
     * @throws \InvalidArgumentException
     *
     * @return string The generated URL
     *
     * @api
     */
    public function generate(string $name, array $parameters = [], int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string
    {
        if ($name === '' &&
            array_key_exists(RouteObjectInterface::ROUTE_OBJECT, $parameters) &&
            $this->supportsObject($parameters[RouteObjectInterface::ROUTE_OBJECT])
        ) {
            $location = $parameters[RouteObjectInterface::ROUTE_OBJECT];
            unset($parameters[RouteObjectInterface::ROUTE_OBJECT]);

            return $this->generator->generate($location, $parameters, $referenceType);
        }

        // Normal route name
        if ($name === self::URL_ALIAS_ROUTE_NAME) {
            if (isset($parameters['location']) || isset($parameters['locationId'])) {
                // Check if location is a valid Location object
                if (isset($parameters['location']) && !$parameters['location'] instanceof Location) {
                    throw new LogicException(
                        "When generating a UrlAlias route, the 'location' parameter must be a valid " . Location::class . '.'
                    );
                }

                $location = isset($parameters['location']) ? $parameters['location'] : $this->locationService->loadLocation($parameters['locationId']);
                unset($parameters['location'], $parameters['locationId'], $parameters['viewType'], $parameters['layout']);

                return $this->generator->generate($location, $parameters, $referenceType);
            }

            if (isset($parameters['contentId'])) {
                $contentInfo = $this->contentService->loadContentInfo($parameters['contentId']);
                unset($parameters['contentId'], $parameters['viewType'], $parameters['layout']);

                if (empty($contentInfo->mainLocationId)) {
                    throw new LogicException('Cannot generate a UrlAlias route for content without main Location.');
                }

                return $this->generator->generate(
                    $this->locationService->loadLocation($contentInfo->mainLocationId),
                    $parameters,
                    $referenceType
                );
            }

            throw new InvalidArgumentException(
                "When generating a UrlAlias route, either 'location', 'locationId', or 'contentId' must be provided."
            );
        }

        throw new RouteNotFoundException('Could not match route');
    }

    public function setContext(RequestContext $context)
    {
        $this->requestContext = $context;
        $this->generator->setRequestContext($context);
    }

    public function getContext()
    {
        return $this->requestContext;
    }

    /**
     * Not supported. Please use matchRequest() instead.
     *
     * @param $pathinfo
     *
     * @throws \RuntimeException
     */
    public function match($pathinfo)
    {
        throw new \RuntimeException("The UrlAliasRouter doesn't support the match() method. Use matchRequest() instead.");
    }

    /**
     * Whether the router supports the thing in $name to generate a route.
     *
     * This check does not need to look if the specific instance can be
     * resolved to a route, only whether the router can generate routes from
     * objects of this class.
     *
     * @param mixed $name The route name or route object
     *
     * @return bool
     */
    public function supports($name)
    {
        return $name === self::URL_ALIAS_ROUTE_NAME || $this->supportsObject($name);
    }

    private function supportsObject($object): bool
    {
        return $object instanceof Location;
    }

    /**
     * @see \Symfony\Cmf\Component\Routing\VersatileGeneratorInterface::getRouteDebugMessage()
     */
    public function getRouteDebugMessage($name, array $parameters = [])
    {
        if ($name instanceof RouteObjectInterface) {
            return 'Route with key ' . $name->getRouteKey();
        }

        if ($name instanceof SymfonyRoute) {
            return 'Route with pattern ' . $name->getPath();
        }

        return $name;
    }
}

class_alias(UrlAliasRouter::class, 'eZ\Publish\Core\MVC\Symfony\Routing\UrlAliasRouter');
