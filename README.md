# 一个简易的PHP MVC框架 

### 安装
1. 安装需求
>* php >= 7.1（如果用Security.php文件） >= 7.0
>* extension: pdo_mysql openssl
>* database: mysql
>* web server: nginx

2. 设置web服务器    
见nginx.conf

3. 安装框架    
shell执行
```sh
  git clone git@github.com:Alrash/Aria.git
  cd Aria
  composer install
```


### 目录格式及作用
```
----webRoot/                    #web根目录
    |----Aria/                  #框架目录
         |----algorithm/        #所用所用算法目录
         |----base/             #基础框架实现目录
         |----db/               #数据库连接/操作目录
         |----helpers/          #助手文件目录
         |----security/         #简单安全类目录，如对称加密及字符串散列
         |----stack/            #对扩展
         |----verification/     #模型规则检验类目录
         |----Aria.php          #项目环境类（第一个执行的类文件）
    |----common/                #通用文件目录（设置文件、资源文件、模型、视图）
         |----config/           #通用设置文件目录
              |----classMap.php #类映射设置（可被项目内设置文件追加，已有项被覆盖）
              |----config.php   #数据库、session、cookie设置（可被项目内设置文件追加，已有项被覆盖）
              |----message.php  #规则检测错误提示（可被项目内设置文件追加，已有项被覆盖，可运行时被内部数组替换）
              |----route.php    #路由设置文件（若项目内本文件存在，则被替换）
              |----setting.php  #项目宏设置（设置项均已使用）
              |----viewMap.php  #视图渲染文件组设置
         |----model/            #通用模型文件目录
         |----resources/        #通用资源文件目录
         |----view/             #通用视图文件目录
              |----Json/        #预置Json格式视图渲染文件(renderAsJson使用)
                   |----index.php
              |----XML/         #预置XML格式视图渲染文件(renderAsXML使用)
                   |----index.php
    |----runtime/               #运行时文件存放目录（框架内只存放日志文件）
         |----logs/             #日志文件目录
    |----vender/                #第三方库目录
    |
    |----env01/                 #项目环境01目录
         |----application/
              |----controller/  #项目控制器目录
              |----model/       #项目模型目录
              |----view/        #项目视图目录
         |----config/           #项目配置文件目录
              |----setting.php  #项目环境宏定义设置文件（已定义可不用更改，创建新项目时，可直接复制）
         |----public/           #对外目录（web服务器，服务根目录）
              |----resources/   #项目资源目录
              |----index.php    #web服务入口文件
```

----
### 剩余说用说明    
[Wiki](https://github.com/Alrash/Aria/wiki)    
**注：部分思想来源 yii2.0 thinkphp**    

----
### 样例测试    
使用提供nignx.conf(须更改root变量)
```url
http://127.0.0.1
#测试，显示单页(index/index)
http://127.0.0.1/index.htm
#测试，同上（顺带测试url设置：后缀htm，使用美化，路由规则/<action:[^/]+> => /index/<action>）
http://127.0.0.1/index
#测试，url设置：后缀htm，使用美化，路由规则deny => /error(顺带测试使用视图组渲染)
http://127.0.0.1/abc.htm
#测试，url设置：后缀htm，使用美化，路由规则error => /error/action(顺带测试控制器重定向)
```
