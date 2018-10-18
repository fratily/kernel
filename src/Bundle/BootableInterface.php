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

/**
 * - root / project
 * - - asset
 * - - config
 * - - src <- Base point of bundle namespace.
 * - - - Controller
 * - - - Container
 * - - composer.json <- The directory where this file exists is the project dir.
 */
interface BootableInterface{

    /**
     * バンドルの起動時処理
     *
     * @return  void
     */
    public function boot(): void;

    /**
     * バンドルの終了時処理
     *
     * @return  void
     */
    public function shutdown(): void;
}