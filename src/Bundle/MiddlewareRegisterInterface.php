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
 * - root / project
 * - - asset
 * - - config
 * - - src <- Base point of bundle namespace.
 * - - - Controller
 * - - - Container
 * - - composer.json <- The directory where this file exists is the project dir.
 */
interface MiddlewareRegisterInterface{

    /**
     * バンドル内でのみ実行されるミドルウェアを登録する
     *
     * @param RequestHandlerBuilder $builder
     *  ハンドラのビルダー
     *
     * @return  void
     */
    public function middlewareRegister(RequestHandlerBuilder $builder);

    /**
     * 全てのバンドルで実行されるミドルウェアを登録する
     *
     * @param   RequestHandlerBuilder   $builder
     *  ハンドラのビルダー
     *
     * @return  void
     */
    public function middlewareRegisterForGlobal(RequestHandlerBuilder $builder);
}