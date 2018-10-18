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

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 *
 */
class Kernel{

    /**
     * @var KernelConfigure
     */
    private $config;

    /**
     * @var string[]
     */
    private $bundles;

    /**
     * Constructor
     *
     * @param   KernelConfig    $config
     *  カーネル設定クラスインスタンス
     */
    public function __construct(KernelConfigure $config){
        $this->config   = $config;

        foreach($config->getBundles() as $bundle){
            $this->addBundle($bundle);
        }
    }

    /**
     * バンドルを登録する
     *
     * @param   string  $bundle
     *  バンドルクラス名
     *
     * @return  void
     */
    private function addBundle(string $bundle){
        if(
            !class_exists($bundle)
            || !is_subclass_of($bundle, Bundle\BundleInterface::class)
        ){
            $interface  = Bundle\BundleInterface::class;

            throw new \InvalidArgumentException(
                "'{$bundle}' is not a bundle."
                . " The bundle must be a class that implements '{$interface}'."
            );
        }

        if(array_key_exists($bundle, $this->bundles)){
            return;
        }

        $this->bundles[$bundle] = $bundle;

        foreach($bundle::dependBundles() as $dependBundle){
            $this->addBundle($dependBundle);
        }
    }

    /**
     * カーネル起動時処理
     *
     * @return  BootedKernel
     *
     * @throws  Exception\KernelBootException
     */
    public function boot(){
        return new BootedKernel($this->config, $this->bundles);
    }

    /**
     * リクエストインスタンスからレスポンスインスタンスを生成する
     *
     * @param   ServerRequestInterface  $request
     *  リクエストインスタンス
     *
     * @return  ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface{
        return $this->boot()->handle($request);
    }
}