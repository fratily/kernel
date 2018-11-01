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

use Fratily\Kernel\Kernel;
use Fratily\Http\Server\RequestHandlerBuilder;
use Symfony\Component\Console\Application;

/**
 *
 */
abstract class Bundle implements DirectoryStructureInterface, MiddlewareRegisterInterface, CommandRegisterInterface{

    use DirectoryStructureTrait;

    /**
     * @var Kernel
     */
    private $kernel;

    /**
     * @var null|string
     */
    protected $name;

    /**
     * Constructor
     *
     * @param   Kernel  $kernel
     *  カーネル
     */
    final public function __construct(Kernel $kernel){
        $this->kernel   = $kernel;
    }

    /**
     * 起動済みカーネルインスタンスを取得する
     *
     * @return  BootedKernel
     */
    public function getKernel(){
        return $this->kernel;
    }

    /**
     * バンドルの名前を取得する
     *
     * @return  string
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