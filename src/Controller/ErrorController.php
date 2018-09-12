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

use Psr\Http\Message\RequestInterface;
use Psr\Http\Server\MiddlewareInterface;

/**
 *
 */
abstract class AbstractController{

    /**
     * アクションを実行する前に実行するミドルウェアのリストを返す
     *
     * @param   RequestInterface    $request
     *  リクエストインスタンス
     *
     * @return  MiddlewareInterface[]
     */
    public function preProccessMiddlewares(RequestInterface $request): array{
        return [];
    }

    /**
     * アクションを実行した後に実行するミドルウェアのリストを返す
     *
     * @param   RequestInterface    $request
     *  リクエストインスタンス
     *
     * @return  MiddlewareInterface[]
     */
    public function postProccessMiddlewares(RequestInterface $request): array{
        return [];
    }
}