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
namespace Fratily\Kernel\Controller;

use Fratily\Kernel\Kernel;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;

/**
 *
 */
abstract class AbstractController{

    /**
     * @var Kernel
     */
    private $kernel;

    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * Constructor
     *
     * @param   Kernel  $kernel
     *  カーネル
     * @param   ResponseFactoryInterface
     *  レスポンスファクトリ
     */
    public function __construct(
        Kernel $kernel,
        ResponseFactoryInterface $responseFactory
    ){
        $this->kernel           = $kernel;
        $this->responseFactory  = $responseFactory;
    }

    /**
     * カーネルを取得する
     *
     * @return  Kernel
     */
    protected function getKernel(){
        return $this->kernel;
    }

    /**
     * レスポンスファクトリを取得する
     *
     * @return  ResponseFactory
     */
    protected function getResponseFactory(){
        return $this->responseFactory;
    }

    /**
     * ルート名からURLを生成する
     *
     * @param   UriInterface  $baseUri
     *  もととなるURIインスタンス
     * @param   string  $route
     *  ルート名
     * @param   mixed[] $parameters
     *  パラメータの配列
     *
     * @return  UriInterface
     *
     * @throws \LogicException
     */
    protected function generateUrl(
        UriInterface $baseUri,
        string $route,
        array $parameters = []
    ): UriInterface{
        $query  = "";
        $path   = $this->getKernel()
            ->getRouteCollector()
            ->reverseRouter($route)
            ->createPath($parameters)
        ;

        if(false !== strpos($path, "?")){
            $explode    = explode("?", $path, 2);
            $path       = $explode[0];
            $query      = $explode[1];
        }

        return $baseUri->withPath($path)->withQuery($query);
    }

    /**
     * レスポンスインスタンスを生成する
     *
     * @param   int $code
     *  ステータスコード
     * @param   string  $reasonPhrase
     *  ステータスフレーズ
     *
     * @return  ResponseInterface
     */
    protected function generateResponse(
        int $code,
        string $reasonPhrase
    ): ResponseInterface{
        return $this->getResponseFactory()
            ->createResponse($code, $reasonPhrase)
        ;
    }

    /**
     * リダイレクトレスポンスを生成する
     *
     * @param   UriInterface    $uri
     *  リダイレクト先URI
     * @param   int $status
     *  レスポンスステータス
     * @param   bool    $absolute
     *  リダイレクト先URIを絶対パスで指定するか
     *
     * @return  RedirectResponse
     */
    protected function redirect(
        UriInterface $uri,
        int $status = 302,
        bool $absolute = true
    ): ResponseInterface{
        throw new \LogicException("未実装");
    }
}