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

use Fratily\Kernel\Bundle\BundleInterface;
use Fratily\Router\Route;

/**
 *
 */
interface ControllerResolverInterface{

    /**
     * コントローラ内の全てのアクションのルート定義を取得する
     *
     * @param   string  $controller
     *  コントローラクラス
     * @param   BundleInterface $bundle
     *  バンドルインスタンス
     *
     * @return  Route[]
     *
     * @throws  \InvalidArgumentException
     * @throws  Exception\ControllerException
     * @throws  Exception\ActionException
     * @throws  Exception\AnnotationException
     */
    public function getRoutes(string $controller, BundleInterface $bundle): array;
}