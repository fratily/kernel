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
     * @var string
     */
    protected $name       = null;

    /**
     * @var string
     */
    protected $namespace  = null;

    /**
     * @var string
     */
    protected $path       = null;

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
    public function getEnvironment(): string{
        return $this->environment;
    }

    /**
     * {@inheritdoc}
     */
    public function isDebug(): bool{
        return $this->debug;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string{
        if(null === $this->name){
            $reflection = new \ReflectionClass(static::class);
            $this->name = ltrim(strtolower(preg_replace("/[A-Z]/", '_$0', $reflection->getShortName())), "_");

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
            $reflection         = new \ReflectionClass(static::class);
            $this->namespace    = $reflection->getNamespaceName();
        }

        return $this->namespace;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath(): string{
        if(null === $this->path){
            $reflection = new \ReflectionClass(static::class);
            $this->path = dirname($reflection->getFileName());
        }

        return $this->path;
    }

    /**
     * {@inheritdoc}
     */
    public function registerContainers(): array{
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function registerControllers(): array{
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function registerMiddlewares(): array{
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

}