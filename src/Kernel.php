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
use Fratily\Router\RouteCollector;
use Fratily\Http\Server\RequestHandler;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;


/**
 *
 */
class Kernel{

    /**
     * @var string
     */
    private $environment;

    /**
     * @var bool
     */
    private $debug;

    /**
     * @var Container
     */
    private $container;

    /**
     * @var null
     */
    private $eventDispatcher;

    /**
     * @var Controller\ControllerResolver
     */
    private $controllerResolver;

    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * @var RouteCollector
     */
    private $routeCollector;

    /**
     * @var RequestHandler
     */
    private $requestHandler;

    public function __construct(
        string $environment,
        bool $debug,
        Container $container,
        Controller\ControllerResolver $ctrlResolver = null,
        ResponseFactoryInterface $responseFactory = null,
        RouteCollector $routeCollector = null
    ){
        $this->environment          = $environment;
        $this->debug                = $debug;
        $this->container            = $container;
        $this->controllerResolver   = $ctrlResolver
            ?? new Controller\ControllerResolver(
                new \Doctrine\Common\Annotations\AnnotationReader()
            )
        ;
        $this->responseFactory  = $responseFactory
            ?? new \Fratily\Http\Message\ResponseFactory()
        ;
        $this->routeCollector   = $routeCollector ?? new RouteCollector();
        $this->requestHandler   = new RequestHandler($this->responseFactory);
    }

    /**
     * リクエストハンドラを用いてリクエストをレスポンスに変換する
     *
     * @param   ServerRequestInterface  $request
     *  リクエストインスタンス
     *
     * @return  ResponseInterface
     */
    public function handle(ServerRequestInterface $request){
        $request    = $request
            ->withAttribute("kernel.container", $this->container)
            ->withAttribute("kernel.routes", $this->routeCollector)
            ->withAttribute("kernel.responseFactory", $this->responseFactory)
            ->withAttribute("kernel.environment", $this->environment)
            ->withAttribute("kernel.debug", $this->debug)
        ;
        $router     = $this->routeCollector
            ->router($request->getUri()->getHost(), $request->getMethod())
        ;
        $routing    = $router->search($request->getUri()->getPath());
        $pre        = [];
        $post       = [];
        $action     = function(){
            throw new \Fratily\Http\Message\Status\NotFound();
        };

        if($routing->found){
            $method = $routing->data["action"]["method"];
            $object = $this->container->getInstance(
                $routing->data["action"]["class"]
            );
            $pre    = $object->preProccessMiddlewares($request);
            $pre    = $object->postProccessMiddlewares($request);
        }

        if(!empty($pre)){
            $this->appendMiddlewares($pre, get_class($object), $method);
        }

        $this->requestHandler->append(
            new Controller\ActionMiddleware($this->container, $action, $routing)
        );

        if(!empty($post)){
            $this->appendMiddlewares($post, get_class($object), $method);
        }

        return $this->requestHandler->handle($request);
    }

    /**
     * リクエストハンドラにミドルウェアを複数追加する
     *
     * @param   MiddlewareInterface[]   $middlewares
     *  ミドルウェアインスタンスの配列
     * @param   string  $controller
     *  アクションコントローラクラス名
     * @param   string  $method
     *  アクションメソッド名
     *
     * @return  void
     *
     * @throws  Controller\Exception\InvalidMiddlewareList
     */
    private function appendMiddlewares(array $middlewares, string $controller, string $method){
        foreach($middlewares as $key => $middleware){
            if(!($middleware instanceof MiddlewareInterface)){
                $middlewareClass    = MiddlewareInterface::class;
                $type               = "object" === gettype($middleware)
                    ? get_class($middleware)
                    : gettype($middleware)
                ;

                throw new Controller\Exception\InvalidMiddlewareList(
                    "The method {$method} of controller class MUST return array"
                    . " of {$middlewareClass}. But {$controller}::{$method}"
                    . " contains {$type} at index {$key}"
                );
            }

            $this->requestHandler->append($middleware);
        }
    }

    /**
     * コントローラークラスを登録する
     *
     * @param   string  $controller
     *  コントローラークラス
     *
     * @return  $this
     */
    public function addController(string $controller){
        $routes = $this->controllerResolver->getRoutes($controller);

        foreach($routes as $route){
            $this->routeCollector->add($route);
        }

        return $this;
    }

    /**
     * ミドルウェアを末尾に追加する
     *
     * @param   MiddlewareInterface $middleware
     *
     * @return  $this
     */
    public function append(MiddlewareInterface $middleware){
        $this->requestHandler->append($middleware);

        return $this;
    }

    /**
     * ミドルウェアを先頭に追加する
     *
     * @param   MiddlewareInterface $middleware
     *
     * @return  $this
     */
    public function prepend(MiddlewareInterface $middleware){
        $this->requestHandler->prepend($middleware);

        return $this;
    }
}