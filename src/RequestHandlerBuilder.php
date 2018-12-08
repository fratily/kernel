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
use Fratily\Http\Server\RequestHandlerBuilder as BaseRequestHandlerBuilder;
use Psr\Http\Server\MiddlewareInterface;

/**
 *
 */
class RequestHandlerBuilder extends BaseRequestHandlerBuilder{

    /**
     * @var Container
     */
    private $container;

    /**
     * Constructor
     *
     * @param   Container   $container
     *  サービスコンテナ
     */
    public function __construct(Container $container){
        parent::__construct();

        $this->container    = $container;
    }

    /**
     * サービスコンテナからミドルウェアインスタンスを取得する
     *
     * @param   string  $service
     *  サービスID
     *
     * @return  MiddlewareInterface
     */
    public function getMiddlewareFromContainer(
        string $service
    ): MiddlewareInterface{
        if($this->container->has($service)){
            $middleware = $this->container->get($service);
        }elseif(class_exists($service)){
            $middleware = $this->container->getInstance($service);
        }else{
            throw new \LogicException;
        }

        if(!$middleware instanceof MiddlewareInterface){
            throw new \LogicException;
        }

        return $middleware;
    }

    /**
     * ミドルウェアサービスを末尾に追加する
     *
     * @param   string  $middleware
     *  ミドルウェアインスタンスのサービスID
     *
     * @return  $this
     *
     * @throws  Exception\MiddlewareAlreadyRegisteredException
     */
    public function appendService(string $middleware){
        return $this->append($this->getMiddlewareFromContainer($middleware));
    }

    /**
     * ミドルウェアサービスを先頭に追加する
     *
     * @param   string  $middleware
     *  挿入するミドルウェアインスタンスのサービスID
     *
     * @return  $this
     *
     * @throws  Exception\MiddlewareAlreadyRegisteredException
     */
    public function prependService(string $middleware){
        return $this->prepend($this->getMiddlewareFromContainer($middleware));
    }
}