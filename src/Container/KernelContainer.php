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

use Fratily\Kernel\Kernel;
use Fratily\Container\Container;
use Fratily\Container\Builder\AbstractContainer;
use Fratily\Container\Builder\ContainerBuilder;
use Symfony\Component\Console\Application;

class KernelContainer extends AbstractContainer{

    const SHAREVAL_ENV              = "env";
    const SHAREVAL_DEBUG            = "debug";

    const SERVICE_KERNEL            = "kernel";
    const SERVICE_CONSOLE_APP       = "kernel.console";
    const SERVICE_ROUTE_COLLECTOR   = "kernel.routes";
    const SERVICE_RESPONSE_FACTORY  = "kernel.responseFactory";
    const SERVICE_CONTAINER         = "container";

    const TAG_CONSOLE_COMMAND       = "kernel.console.command";

    /**
     * {@inheritdoc}
     */
    public static function build(ContainerBuilder $builder, array $options){
        $kernel = $options["kernel"] ?? null;

        if(!is_object($kernel) || !$kernel instanceof Kernel){
            throw new \LogicException;
        }

        $builder
            // share value
            ->addShareValue(self::SHAREVAL_ENV, $kernel->getEnvironment())
            ->addShareValue(self::SHAREVAL_DEBUG, $kernel->isDebug())
            // service
            ->add(self::SERVICE_KERNEL, $kernel, [], [Kernel::class])
            ->add(
                self::SERVICE_CONSOLE_APP,
                $builder->lazyCallable(
                    function($commands){
                        $app    = new Application();

                        $app->addCommands($commands);

                        return $app;
                    },
                    [$builder->lazyGetTagged(self::TAG_CONSOLE_COMMAND)]
                )
            )
            ->add(
                self::SERVICE_ROUTE_COLLECTOR,
                $builder->lazyCallable(
                    function($kernel){
                        $kernel->getRouteCollector();
                    },
                    [$builder->lazyGet("kernel")]
                ),
                [],
                [
                    \Fratily\Router\RouteCollector::class,
                ]
            )
            ->add(
                self::SERVICE_RESPONSE_FACTORY,
                $builder->lazyNew(
                    \Fratily\Http\Message\ResponseFactory::class
                ),
                [],
                [
                    \Psr\Http\Message\ResponseFactoryInterface::class,
                ]
            )
            ->add(
                self::SERVICE_CONTAINER,
                $builder->lazyGetContainer(),
                [],
                [
                    \Fratily\Container\Container::class,
                    \Psr\Container\ContainerInterface::class,
                ]
            )
        ;
    }

    public static function modify(Container $container){
    }
}