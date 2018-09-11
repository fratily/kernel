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
namespace Fratily\Tests\Kernel\Controller;

use Fratily\Kernel\Controller\ControllerResolver;
use Fratily\Kernel\Controller\Exception;
use Doctrine\Common\Annotations\AnnotationReader;


/**
 *
 */
class ControllerResolverTest extends \PHPUnit\Framework\TestCase{

    /**
     * @var AnnotationReader
     */
    private $reader;

    public function setup(){
        $this->reader   = new AnnotationReader();
    }

    /**
     * @dataProvider    providerGetRouteName
     */
    public function testGetRouteName($expected, $className, $methodName, $baseName){
        $resolver   = new ControllerResolver($this->reader, $baseName);
        $class      = $this->createMock(\ReflectionClass::class);
        $method     = $this->createMock(\ReflectionMethod::class);

        $class->method("getName")->willReturn($className);
        $method->method("getName")->willReturn($methodName);
        $method->method("getDeclaringClass")->willReturn($class);

        $this->assertSame($expected, $resolver->getRouteName($method));
    }

    public function providerGetRouteName(){
        return [
            [
                "fratily_tests_kernel_controller_sample_sample:actionMethod",
                Sample\SampleController::class,
                "actionMethod",
                "",
            ],
            [
                "sample:actionMethod",
                Sample\SampleController::class,
                "actionMethod",
                "Fratily\\Tests\Kernel\\Controller\\Sample\\",
            ],
        ];
    }
}