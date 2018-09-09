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

    /**
     * @var Controller\DummyActionMiddleware
     */
    private $dummyAction;

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
        $this->dummyAction      = new Controller\DummyActionMiddleware();

        $this->requestHandler->append($this->dummyAction);
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
        $router = $this->routeCollector
            ->router($request->getUri()->getHost(), $request->getMethod())
        ;

        $routing    = $router->search($request->getUri()->getPath());
        $action     = $routing->found
            ?
                [
                    $this->container->getInstance(
                        $routing->data["action"]["class"]
                    ),
                    $routing->data["action"]["method"],
                ]
            :
                function(){
                    throw new \Fratily\Http\Message\Status\NotFound();
                }
        ;

        $this->requestHandler->replaceObject(
            $this->dummyAction,
            new Controller\ActionMiddleware($this->container, $action, $routing)
        );

        return $this->requestHandler->handle($request);
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

    /**
     * ミドルウェアをアクションミドルウェアの直前に追加する
     *
     * @param   MiddlewareInterface $middleware
     *
     * @return  $this
     */
    public function addBeforeAction(MiddlewareInterface $middleware){
        $this->requestHandler->insertBeforeObject($this->dummyAction, $middleware);

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
        $this->requestHandler->insertAfterObject($this->dummyAction, $middleware);

        return $this;
    }
}