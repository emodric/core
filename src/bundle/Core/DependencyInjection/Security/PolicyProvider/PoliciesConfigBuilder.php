<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Bundle\Core\DependencyInjection\Security\PolicyProvider;

use Ibexa\Bundle\Core\DependencyInjection\Configuration\ContainerConfigBuilder;
use Symfony\Component\Config\Resource\ResourceInterface;

class PoliciesConfigBuilder extends ContainerConfigBuilder
{
    public function addConfig(array $config)
    {
        $previousPolicyMap = [];

        if ($this->containerBuilder->hasParameter('ibexa.api.role.policy_map')) {
            $previousPolicyMap = $this->containerBuilder->getParameter('ibexa.api.role.policy_map');
        }

        // We receive limitations as values, but we want them as keys to be used by isset().
        foreach ($config as $module => $functionArray) {
            foreach ($functionArray as $function => $limitationCollection) {
                if (null !== $limitationCollection && $this->policyExists($previousPolicyMap, $module, $function)) {
                    $limitations = array_merge_recursive($previousPolicyMap[$module][$function], array_fill_keys((array)$limitationCollection, true));
                } else {
                    $limitations = array_fill_keys((array)$limitationCollection, true);
                }

                $previousPolicyMap[$module][$function] = $limitations;
            }
        }

        $this->containerBuilder->setParameter(
            'ibexa.api.role.policy_map',
            $previousPolicyMap
        );
    }

    public function addResource(ResourceInterface $resource)
    {
        $this->containerBuilder->addResource($resource);
    }

    /**
     * Checks if policy for module and function exist in Policy Map.
     *
     * @param array $policyMap
     * @param string $module
     * @param string $function
     *
     * @return bool
     */
    private function policyExists(array $policyMap, $module, $function)
    {
        return array_key_exists($module, $policyMap) && array_key_exists($function, $policyMap[$module]);
    }
}

class_alias(PoliciesConfigBuilder::class, 'eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Security\PolicyProvider\PoliciesConfigBuilder');
