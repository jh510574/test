<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2012 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

/**
 * Think 命令行模式公共函数库
 * @category   Think
 * @package  Common
 * @author   liu21st <liu21st@gmail.com>
 */

// 错误输出
function halt($error) {
  exit($error);
}

// 自定义异常处理
function throw_exception($msg, $type = 'ThinkException', $code = 0) {
  halt($msg);
}

// 浏览器友好的变量输出
function dump($var, $echo = true, $label = null, $strict = true) {
  $label = ($label === null) ? '' : rtrim($label).' ';
  if (!$strict) {
    if (ini_get('html_errors')) {
      $output = print_r($var, true);
      $output = "<pre>".$label.htmlspecialchars($output, ENT_QUOTES)."</pre>";
    } else {
      $output = $label.print_r($var, true);
    }
  } else {
    ob_start();
    var_dump($var);
    $output = ob_get_clean();
    if (!extension_loaded('xdebug')) {
      $output = preg_replace("/\]\=\>\n(\s+)/m", "] => ", $output);
      $output = '<pre>'.$label.htmlspecialchars($output, ENT_QUOTES).'</pre>';
    }
  }
  if ($echo) {
    echo($output);
    return null;
  } else
    return $output;
}

// 区间调试开始
function debug_start($label = '') {
  $GLOBALS[$label]['_beginTime'] = microtime(TRUE);
  if (MEMORY_LIMIT_ON)
    $GLOBALS[$label]['_beginMem'] = memory_get_usage();
}

// 区间调试结束，显示指定标记到当前位置的调试
function debug_end($label = '') {
  $GLOBALS[$label]['_endTime'] = microtime(TRUE);
  echo '<div style="text-align:center;width:100%">Process '.$label.': Times '.number_format($GLOBALS[$label]['_endTime'] - $GLOBALS[$label]['_beginTime'], 6).'s ';
  if (MEMORY_LIMIT_ON) {
    $GLOBALS[$label]['_endMem'] = memory_get_usage();
    echo ' Memories '.number_format(($GLOBALS[$label]['_endMem'] - $GLOBALS[$label]['_beginMem']) / 1024).' k';
  }
  echo '</div>';
}

// 全局缓存设置和读取
function S($name, $value = '', $expire = '', $type = '', $options = null) {
  static $_cache = array();
  alias_import('Cache');
  //取得缓存对象实例
  $cache = Cache::getInstance($type, $options);
  if ('' !== $value) {
    if (is_null($value)) {
      // 删除缓存
      $result = $cache->rm($name);
      if ($result)
        unset($_cache[$type.'_'.$name]);
      return $result;
    } else {
      // 缓存数据
      $cache->set($name, $value, $expire);
      $_cache[$type.'_'.$name] = $value;
    }
    return;
  }
  if (isset($_cache[$type.'_'.$name]))
    return $_cache[$type.'_'.$name];
  // 获取缓存数据
  $value = $cache->get($name);
  $_cache[$type.'_'.$name] = $value;
  return $value;
}

// 快速文件数据读取和保存 针对简单类型数据 字符串、数组
function F($name, $value = '', $path = DATA_PATH) {
  static $_cache = array();
  $filename = $path.$name.'.php';
  if ('' !== $value) {
    if (is_null($value)) {
      // 删除缓存
      return unlink($filename);
    } else {
      // 缓存数据
      $dir = dirname($filename);
      // 目录不存在则创建
      if (!is_dir($dir))
        mkdir($dir);
      return file_put_contents($filename, strip_whitespace("<?php\nreturn ".var_export($value, true).";\n?>"));
    }
  }
  if (isset($_cache[$name]))
    return $_cache[$name];
  // 获取缓存数据
  if (is_file($filename)) {
    $value = include $filename;
    $_cache[$name] = $value;
  } else {
    $value = false;
  }
  return $value;
}

// 取得对象实例 支持调用类的静态方法
function get_instance_of($name, $method = '', $args = array()) {
  static $_instance = array();
  $identify = empty($args) ? $name.$method : $name.$method.to_guid_string($args);
  if (!isset($_instance[$identify])) {
    if (class_exists($name)) {
      $o = new $name();
      if (method_exists($o, $method)) {
        if (!empty($args)) {
          $_instance[$identify] = call_user_func_array(array( & $o, $method), $args);
        } else {
          $_instance[$identify] = $o->$method();
        }
      } else
        $_instance[$identify] = $o;
    } else
      halt(L('_CLASS_NOT_EXIST_').':'.$name);
  }
  return $_instance[$identify];
}

// 根据PHP各种类型变量生成唯一标识号
function to_guid_string($mix) {
  if (is_object($mix) && function_exists('spl_object_hash')) {
    return spl_object_hash($mix);
  }
  elseif(is_resource($mix)) {
    $mix = get_resource_type($mix).strval($mix);
  }
  else {
    $mix = serialize($mix);
  }
  return md5($mix);
}

// 加载扩展配置文件
function load_ext_file() {
  // 加载自定义外部文件
  if (C('LOAD_EXT_FILE')) {
    $files = explode(',', C('LOAD_EXT_FILE'));
    foreach($files as $file) {
      $file = COMMON_PATH.$file.'.php';
      if (is_file($file))
        include $file;
    }
  }
  // 加载自定义的动态配置文件
  if (C('LOAD_EXT_CONFIG')) {
    $configs = C('LOAD_EXT_CONFIG');
    if (is_string($configs))
      $configs = explode(',', $configs);
    foreach($configs as $key => $config) {
      $file = CONF_PATH.$config.'.php';
      if (is_file($file)) {
        is_numeric($key) ? C(include $file) : C($key, include $file);
      }
    }
  }
}

/**
 * session管理函数
 * @param string|array $name session名称 如果为数组则表示进行session设置
 * @param mixed $value session值
 * @return mixed
 */
function session($name, $value = '') {
  $prefix = C('SESSION_PREFIX');

  if (is_array($name)) { // session初始化 在session_start 之前调用
    if (isset($name['prefix']))
      C('SESSION_PREFIX', $name['prefix']);
    if (C('VAR_SESSION_ID') && isset($_REQUEST[C('VAR_SESSION_ID')])) {
      session_id($_REQUEST[C('VAR_SESSION_ID')]);
    }
    elseif(isset($name['id'])) {
      session_id($name['id']);
    }
    ini_set('session.auto_start', 0);
    if (isset($name['name'])) {
      session_name($name['name']);
    }
    if (isset($name['path'])) {
      session_save_path($name['path']);
    }
    if (isset($name['domain'])) {
      ini_set('session.cookie_domain', $name['domain']);
    }
    if (isset($name['expire'])) {
      ini_set('session.gc_maxlifetime', $name['expire']);
    }
    if (isset($name['use_trans_sid'])) {
      ini_set('session.use_trans_sid', $name['use_trans_sid'] ? 1 : 0);
    }
    if (isset($name['use_cookies'])) {
      ini_set('session.use_cookies', $name['use_cookies'] ? 1 : 0);
    }
    if (isset($name['cache_limiter'])) {
      session_cache_limiter($name['cache_limiter']);
    }
    if (isset($name['cache_expire'])) {
      session_cache_expire($name['cache_expire']);
    }
    if (isset($name['type'])) {
      C('SESSION_TYPE', $name['type']);
    }
    if (C('SESSION_TYPE')) { // 读取session驱动
      $class = 'Session'.ucwords(strtolower(C('SESSION_TYPE')));
      // 检查驱动类
      if (require_cache(EXTEND_PATH.'Driver/Session/'.$class.'.class.php')) {
        $hander = new $class();
        $hander->execute();
      } else {
        // 类没有定义
        throw_exception(L('_CLASS_NOT_EXIST_').': '.$class);
      }
    }
    // 启动session
    if (C('SESSION_AUTO_START')) {
      session_start();
    }
  }
  elseif('' === $value) {
    if (0 === strpos($name, '[')) { // session 操作
      if ('[pause]' == $name) { // 暂停session
        session_write_close();
      }
      elseif('[start]' == $name) { // 启动session
        session_start();
      }
      elseif('[destroy]' == $name) { // 销毁session
        $_SESSION = array();
        session_unset();
        session_destroy();
      }
      elseif('[regenerate]' == $name) { // 重新生成id
        session_regenerate_id();
      }
    }
    elseif(0 === strpos($name, '?')) { // 检查session
      $name = substr($name, 1);
      if ($prefix) {
        return isset($_SESSION[$prefix][$name]);
      } else {
        return isset($_SESSION[$name]);
      }
    }
    elseif(is_null($name)) { // 清空session
      if ($prefix) {
        unset($_SESSION[$prefix]);
      } else {
        $_SESSION = array();
      }
    }
    elseif($prefix) { // 获取session
      return isset($_SESSION[$prefix][$name]) ? $_SESSION[$prefix][$name] : null;
    }
    else {
      return isset($_SESSION[$name]) ? $_SESSION[$name] : null;
    }
  }
  elseif(is_null($value)) { // 删除session
    if ($prefix) {
      unset($_SESSION[$prefix][$name]);
    } else {
      unset($_SESSION[$name]);
    }
  }
  else { // 设置session
    if ($prefix) {
      if (!is_array($_SESSION[$prefix])) {
        $_SESSION[$prefix] = array();
      }
      $_SESSION[$prefix][$name] = $value;
    } else {
      $_SESSION[$name] = $value;
    }
  }
}

/**
 * XML编码
 * @param mixed $data 数据
 * @param string $encoding 数据编码
 * @param string $root 根节点名
 * @return string
 */
function xml_encode($data, $encoding = 'utf-8', $root = 'think') {
  $xml = '<?xml version="1.0" encoding="'.$encoding.'"?>';
  $xml .= '<'.$root.'>';
  $xml .= data_to_xml($data);
  $xml .= '</'.$root.'>';
  return $xml;
}

/**
 * 数据XML编码
 * @param mixed $data 数据
 * @return string
 */
function data_to_xml($data) {
  $xml = '';
  foreach($data as $key => $val) {
    is_numeric($key) && $key = "item id=\"$key\"";
    $xml .= "<$key>";
    $xml .= (is_array($val) || is_object($val)) ? data_to_xml($val) : $val;
    list($key, ) = explode(' ', $key);
    $xml .= "</$key>";
  }
  return $xml;
}


// 以16进制逐字节打印出来
function print_hex($val, $print=true){
  $ret = '';
  $eax = unpack('H*', $val);
  if(count($eax)==1 && array_key_exists(1, $eax)){
    $eax = strtoupper($eax[1]);
    $eax = chunk_split($eax, 2, ',');
    $ret = rtrim($eax, ',');
  }
  if($print){
    dump($ret);
  }else{
    return $ret;
  }
}
?>