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

use Symfony\Component\Console\Input\InputInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 *
 */
class KernelManager{

    /**
     * @var KernelConfiguration
     */
    private $config;

    /**
     * Constructor
     *
     * @param   KernelConfig    $config
     *  カーネル設定クラスインスタンス
     */
    public function __construct(KernelConfiguration $config){
        $this->config   = $config;
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
        $kernel     = new Kernel($this->config);
        $routing    = $kernel->getRouteCollector()
            ->router($request->getUri()->getHost(), $request->getMethod())
            ->search($request->getUri()->getPath())
        ;

        if($routing->found){
            $method = $routing->data["action"]["method"];
            $object = $this->container->getInstance(
                $routing->data["action"]["class"]
            );
            $action = [$object, $method];
        }else{
            $action = function(){
                throw new \Fratily\Http\Message\Status\NotFound();
            };
        }

        $handlerBuilder = $kernel->getRequestHandlerBuilder();

        $handlerBuilder->append(
            new Controller\ActionMiddleware($kernel, $action, $routing)
        );

        return $handlerBuilder
            ->create(
                $this->container->has("kernel.responseFactory")
                    ? $this->container->get("kernel.responseFactory")
                    : new \Fratily\Http\Message\ResponseFactory()
            )
            ->handle(
                $request
                    ->withAttribute(
                        "environment",
                        $kernel->getConfig()->getEnvironment()
                    )
                    ->withAttribute(
                        "debug",
                        $kernel->getConfig()->isDebug()
                    )
                    ->withAttribute("kernel", $kernel)
            )
        ;
    }

    public function run(InputInterface $input){
        $kernel = new Kernel($this->config);
        $app    = $kernel->getConsoleApplication();

        $app->run($input);

        return;
    }
}