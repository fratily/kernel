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
namespace Fratily\Kernel;

use Fratily\Container\Container;
use Fratily\Container\ContainerFactory;
use Fratily\Router\RouteCollector;
use Fratily\Http\Server\RequestHandlerBuilder;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;


/**
 *
 */
class BootedKernel{

    /**
     * @var KernelConfigure
     */
    private $config;

    /**
     * @var Bundle\BundleInterface[]
     */
    private $bundles;

    /**
     * @var Container|null
     */
    private $container;

    /**
     * @var RouteCollector|null
     */
    private $routeCollector;

    /**
     * @var RequestHandlerBuilder|null
     */
    private $requestHandlerBuilder;

    /**
     * バンドルクラス名の配列からバンドルインスタンスの配列を生成する
     *
     * @param   KernelConfigure $config
     *  カーネル設定クラスインスタンス
     * @param   string  $bundles
     *  バンドルクラス名の配列
     *
     * @return  Bundle\BundleInterface[]
     *
     * @throws  Exception\KernelBootException
     */
    protected static function createBundles(
        KernelConfigure $config,
        array $bundles
    ){
        $result = [];

        foreach($bundles as $class){
            if(
                !is_string($class)
                || !is_subclass_of($class, Bundle\BundleInterface::class)
            ){
                throw new \InvalidArgumentException;
            }

            $bundle = new $class($config);
            $name   = $bundle->getName();

            if(array_key_exists($name, $result)){
                $_class = get_class($result[$name]);

                throw new Exception\KernelBootException(
                    "Bundle name'{$name}' conflicts with {$_class} and {$class}."
                );
            }

            $result[$name]  = $bundle;
        }

        return $result;
    }

    /**
     * サービスコンテナを生成する
     *
     * @param   KernelConfigure $config
     *  カーネル設定クラスインスタンス
     * @param   string  $bundles
     *  バンドルインスタンスの配列
     *
     * @return  ContainerInterface
     *
     * @throws  Exception\KernelBootException
     */
    protected static function createContainer(
        KernelConfigure $config,
        array $bundles
    ){
        $factory    = new ContainerFactory();

        $factory->append(Container\KernelContainer::class);

        try{
            foreach($config->getContainers() as $container){
                $factory->append($container);
            }

            foreach($bundles as $bundle){
                foreach($bundle->registerContainers() as $container){
                    $factory->append($container);
                }
            }
        }catch(\Exception $e){
            $class = isset($bundle) ? get_class($bundle) : get_class($config);

            throw new Exception\KernelBootException(
                "An error occurred in the service container definition of {$class}.",
                $e->getCode(),
                $e
            );
        }

        try{
            return $factory->create([
                "environment"   => $config->getEnvironment(),
                "debug"         => $config->isDebug(),
                "config"        => $config,
            ]);
        }catch(\Exception $e){
            throw new Exception\KernelBootException(
                "An error occurred while constructing the service container.",
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * ルートコレクターを生成する
     *
     * @param   KernelConfigure $config
     *  カーネル設定クラスインスタンス
     * @param   Container   $container
     *  サービスコンテナ
     * @param   Bundle\BundleInterface  $bundles
     *  バンドルインスタンスの配列
     *
     * @return  RouteCollector
     *
     * @throws  Exception\KernelBootException
     */
    protected static function createRouteCollector(
        KernelConfigure $config,
        ContainerInterface $container,
        array $bundles
    ){
        $routeCollector = $container->get("kernel.routeCollector");
        $resolver       = $container->get("kernel.controllerResolver");

        try{
            foreach($config->getControllers() as $controller){
                foreach($resolver->getRoutes($controller, "app", $config->getNameSpace()) as $route){
                    $routeCollector->add(
                        $route->withData(
                            ["bundle" => "app"] + $route->getData()
                        )
                    );
                }
            }

            foreach($bundles as $name => $bundle){
                foreach($bundle->registerControllers() as $controller){
                    foreach($resolver->getRoutes($controller, $bundle->getName(), $bundle->getNameSpace()) as $route){
                        $routeCollector->add(
                            $route->withData(
                                ["bundle" => $name] + $route->getData()
                            )
                        );
                    }
                }
            }
        }catch(\Exception $e){
            $class  = isset($bundle) ? get_class($bundle) : get_class($config);

            throw new Exception\KernelBootException(
                "An error occurred in the route definition of {$class}.",
                $e->getCode(),
                $e
            );
        }

        return $routeCollector;
    }

    /**
     * リクエストハンドラビルダーを生成する
     *
     * @param   KernelConfigure $config
     *  カーネル設定クラスインスタンス
     * @param   Bundle\BundleInterface  $bundles
     *  バンドルインスタンスの配列
     *
     * @return RequestHandlerBuilder
     *
     * @throws Exception\KernelBootException
     */
    protected static function createRequesthandler(
        KernelConfigure $config,
        array $bundles
    ){
        $builder    = new RequestHandlerBuilder();

        $config->middlewareRegisterForGlobal($builder);

        foreach($bundles as $bundele){
            $bundele->middlewareRegister($builder);
        }

        return $builder;
    }

    /**
     * Constructor
     *
     * @param   KernelConfigure $config
     *  カーネル設定クラスインスタンス
     * @param   string  $bundles
     *  バンドルクラスの配列
     */
    public function __construct(
        KernelConfigure $config,
        array $bundles
    ){
        $this->config                   = $config;
        $this->bundles                  = static::createBundles(
            $config,
            $bundles
        );
        $this->container                = static::createConainer(
            $config,
            $this->bundles
        );
        $this->routeCollector           = static::createRouteCollector(
            $config,
            $this->container,
            $this->bundles
        );
        $this->requestHandlerBuilder    = static::createRequesthandler(
            $config,
            $this->bundles
        );

        $config->boot();

        foreach($this->bundles as $bundle){
            $bundle->boot();
        }
    }

    /**
     *
     */
    public function __destruct(){
        foreach(array_reverse($this->bundles) as $bundle){
            $bundle->shutdown();
        }

        $this->config->shutdown();
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ServerRequestInterface $request): ResponseInterface{
        $routing    = $this->routeCollector
            ->router($request->getUri()->getHost(), $request->getMethod())
            ->search($request->getUri()->getPath())
        ;
        $action     = function(){
            throw new \Fratily\Http\Message\Status\NotFound();
        };

        if($routing->found){
            $method = $routing->data["action"]["method"];
            $object = $this->container->getInstance(
                $routing->data["action"]["class"]
            );
            $action = [$object, $method];
            $bundle = "app" === $routing->data["bundle"]
                ? $this->config
                : $this->bundles[$routing->data["bundle"]]
            ;

            $bundle->middlewareRegister($this->requestHandlerBuilder);
        }

        $this->requestHandlerBuilder->append(
            new Controller\ActionMiddleware($this->container, $action, $routing)
        );

        return $this->requestHandlerBuilder
            ->create(
                $this->container->has("kernel.responseFactory")
                    ? $this->container->get("kernel.responseFactory")
                    : new \Fratily\Http\Message\ResponseFactory()
            )
            ->handle(
                $request
                    ->withAttribute("kernel.environment", $this->config->getEnvironment())
                    ->withAttribute("kernel.debug", $this->config->isDebug())
                    ->withAttribute("kernel.config", $this->config)
            )
        ;
    }
}