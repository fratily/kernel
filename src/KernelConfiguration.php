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
namespace Fratily\Kernel;

use Fratily\Http\Server\RequestHandlerBuilder;
use Symfony\Component\Console\Application;

/**
 *
 */
abstract class KernelConfiguration implements Bundle\DirectoryStructureInterface, Bundle\MiddlewareRegisterInterface, Bundle\CommandRegisterInterface{

    use Bundle\DirectoryStructureTrait;

    /**
     * @var string
     */
    private $environment;

    /**
     * @var bool
     */
    private $debug;

    /**
     * @var string[]
     */
    protected $bundles;

    /**
     * @var string[]|null
     */
    protected $controllers;

    /**
     * Constructor
     *
     * @param   string  $environment
     *  環境識別文字
     * @param   bool    $debug
     *  デバッグモードフラグ
     */
    public function __construct(string $environment, bool $debug){
        $this->environment  = $environment;
        $this->debug        = $debug;
    }

    /**
     * 環境識別文字列を取得する
     *
     * @return  string
     */
    final public function getEnvironment(){
        return $this->environment;
    }

    /**
     * デバッグモードが有効か確認する
     *
     * @return  bool
     */
    final public function isDebug(){
        return $this->debug;
    }

    /**
     * アプリケーションで使用するバンドルクラスの配列
     *
     * @return  string[]
     */
    public function getBundles(): array{
        if(null === $this->bundles){
            $this->bundles  = include $this->getConfigDir() . "/bundle.php";
        }

        return $this->bundles;
    }

    /**
     * コントローラークラスの配列を取得する
     *
     * @return  string[]
     */
    public function getControllers(): array{
        if(null === $this->controllers){
            $this->controllers  = $this->getClasses(
                "Controller",
                true,
                function(string $class){
                    return (new \ReflectionClass($class))->isInstantiable();
                }
            );
        }

        return $this->controllers;
    }

    /**
     * {@inheritdoc}
     */
    public function middlewareRegister(
        RequestHandlerBuilder $builder,
        array $options = []
    ): void{
    }

    /**
     * {@inheritdoc}
     */
    public function commandRegister(
        Application $app,
        array $options = []
    ): void{
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
}