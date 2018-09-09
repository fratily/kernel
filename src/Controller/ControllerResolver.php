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

use Fratily\Router\Annotation\Route;
use Doctrine\Common\Annotations\AnnotationReader;

/**
 *
 */
class ControllerResolver{

    /**
     * @var AnnotationReader
     */
    private $reader;

    /**
     * @var string
     */
    private $baseName;

    /**
     * Constructor
     *
     * @param   AnnotationReader    $reader
     * @param   string  $baseName
     */
    public function __construct(AnnotationReader $reader, string $baseName = ""){
        $this->reader   = $reader;
        $this->baseName = $baseName;

        if(!class_exists(Route::class, true)){
            // AnnotationReaderがオートロードを行わないので、先にロードしておく必要がある
            throw new \LogicException();
        }
    }

    /**
     * コントローラ内の全てのアクションのルート定義を取得する
     *
     * @param   string  $controller
     *  コントローラクラス
     *
     * @return  \Fratily\Router\Route
     *
     * @throws  \InvalidArgumentException
     * @throws  Exception\AnnotationException
     */
    public function getRoutes(string $controller){
        if(!class_exists($controller)){
            throw new \InvalidArgumentException(
                "Class '{$controller}' not found."
            );
        }

        $class  = new \ReflectionClass($controller);
        $result = [];

        if(
            $this->baseName !== ""
            && 0 !== strpos($class->getName(), $this->baseName)
        ){
            throw new \InvalidArgumentException(
                "Class {$class->getName()} is not recognized as a controller, "
                . "because its name does not begin with {$this->baseName}."
            );
        }

        if(!$class->isInstantiable()){
            throw new \InvalidArgumentException(
                "Class '{$class->getName()}' is not instantiable."
            );
        }

        $parent = $this->getParentRouteAnnotation($class);

        foreach($class->getMethods(\ReflectionMethod::IS_PUBLIC) as $method){
            if(
                $method->isStatic()
                || $method->isAbstract()
                || 0 === strpos($method->getName(), "__")
            ){
                throw new Exception\AnnotationException(
                    "Method '{$class->getName()}::{$method->getName()}()' can not be used as action."
                );
            }

            if(null !== $route){
                $route = $this->reader->getMethodAnnotation($method, Route::class);

                if(null !== $parent){
                    $route->setParent($parent);
                }

                $route  = $route->cerateRoute();

                if(null === $route->getName()){
                    $route  = $route->withName(
                        $this->getRouteName($class, $method)
                    );
                }

                $result[]   = $route->withData(
                    [
                        "action"    => [
                            "class"     => $class->getName(),
                            "method"    => $method->getName(),
                        ],
                    ]
                );
            }
        }

        return $result;
    }

    /**
     * アクションメソッドの親Routeアノテーションを取得する
     *
     * @param   \ReflectionClass    $current
     *  取得対象コントローラクラスのリフレクションインスタンス
     *
     * @return  Route|null
     *
     * @throws  Controller\Exception\AnnotationException
     */
    protected function getParentRouteAnnotation(\ReflectionClass $current){
        $result     = null;

        do{
            try{
                $route  = $this->reader->getClassAnnotation($current, Route::class);
            }catch(\Exception $e){
                throw new Exception\AnnotationException(
                    "Annotation error occurred in {$controller}. ({$e->getMessage()})",
                    $e->getCode(),
                    $e
                );
            }

            if(null !== $route){
                if(null === $result){
                    $result = $route;
                }else{
                    $result->setParent($route);
                }
            }
        }while(false !== ($current = $current->getParentClass()));

        return $result;
    }

    /**
     * アクションメソッドのルート名を取得する
     *
     * @param   \ReflectionClass    $class
     *  コントローラークラスのリフレクションインスタンス
     * @param   \ReflectionMethod   $method
     *  アクションメソッドのリフレクションインスタンス
     *
     * @return  string
     */
    public function getRouteName(\ReflectionClass $class, \ReflectionMethod $method){
        if($this->baseName !== "" && 0 !== strpos($class->getName(), $this->baseName)){
            throw new \InvalidArgumentException(
                "Class {$class->getName()} is not recognized as a controller, "
                . "because its name does not begin with {$this->baseName}."
            );
        }

        return
            implode(
                "_",
                explode(
                    "\\",
                    strtolower(
                        substr(
                            $class->getName(),
                            strlen($this->baseName)
                        )
                    )
                )
            )
            . ":"
            . strtolower($method->getName())
        ;
    }
}