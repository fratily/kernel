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
use Fratily\Http\Server\RequestHandlerBuilder;

/**
 *
 */
abstract class Bundle implements BundleInterface{

    use DirectoryStructureTrait;

    /**
     * @var KernelConfigure
     */
    private $config;

    /**
     * @var string
     */
    protected $name       = null;

    /**
     * {@inheritdoc}
     */
    public static function dependBundles(): array{
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function __construct(KernelConfigure $config){
        $this->config   = $config;
    }

    /**
     * カーネル設定クラスインスタンスを取得する
     *
     * @return  KErnelConfigure
     */
    public function getConfig(){
        return $this->config;
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
    public function boot(): void{
    }

    /**
     * {@inheritdoc}
     */
    public function shutdown(): void{
    }
}