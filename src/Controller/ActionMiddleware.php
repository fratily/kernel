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

use Fratily\Kernel\Kernel;
use Fratily\Router\Routing;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 *
 */
class ActionMiddleware implements MiddlewareInterface{

    /**
     * @var Kernel
     */
    private $kernel;

    /**
     * @var ReflectionCallable
     */
    private $action;

    /**
     * @var Routing
     */
    private $routing;

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
    public function __construct(
        Kernel $kernel,
        callable $action,
        Routing $routing
    ){
        $this->kernel   = $kernel;
        $this->action   = $action;
        $this->routing  = $routing;
    }

    /**
     * {@inheritdoc}
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface{
        $response   = $this->kernel->getContainer()->invokeCallback(
            $this->action,
            array_merge(
                $this->routing->params,
                [
                    "request"   => $request,
                    "_route"    => $this->routing->name,
                    "_handler"  => $handler,
                ]
            ),
            [
                ServerRequestInterface::class   => $request,
            ]
        );

        if(!($response instanceof ResponseInterface)){
            $class  = ResponseInterface::class;
            throw new Exception\ActionException(
                "Action method must return an instance of the object"
                . "that implements {$class}."
            );
        }

        return $response;
    }
}