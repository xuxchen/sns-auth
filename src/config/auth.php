<?php
/**
 *
 */
return[
    'auth_on'           => 1, // 权限开关
    'auth_type'         => 1, // 认证方式，1为实时认证；2为登录认证。
    'auth_role'         => 'auth_role', // 用户角色数据不带前缀表名
    'auth_role_access'  => 'auth_role_access', // 用户-角色关系不带前缀表名
    'auth_rule'         => 'auth_rule', // 权限规则不带前缀表名
    'auth_user'         => 'user', // 用户信息表不带前缀表名,主键自增字段为id
];