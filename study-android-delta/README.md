# APP增量包功能开发

思路简单理一下：

 1. 依赖一个python脚本，主要采用bsdiff4工具包，将2个包进行二进制对比，然后生成1个增量包
 2. 前端提供界面，上传最新包
 3. php获取最近的3个包（当然也可以更多），然后与最新包进行差分（即调用python脚本，生成增量包）

# 文件说明

 1. make_delta.py 就是这个python脚本（当然在使用这个脚本前，你需要pipe bsdiff4）
 2. make_delta.php 就是php调用python的文件。你不需要去运行该php，只是了解下大概的思路就好了，（这也是为什么我放到study仓库，而没有单独启用一个新的仓库）
 3. appdelta.php 是配置文件。在make_delta.php中的rest_config::get('appdelta');中有调用。
 
 经过上面步骤，可以实现php生成增量包的行为，这里有个坑，请使用异步的方式去运行php脚本，因为生成增量包的行为是花时间的。我在项目中测试过，一个12M的apk文件，diff的时间在24S左右。

目前比较有效的php异步模式：cli或者使用swoole拓展。这里不详说。


# 参考资料

 - bsdiff http://www.daemonology.net/bsdiff/
 - python extension http://starship.python.net/crew/atuining/cx_bsdiff/index.html

