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

/**
 *
 */
abstract class KernelConfigure implements Bundle\DirectoryStructureInterface, Bundle\BootableInterface, Bundle\MiddlewareRegisterInterface{

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
     * {@inheritdoc}
     */
    public function __construct(
        string $environment,
        bool $debug
    ){
        $this->environment  = $environment;
        $this->debug        = $debug;
    }

    /**
     * 環境識別文字列を取得する
     *
     * @return  string
     */
    public function getEnvironment(){
        return $this->environment;
    }

    /**
     * デバッグモードが有効か確認する
     *
     * @return  bool
     */
    public function isDebug(){
        return $this->debug;
    }

    /**
     * アプリケーションで使用するバンドルクラスの配列
     *
     * @return  string[]
     */
    abstract public function getBundles(): array;

    /**
     * {@inheritdoc}
     */
    public function middlewareRegister(RequestHandlerBuilder $builder){
    }

    /**
     * {@inheritdoc}
     */
    public function middlewareRegisterForGlobal(RequestHandlerBuilder $builder){
    }
}