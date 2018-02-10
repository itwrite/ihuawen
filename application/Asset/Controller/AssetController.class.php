<?php
namespace Asset\Controller;

use Common\Controller\AdminbaseController;

class AssetController extends AdminbaseController {

    function _initialize() {
    	$adminid=sp_get_current_admin_id();
    	$userid=sp_get_current_userid();
    	if(empty($adminid) && empty($userid)){
    		exit("非法上传！");
    	}
    }
    
    
    // 文件上传
    public function plupload(){
        $upload_setting=sp_get_upload_setting();
        
        $filetypes=array(
            'image'=>array('title'=>'Image files','extensions'=>$upload_setting['image']['extensions']),
            'video'=>array('title'=>'Video files','extensions'=>$upload_setting['video']['extensions']),
            'audio'=>array('title'=>'Audio files','extensions'=>$upload_setting['audio']['extensions']),
            'file'=>array('title'=>'Custom files','extensions'=>$upload_setting['file']['extensions'])
        );
        
        $image_extensions=explode(',', $upload_setting['image']['extensions']);
        
        if (IS_POST) {
            $all_allowed_exts=array();
            foreach ($filetypes as $mfiletype){
                array_push($all_allowed_exts, $mfiletype['extensions']);
            }
            $all_allowed_exts=implode(',', $all_allowed_exts);
            $all_allowed_exts=explode(',', $all_allowed_exts);
            $all_allowed_exts=array_unique($all_allowed_exts);
            
            $file_extension=sp_get_file_extension($_FILES['file']['name']);
            $upload_max_filesize=$upload_setting['upload_max_filesize'][$file_extension];
            $upload_max_filesize=empty($upload_max_filesize)?2097152:$upload_max_filesize;//默认2M
            
            $app=I('post.app/s','');
            if(!in_array($app, C('MODULE_ALLOW_LIST'))){
                $app='default';
            }else{
                $app= strtolower($app);
            }
            
			$savepath=$app.'/'.date('Ymd').'/';
            //上传处理类
            $config=array(
            		'rootPath' => './'.C("UPLOADPATH"),
            		'savePath' => $savepath,
            		'maxSize' => $upload_max_filesize,
            		'saveName'   =>    array('uniqid',''),
            		'exts'       =>    $all_allowed_exts,
            		'autoSub'    =>    false,
            );
			$upload = new \Think\Upload($config);// 
			$info=$upload->upload();
            //开始上传
            if ($info) {
                $oriName = $_FILES['file']['name'];
                //写入附件数据库信息
                $first=array_shift($info);

                $upload_dir = $config['rootPath'].$savepath;

                $thumbs =  $this->_save_thumbs($first,$upload_dir,$savepath);
                //上传成功
                if(!empty($first['url'])){
                	$url=$first['url'];
                	$storage_setting=sp_get_cmf_settings('storage');
                	$qiniu_setting=$storage_setting['Qiniu']['setting'];
                	$url=preg_replace('/^https/', $qiniu_setting['protocol'], $url);
                	$url=preg_replace('/^http/', $qiniu_setting['protocol'], $url);
                	
                	$preview_url=$url;
                	
                	if(in_array($file_extension, $image_extensions)){
                	    if(C('FILE_UPLOAD_TYPE')=='Qiniu' && $qiniu_setting['enable_picture_protect']){
                	        $preview_url = $url.$qiniu_setting['style_separator'].$qiniu_setting['styles']['thumbnail300x300'];
                	        $url= $url.$qiniu_setting['style_separator'].$qiniu_setting['styles']['watermark'];
                	    }
                	}else{
                	    $preview_url='';
                	    $url=sp_get_file_download_url($first['savepath'].$first['savename'],3600*24*365*50);//过期时间设置为50年
                	}
                	
                }else{
                	$url=sp_get_host().C("TMPL_PARSE_STRING.__UPLOAD__").$savepath.$first['savename'];
                	$preview_url=$url;
                }
                $filepath = $savepath.$first['savename'];

                if($thumbs){
                    $filepath = $thumbs['big_url'];
                }
                
				$this->ajaxReturn(array('preview_url'=>$preview_url,'filepath'=>$filepath,'url'=>$url,'name'=>$oriName,'status'=>1,'message'=>'success'));
            } else {
                $this->ajaxReturn(array('name'=>'','status'=>0,'message'=>$upload->getError()));
            }
        } else {
            $filetype = I('get.filetype/s','image');
            $mime_type=array();
            if(array_key_exists($filetype, $filetypes)){
                $mime_type=$filetypes[$filetype];
            }else{
                $this->error('上传文件类型配置错误！');
            }
            
            $multi=I('get.multi',0,'intval');
            $app=I('get.app/s','');
            $upload_max_filesize=$upload_setting[$filetype]['upload_max_filesize'];
            $this->assign('extensions',$upload_setting[$filetype]['extensions']);
            $this->assign('upload_max_filesize',$upload_max_filesize);
            $this->assign('upload_max_filesize_mb',intval($upload_max_filesize/1024));
            $this->assign('mime_type',json_encode($mime_type));
            $this->assign('multi',$multi);
            $this->assign('app',$app);
            $this->display(':plupload');
        }
    }
    
    public function upload_post_image(){
        $upload_setting=sp_get_upload_setting();
        $all_allowed_exts = $image_extensions=explode(',', $upload_setting['image']['extensions']);

        $session_admin_id=session('ADMIN_ID');
        $post_id = I('get.post_id',0,'intval');

        if (IS_POST) {
            $file_extension=sp_get_file_extension($_FILES['file']['name']);
            $upload_max_filesize=$upload_setting['upload_max_filesize'][$file_extension];
            $upload_max_filesize=empty($upload_max_filesize)?2097152:$upload_max_filesize;//默认2M

            $savepath='posts/'.date('Ymd').'/';
            //上传处理类
            $config=array(
                'rootPath' => './'.C("UPLOADPATH"),
                'savePath' => $savepath,
                'maxSize' => $upload_max_filesize,
                'saveName'   =>    array('uniqid',''),
                'exts'       =>    $all_allowed_exts,
                'autoSub'    =>    false,
            );
            $upload = new \Think\Upload($config);//
            $info=$upload->upload();
            //开始上传
            if ($info) {
                //上传成功

                $img_1 = $info['img_1'];

                $upload_dir = $config['rootPath'].$savepath;

                $this->_save_image($img_1,$post_id,$upload_dir,$savepath);
                $this->ajaxReturn(array('status'=>1,'message'=>'success'));
            } else {
                $this->ajaxReturn(array('status'=>0,'message'=>$upload->getError()));
            }
        }
    }

    private function _save_image($img_1,$post_id,$upload_dir,$savepath){
        if ($img_1) {
            //上传成功

            /**
             * 缩略图
             */
            import("Think.image");
            $image_obj = new \Think\Image();

            $url= $savepath.$img_1['savename'];
            $filepath = $upload_dir.$img_1['savename'];

            //获取图像信息
            $img_info = getimagesize($filepath);

            //检测图像合法性
            if(false === $img_info || (IMAGETYPE_GIF === $img_info[2] && empty($img_info['bits']))){
                return false;
                exit;
            }

            //设置图像信息
            $img_info_arr = array(
                'width'  => $img_info[0],
                'height' => $img_info[1],
                'type'   => image_type_to_extension($img_info[2], false),
                'mime'   => $img_info['mime'],
            );

            //big thumb image
            //4:3
            $w = 700;
            $img_name = "big_{$w}_".$img_1['savename'];
            $big_url = $savepath.$img_name;
            $big_path = $upload_dir.$img_name;
            $img = $image_obj->open($filepath)->thumb($w,$w*0.75)->save($big_path);

            //thumb image
            //4:3
            $w = 300;
            $img_name = "thumb_{$w}_".$img_1['savename'];
            $thumb_url = $savepath.$img_name;
            $thumb_path = $upload_dir.$img_name;
            $img = $image_obj->open($filepath)->thumb($w,$w*0.75)->save($thumb_path);

            //small image
            //4:3
            $w = 100;
            $img_name = "small_{$w}_".$img_1['savename'];
            $small_url = $savepath.$img_name;
            $small_path = $upload_dir.$img_name;
            $img = $image_obj->open($filepath)->thumb($w,$w*0.75)->save($small_path);

            $arr = array();
            $arr['url'] = $url;
            $arr['file_name'] = $img_1['name'];
            $arr['big_url'] = $big_url;
            $arr['thumb_url'] = $thumb_url;
            $arr['small_url'] = $small_url;

            $arr['width'] = $img_info_arr['width'];
            $arr['height'] = $img_info_arr['height'];

            $arr['post_id'] = (int)$post_id;
            $arr['user_id'] = (int)session('ADMIN_ID');

            M("PostsImages")->add($arr);

            return true;
        }
        return false;
    }

    /**
     * @param $img_1
     * @param $upload_dir
     * @param $savepath
     * @return array|bool
     */
    private function _save_thumbs($img_1,$upload_dir,$savepath){
        if ($img_1) {
            //上传成功

            /**
             * 缩略图
             */
            import("Think.image");
            $image_obj = new \Think\Image();

            $url= $savepath.$img_1['savename'];
            $filepath = $upload_dir.$img_1['savename'];

            //获取图像信息
            $img_info = getimagesize($filepath);

            //检测图像合法性
            if(false === $img_info || (IMAGETYPE_GIF === $img_info[2] && empty($img_info['bits']))){
                return false;
                exit;
            }

            //设置图像信息
            $img_info_arr = array(
                'width'  => $img_info[0],
                'height' => $img_info[1],
                'type'   => image_type_to_extension($img_info[2], false),
                'mime'   => $img_info['mime'],
            );

            //big thumb image
            //4:3
            $w = 700;
            $img_name = "big_{$w}_".$img_1['savename'];
            $big_url = $savepath.$img_name;
            $big_path = $upload_dir.$img_name;
            $img = $image_obj->open($filepath)->thumb($w,$w*0.75)->save($big_path);

            //thumb image
            //4:3
            $w = 300;
            $img_name = "thumb_{$w}_".$img_1['savename'];
            $thumb_url = $savepath.$img_name;
            $thumb_path = $upload_dir.$img_name;
            $img = $image_obj->open($filepath)->thumb($w,$w*0.75)->save($thumb_path);

            //small image
            //4:3
            $w = 100;
            $img_name = "small_{$w}_".$img_1['savename'];
            $small_url = $savepath.$img_name;
            $small_path = $upload_dir.$img_name;
            $img = $image_obj->open($filepath)->thumb($w,$w*0.75)->save($small_path);

            $arr = array();
            $arr['url'] = $url;
            $arr['file_name'] = $img_1['name'];
            $arr['big_url'] = $big_url;
            $arr['thumb_url'] = $thumb_url;
            $arr['small_url'] = $small_url;

            $arr['width'] = $img_info_arr['width'];
            $arr['height'] = $img_info_arr['height'];
            return $arr;
        }
        return false;
    }

    public function get_post_images(){
        $session_admin_id=session('ADMIN_ID');
        $post_id = I('request.post_id',0,'intval');

        $list = M('PostsImages')->where(array('post_id'=>$post_id))->select();

        $html = '';
        foreach ($list as $row) {
            $id = $row['id'];
            $big_url = sp_get_asset_upload_path($row['big_url']);
            $html.= '<li id="img_'.$id.'"><img src="'.$big_url.'" /><br/>
<a onclick="post_image_remove('.$id.')" class="btn btn-default btn-xs">移除</a></li>';
        }

        echo $html;
        exit;
    }

    public function del_post_image(){

        $id = I("request.image_id",0);

        $img =  M('PostsImages')->find($id);
        if($img){

            $path = './'.C("UPLOADPATH").$img['url'];
            $big_path = './'.C("UPLOADPATH").$img['big_url'];
            $thumb_path = './'.C("UPLOADPATH").$img['thumb_url'];
            $small_path = './'.C("UPLOADPATH").$img['small_url'];


            /*
             * 仅删除原图，文章中使用的
             *

            if(is_file($big_path)){
                unlink($big_path);
            }

            if(is_file($thumb_path)){
                unlink($thumb_path);
            }

            if(is_file($small_path)){
                unlink($small_path);
            }
            */
            M('PostsImages')->delete($id);
            $this->ajaxReturn(array('img'=>$img,'status'=>1,'message'=>'success'));

        }

        $this->ajaxReturn(array('status'=>1,'message'=>'success'));
    }
}
