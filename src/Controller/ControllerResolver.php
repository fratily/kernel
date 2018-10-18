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
use Fratily\Router\Annotation\Route;
use Doctrine\Common\Annotations\AnnotationReader;

/**
 *
 */
class ControllerResolver implements ControllerResolverInterface{

    /**
     * @var AnnotationReader
     */
    private $reader;

    /**
     * Constructor
     *
     * @param   AnnotationReader    $reader
     * @param   string  $baseName
     */
    public function __construct(AnnotationReader $reader){
        $this->reader   = $reader;

        if(!class_exists(Route::class, true)){
            // AnnotationReaderがオートロードを行わないので、先にロードしておく必要がある
            throw new \LogicException();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutes(
        string $controller,
        string $prefix,
        string $baseNameSpace
    ): array{
        $this->isController($baseNameSpace, $controller, true);

        $class  = new \ReflectionClass($controller);
        $result = [];
        $parent = $this->getParentRouteAnnotation($class);

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

            if(null !== $route){
                if(
                    $method->isStatic()
                    || $method->isAbstract()
                    || 0 === strpos($method->getName(), "__")
                ){
                    throw new Exception\ActionException(
                        "Method '{$class->getName()}::{$method->getName()}()' can not be used as action."
                    );
                }

                if(null !== $parent){
                    $route->setParent($parent);
                }

                $route  = $route->cerateRoute();

                if(null === $route->getName()){
                    $route  = $route->withName(
                        $this->getRouteName($prefix, $baseNameSpace, $method)
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
     * クラスがコントローラーとして使用可能か確認する
     *
     * @param   string  $baseNameSpace
     *  コントローラークラスのベースとなるネームスペース
     * @param   string  $class
     *  確認対象クラス名
     * @param   bool    $throw
     *  もしtrueが設定された場合、コントローラとして利用できない場合に
     *  例外をスローする。
     *
     * @return  bool
     *  コントローラとして使用可能であればtrue、それ以外の場合はfalseを返す
     *
     * @throws  \InvalidArgumentException
     * @throws  Exception\ControllerException
     */
    protected function isController(string $baseNameSpace, string $class, bool $throw = true){
        try{
            // is exists?
            if(!class_exists($class)){
                throw new \InvalidArgumentException(
                    "Class '{$class}' not found."
                );
            }

            $ref    = new \ReflectionClass($class);

            // is instantiable?
            if(!$ref->isInstantiable()){
                throw new \InvalidArgumentException(
                    "Class '{$ref->getName()}' is not instantiable."
                );
            }

            // must implements ControllerInterface
            if(!is_subclass_of($class, ControllerInterface::class)){
                $parent = AbstractController::class;
                throw new Exception\ControllerException(
                    "Controller must inherit {$parent}."
                    . " But {$ref->getName()} dose not inherit it."
                );
            }

            if(0 !== strpos($ref->getName(), $baseNameSpace)){
                throw new Exception\ControllerException(
                    "Controller class namespace must begin with '{$baseNameSpace}'"
                    . " But the namespace of {$ref->getName()}"
                    . "dose not begin with '{$baseNameSpace}'"
                );
            }

            // end Controller
            if("Controller" !== substr($ref->getShortName(), -10)){
                throw new Exception\ControllerException(
                    "The name of the controller class must end with 'Controller'."
                    . " But {$ref->getName()} does not end with 'Controller'."
                );
            }
        }catch(\Exception $e){
            if($throw){
                throw $e;
            }

            return false;
        }

        return true;
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
     * @param   string  $prefix
     *  ルート名プレフィックス
     * @param   string  $baseNameSpace
     *  コントローラークラスのベースとなるネームスペース
     * @param   \ReflectionMethod   $method
     *  アクションメソッドのリフレクションインスタンス
     *
     * @return  string
     */
    protected function getRouteName(
        string $prefix,
        string $baseNameSpace,
        \ReflectionMethod $method
    ){
        $this->isController(
            $baseNameSpace,
            $method->getDeclaringClass()->getName(),
            true
        );


        $class  = $method->getDeclaringClass()->getName();
        $name   = $prefix . "\\" . substr($class, strlen($baseNameSpace), -10);

        return
            implode("_", array_map("lcfirst", explode("\\", $name)))
            . ":"
            . $method->getName()
        ;
    }
}