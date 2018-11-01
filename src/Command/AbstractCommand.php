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
namespace Fratily\Kernel\Command;

use Fratily\Kernel\BootedKernel;
use Fratily\Kernel\KernelConfiguration;
use Fratily\Container\Container;
use Symfony\Component\Console\Command\Command as BaseCommand;

abstract class Command extends BaseCommand{

    public function __construct(Container $container){

    }
}