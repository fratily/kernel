<?php
/**
 * FratilyPHP Kernel
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.
 * Redistributions of files must retain the above copyright notice.
 *
 * @author      Kento Oka <kento-oka@kentoka.com>
 * @copyright   (c) Kento Oka
 * @license     MIT
 * @since       1.0.0
 */
namespace Fratily\Kernel\Container;

use Fratily\Container\Container;
use Fratily\Container\ContainerConfig;
use Psr\Container\ContainerInterface;

class KernelConfig extends ContainerConfig{

    public function define(Container $container){
        $container
            ->type(ContainerInterface::class, $container)
            ->type(Container::class, $container)
            ->type(
                \Fratily\Router\RouteCollector::class,
                $container->lazyGet("kernel.routeCollector")
            )
        ;

        $container
            ->set(
                "kernel.routeCollector",
                \Fratily\Router\RouteCollector::class
            )
            ->set(
                "kernel.controllerResolver",
                \Fratily\Kernel\Controller\ControllerResolver::class
            )
        ;
    }
}