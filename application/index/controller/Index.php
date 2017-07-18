<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2017 河源市卓锐科技有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------
// | 开源协议 ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------

namespace plugins\CheatSheet\controller;

use think\Controller;
use plugins\CheatSheet\model\CheatSheet;
use plugins\CheatSheet\model\CheatSheetDetail;
use app\common\controller\Common;
use Crada\Apidoc\Extractor;

class Index extends Common
{
	public function index(){
		$info = [
			'name'        => sprintf('ThinkPHP %s 速查表', THINK_VERSION),
			'info'        => '<p>本速查表里的类都是think为命名空间的，实例化时省去了 &nbsp;use。用的时候注意。</p><p>本速查表里会有四种方法的调用：</p><p>&nbsp; &nbsp; 公有方法 $class = new Class(); &nbsp;$class-&gt;foo();<br></p><p>&nbsp; &nbsp; 公有静态 Class::foo();<br></p><p>&nbsp; &nbsp; 私有方法 $this-&gt;foo();<br></p><p>&nbsp; &nbsp; 私有静态 self::foo();<br></p><p>关于注释，为了简洁，/** 的单行注释被我改为了 //&nbsp;</p><p><br></p>',
			'description' => 'ThinkPHP Cheat Sheet , Codes , function , methods of laravel framework',
			'author'      => 'yangweijie',
		];
		$classNames = $this->get_core_class();
		$generrates = $this->generate($classNames);
		$chapters = $this->parseChapter($generrates);
		config('default_return_type', 'html');

		// trace($this->get_core_class());

		// dump($chapters);
		// die;
		$this->assign('info', $info);
		$this->assign('chapters', $chapters);
		return $this->fetch();
	}

	public function get_core_class(){
		$class_path = CORE_PATH;
		$before_cwd = getcwd();
		chdir($class_path);
		$names = glob('*.php');
		$ret = [];
		foreach ($names as $key => $name) {
			$ret[] = 'think\\'. str_ireplace('.php', '', $name);
		}
		chdir($before_cwd);
		return $ret;
	}

	public function generate($classNames){
		config('default_return_type', 'json');
		// TODO 获取待处理的类命名空间数据
		// TODO 遍历类 反射
		// TODO 获取类的信息 (名称、方法列表)
		// TODO 遍历方法列表， 获取方法的类型 和注释

		$outputs = [];
		foreach ($classNames as $k => $className) {
			$class= new\ReflectionClass($className);
			$key = $class->getShortName();
			// dump($key);
			$outputs[$key] = $this->getClassAnnotation($class);
		}
		return $outputs;
	}

	public function parseChapter($classes){
		$ret = [];
		foreach ($classes as $class_name => $class) {
			$temp = [];
			$temp['name'] = $class_name;
			$content = [];
			if($class['hasPublicMethods']){
				$content[] = "\${$class_name} = new {$class_name}();".PHP_EOL;
			}
			foreach ($class['methods'] as $method_name => $method) {
				$content[] = $method['docComment_formated'];
				switch ($method['type']) {
					case 'public_public':
						$content[] = "{$class_name}->{$method_name}({$method['args_formated']})";
						break;
					case 'public_static':
						$content[] = "{$class_name}::{$method_name}({$method['args_formated']})";
						break;
					case 'private_public':
						$content[] = "\$this->{$method_name}({$method['args_formated']})";
						break;
					case 'private_static':
						$content[] = "self::{$method_name}({$method['args_formated']})";
						break;
					default:
						$content[] = "为支持的方法类型：{$method['type']}";
						break;
				}
				$content[] = PHP_EOL;
			}
			$temp['content'] = implode(PHP_EOL, $content);
			$ret[] = $temp;
		}
		return $ret;
	}

	public function parseMethod($class, $method){
		// doc
		$method = new \ReflectionMethod($class, $method);
		// $method->isFinal() ? ' final' : '',
        // $method->isPublic() ? ' public' : '',
        // $method->isPrivate() ? ' private' : '',
        // $method->isProtected() ? ' protected' : '',
        // $method->isStatic() ? ' static' : '',
		// $method->isConstructor()
		//$method->getDocComment()
		// $method->getParameters()
	}

	// 转换注释风格
	public function parseMethodDoc($doc){
		if(empty($doc)){
			return $doc;
		}
		$doc = str_ireplace('    ','', $doc);
		$lines = explode(PHP_EOL, $doc);
		if($lines > 1){
			return $doc;
		}
		if(stripos('/**', $doc) !== false){
			$arr_doc = explode(' ', $doc);
			$arr_doc[0] = '//';
			array_pop($arr_doc);
			return implode(' ', $arr_doc);
		}
	}

	public function parseParameters($class, $method, $args){
		if($args){
			$args_str = [];
			foreach ($args as $key => $arg) {
				$p = new \ReflectionParameter(array($class, $method), $key);
				if($p->isPassedByReference()){
					$arg_str_new = "&\$".$p->getName();
				}else{
					$arg_str_new = "\$".$p->getName();
				}
				if ($p->isOptional() && $p->isDefaultValueAvailable()) {
					$a_clsss = $class;
					try{
						$defaul = $p->getDefaultValue();
						$arg_str_new .= is_array($defaul) ? ' = '. '[]': ' = '. var_export($defaul, 1);
					}catch(\Exception $e){
						trace($p->isVariadic());
						trace($a_clsss.'/'.$method.'_'.$key);
					}
				}
				$args_str[] = $arg_str_new;
			}
			return implode(', ', $args_str);
		}
		return '';
	}

	public function getClassAnnotation($class){

		$ret = [
			'hasPublicMethods'=>0,
		];
		$methods = $class->getMethods();
		foreach ($methods as $key => $method) {
			$class = $method->class;
			$method_name = $method->name;
			$rm = new \ReflectionMethod($class, $method_name);
			if($rm->isConstructor() || $rm->isDestructor()){
				continue;
			}
			$foo = [];
			$foo['docComment'] = $rm->getDocComment();
			$foo['docComment_formated'] = $this->parseMethodDoc($foo['docComment']);
			$foo['args'] = $rm->getParameters();
			$foo['args_formated'] = $this->parseParameters($class, $method_name, $foo['args']);
			if($rm->isPublic()){
				$type = $rm->isStatic()? 'public_static' : 'public_public';
			}else{
				$type = $rm->isStatic()? 'private_static' : 'private_public';
			}
			if(empty($ret['hasPublicMethods'])){
				$ret['hasPublicMethods'] = stripos($type, '_public') !== false;
			}
			$foo['type'] = $type;
			$ret['methods'][$method_name] = $foo;
		}

		return $ret;
		// $className = 'think\\App';
		// $class = new \ReflectionClass($className);
		// config('default_return_type', 'json');

		// 类名
		// return $class->name;

		// ReflectionClass 实例的一个字符串表示形式
		// return $class->__toString();

		// 同上
		// return \ReflectionClass::export($className, 1);

		// 获取类常量
		// return json_encode($class->getConstants());

		// 获取构造方法
		// return $class->getConstructor();

		// 类名相关
		// var_dump($class->inNamespace());
		// var_dump($class->getName());
		// var_dump($class->getNamespaceName());
		// var_dump($class->getShortName());

		# 文件相关
		// getFileName
		// getExtensionName

		// 属性相关
		// return $class->getDefaultProperties();
		// return $class->getProperties(\ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED);

		// const integer IS_STATIC = 1 ;
		// const integer IS_PUBLIC = 256 ;
		// const integer IS_PROTECTED = 512 ;
		// const integer IS_PRIVATE = 1024 ;

		// return $class->getStaticProperties();

		// 类注释
		// return $class->getDocComment();
	}
}
