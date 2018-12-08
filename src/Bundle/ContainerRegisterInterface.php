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

use Fratily\Container\ContainerFactory;

/**
 *
 */
interface ContainerRegisterInterface{

    /**
     * サービスコンテナを登録する
     *
     * @param   ContainerFactory    $factory
     *  アプリケーション
     *
     * @return  void
     */
    public function containerRegister(ContainerFactory $factory): void;
}