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

use Fratily\Container\ContainerFactory;

/**
 *
 */
interface BundleInterface{

    /**
     * Constructor
     *
     * @param   string  $environment
     *  環境識別文字
     * @param   bool    $debug
     *  デバッグモードフラグ
     */
    public function __construct(string $environment, bool $debug);

    /**
     * 環境識別文字列を取得する
     *
     * @return  string
     */
    public function getEnvironment(): string;

    /**
     * デバッグモードが有効か確認する
     *
     * @return  bool
     */
    public function isDebug(): bool;

    /**
     * バンドルの名前を取得する
     *
     * @return  string
     */
    public function getName(): string;

    /**
     * ソースコードディレクトリに対応するネームスペースを取得する
     *
     * 末尾にバックスラッシュを含んではならない。
     *
     * @return  string
     */
    public function getNameSpace(): string;

    /**
     * プロジェクトディレクトリを取得する
     *
     * プロジェクトディレクトリの直接の子供にcomposer.jsonが含まれる必要がある。
     *
     * @return  string
     */
    public function getProjectDir(): string;

    /**
     * ソースコードディレクトリを取得する
     *
     * @return  string
     */
    public function getSrcDir(): string;

    /**
     * コントローラークラスのリストを取得する
     *
     * @return  string[]
     */
    public function getControllers(): array;

    /**
     * 初期化処理
     *
     * @return  void
     */
    public function boot(): void;

    /**
     * 終了処理
     *
     * @return  void
     */
    public function shutdown(): void;
}