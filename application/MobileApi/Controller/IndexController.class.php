<?php
namespace MobileApi\Controller;

use Common\Controller\ApibaseController;

class IndexController extends ApibaseController
{

    protected $interfaces = array();

    function __construct()
    {

//        echo sp_get_host().C("TMPL_PARSE_STRING.__UPLOAD__");exit;
        parent::__construct();
        //echo TMPL_PATH;
        $token = '';

        if (get_user_with_token(session('token'))) {
            $token = session('token');
        }

        $this->interfaces = array(
            /**
             * 首页相关的接口
             */
            /**/
            array(
                'name' => "首页相关",
                'tag' => "",
                'description' => "",
                'paths' => array(
                    '/index.php?g=MobileApi&m=home&a=index' => array(
                        'summary' => "首页",
                        'parameters' => array(

                            (new Parameter(array(
                                'name' => 'p',
                                'description' => '当前页码',
                                'type' => 'number',
                                'required' => false,
                                'value' => 1
                            )))->toArray(),

                            (new Parameter(array(
                                'name' => 'area_tag_id',
                                'description' => '地域标签的ID',
                                'type' => 'number',
                                'required' => false,
                                'value' => 1
                            )))->toArray(),
                        ),
                        'method' => 'get',
                    ),
                    '/index.php?g=MobileApi&m=home&a=area_tags' => array(
                        'summary' => "地域标签",
                        'parameters' => array(
                            (new Parameter(array(
                                'name' => 'p',
                                'description' => '当前页码',
                                'type' => 'number',
                                'required' => false,
                                'value' => 1
                            )))->toArray(),
                        ),
                        'method' => 'get',
                    )
                )
            ),
            /**/
            /**
             * 文章列表相关的接口：
             * 按分类搜索、关键字搜索...
             */
            array(
                'name' => "文章相关",
                'tag' => "",
                'description' => "",
                'paths' => array(
                    '/index.php?g=MobileApi&m=category&a=index' => array(
                        'summary' => "所有分类",
                        'parameters' => array(),
                        'method' => 'get',

                    ),
                    '/index.php?g=MobileApi&m=list&a=index' => array(
                        'summary' => "分类查找",
                        'parameters' => array(
                            (new Parameter(array(
                                'name' => 'cid',
                                'description' => '分类ID',
                                'type' => 'text',
                                'required' => true,
                                'value' => 45
                            )))->toArray(),
                            (new Parameter(array(
                                'name' => 'p',
                                'description' => '当前页码',
                                'type' => 'number',
                                'required' => false,
                                'value' => 1
                            )))->toArray(),
                            (new Parameter(array(
                                'name' => 'area_tag_id',
                                'description' => '地域标签的ID',
                                'type' => 'number',
                                'required' => false,
                                'value' => 1
                            )))->toArray(),
                        ),
                        'method' => 'get',
                    ),
                    '/index.php?g=MobileApi&m=list&a=magazines' => array(
                        'summary' => "杂志接口",
                        'parameters' => array(
                            (new Parameter(array(
                                'name' => 'p',
                                'description' => '当前页码',
                                'type' => 'number',
                                'required' => false,
                                'value' => 1
                            )))->toArray(),
                        ),
                        'method' => 'get',
                    ),
                    '/index.php?g=MobileApi&m=list&a=search' => array(
                        'summary' => "关键字搜索",
                        'parameters' => array(
                            (new Parameter(array(
                                'name' => 'keywords',
                                'description' => '关键字',
                                'type' => 'text',
                                'required' => true
                            )))->toArray(),
                            (new Parameter(array(
                                'name' => 'p',
                                'description' => '当前页码',
                                'type' => 'number',
                                'required' => false,
                                'value' => 1
                            )))->toArray(),
                            (new Parameter(array(
                                'name' => 'area_tag_id',
                                'description' => '地域标签的ID',
                                'type' => 'number',
                                'required' => false,
                                'value' => 1
                            )))->toArray(),
                        ),
                        'method' => 'get',

                    ),
                    '/index.php?g=MobileApi&m=article&a=detail' => array(
                        'summary' => "文章内页（详情页）",
                        'parameters' => array(
                            (new Parameter(array(
                                'name' => 'id',
                                'description' => '文章ID',
                                'type' => 'number',
                                'required' => true,
                                'value' => 33247
                            )))->toArray(),
                            (new Parameter(array(
                                'name' => 'token',
                                'description' => '已登录凭证。如果用户已登录则带上。',
                                'type' => 'text',
                                'required' => false,
                                'value' => $token
                            )))->toArray()
                        ),
                        'method' => 'get',

                    ),
                    '/index.php?g=MobileApi&m=article&a=add_favorite' => array(
                        'summary' => "收藏文章",
                        'parameters' => array(
                            (new Parameter(array(
                                'name' => 'token',
                                'description' => '已登录凭证',
                                'type' => 'text',
                                'required' => true,
                                'value' => $token
                            )))->toArray(),
                            (new Parameter(array(
                                'name' => 'post_id',
                                'description' => '文章ID',
                                'type' => 'number',
                                'required' => true,
                                'value' => 33247
                            )))->toArray(),
                        ),
                        'method' => 'post',

                    ),
                    '/index.php?g=MobileApi&m=article&a=remove_favorite' => array(
                        'summary' => "取消收藏",
                        'parameters' => array(
                            (new Parameter(array(
                                'name' => 'token',
                                'description' => '已登录凭证',
                                'type' => 'text',
                                'required' => true,
                                'value' => $token
                            )))->toArray(),
                            (new Parameter(array(
                                'name' => 'post_id',
                                'description' => '文章ID',
                                'type' => 'number',
                                'required' => true,
                                'value' => ''
                            )))->toArray(),
                        ),
                        'method' => 'post',

                    ),
                    '/index.php?g=MobileApi&m=comment&a=index' => array(
                        'summary' => "文章对应的评论",
                        'parameters' => array(
                            (new Parameter(array(
                                'name' => 'post_id',
                                'description' => '文章ID',
                                'type' => 'number',
                                'required' => true,
                                'value' => 33247
                            )))->toArray(),
                            (new Parameter(array(
                                'name' => 'token',
                                'description' => '已登录凭证',
                                'type' => 'text',
                                'required' => false,
                                'value' => $token
                            )))->toArray(),
                        ),
                        'method' => 'get',

                    ),
                    '/index.php?g=MobileApi&m=comment&a=post' => array(
                        'summary' => "提交一个评论",
                        'parameters' => array(
                            (new Parameter(array(
                                'name' => 'token',
                                'description' => '已登录凭证',
                                'type' => 'text',
                                'required' => true,
                                'value' => $token
                            )))->toArray(),
                            (new Parameter(array(
                                'name' => 'post_id',
                                'description' => '文章ID',
                                'type' => 'number',
                                'required' => true,
                                'value' => 33247
                            )))->toArray(),
                            (new Parameter(array(
                                'name' => 'content',
                                'description' => '评论内容',
                                'type' => 'text',
                                'required' => true,
                                'value' => ''
                            )))->toArray(),
//							(new Parameter(array(
//								'name'=>'to_uid',
//								'description'=>'回复人的ID，即目标评论的uid',
//								'type'=>'number',
//								'required'=>false,
//								'value'=>''
//							)))->toArray(),
//							(new Parameter(array(
//								'name'=>'parent_id',
//								'description'=>'目标评论的id',
//								'type'=>'number',
//								'required'=>false,
//								'value'=>''
//							)))->toArray(),
                        ),
                        'method' => 'post',

                    ),
                    '/index.php?g=MobileApi&m=comment&a=do_like' => array(
                        'summary' => "对某条评论点赞",
                        'parameters' => array(
                            (new Parameter(array(
                                'name' => 'token',
                                'description' => '已登录凭证',
                                'type' => 'text',
                                'required' => true,
                                'value' => $token
                            )))->toArray(),
                            (new Parameter(array(
                                'name' => 'id',
                                'description' => '评论ID',
                                'type' => 'number',
                                'required' => true,
                                'value' => ''
                            )))->toArray(),
                        ),
                        'method' => 'post',

                    ),
                )
            ),

            /**
             * 用户中心相关的接口
             * 用户注册、用户登录、我的收藏、我的消息
             */
            array(
                'name' => "我的",
                'tag' => "",
                'description' => "",
                'paths' => array(
                    '/index.php?g=MobileApi&m=login&a=register' => array(
                        'summary' => "用户注册",
                        'parameters' => array(
                            (new Parameter(array(
                                'name' => 'username',
                                'description' => '用于登录的账号',
                                'type' => 'text',
                                'required' => true
                            )))->toArray(),
                            (new Parameter(array(
                                'name' => 'password',
                                'description' => '账号的密码',
                                'type' => 'password',
                                'required' => true
                            )))->toArray(),
                            (new Parameter(array(
                                'name' => 'repassword',
                                'description' => '确认的密码',
                                'type' => 'password',
                                'required' => true
                            )))->toArray(),
                        ),
                        'method' => 'post',

                    ),
                    '/index.php?g=MobileApi&m=login&a=index' => array(
                        'summary' => "用户登录",
                        'parameters' => array(
                            (new Parameter(array(
                                'name' => 'username',
                                'description' => '用于登录的账号',
                                'type' => 'text',
                                'required' => true
                            )))->toArray(),
                            (new Parameter(array(
                                'name' => 'password',
                                'description' => '账号的密码',
                                'type' => 'password',
                                'required' => true
                            )))->toArray(),
                        ),
                        'method' => 'post',

                    ),
                    '/index.php?g=MobileApi&m=user&a=favorite' => array(
                        'summary' => "我的收藏",
                        'parameters' => array(
                            (new Parameter(array(
                                'name' => 'token',
                                'description' => '已登录凭证',
                                'type' => 'text',
                                'required' => true,
                                'value' => $token
                            )))->toArray(),
                            (new Parameter(array(
                                'name' => 'p',
                                'description' => '当前页码',
                                'type' => 'number',
                                'required' => false,
                                'value' => 1
                            )))->toArray(),
                        ),
                        'method' => 'get',

                    ),
                    '/index.php?g=MobileApi&m=user&a=comments' => array(
                        'summary' => "我的评论",
                        'parameters' => array(
                            (new Parameter(array(
                                'name' => 'token',
                                'description' => '已登录凭证',
                                'type' => 'text',
                                'required' => true,
                                'value' => $token
                            )))->toArray(),
                            (new Parameter(array(
                                'name' => 'p',
                                'description' => '当前页码',
                                'type' => 'number',
                                'required' => false,
                                'value' => 1
                            )))->toArray(),
                        ),
                        'method' => 'get',
                    ),
                    '/index.php?g=MobileApi&m=user&a=comments_like_users' => array(
                        'summary' => "我的评论的点赞者",
                        'parameters' => array(
                            (new Parameter(array(
                                'name' => 'token',
                                'description' => '已登录凭证',
                                'type' => 'text',
                                'required' => true,
                                'value' => $token
                            )))->toArray(),
                            (new Parameter(array(
                                'name' => 'comment_id',
                                'description' => '评论ID',
                                'type' => 'number',
                                'required' => true,
                                'value' => ''
                            )))->toArray(),
                            (new Parameter(array(
                                'name' => 'p',
                                'description' => '当前页码',
                                'type' => 'number',
                                'required' => false,
                                'value' => 1
                            )))->toArray(),
                        ),
                        'method' => 'get',
                    ),
                    '/index.php?g=MobileApi&m=comment&a=remove' => array(
                        'summary' => "移除评论",
                        'parameters' => array(
                            (new Parameter(array(
                                'name' => 'token',
                                'description' => '已登录凭证',
                                'type' => 'text',
                                'required' => true,
                                'value' => $token
                            )))->toArray(),
                            (new Parameter(array(
                                'name' => 'comment_id',
                                'description' => '评论ID',
                                'type' => 'number',
                                'required' => true,
                                'value' => ''
                            )))->toArray()
                        ),
                        'method' => 'post',
                    ),
                    '/index.php?g=MobileApi&m=user&a=profile' => array(
                        'summary' => "用户基本信息",
                        'parameters' => array(
                            (new Parameter(array(
                                'name' => 'token',
                                'description' => '已登录凭证',
                                'type' => 'text',
                                'required' => true,
                                'value' => $token
                            )))->toArray()
                        ),
                        'method' => 'get',
                    ),
                    '/index.php?g=MobileApi&m=user&a=profile_post' => array(
                        'summary' => "用户资料更新(<i style='font-size:10px;'>后续一些用户属性会以增加参数的形式更新</i>)",
                        'parameters' => array(
                            (new Parameter(array(
                                'name' => 'token',
                                'description' => '已登录凭证',
                                'type' => 'text',
                                'required' => true,
                                'value' => $token
                            )))->toArray(),
                            (new Parameter(array(
                                'name' => 'user_nicename',
                                'description' => '妮称',
                                'type' => 'text',
                                'required' => true,
                                'value' => ''
                            )))->toArray()
                        ),
                        'method' => 'post',
                    ),
                    '/index.php?g=MobileApi&m=user&a=avatar_upload' => array(
                        'summary' => "上传头像",
                        'parameters' => array(
                            (new Parameter(array(
                                'name' => 'token',
                                'description' => '已登录凭证',
                                'type' => 'text',
                                'required' => true,
                                'value' => $token
                            )))->toArray(),
                            (new Parameter(array(
                                'name' => 'img_file',
                                'description' => '图片文件',
                                'type' => 'file',
                                'required' => true,
                                'value' => ''
                            )))->toArray()
                        ),
                        'method' => 'post',
                    ),
                    '/index.php?g=MobileApi&m=user&a=notifications' => array(
                        'summary' => "用户消息",
                        'parameters' => array(
                            (new Parameter(array(
                                'name' => 'token',
                                'description' => '已登录凭证',
                                'type' => 'text',
                                'required' => true,
                                'value' => $token
                            )))->toArray(),
                            (new Parameter(array(
                                'name' => 'p',
                                'description' => '当前页码',
                                'type' => 'number',
                                'required' => false,
                                'value' => 1
                            )))->toArray(),
                        ),
                        'method' => 'get',
                    ),
                    '/index.php?g=MobileApi&m=notification&a=detail' => array(
                        'summary' => "消息的详情内页",
                        'parameters' => array(
                            (new Parameter(array(
                                'name' => 'token',
                                'description' => '已登录凭证',
                                'type' => 'text',
                                'required' => true,
                                'value' => $token
                            )))->toArray(),
                            (new Parameter(array(
                                'name' => 'notification_id',
                                'description' => '消息的ID',
                                'type' => 'number',
                                'required' => true,
                                'value' => 42
                            )))->toArray(),
                        ),
                        'method' => 'get',
                    ),
                    '/index.php?g=MobileApi&m=user&a=post_feedback' => array(
                        'summary' => "用户反馈",
                        'parameters' => array(
                            (new Parameter(array(
                                'name' => 'token',
                                'description' => '已登录凭证',
                                'type' => 'text',
                                'required' => true,
                                'value' => $token
                            )))->toArray(),
                            (new Parameter(array(
                                'name' => 'msg',
                                'description' => '反馈的内容',
                                'type' => 'text',
                                'required' => true,
                                'value' => ''
                            )))->toArray(),
                        ),
                        'method' => 'post',
                    ),
                ),
                /**
                 * 第三方相关
                 */

            ),
             array(
                 'name' => "第三方相关",
                 'tag' => "",
                 'description' => "",
                 'paths' => array(
                     '/index.php?g=MobileApi&m=oauth&a=callback_login_handle' => array(
                         'summary' => "app调第三方登录后回传数据",
                         'parameters' => array(
                             (new Parameter(array(
                                 'name' => 'type',
                                 'description' => '第三方名称',
                                 'type' => 'text',
                                 'required' => true,
                                 'value' => 'google'
                             )))->toArray(),

                             (new Parameter(array(
                                 'name' => 'openid',
                                 'description' => '从第三方获取的openid或者uid',
                                 'type' => 'text',
                                 'required' => true,
                                 'value' => ''
                             )))->toArray(),
                             (new Parameter(array(
                                 'name' => 'name',
                                 'description' => '获取的妮称',
                                 'type' => 'text',
                                 'required' => true,
                                 'value' => ''
                             )))->toArray(),


                             (new Parameter(array(
                                 'name' => 'access_token',
                                 'description' => 'access_token',
                                 'type' => 'text',
                                 'required' => true,
                                 'value' => ''
                             )))->toArray(),
                             (new Parameter(array(
                                 'name' => 'expires_in',
                                 'description' => '单位：秒',
                                 'type' => 'text',
                                 'required' => true,
                                 'value' => ''
                             )))->toArray(),
                             (new Parameter(array(
                                 'name' => 'head_img',
                                 'description' => '获取的头像',
                                 'type' => 'text',
                                 'required' => false,
                                 'value' => ''
                             )))->toArray(),
                         ),
                         'method' => 'post',
                     )
                 )
             ),
        );
    }

    function index()
    {
//		print_r(C());exit;


        $this->assign('interfaces', $this->interfaces);
        $this->display();
    }

    function index2()
    {
        $this->display();
    }

    function interfaces()
    {
        $this->ajaxReturn(array('data' => array('interfaces' => $this->interfaces)));
    }
}

class Parameter
{
    public $name = '';
    public $description = "";
    public $type = 'string';
    public $required = false;
    public $value = '';

    function __construct($values)
    {
        foreach ($values as $pro => $val) {
            if (property_exists($this, $pro)) {
                $this->$pro = $val;
            }
        }
    }

    function toArray()
    {
        return (array)$this;
    }
}