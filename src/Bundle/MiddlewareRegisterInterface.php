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
namespace Fratily\Kernel\Bundle;

use Fratily\Http\Server\RequestHandlerBuilder;

/**
 *
 */
interface MiddlewareRegisterInterface{

    /**
     * ミドルウェアを登録する
     *
     * @param RequestHandlerBuilder $builder
     *  ハンドラのビルダー
     * @param   mixed[] $options
     *  オプション値のリスト
     *
     * @return  void
     */
    public function middlewareRegister(
        RequestHandlerBuilder $builder,
        array $options
    ): void;
}