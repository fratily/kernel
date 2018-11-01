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
use Symfony\Component\Console\Application;

/**
 *
 */
class Kernel{

    /**
     * @var KernelConfiguration
     */
    private $config;

    /**
     * @var Bundle\Bundle[]
     */
    private $bundles;

    /**
     * @var null|Container
     */
    private $container;

    /**
     * @var null|RouteCollector
     */
    private $routeCollector;

    /**
     * @var null|RequestHandlerBuilder
     */
    private $requestHandlerBuilder;

    /**
     * @var null|Application
     */
    private $application;

    /**
     * Constructor
     *
     * @param   KernelConfiguration $config
     *  カーネル設定クラスインスタンス
     */
    public function __construct(KernelConfiguration $config){
        $this->config   = $config;
        $this->bundles  = [];

        foreach($this->config->getBundles() as $bundle){
            if(!is_string($bundle)){
                throw new \LogicException();
            }

            $bundle = "\\" === substr($bundle, 0, 1) ? substr($bundle, 1) : $bundle;

            if(array_key_exists($bundle, $this->bundles)){
                throw new \LogicException();
            }

            if(
                !class_exists($bundle)
                || !is_subclass_of($bundle, Bundle\Bundle::class)
                || !(new \ReflectionClass($bundle))->isInstantiable()
            ){
                $interface  = Bundle\Bundle::class;

                throw new \InvalidArgumentException(
                    "'{$bundle}' is not a bundle."
                    . " The bundle must be a class that implements '{$interface}'."
                );
            }

            $this->bundles[$bundle] = new $bundle($this);
        }

        $config->boot();

        foreach($this->bundles as $bundle){
            $bundle->boot($this);
        }
    }

    /**
     * Destructor
     */
    public function __destruct(){
        foreach(array_reverse($this->bundles) as $bundle){
            $bundle->shutdown();
        }

        $this->config->shutdown();
    }

    /**
     * カーネル設定クラスインスタンスを取得する
     *
     * @return  KernelConfiguration
     */
    public function getConfig(){
        return $this->config;
    }

    /**
     * バンドルのリストを取得
     *
     * @return  Bundle\Bundle[]
     *
     * @throws  Exception\KernelBootException
     */
    public function getBundles(){
        return $this->bundles;
    }

    /**
     * バンドルを取得する
     *
     * @param   string  $name
     *  バンドル名
     *
     * @return  Bundle\Bundle
     */
    public function getBundle(string $name){
        return $this->bundles[$name] ?? null;
    }

    /**
     * サービスコンテナを取得
     *
     * @return  Container
     *
     * @throws  Exception\KernelBootException
     */
    public function getContainer(){
        if(null === $this->container){
            $factory    = (new ContainerFactory())
                ->append(\Fratily\Kernel\Container\KernelContainer::class)
            ;

            foreach($this->getBundles() as $bundle){
                foreach($bundle->getContainers() as $container){
                    $factory->append($container);
                }
            }

            foreach($this->getConfig()->getContainers() as $container){
                $factory->append($container);
            }

            $this->container    = $factory->create([
                "kernel"    => $this,
            ]);
        }

        return $this->container;
    }

    /**
     * ルートコレクターを取得
     *
     * @return  RouteCollector
     *
     * @throws  Exception\KernelBootException
     */
    public function getRouteCollector(){
        if(null === $this->routeCollector){
            $this->routeCollector   = new RouteCollector();
            $resolver               = new Controller\ControllerResolver(
                $this,
                new \Doctrine\Common\Annotations\AnnotationReader(null)
            );

            foreach($this->getConfig()->getControllers() as $controller){
                foreach($resolver->getRoutes($controller) as $route){
                    $this->routeCollector->add($route);
                }
            }
        }

        return $this->routeCollector;
    }

    /**
     * リクエストハンドラのビルダーを取得
     *
     * @return  RequestHandlerBuilder
     */
    public function getRequestHandlerBuilder(){
        if(null === $this->requestHandlerBuilder){
            $this->requestHandlerBuilder    = new RequestHandlerBuilder();

            $this->getConfig()->middlewareRegister($this->requestHandlerBuilder);

            foreach($this->getBundles() as $bundele){
                $bundele->middlewareRegister(
                    $this->requestHandlerBuilder,
                    ["kernel" => $this]
                );
            }
        }

        return clone $this->requestHandlerBuilder;
    }

    /**
     * コンソールアプリケーションを取得
     *
     * @return  Application
     */
    public function getConsoleApplication(){
        if(null === $this->application){
            $this->application  = new Application();

            foreach($this->getBundles() as $bundle){
                $bundle->commandRegister(
                    $this->application,
                    ["kernel" => $this]
                );
            }

            $this->config;
        }

        return $this->application;
    }
}