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

use Fratily\Kernel\KernelConfigure;

/**
 *
 */
interface BundleInterface extends DirectoryStructureInterface, BootableInterface, MiddlewareRegisterInterface{

    /**
     * 依存するバンドルクラスのリスト
     *
     * @return  string[]
     */
    public static function dependBundles(): array;

    /**
     * Constructor
     *
     * @param   KernelConfigure $config
     *  カーネル設定クラスインスタンス
     */
    public function __construct(KernelConfigure $config);

    /**
     * バンドルの名前を取得する
     *
     * @return  string
     */
    public function getName(): string;
}