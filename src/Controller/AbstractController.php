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

use Fratily\Router\RouteCollector;
use Fratily\Http\Message\Uri;
use Fratily\Http\Message\Response\RedirectResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;

/**
 *
 */
abstract class AbstractController implements ControllerInterface{

    /**
     * @var RouteCollector|null
     */
    private $routeCollector = null;

    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * Constructor
     *
     * @param   ResponseFactoryInterface    $factory
     *  レスポンスファクトリーインスタンス
     */
    public function __construct(
        ResponseFactoryInterface $factory,
        RouteCollector $routeCollector
    ){
        $this->responseFactory  = $factory;
        $this->routeCollector   = $routeCollector;
    }

    /**
     * ルート名からURLを生成する
     *
     * @param   ServerRequestInterface  $request
     *  リクエストインスタンス
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
        ServerRequestInterface $request,
        string $route,
        array $parameters = []
    ): UriInterface{
        if(!$this->routeCollector instanceof RouteCollector){
            throw new \LogicException;
        }

        return Uri::newInstance(
            $request->getUri()->getScheme(),
            $request->getUri()->getUserInfo(),
            $request->getUri()->getHost(),
            $request->getUri()->getPort(),
            $this->routeCollector->reverseRouter($route)->createPath($parameters),
            "",
            ""
        );
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
        return $this->responseFactory->createResponse($code, $reasonPhrase);
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

    /**
     * ルート名からリダイレクトレスポンスを生成する
     *
     * @param   ServerRequestInterface  $request
     *  リクエストインスタンス
     * @param   string  $route
     *  ルート名
     * @param   mixed[] $parameters
     *  パラメータの配列
     * @param   int $status
     *  レスポンスステータス
     * @param   bool    $absolute
     *  リダイレクト先URIを絶対パスで指定するか
     *
     * @return  RedirectResponse
     */
    protected function redirectToRoute(
        ServerRequestInterface $request,
        string $route,
        array $parameters = [],
        int $status = 302,
        bool $absolute = true
    ): ResponseInterface{
        throw new \LogicException("未実装");
        return $this->redirect(
            $this->generateUrl($request, $route, $parameters),
            $status,
            $absolute
        );
    }
}