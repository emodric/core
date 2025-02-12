<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Bundle\Core\DependencyInjection\Configuration\SiteAccessAware;

/**
 * Interface for dynamic setting parsers.
 * A dynamic setting is a string representation of a ConfigResolver::getParameter() call.
 * It allows usage of the ConfigResolver from e.g. configuration files.
 *
 * Supported syntax for dynamic settings: $<paramName>[;<namespace>[;<scope>]]$
 *
 * The following will work :
 * $my_param$ (using default namespace, e.g. ibexa.site_access.config, with current scope).
 * $my_param;foo$ (using "foo" as namespace, in current scope).
 * $my_param;foo;some_siteaccess$ (using "foo" as namespace, forcing "some_siteaccess scope").
 *
 * $my_param$ is the equivalent of $configResolver->getParameter( 'my_param' );
 * $my_param;foo$ is the equivalent of $configResolver->getParameter( 'my_param', 'foo' );
 * $my_param;foo;some_siteaccess$ is the equivalent of $configResolver->getParameter( 'my_param', 'foo', 'some_siteaccess' );
 */
interface DynamicSettingParserInterface
{
    public const BOUNDARY_DELIMITER = '$';
    public const INNER_DELIMITER = ';';

    /**
     * Checks if $setting is considered to be dynamic.
     * i.e. if $setting follows the expected format.
     *
     * @param string $setting
     *
     * @return bool
     */
    public function isDynamicSetting($setting);

    /**
     * Parses $setting and returns a hash of corresponding arguments.
     * Returned hash will contain the following entries:.
     *
     * - "param": the parameter name (e.g. "my_param").
     * - "namespace": the namespace. Will be null if none was specified (considered default).
     * - "scope": the scope. Will be null if none was specified (considered default).
     *
     * @param string $setting
     *
     * @return array
     */
    public function parseDynamicSetting($setting);
}

class_alias(DynamicSettingParserInterface::class, 'eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\SiteAccessAware\DynamicSettingParserInterface');
