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
abstract class Bundle implements BundleInterface{

    /**
     * @var string
     */
    private $environment;

    /**
     * @var bool
     */
    private $debug;

    /**
     * @var null|string
     */
    protected $name;

    /**
     * @var null|string
     */
    protected $namespace;

    /**
     * @var null|string
     */
    protected $projectDir;

    /**
     * @var null|string
     */
    protected $srcDir;

    /**
     * {@inheritdoc}
     */
    public function __construct(string $environment, bool $debug){
        $this->environment  = $environment;
        $this->debug        = $debug;
    }

    /**
     * {@inheritdoc}
     */
    final public function getEnvironment(): string{
        return $this->environment;
    }

    /**
     * {@inheritdoc}
     */
    final public function isDebug(): bool{
        return $this->debug;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string{
        if(null === $this->name){
            $this->name = ltrim(
                strtolower(
                    preg_replace(
                        "/[A-Z]/",
                        '_$0',
                        (new \ReflectionClass(static::class))->getShortName()
                    )
                ),
                "_"
            );

            if("_bundle" === substr($this->name, -7)){
                $this->name = substr($this->name, 0, -7);
            }
        }

        return $this->name;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function getControllers(): array{
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function boot(): void{
    }

    /**
     * {@inheritdoc}
     */
    public function shutdown(): void{
    }

    /**
     * ソースコードディレクトリ内に存在するクラスのリストを取得する
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
    protected function getClasses(
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