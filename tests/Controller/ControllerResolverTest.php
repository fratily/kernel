<?php
/**
 * FratilyPHP Router
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

        $this->assertSame($expected, $resolver->getRouteName($class, $method));
    }

    public function providerGetRouteName(){
        return [
            [
                "controllerclass:actionmethod",
                "ControllerClass",
                "actionMethod",
                "",
            ],
            [
                "foo_bar_controllerclass:actionmethod",
                "Foo\\Bar\\ControllerClass",
                "actionMethod",
                "",
            ],
            [
                "controllerclass:actionmethod",
                "Foo\\Bar\\ControllerClass",
                "actionMethod",
                "Foo\\Bar\\",
            ],
        ];
    }

    /**
     * @expectedException   \InvalidArgumentException
     */
    public function testGetActionNameThrowException(){
        $resolver   = new ControllerResolver($this->reader, "Foo\\Bar\\");
        $class      = $this->createMock(\ReflectionClass::class);
        $method     = $this->createMock(\ReflectionMethod::class);

        $class->method("getName")->willReturn("Bar\\Baz\\ControllerClass");
        $method->method("getName")->willReturn("actionMethod");

        $resolver->getRouteName($class, $method);
    }

}