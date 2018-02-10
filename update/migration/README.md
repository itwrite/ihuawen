#以下是数据导入的流程

#
# 1、导入文章分类
category => terms （PS:已完成，可跳过）

#
# 2、导入用户（包括普通用户和管理员）
user => users （PS:已完成，可跳过）

3、导入文章
append_posts => posts （追加方式）//article => posts （重置方式）

4、更新文章地域划分
separate_by_area

5、更新部份老版本图片问题
update_imgs

