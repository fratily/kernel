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
namespace Fratily\Kernel\Controller;

use Fratily\Container\Container;
use Fratily\Router\Routing;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 *
 */
class ActionMiddleware implements MiddlewareInterface{

    /**
     * @var Container
     */
    private $container;

    /**
     * Constructor
     *
     * @param   Kernel  $kernel
     *  カーネル
     * @param   callable    $action
     *  アクションコールバック
     * @param   Routing $routing
     *  ルーティング結果
     */
    public function __construct(Container $container){
        $this->container    = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface{

        // アクション実行前にアクションコールバックを使うイベントを実行
        // 例) コールバックを包むコールバックを生成してアクションとする等

        if(!is_callable($request->getAttribute("action"))){
            throw new \LogicException;
        }

        if(
            !is_object($request->getAttribute("routing"))
            || !$request->getAttribute("routing") instanceof Routing
        ){
            throw new \LogicException;
        }

        $response   = $this->container->invokeCallback(
            $request->getAttribute("action"),
            array_merge(
                $request->getAttribute("routing")->params,
                [
                    "request"   => $request,
                    "_route"    => $request->getAttribute("routing")->name,
                    "_handler"  => $handler,
                ]
            ),
            [
                ServerRequestInterface::class   => $request,
                RequestInterface::class         => $request,
            ]
        );

        // アクション実行後にレスポンスを使うイベントを実行
        // 例) Allow: jsonなリクエストの場合は配列のレスポンスをJsonResponseに置き換える等

        if(!$response instanceof ResponseInterface){
            $class  = ResponseInterface::class;
            throw new Exception\ActionException(
                "Action method must return an instance of the object"
                . "that implements {$class}."
            );
        }

        return $response;
    }
}