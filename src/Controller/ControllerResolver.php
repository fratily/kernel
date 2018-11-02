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
use Fratily\Router\Annotation\Route;
use Doctrine\Common\Annotations\AnnotationReader;

/**
 *
 */
class ControllerResolver{

    /**
     * @var Kernel
     */
    private $kernel;

    /**
     * @var AnnotationReader
     */
    private $reader;

    /**
     * Constructor
     *
     * @param   Kernel  $kernel
     *  カーネル
     * @param   AnnotationReader    $reader
     *  アノテーションリーダー
     */
    public function __construct(
        Kernel $kernel,
        AnnotationReader $reader
    ){
        $this->kernel   = $kernel;
        $this->reader   = $reader;

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
     * @return  \Fratily\Router\Route[]
     *
     * @throws  Exception\ControllerException
     * @throws  Exception\ActionException
     * @throws  Exception\AnnotationException
     */
    public function getRoutes(string $controller): array{
        if(!class_exists($controller)){
            throw new \InvalidArgumentException();
        }

        $result = [];
        $class  = new \ReflectionClass($controller);
        $parent = null;

        $this->isController($class, true);

        try{
            $parent = $this->reader->getClassAnnotation($class, Route::class);
        }catch(\Exception $e){
            throw new Exception\AnnotationException(
                "Annotation error occurred in {$controller}. ({$e->getMessage()})",
                $e->getCode(),
                $e
            );
        }

        foreach($class->getMethods(\ReflectionMethod::IS_PUBLIC) as $method){
            try{
                $route = $this->reader->getMethodAnnotation($method, Route::class);
            }catch(\Exception $e){
                throw new Exception\AnnotationException(
                    "Annotation error occurred in {$controller}::{$method}(). ({$e->getMessage()})",
                    $e->getCode(),
                    $e
                );
            }

            if(null === $route){
                continue;
            }

            $this->isAction($method, true);

            if(null !== $parent){
                $route->setParent($parent);
            }

            $route  = $route->cerateRoute();

            if(null === $route->getName()){
                $route  = $route->withName($this->getRouteName($method));
            }

            $result[]   = $route->withData([
                "action"    => [
                    "class"     => $class->getName(),
                    "method"    => $method->getName(),
                ],
            ]);
        }

        return $result;
    }

    /**
     * クラスがコントローラーの定義を満たしているか確認する
     *
     * @param   \ReflectionClass    $class
     *  確認対象クラス
     * @param   bool    $throw
     *  もしtrueが設定された場合、コントローラとして利用できない場合に
     *  例外をスローする。
     *
     * @return  bool
     *  コントローラとして使用可能であればtrue、それ以外の場合はfalseを返す
     *
     * @throws  Exception\ControllerException
     */
    protected function isController(\ReflectionClass $class, bool $throw = true){
        $result     = false;
        $namespace  = $this->kernel->getConfig()->getNameSpace() . "\\Controller\\";

        try{
            if(!$class->isInstantiable()){
                throw new \InvalidArgumentException(
                    "Class '{$class->getName()}' is not instantiable."
                );
            }

            if(0 !== strpos($class->getName(), $namespace)){
                throw new Exception\ControllerException(
                    "Class '{$class}' is not a controller. The controller class"
                    . " must be included in the namespace '{$namespace}'."
                );
            }

            if("Controller" !== substr($class->getShortName(), -10)){
                throw new Exception\ControllerException(
                    "The name of the controller class must end with 'Controller'."
                    . " But {$class->getName()} does not end with 'Controller'."
                );
            }

            $result = true;
        }catch(\Exception $e){
            if($throw){
                throw $e;
            }
        }

        return $result;
    }

    /**
     * メソッドがアクションの定義を満たしているか確認する
     *
     * @param   \ReflectionMethod   $method
     *  確認対象メソッド
     * @param   bool    $throw
     *  もしtrueが設定された場合、コントローラとして利用できない場合に
     *  例外をスローする。
     *
     * @return  bool
     *  アクションとして使用可能であればtrue、それ以外の場合はfalseを返す
     *
     * @throws  Exception\ControllerException
     */
    protected function isAction(\ReflectionMethod $method, bool $throw = true){
        $result = false;

        try{
            if(!$method->isPublic()){
                throw new Exception\ActionException();
            }

            if($method->isAbstract()){
                throw new Exception\ActionException();
            }

            if($method->isStatic()){
                throw new Exception\ActionException();
            }

            if(0 === strpos($method->getName(), "__")){
                throw new Exception\ActionException();
            }

            $result = true;
        }catch(Exception\ActionException $e){
            if($throw){
                throw $e;
            }
        }

        return $result;
    }

    /**
     * アクションメソッドのルート名を取得する
     *
     * @param   string  $prefix
     *  ルート名プレフィックス
     * @param   string  $baseNameSpace
     *  コントローラークラスのベースとなるネームスペース
     * @param   \ReflectionMethod   $method
     *  アクションメソッドのリフレクションインスタンス
     *
     * @return  string
     */
    private function getRouteName(\ReflectionMethod $method){
        $class  = $method->getDeclaringClass()->getName();
        $name   = substr(
            $class,
            strlen($this->kernel->getConfig()->getNameSpace() . "\\"),
            -10 // Controller
        );

        $controller = implode("_", array_map("strtolower", explode("\\", $name)));
        $action     = $method->getName();

        if("Action" === substr($action, -6)){
            $action = substr($action, 0, -6);
        }

        return "{$controller}:{$action}";
    }
}