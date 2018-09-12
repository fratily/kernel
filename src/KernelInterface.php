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
interface KernelInterface{

    /**
     * Constructor
     *
     * @param   string  $environment
     *  環境識別文字列
     * @param   bool    $debug
     *  デバッグフラグ
     * @param   string[]    $bundles
     *  登録するバンドルクラスの配列
     *
     * @throws  \InvalidArgumentException
     */
    public function __construct(
        string $environment,
        bool $debug,
        array $bundles = []
    );

    /**
     * カーネル起動時処理
     *
     * @return  void
     *
     * @throws  Exception\KernelBootException
     */
    public function boot();

    /**
     * カーネル終了時処理
     *
     * @return  void
     */
    public function shutdown();

    /**
     * リクエストインスタンスからレスポンスインスタンスを生成する
     *
     * @param   ServerRequestInterface  $request
     *  リクエストインスタンス
     *
     * @return  ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface;
}