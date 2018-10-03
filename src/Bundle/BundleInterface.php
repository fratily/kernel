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
 *
 */
interface BundleInterface{

    /**
     * 依存するバンドルクラスのリスト
     *
     * @return  string[]
     */
    public static function dependBundles(): array;

    /**
     * Constructor
     *
     * @param   string  $environment
     *  実行環境識別文字列
     * @param   bool    $debug
     *  デバッグモードか
     */
    public function __construct(string $environment, bool $debug);

    /**
     * 環境識別文字列を取得する
     *
     * @return  string
     */
    public function getEnvironment(): string;

    /**
     * デバッグモードか確認する
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
     * バンドルのネームスペースを取得する
     */
    public function getNamespace(): string;

    /**
     * バンドルのディレクトリを取得する
     *
     * @return  string
     */
    public function getPath(): string;

    /**
     * 登録するコンテナ構成クラスの配列
     *
     * @return  string[]
     */
    public function registerContainers(): array;

    /**
     * 登録するコントローラーの配列
     *
     * @return  string[]
     */
    public function registerControllers(): array;

    /**
     * 登録するミドルウェアインスタンスの配列
     *
     * @return  MiddlewareInterface[]
     */
    public function registerMiddlewares(): array;

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