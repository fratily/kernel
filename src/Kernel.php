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

use Fratily\Kernel\Container\KernelContainer;
use Fratily\Container\Container;
use Fratily\Container\ContainerFactory;
use Fratily\Router\RouteCollector;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 *
 */
abstract class Kernel extends Bundle\Bundle{

    /**
     * @var Bundle\BundleInterface[]
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
     * Constructor
     *
     * @param   string  $environment
     *  環境識別文字
     * @param   bool    $debug
     *  デバッグモードフラグ
     */
    public function __construct(string $environment, bool $debug){
        parent::__construct($environment, $debug);

        $this->bundles  = [];

        foreach($this->getRegisterBundles() as $bundle){
            if(!is_string($bundle) || !class_exists($bundle)){
                throw new \LogicException();
            }

            $reflection = new \ReflectionClass($bundle);

            if(
                !$reflection->implementsInterface(Bundle\BundleInterface::class)
                || !$reflection->isInstantiable()
            ){
                throw new \LogicException();
            }

            $this->bundles[$reflection->getName()] = $reflection->newInstance();
        }

        $this->bundles[static::class]   = $this;

        foreach($this->bundles as $bundle){
            $bundle->boot();
        }
    }

    /**
     * Destructor
     */
    public function __destruct(){
        foreach($this->bundles as $bundle){
            $bundle->shutdown();
        }
    }

    /**
     * 登録するバンドルクラスのリストを取得する
     *
     * @return  $string[]
     */
    abstract protected function getRegisterBundles(): array;

    /**
     * カーネル設定クラスインスタンスを取得する
     *
     * @return  KernelConfiguration
     */
    public function getConfigDir(){
        return $this->getProjectDir() . DIRECTORY_SEPARATOR . "config";
    }

    /**
     * {@inheritdoc}
     */
    public function getControllers(){
        return $this->getClasses(
            "Container",
            true,
            function(string $class){
                return (new \ReflectionClass($class))->isInstantiable();
            }
        );
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

            foreach($this->bundles as $bundle){
                if($bundle instanceof Bundle\ContainerRegisterInterface){
                    $bundle->containerRegister($factory);
                }
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

            foreach($this->bundles as $bundle){
                foreach($bundle->getControllers() as $controller){
                    foreach($resolver->getRoutes($controller) as $route){
                        $this->routeCollector->add($route);
                    }
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
            $this->requestHandlerBuilder    = new RequestHandlerBuilder(
                $this->getContainer()
            );

            foreach($this->bundles as $bundle){
                if($bundle instanceof Bundle\MiddlewareRegisterInterface){
                    $bundle->middlewareRegister($this->requestHandlerBuilder);
                }
            }
        }

        return clone $this->requestHandlerBuilder;
    }

    /**
     * リクエストインスタンスからレスポンスインスタンスを生成する
     *
     * @param   ServerRequestInterface  $request
     *  リクエストインスタンス
     *
     * @return  ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface{
        $routing        = $this->getRouteCollector()
            ->router($request->getUri()->getHost(), $request->getMethod())
            ->search($request->getUri()->getPath())
        ;
        $handlerBuilder = $kernel->getRequestHandlerBuilder();

        if($routing->found){
            $method = $routing->data["action"]["method"];
            $object = $kernel->getContainer()->getInstance(
                $routing->data["action"]["class"]
            );
            $action = [$object, $method];

            if($object instanceof Bundle\MiddlewareRegisterInterface){
                $object->middlewareRegister($handlerBuilder);
            }
        }else{
            $action = function(){
                throw new \Fratily\Http\Message\Status\NotFound();
            };
        }

        return $handlerBuilder
            ->append(
                new Controller\ActionMiddleware($this->getContainer())
            )
            ->create(
                $kernel->getContainer()->has("kernel.responseFactory")
                    ? $kernel->getContainer()->get("kernel.responseFactory")
                    : new \Fratily\Http\Message\ResponseFactory()
            )
            ->handle(
                $request
                    ->withAttribute("action", $action)
                    ->withAttribute("routing", $routing)
                    ->withAttribute("kernel", $kernel)
                    ->withAttribute(
                        "environment",
                        $kernel->getConfig()->getEnvironment()
                    )
                    ->withAttribute(
                        "debug",
                        $kernel->getConfig()->isDebug()
                    )
            )
        ;
    }

    /**
     *
     * @param \Fratily\Kernel\InputInterface $input
     * @return type
     * @throws \LogicException
     */
    public function run(InputInterface $input){
        if(!$this->getContainer()->has(KernelContainer::SERVICE_CONSOLE_APP)){
            throw new \LogicException;
        }

        $console    = $this->getContainer()->get(KernelContainer::SERVICE_CONSOLE_APP);

        if(!$console instanceof Application){
            throw new \LogicException;
        }

        $console->run($input);

        return;
    }
}