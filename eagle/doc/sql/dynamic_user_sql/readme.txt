user库 动态生成表


如： priminister平台相关的表，只有在用户绑定新账号成功的时候，才会在对应的user库生成相关的表。  当前这个目录是放置sql文件 priminister.sql，该文件内容就是 相关的表的生成sql语句。为了避免同一个table生成2次而导致报错，这里的生成sql
必须是 “create table if not exist” 开头。


后台任务必须在第二个参数传入puid ，前端任务不需传入puid