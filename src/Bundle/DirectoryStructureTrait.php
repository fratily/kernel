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

use Fratily\Utility\FileSystem;

/**
 *
 */
trait DirectoryStructureTrait{

    /**
     * @var string|null
     */
    private $rootDir;

    /**
     * @var string|null
     */
    private $projectDir;

    /**
     * @var string|null
     */
    private $namespace;

    /**
     * @var string[]|null
     */
    private $controllers;

    /**
     * @var string[]|null
     */
    private $containers;

    /**
     * ルートディレクトリを取得する
     *
     * @return  string
     */
    public function getRootDir(): string{
        if(null === $this->rootDir){
            $this->rootDir  = realpath(
                dirname(
                    (new \ReflectionClass(static::class))->getFileName()
                )
            );
        }

        return $this->rootDir;
    }

    /**
     * プロジェクトディレクトリを取得する
     *
     * プロジェクトディレクトリの直接の子供にcomposer.jsonが含まれる必要がある。
     *
     * @return  string
     */
    public function getProjectDir(): string{
        if(null === $this->projectDir){
            $this->projectDir   = $this->getRootDir();

            while(!file_exists($this->projectDir . "/composer.json")){
                if($this->projectDir === dirname($this->projectDir)){
                    $this->projectDir   = $this->getRootDir();
                    break;
                }

                $this->projectDir   = dirname($this->projectDir);
            }
        }

        return $this->projectDir;
    }

    /**
     * アセットディレクトリを取得する
     *
     * jsやcssなどアプリケーション内で共有されるリソースを格納するディレクトリ。
     *
     * @return  string
     */
    public function getAssetDir(): string{
        return $this->getRootDir() . DIRECTORY_SEPARATOR . "asset";
    }

    /**
     * 設定ディレクトリを取得する
     *
     * @return  string
     */
    public function getConfigDir(): string{
        return $this->getRootDir() . DIRECTORY_SEPARATOR . "config";
    }

    /**
     * ソースコードディレクトリを取得する
     *
     * @return  string
     */
    public function getSrcDir(): string{
        return $this->getRootDir() . DIRECTORY_SEPARATOR . "src";
    }

    /**
     * ソースコードディレクトリに対応するネームスペースを取得する
     */
    public function getNameSpace(): string{
        if(null === $this->namespace){
            $this->namespace    = (new \ReflectionClass(static::class))
                ->getNamespaceName()
            ;
        }

        return $this->namespace;
    }

    /**
     * サービスコンテナ構築クラスの配列を取得する
     *
     * @return  string[]
     */
    public function getContainers(): array{
        if(null === $this->containers){
            $this->containers   = $this->getClassesInSrcDir(
                "Container",
                "Container"
            );
        }

        return $this->containers;
    }

    /**
     * コントローラークラスの配列を取得する
     *
     * @return  string[]
     */
    public function getControllers(): array{
        if(null === $this->controllers){
            $this->controllers  = $this->getClassesInSrcDir(
                "Controller",
                "Controller"
            );
        }

        return $this->controllers;
    }

    /**
     * ソースコードディレクトリ内で定義されているクラスリストを取得する
     *
     * PSR-4に従ったオートロード構成の場合のみ正しく動作する。
     *
     * @param   string  $subDir
     *  ソースコード内の検索するサブディレクトリ
     * @param   string  $suffix
     *  クラス名の接尾語
     *
     * @return  string[]
     */
    protected function getClassesInSrcDir(
        string $subDir = "",
        string $suffix = ""
    ){
        $result = [];
        $subDir = trim(
            str_replace(["\\", "/"], DIRECTORY_SEPARATOR, $subDir),
            DIRECTORY_SEPARATOR
        );
        $subNs  = str_replace(DIRECTORY_SEPARATOR, "\\", $subDir);
        $search = realpath(
            __DIR__ . "/src"
            . (
                "" === $subDir
                    ? ""
                    : DIRECTORY_SEPARATOR . $subDir
            )
        );

        if(false === $search || !is_dir($search)){
            return [];
        }

        foreach(FileSystem::getFiles($search, true) as $file){
            if("{$suffix}.php" !== substr($file, -(strlen($suffix) + 4))){
                continue;
            }

            $class  =
                $this->getNameSpace()
                . "\\{$subNs}\\"
                . str_replace(
                    "/",
                    "\\",
                    substr(substr($file, 0, -4), strlen($search) + 1)
                )
            ;

            if(!class_exists($class)){
                continue;
            }

            $result[]   = $class;
        }

        return $result;
    }
}