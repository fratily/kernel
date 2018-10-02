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
use Fratily\Container\Builder\ContainerBuilderInterface;

class KernelContainer extends AbstractContainer{

    /**
     * {@inheritdoc}
     */
    public static function build(ContainerBuilderInterface $builder, array $options){
        $builder
            ->add(
                "kernel.routeCollector",
                \Fratily\Router\RouteCollector::class,
                [],
                [\Fratily\Router\RouteCollector::class]
            )
            ->add(
                "kernel.controllerResolver",
                \Fratily\Kernel\Controller\ControllerResolver::class
            )
            ->add(
                "kernel.responceFactory",
                \Fratily\Http\Message\ResponseFactory::class,
                [],
                [
                    \Psr\Http\Message\ResponseFactoryInterface::class,
                ]
            )
        ;
    }
}