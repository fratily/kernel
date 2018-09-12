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
use Fratily\Http\Server\RequestHandler;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;


/**
 *
 */
class Kernel implements KernelInterface{

    /**
     * @var string
     */
    private $environment;

    /**
     * @var bool
     */
    private $debug;

    /**
     * @var string[]
     */
    private $bundles;

    /**
     * @var MiddlewaresInterface[]|string[]
     */
    private $middlewares;

    /**
     * @var bool
     */
    private $booted = false;

    /**
     * @var Bundle\BundleInterface[]
     */
    private $bundleInstances;

    /**
     * @var Container|null
     */
    private $container;

    /**
     * @var RouteCollector|null
     */
    private $routeCollector;

    /**
     * @var RequestHandler|null
     */
    private $requestHandler;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        string $environment,
        bool $debug,
        array $bundles = [],
        array $middlewares = []
    ){
        foreach($bundles as $bundle){
            if(!class_exists($bundle)){
                throw new \InvalidArgumentException(
                    "Class {$bundle} not found."
                );
            }

            if(!is_subclass_of($bundle, Bundle\BundleInterface::class)){
                $interface  = Bundle\BundleInterface::class;

                throw new \InvalidArgumentException(
                    "Class {$bundle} dose not implemented {$interface},"
                    . " it can not be recognized as a bundle."
                );
            }
        }

        foreach($middlewares as $middleware){
            if(
                is_object($middleware)
                && !is_subclass_of($middleware, MiddlewareInterface::class)
            ){
                throw new \InvalidArgumentException(
                    ""
                );
            }elseif(!is_string($middleware)){
                throw new \InvalidArgumentException();
            }
        }

        $this->environment  = $environment;
        $this->debug        = $debug;
        $this->bundles      = $bundles;
        $this->middlewares  = $middlewares;
    }

    /**
     * {@inheritdoc}
     */
    public function boot(){
        if($this->booted){
            return;
        }

        $this->initialBundleInstances();

        foreach($this->bundleInstances as $bundle){
            $bundle->boot();
        }

        $this->container        = $this->generateContainer();
        $this->routeCollector   = $this->generateRouteCollector();
        $this->requestHandler   = new RequestHandler(
            $this->container->has("kernel.responseFactory")
                ? $this->container->get("kernel.responseFactory")
                : new \Fratily\Http\Message\ResponseFactory()
        );

        foreach($this->middlewares as $middleware){
            if(is_object($middleware)){
                $this->requestHandler->append($middleware);
                continue;
            }

            if(!$this->container->has($middleware)){
                throw new Exception\KernelBootException();
            }

            $this->requestHandler->append($this->container->get($middleware));
        }

        $this->booted   = true;
    }

    /**
     * {@inheritdoc}
     */
    public function shutdown(){
        if(!$this->booted){
            return;
        }

        foreach($this->bundleInstances as $bundle){
            $bundle->shutdown();
        }

        $this->container        = null;
        $this->routeCollector   = null;
        $this->requestHandler   = null;
        $this->booted           = false;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ServerRequestInterface $request): ResponseInterface{
        if($this->booted){
            $this->shutdown();
        }

        $this->boot();

        $request    = $request
            ->withAttribute("kernel.environment", $this->environment)
            ->withAttribute("kernel.debug", $this->debug)
        ;
        $router     = $this->routeCollector
            ->router($request->getUri()->getHost(), $request->getMethod())
        ;
        $routing    = $router->search($request->getUri()->getPath());
        $action     = function(){
            throw new \Fratily\Http\Message\Status\NotFound();
        };

        if($routing->found){
            $method = $routing->data["action"]["method"];
            $object = $this->container->getInstance(
                $routing->data["action"]["class"]
            );
            $action = [$object, $method];

            foreach($object->registerMiddlewares($request) as $middleware){
                $this->requestHandler->append($middleware);
            }
        }

        $this->requestHandler->append(
            new Controller\ActionMiddleware($this->container, $action, $routing)
        );

        try{
            return $this->requestHandler->handle($request);
        }finally{
            $this->shutdown();
        }
    }

    protected function initialBundleInstances(){
        $this->bundleInstances  = [];

        foreach($this->bundles as $class){
            $bundle = new $class($this->environment, $this->debug);
            $name   = $bundle->getName();

            if(array_key_exists($name, $this->bundleInstances)){
                $_class = get_class($this->bundleInstances[$name]);

                throw new Exception\KernelBootException(
                    "Bundle name'{$name}' conflicts with {$_class} and {$class}."
                );
            }

            $this->bundleInstances[$name]   = $bundle;
        }
    }

    /**
     * サービスコンテナを生成する
     *
     * @return  Container
     *
     * @throws  Exception\KernelBootException
     */
    private function generateContainer(){
        $factory    = new ContainerFactory();

        $factory->append(new \Fratily\Kernel\Container\KernelConfig());

        try{
            foreach($this->bundleInstances as $bundle){
                foreach($bundle->registerContainerConfigurations() as $config){
                    $factory->append($config);
                }
            }
        }catch(\Exception $e){
            $class = get_class($bundle);

            throw new Exception\KernelBootException(
                "An error occurred in the service container definition of {$class}.",
                $e->getCode(),
                $e
            );
        }

        try{
            return $factory->create();
        }catch(\Exception $e){
            throw new Exception\KernelBootException(
                "An error occurred while constructing the service container.",
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * ルートコレクタを生成する
     *
     * @return  RouteCollector
     *
     * @throws  Exception\KernelBootException
     */
    private function generateRouteCollector(){
        $routeCollector = $this->container->get("kernel.routeCollector");
        $resolver       = $this->container->get("kernel.controllerResolver");

        try{
            foreach($this->bundleInstances as $name => $bundle){
                foreach($bundle->registerControllers() as $controller){
                    foreach($resolver->getRoutes($controller) as $route){
                        $data   = [
                            "bundle"        => $name,
                            "middleware"    => $bundle->registerMiddlewares(),
                        ];

                        $routeCollector->add(
                            $route->withData(array_merge($route->getData, $data))
                        );
                    }
                }
            }
        }catch(\Exception $e){
            $class  = get_class($bundle);

            throw new Exception\KernelBootException(
                "An error occurred in the route definition of {$class}.",
                $e->getCode(),
                $e
            );
        }

        return $routeCollector;
    }
}