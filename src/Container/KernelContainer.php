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

use Fratily\Container\Builder\AbstractContainer;
use Fratily\Container\Builder\ContainerBuilder;

class KernelContainer extends AbstractContainer{

    /**
     * {@inheritdoc}
     */
    public static function build(ContainerBuilder $builder, array $options){
        $builder
            ->add(
                "kernel",
                $options["kernel"],
                [],
                [
                    \Fratily\Kernel\Kernel::class,
                ]
            )
            ->add(
                "kernel.container",
                $builder->lazyGetContainer(),
                [],
                [
                    \Fratily\Container\Container::class,
                    \Psr\Container\ContainerInterface::class,
                ]
            )
            ->add(
                "kernel.routeCollector",
                $builder->lazyCallable(
                    function($kernel){
                        $kernel->getRouteCollector();
                    },
                    $builder->lazyGet("kernel")
                ),
                [],
                [
                    \Fratily\Router\RouteCollector::class,
                ]
            )
            ->add(
                "kernel.consoleApplication",
                $builder->lazyCallable(
                    function($kernel){
                        $kernel->getConsoleApplication();
                    },
                    $builder->lazyGet("kernel")
                ),
                [],
                [
                    \Fratily\Router\RouteCollector::class,
                ]
            )
            ->add(
                "kernel.responseFactory",
                $builder->lazyNew(
                    \Fratily\Http\Message\ResponseFactory::class
                ),
                [],
                [
                    \Psr\Http\Message\ResponseFactoryInterface::class,
                ]
            )
            ->addShareValue(
                "kernel.environment",
                $options["kernel"]->getConfig()->getEnvironment()
            )
            ->addShareValue(
                "kernel.debug",
                $options["kernel"]->getConfig()->isDebug()
            )
        ;
    }
}