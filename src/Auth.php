<?php

namespace think\xuxchen;

use think\facade\Db;
use think\facade\Config;
use think\facade\Session;
use think\facade\Request;

/**
 * 权限认证类
 * 功能特性：
 * 1，是对规则进行认证，不是对节点进行认证。用户可以把节点当作规则名称实现对节点进行认证。
 *      $auth=new Auth();  $auth->check('规则名称','用户id')
 * 2，可以同时对多条规则进行认证，并设置多条规则的关系（or或者and）
 *      $auth=new Auth();  $auth->check('规则1,规则2','用户id','and')
 *      第三个参数为and时表示，用户需要同时具有规则1和规则2的权限。 当第三个参数为or时，表示用户值需要具备其中一个条件即可。默认为or
 * 3，一个用户可以属于多个角色(auth_role_access表 定义了用户所属角色)。我们需要设置每个角色拥有哪些规则(auth_role 定义了角色权限)
 *
 * 4，支持规则表达式。
 *      在auth_rule 表中定义一条规则时，如果type为1， condition字段就可以定义规则表达式。 如定义{score}>5  and {score}<100  表示用户的分数在5-100之间时这条规则才会通过。
 */
//数据库 请手动创建下sql
/*

-- ----------------------------
-- Table structure for t_auth_role
-- ----------------------------
DROP TABLE IF EXISTS `t_auth_role`;
CREATE TABLE `t_auth_role` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `name` char(100) NOT NULL DEFAULT '',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `sort` int(11) DEFAULT '50',
  `create_time` int(10) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(10) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(10) DEFAULT NULL COMMENT '删除时间',
  `create_by` varchar(20) CHARACTER SET utf8mb4 DEFAULT NULL COMMENT '创建者',
  `update_by` varchar(20) CHARACTER SET utf8mb4 DEFAULT NULL COMMENT '更新者',
  `version` int(11) DEFAULT '0' COMMENT '乐观锁',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8 COMMENT='角色\n';

-- ----------------------------
-- Table structure for t_auth_rule
-- ----------------------------
DROP TABLE IF EXISTS `t_auth_rule`;
CREATE TABLE `t_auth_rule` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `title` char(30) NOT NULL DEFAULT '' COMMENT '规则中文名称',
  `module` varchar(20) DEFAULT NULL COMMENT '所属模型',
  `type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '类型(1=模块|2=导航|3=菜单|4=节点)',
  `permission` varchar(100) DEFAULT '1' COMMENT '规则唯一标识',
  `condition` char(100) NOT NULL DEFAULT '' COMMENT '规则表达式，为空表示存在就验证，不为空表示按照条件验证',
  `pid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '父ID',
  `icon` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '图标',
  `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '规则URL',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '备注',
  `sort` int(11) DEFAULT '50' COMMENT '排序',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：为1正常，为0禁用',
  `create_time` int(10) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(10) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(10) DEFAULT NULL COMMENT '删除时间',
  `create_by` varchar(20) CHARACTER SET utf8mb4 DEFAULT NULL COMMENT '创建者',
  `update_by` varchar(20) CHARACTER SET utf8mb4 DEFAULT NULL COMMENT '更新者',
  `version` int(11) DEFAULT '0' COMMENT '乐观锁',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=20 DEFAULT CHARSET=utf8 COMMENT='权限规则\n';

-- ----------------------------
-- Table structure for t_auth_rule_access
-- ----------------------------
DROP TABLE IF EXISTS `t_auth_rule_access`;
CREATE TABLE `t_auth_rule_access` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '类型(1=角色|2=用户)',
  `type_id` int(11) NOT NULL COMMENT '类型主键',
  `rule_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=50 DEFAULT CHARSET=utf8 COMMENT='角色-规则关系表\n';

-- ----------------------------
-- Table structure for t_auth_user_role
-- ----------------------------
DROP TABLE IF EXISTS `t_auth_user_role`;
CREATE TABLE `t_auth_user_role` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(11) unsigned NOT NULL,
  `role_id` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid_role_id` (`uid`,`role_id`),
  KEY `uid` (`uid`),
  KEY `role_id` (`role_id`)
) ENGINE=MyISAM AUTO_INCREMENT=29 DEFAULT CHARSET=utf8 COMMENT='用户-角色关系表';
*/

class Auth
{
    /**
     * var object 对象实例
     */
    protected static $instance;
    //默认配置
    protected $config = [
        'auth_on' => 1, // 权限开关
        'auth_type' => 1, // 认证方式，1为实时认证；2为登录认证。
        'auth_role' => 'auth_role', // 角色数据表名
        'auth_user_role' => 'auth_user_role', // 用户-角色关系表
        'auth_rule' => 'auth_rule', // 权限规则表
        'auth_user' => 'auth_user', // 用户信息表
        'auth_rule_access' => 'auth_rule_access', // 角色-规则关系表
    ];

    /**
     * 类架构函数
     * Auth constructor.
     */
    public function __construct()
    {
        //可设置配置项 auth, 此配置项为数组。
        if ($auth = Config::get('auth')) {
            $this->config = array_merge($this->config, $auth);
        }
    }

    /**
     * 初始化
     * access public
     * @param array $options 参数
     * return \think\Request
     */
    public static function instance($options = [])
    {
        if (is_null(self::$instance)) {
            self::$instance = new static($options);
        }
        return self::$instance;
    }

    /**
     * 检查权限
     * @param $name string|array  需要验证的规则列表,支持逗号分隔的权限规则或索引数组
     * @param $uid  int           认证用户的id
     * @param int $type 认证类型
     * @param string $mode 执行check的模式
     * @param string $relation 如果为 'or' 表示满足任一条规则即通过验证;如果为 'and'则表示需满足所有规则才能通过验证
     * return bool               通过验证返回true;失败返回false
     */
    public function check($name, $uid, $type = 1, $mode = 'url', $relation = 'or')
    {
        if (!$this->config['auth_on']) {
            return true;
        }
        // 获取用户需要验证的所有有效规则列表
        $authList = $this->getAuthList($uid, $type);
        if (is_string($name)) {
            $name = strtolower($name);
            if (strpos($name, ',') !== false) {
                $name = explode(',', $name);
            } else {
                $name = [$name];
            }
        }
        $list = []; //保存验证通过的规则名
        if ('url' == $mode) {
            $REQUEST = unserialize(strtolower(serialize(Request::param())));
        }

        foreach ($authList as $avo) {
            $auth = $avo['permission'];
            if ('url' == $mode) {
                $query = preg_replace('/^.+\?/U', '', $auth);
                if ($query == $auth){
                    $auth = $avo['url'];
                    if (in_array($auth, $name)) {
                        $list[] = $auth;
                    }
                }else{
                    parse_str($query, $param); //解析规则中的param
                    $intersect = array_intersect_assoc($REQUEST, $param);
                    $auth = preg_replace('/\?.*$/U', '', $auth);
                    if (in_array($auth, $name) && $intersect == $param) {
                        //如果节点相符且url参数满足
                        $list[] = $auth;
                    }
                }
            } else {
                if (in_array($auth, $name)) {
                    $list[] = $auth;
                }
            }
        }
        if ('or' == $relation && !empty($list)) {
            return true;
        }
        $diff = array_diff($name, $list);
        if ('and' == $relation && empty($diff)) {
            return true;
        }
        return false;
    }

    /**
     * 根据用户id获取角色,返回值为数组
     * @param  $uid int     用户id
     * return array       用户所属的角色 array(
     *     array('uid'=>'用户id','role_id'=>'角色id','title'=>'角色名称','rules'=>'角色拥有的规则id,多个,号隔开'),
     *     ...)
     */
    public function getRoles($uid)
    {
        static $roles = [];
        if (isset($roles[$uid])) {
            return $roles[$uid];
        }
        // 转换表名
        $auth_user_role = $this->config['auth_user_role'];
        $auth_role = $this->config['auth_role'];
        // 执行查询
        $user_roles = Db::view($auth_user_role, 'uid,role_id')
            ->view($auth_role, 'name', "{$auth_user_role}.role_id={$auth_role}.id", 'LEFT')
            ->where("{$auth_user_role}.uid='{$uid}' and {$auth_role}.status='1'")
            ->select();
        $roles[$uid] = $user_roles ?: [];
        return $roles[$uid];
    }

    /**
     * 获得权限列表
     * @param integer $uid 用户id
     * return array
     */
    protected function getAuthList($uid)
    {
        static $_authList = []; //保存用户验证通过的权限列表
        if (isset($_authList[$uid])) {
            return $_authList[$uid];
        }
        if (2 == $this->config['auth_type'] && Session::has('_auth_list_' . $uid)) {
            return Session::get('_auth_list_' . $uid);
        }
        //读取用户所属角色
        $roles = $this->getRoles($uid);
        $rids = [];
        $ids = []; //保存用户所属角色设置的所有权限规则id

        foreach ($roles as $g) {
            $rids[] = $g['role_id'];
        }

        $map1 = [];
        if ($rids) {
            $map1 = [
                ['type', '=', 1],
                ['type_id', 'in', $rids],
            ];
        }
        $map2 = [
            ['type', '=', 2],
            ['type_id', '=', $uid],
        ];
        $map = [$map1, $map2];
        $roleRules = Db::name($this->config['auth_rule_access'])->where(function ($query) use ($map) {
            $query->whereOr($map);
        })->select();
        foreach ($roleRules as $g) {
            $ids[] = $g['rule_id'];
        }
        $ids = array_unique($ids);
        if (empty($ids)) {
            $_authList[$uid] = [];
            return [];
        }
        $map = [
            ['id', 'in', $ids],
            ['status', '=', 1],
        ];
        //读取角色所有权限规则
        $rules = Db::name($this->config['auth_rule'])->where($map)->field('id,condition,title,type,pid,icon,url,permission')->select();
        //循环规则，判断结果。
        $authList = []; //
        foreach ($rules as $rule) {
            if (!empty($rule['condition'])) {
                //根据condition进行验证
                $user = $this->getUserInfo($uid); //获取用户信息,一维数组
                $command = preg_replace('/\{(\w*?)\}/', '$user[\'\\1\']', $rule['condition']);
                //dump($command); //debug
                @(eval('$condition=(' . $command . ');'));
                if ($condition) {
                    $authList[] = $rule;
                }
            } else {
                //只要存在就记录
                $authList[] = $rule;
            }
        }
        $_authList[$uid] = $authList;
        if (2 == $this->config['auth_type']) {
            //规则列表结果保存到session
            Session::set('_auth_list_' . $uid, $authList);
        }
        return $authList;
    }

    /**
     * 获取菜单权限列表树
     * @param $uid
     * @return array
     */
    public function getPermissionMenuList($uid)
    {
        $rules = $this->getAuthList($uid);
        $menuList = [];
        foreach ($rules as $val) {
            if ($val['type'] != 4) {
                $menuList[] = $val;
            }
        }

        return $this->listToTree($menuList, 'id', 'pid', 'children');
    }

    /**
     * 获取所有菜单
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getAllMenuList()
    {
        $menuList = Db::name($this->config['auth_rule'])->where('delete_time', null)->field('id,condition,title,type,pid,icon,url,permission,status,remark,sort')->order("id asc")->select()->toArray();
        return $menuList;
    }


    /**
     * 根据角色/用户获取权限规则，配置的规则为选中
     * @param $type
     * @param $typeId
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getRulePermission($type,$typeId = 0)
    {
        if ($typeId <= 0) {
            return [];
        }
        $map = [
            ['status', '=', 1],
        ];
        //读取角色所有权限规则
        $rules = Db::name($this->config['auth_rule'])->where($map)->field('id,condition,title,type,pid,icon,url,permission')->select()->toArray();

        // 获取有权限的菜单
        $where = [
            ['type', '=', $type],
            ['type_id', '=', $typeId],
        ];
        $permissionList = Db::name($this->config['auth_rule_access'])->where($where)->order("rule_id asc")->select()->toArray();
        $checkList = [];
        if ($permissionList) {
            $checkList = array_column($permissionList, "rule_id");
        }
        $list = [];
        if (!empty($rules)) {
            foreach ($rules as $val) {
                $data = [];
                $data['id'] = $val['id'];
                $data['name'] = trim($val['title']);
                $data['pId'] = $val['pid'];
                if (in_array($val['id'], $checkList)) {
                    $data['checked'] = true;
                } else {
                    $data['checked'] = false;
                }
                $data['open'] = true;
                $list[] = $data;
            }
        }
        return $list;
    }

    /**
     * 获得用户资料,根据自己的情况读取数据库
     */
    function getUserInfo($uid)
    {
        static $userinfo = [];
        $user = Db::name($this->config['auth_user']);
        // 获取用户表主键
        $_pk = is_string($user->getPk()) ? $user->getPk() : 'uid';
        if (!isset($userinfo[$uid])) {
            $userinfo[$uid] = $user->where($_pk, $uid)->find();
        }
        return $userinfo[$uid];
    }

    /**
     * 根据uid获取角色名称
     * @param $uid
     * @return mixed|string
     */
    function getRole($uid)
    {
        try {
            $auth_user_role = Db::name($this->config['auth_user_role'])->where('uid', $uid)->find();
            $title = Db::name($this->config['auth_role'])->where('id', $auth_user_role['role_id'])->value('title');
            return $title;
        } catch (\Exception $e) {
            return '此用户未授予角色';
        }
    }

    /**
     * 授予用户权限
     */
    public function setRole($uid, $role_id)
    {
        $res = Db::name('auth_user_role')
            ->where('uid', $uid)
            ->update(['role_id' => $role_id]);
        return true;
    }

    public function setRoles($uid, array $roleIds)
    {
        // 删除现有的角色
        Db::name($this->config['auth_user_role'])->where('uid',$uid)->delete();
        if ($roleIds){
            foreach ($roleIds as $val) {
                $data = [
                    'uid' => $uid,
                    'role_id' => $val,
                ];
                Db::name($this->config['auth_user_role'])->save($data);
            }
        }
        return true;
    }

    /**
     * 授予权限
     * @param $type
     * @param $typeId
     * @param $ruleIds
     * @return void
     * @throws \think\db\exception\DbException
     */
    public function setRulePermission($type,$typeId,$ruleIds){
        $roleRuleModel = Db::name($this->config['auth_rule_access']);
        // 删除现有的权限
        $where = [
            ['type', '=', $type],
            ['type_id', '=', $typeId],
        ];
        $roleRuleModel->where($where)->delete();
        if ($ruleIds) {
            $itemArr = explode(',', $ruleIds);
            foreach ($itemArr as $val) {
                $data = [
                    'type' => $type,
                    'type_id' => $typeId,
                    'rule_id' => $val,
                ];
                Db::name($this->config['auth_rule_access'])->save($data);
            }
        }
    }

    function listToTree($list, $pk = 'id', $pid = 'pid', $child = 'children', $root = 0)
    {
        $tree = array();
        if (is_array($list)) {
            $refer = array();
            foreach ($list as $key => $data) {
                $refer[$data[$pk]] = &$list[$key];
            }

            foreach ($list as $key => $data) {
                // 判断是否存在parent
                $parentId = $data[$pid];

                if ($root == $parentId) {
                    $tree[$data[$pk]] = &$list[$key];
                } else {
                    if (isset($refer[$parentId])) {
                        $parent = &$refer[$parentId];
                        $parent[$child][$data[$pk]] = &$list[$key];

                        $parent[$child] = array_values($parent[$child]);
                    }
                }
            }
        }

        return $tree;
    }
}
