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
interface DirectoryStructureInterface{

    /**
     * ルートディレクトリを取得する
     *
     * @return  string
     */
    public function getRootDir(): string;

    /**
     * プロジェクトディレクトリを取得する
     *
     * プロジェクトディレクトリの直接の子供にcomposer.jsonが含まれる必要がある。
     *
     * @return  string
     */
    public function getProjectDir(): string;

    /**
     * アセットディレクトリを取得する
     *
     * jsやcssなどアプリケーション内で共有されるリソースを格納するディレクトリ。
     *
     * @return  string
     */
    public function getAssetDir(): string;

    /**
     * 設定ディレクトリを取得する
     *
     * @return  string
     */
    public function getConfigDir(): string;

    /**
     * ソースコードディレクトリを取得する
     *
     * @return  string
     */
    public function getSrcDir(): string;

    /**
     * ソースコードディレクトリに対応するネームスペースを取得する
     */
    public function getNameSpace(): string;

    /**
     * サービスコンテナ構築クラスの配列を取得する
     *
     * @return  string[]
     */
    public function getContainers(): string;

    /**
     * コントローラークラスの配列を取得する
     *
     * @return  string[]
     */
    public function getControllers(): string;
}