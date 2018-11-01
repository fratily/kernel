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
use Fratily\Container\Builder\AbstractContainer;

/**
 *
 */
trait DirectoryStructureTrait{

    /**
     * @var null|string
     */
    protected $srcDir;

    /**
     * @var null|string
     */
    protected $projectDir;

    /**
     * @var null|string
     */
    protected $namespace;

    /**
     * @var null|string[]
     */
    protected $containers;

    /**
     * @var null|string[]
     */
    protected $commands;

    /**
     * プロジェクトディレクトリを取得する
     *
     * プロジェクトディレクトリの直接の子供にcomposer.jsonが含まれる必要がある。
     *
     * @return  string
     */
    public function getProjectDir(): string{
        if(null === $this->projectDir){
            $this->projectDir   = $this->getSrcDir();

            while(!file_exists($this->projectDir . "/composer.json")){
                if($this->projectDir === dirname($this->projectDir)){ // Reaching root dir
                    $this->projectDir   = $this->getSrcDir();
                    break;
                }

                $this->projectDir   = dirname($this->projectDir);
            }
        }

        return $this->projectDir;
    }

    /**
     * ソースコードディレクトリを取得する
     *
     * @return  string
     */
    public function getSrcDir(): string{
        if(null === $this->srcDir){
            $this->srcDir   = dirname(
                (new \ReflectionObject($this))->getFileName()
            );
        }

        return $this->srcDir;
    }

    /**
     * 設定ディレクトリを取得する
     *
     * @return  string
     */
    public function getConfigDir(): string{
        return $this->getProjectDir() . DIRECTORY_SEPARATOR . "config";
    }

    /**
     * ソースコードディレクトリに対応するネームスペースを取得する
     *
     * 末尾にバックスラッシュを含んではならない。
     *
     * @return  string
     */
    public function getNameSpace(): string{
        if(null === $this->namespace){
            $this->namespace    = (new \ReflectionObject($this))
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
            $this->containers   = $this->getClasses(
                "Container",
                true,
                function(string $class){
                    return is_subclass_of($class, AbstractContainer::class);
                }
            );
        }

        return $this->containers;
    }

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
    ): array{
        $baseNameSpace  = $this->getNameSpace();
        $searchDir      = $this->getSrcDir();
        $result         = [];

        if(null !== $namespace){
            $namespace      = trim($namespace, "\\");
            $baseNameSpace  = $this->getNameSpace() . "\\" . $namespace;
            $searchDir      = realpath(
                $this->getSrcDir()
                . DIRECTORY_SEPARATOR
                . str_replace("\\", DIRECTORY_SEPARATOR, $namespace)
            );
        }

        if(false === $searchDir || !is_dir($searchDir)){
            return $result;
        }

        foreach(FileSystem::getFiles($searchDir, $recursive) as $file){
            $classFile  = substr($file, strlen($searchDir . DIRECTORY_SEPARATOR));

            if(".php" !== substr($classFile, -4)){
                continue;
            }

            $class  = $baseNameSpace . "\\" . substr($classFile, 0, -4);

            if(!class_exists($class)){
                continue;
            }

            if(null !== $filter && false === $filter($class)){
                continue;
            }

            $result[]   = $class;
        }

        return $result;
    }
}