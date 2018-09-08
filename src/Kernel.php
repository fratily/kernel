<?php
/**
 * FratilyPHP Router
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
use Psr\Http\Server\MiddlewareInterface;


/**
 *
 */
abstract class Kernel{

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
     * @var RequestHandler
     */
    private $requestHandler;

    /**
     * @var RouteCollector
     */
    private $routeCollector;

    /**
     * @var MiddlewareInterface[][]
     */
    private $middlewares    = [
        "before"    => [],
        "after"     => [],
    ];

    public function __construct(
        string $environment,
        bool $debug,
        Container $container,
        Controller\ControllerResolver $ctrlResolver = null,
        RequestHandler $requestHandler = null,
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
        $this->requestHandler       = $requestHandler
            ?? new RequestHandler(
                new \Fratily\Http\Message\ResponseFactory()
            )
        ;
        $this->routeCollector       = $routeCollector ?? new RouteCollector();
    }

    public function handle(ServerRequestInterface $request){
        $router = $this->routeCollector
            ->router($request->getUri()->getHost(), $request->getMethod())
        ;
        $result = $router->search($request->getUri()->getPath());

        if($result->found){
            $action = [
                $this->container->getInstance(
                    $result->data["action"]["class"]
                ),
                $result->data["action"]["method"],
            ];
        }else{
            $action = function(){
                throw new \Fratily\Http\Message\Status\NotFound();
            };
        }

        $middlewares    = array_merge(
            $this->middlewares["before"],
            $result->data["middleware.before"],
            $this->createActionMiddleware($action, $result->params),
            $result->data["middleware.before"],
            $this->middlewares["after"]
        );
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
        $this->middlewares["after"][]   = $middleware;

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
        array_unshift($this->middlewares["before"], $middleware);

        return $this;
    }

    /**
     * ミドルウェアをアクションミドルウェアの直前に追加する
     *
     * @param   MiddlewareInterface $middleware
     *
     * @return  $this
     */
    public function addBeforeAction(MiddlewareInterface $middleware){
        $this->middlewares["before"][]  = $middleware;

        return $this;
    }

    /**
     * ミドルウェアをアクションミドルウェアの直後に追加する
     *
     * @param   MiddlewareInterface $middleware
     *
     * @return  $this
     */
    public function addAfterAction(MiddlewareInterface $middleware){
        array_unshift($this->middlewares["after"], $middleware);

        return $this;
    }
}