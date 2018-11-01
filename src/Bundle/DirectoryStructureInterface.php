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
     * 設定ディレクトリを取得する
     *
     * @return  string
     */
    public function getConfigDir(): string;

    /**
     * ソースコードディレクトリに対応するネームスペースを取得する
     *
     * 末尾にバックスラッシュを含んではならない。
     *
     * @return  string
     */
    public function getNameSpace(): string;

    /**
     * サービスコンテナ構築クラスの配列を取得する
     *
     * @return  string[]
     */
    public function getContainers(): array;

    /**
     * srcディレクトリ内に存在するクラスのリストを取得する
     *
     * @param   string  $namespace
     *  ネームスペース。ベースとしてsrcディレクトリのネームスペースが指定される
     *  ため、`$this->getNameSpace() . "\\" . $namespace`で検索される。
     * @param   bool    $recursive
     *  子孫ディレクトリが存在した場合、それらの中のクラスも取得するか
     * @param   callable    $filter
     *  結果に追加するクラスをフィルタリングするためのコールバック
     *
     * @return  string[]
     */
    public function getClasses(
        string $namespace = null,
        bool $recursive = true,
        callable $filter = null
    ): array;
}