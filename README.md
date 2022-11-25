# thinkphp6.0权限扩展
The ThinkPHP6 Auth Package
- 本插件经thinkphp官方推荐认证扩展，请放心使用
- 开发者基本礼仪，star一下
## 安装
> composer require xuxchen/sns-auth

## 兼容版本
- thinkphp 5.0/5.1/6.0

## 配置
```
// auth配置  自定义数据表位置在 ./config/auth.php里面,安装扩展会自动生成。如果没有的话建议手动建立下。
//下面的数据表映射需要与表对应上
[
    'auth_on'           => 1, // 权限开关
    'auth_type'         => 1, // 认证方式，1为实时认证；2为登录认证。
    'auth_role'         => 't_auth_role', // 用户角色数据
    'auth_role_access'  => 't_auth_role_access', // 用户-角色关系
    'auth_rule'         => 't_auth_rule', // 权限规则
    'auth_user'         => 'user', // 用户信息表,主键自增字段为id
],
```

### 导入数据表
> `t_` 为自定义的数据表前缀

```

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
  `component` varchar(255) DEFAULT NULL,
  `target` char(20) DEFAULT NULL,
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '备注',
  `sort` int(11) DEFAULT '50' COMMENT '排序',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：为1正常，为0禁用',
  `note` varchar(50) DEFAULT NULL,
  `create_time` int(10) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(10) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(10) DEFAULT NULL COMMENT '删除时间',
  `create_by` varchar(20) CHARACTER SET utf8mb4 DEFAULT NULL COMMENT '创建者',
  `update_by` varchar(20) CHARACTER SET utf8mb4 DEFAULT NULL COMMENT '更新者',
  `version` int(11) DEFAULT '0' COMMENT '乐观锁',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=216 DEFAULT CHARSET=utf8 COMMENT='权限规则';

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
```

## 原理
Auth权限认证是按规则进行认证。
在数据库中我们有 

- 规则表（t_auth_rule） 
- 用户组表(t_auth_user) 
- 用户组明显表（t_auth_user_role）

我们在规则表中定义权限规则， 在用户组表中定义每个用户组有哪些权限规则，在用户组明显表中定义用户所属的用户组。 

下面举例说明：

我们要判断用户是否有显示一个操作按钮的权限， 首先定义一个规则， 在规则表中添加一个名为 show_button 的规则。 然后在用户组表添加一个用户组，定义这个用户组有show_button 的权限规则（t_auth_group表中rules字段存得时规则ID，多个以逗号隔开）， 然后在用户组明细表定义 UID 为1 的用户 属于刚才这个的这个用户组。 

## 使用
判断权限方法
```
// 引入类库
use think\xuxchen\Auth;

// 获取auth实例
$auth = Auth::instance();

// 检测权限
if($auth->check('show_button',1)){// 第一个参数是规则名称,第二个参数是用户UID
	//有显示操作按钮的权限
}else{
	//没有显示操作按钮的权限
}
```

Auth类也可以对节点进行认证，我们只要将规则名称，定义为节点名称就行了。 
可以在公共控制器Base中定义_initialize方法
```
<?php
use think\Controller;
use think\xuxchen\Auth;
class Base extends Controller
{
    public function _initialize()
	{
		$controller = request()->controller();
		$action = request()->action();
		$auth = new Auth();
		if(!$auth->check($controller . '-' . $action, session('uid'))){
			$this->error('你没有权限访问');
		}
    }
 }
```
这时候我们可以在数据库中添加的节点规则， 格式为： “控制器名称-方法名称”

Auth 类 还可以多个规则一起认证 如： 
```
$auth->check('rule1,rule2',uid); 
```
表示 认证用户只要有rule1的权限或rule2的权限，只要有一个规则的权限，认证返回结果就为true 即认证通过。 默认多个权限的关系是 “or” 关系，也就是说多个权限中，只要有个权限通过则通过。 我们也可以定义为 “and” 关系
```
$auth->check('rule1,rule2',uid,'and'); 
```
第三个参数指定为"and" 表示多个规则以and关系进行认证， 这时候多个规则同时通过认证才有权限。只要一个规则没有权限则就会返回false。

Auth认证，一个用户可以属于多个用户组。 比如我们对 show_button这个规则进行认证， 用户A 同时属于 用户组1 和用户组2 两个用户组 ， 用户组1 没有show_button 规则权限， 但如果用户组2 有show_button 规则权限，则一样会权限认证通过。 
```
$auth->getGroups(uid)
```
通过上面代码，可以获得用户所属的所有用户组，方便我们在网站上面显示。

Auth类还可以按用户属性进行判断权限， 比如
按照用户积分进行判断， 假设我们的用户表 (t_members) 有字段 score 记录了用户积分。 
我在规则表添加规则时，定义规则表的condition 字段，condition字段是规则条件，默认为空 表示没有附加条件，用户组中只有规则 就通过认证。
如果定义了 condition字段，用户组中有规则不一定能通过认证，程序还会判断是否满足附加条件。
比如我们添加几条规则： 

> `name`字段：grade1 `condition`字段：{score}<100 <br/>
> `name`字段：grade2 `condition`字段：{score}>100 and {score}<200<br/>
> `name`字段：grade3 `condition`字段：{score}>200 and {score}<300

这里 `{score}` 表示 `t_members` 表 中字段 `score` 的值。 

那么这时候 

> $auth->check('grade1', uid) 是判断用户积分是不是0-100<br/>
> $auth->check('grade2', uid) 判断用户积分是不是在100-200<br/>
> $auth->check('grade3', uid) 判断用户积分是不是在200-300

