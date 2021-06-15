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

namespace  app\controller;

use app\BaseController;

use think\App;
use think\facade\Env;
use think\facade\View;

class Index extends BaseController
{
	public function index(){

		// halt(realpath(app_path().'../vendor/topthink/framework/src/think'));
		// halt([app_path(), base_path()]);
		// halt(get_defined_constants(true));
		$info = '<p>本速查表里的类都是think为命名空间的，实例化时省去了 &nbsp;use。用的时候注意。</p><p>本速查表里会有四种方法的调用：</p><p>&nbsp; &nbsp; 公有方法 $class = new Class(); &nbsp;$class-&gt;foo();<br></p><p>&nbsp; &nbsp; 公有静态 Class::foo();<br></p><p>&nbsp; &nbsp; 私有方法 $this-&gt;foo();<br></p><p>&nbsp; &nbsp; 私有静态 self::foo();<br></p><p>关于注释，为了简洁，/** 的单行注释被我改为了 //&nbsp;</p><p><br></p>';
		$info = [
			'name'        => sprintf('ThinkPHP %s 速查表', App::VERSION),
			// 'info'        => base64_encode($info),
			'info'=>$info,
			'description' => 'ThinkPHP Cheat Sheet , Codes , function , methods of laravel framework',
			'author'      => 'yangweijie',
		];
		$classNames = $this->get_core_class();

		// require_once Env::get('extend_path').'/ClassMethodExtractor.php';

		$cme = new \ClassMethodExtractor();


		$generrates = $cme->generate($classNames);
		$chapters = $cme->parseChapter($generrates);
		config('default_return_type', 'html');

		// trace($this->get_core_class());

		// dump($chapters);
		// die;
		return View::fetch('', [
			'info'     => $info,
			'chapters' => $chapters,
		]);
	}

	public function get_core_class(){
		$class_path = realpath(app_path().'../vendor/topthink/framework/src/think');
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
}
