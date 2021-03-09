<?php
    /**
     * @class  zzz_menu_newModel
     * @author 난다날아 (sinsy200@gmail.com)
     * @brief  zzz_menu_new 모듈의 model 클래스
     **/

    class zzz_menu_newModel extends zzz_menu_new {

        /**
         * @brief 설정 정보를 구함
         **/
        function getConfig() {
            // 기본 설정을 구한다.
            $config = $this->_getConfig();

            // new 이미지 태그
            $config->new_image_tag = $this->getNewImageTag($config);

            // 댓글 new 이미지 태그
            $config->new_comment_image_tag = $this->getCommentNewImageTag($config);

            return $config;
        }

        /**
         * @brief 설정을 받아옴.
         *
         * @return 환경설정 값이 들어있는 obj.
         *          - use_menu_new : 새글 표시 모듈 사용 여부 (Y/N)
         *          - use_comment : 새 댓글에 대해서 사용 여부 (Y/N)
         *          - duration_new : 새글 표시 시간. 단위 시간. 기본값 24.
         *          - up_new : 하위 메뉴의 새글을 상위 메뉴에도 표시 여부 (Y/N)
         *          - text_new : 메뉴의 text 변수에도 새글 이미지 표시 여부 (Y/N)
         *          - select_module_mode : in : 선택한 모듈은 표시, out : 선택한 모듈은 표시 안함.
         *          - time_check : 표시 시간이 지났는지 계산하기 위한 리눅스 타임 스탬프.
         *          - mid_list2 : 선택한 모듈 배열.
         */
        function _getConfig() {
            // 설정 정보를 받아옴 (module model 객체를 이용)
            $oModuleModel = getModel('module');
            $site_info = Context::get('site_module_info');
            $config = $oModuleModel->getModulePartConfig('zzz_menu_new', $site_info->site_srl);

            // 기본값
            if (!$config->use_menu_new)  $config->use_menu_new = 'Y';
            if (!$config->use_comment)  $config->use_comment = 'N';
            if (!$config->duration_new) $config->duration_new = 24;
            if (!$config->up_new)       $config->up_new = 'N';
            if (!$config->text_new)     $config->text_new = 'N';
            if (!$config->select_module_mode)     $config->select_module_mode = 'in';

            $config->time_check = time() - intVal($config->duration_new) * 60 * 60;

            // mid 목록
            if(empty($config->module_srl_list))
            {
            	$config->mid_list2 = array();
            }
            else
            {
            	$args = new stdClass();
            	$args->module_srls = $config->module_srl_list;
            	$output = executeQueryArray('module.getModulesInfo', $args, array('mid'));
            	if(!$output->toBool())
            	{
            		$config->mid_list2 = array();
            	}
            	else
            	{
            		foreach($output->data as $row)
            		{
            			$config->mid_list2[] = $row->mid;
            		}
            	}
            }

            return $config;
        }

        /**
         * @brief new 이미지 태그
         *
         * 새글 표시 이미지 태그를 반환한다.
         * 새글 이미지를 따로 설정하지 않았을 경우 XE의 기본 이미지를 이용한다.
         *
         * @return 새글 표시 이미지 태그
         **/
        function getNewImageTag($config) {

            if (!empty($config->new_image)) {
                $new_image = sprintf('<img src="%s" alt="new" title="new" style="margin-left:2px;" class="addon_menu_new"/>', $config->new_image);
            }else{
                $path = sprintf('%s%s',getUrl(), 'modules/document/tpl/icons/');
                $new_image = sprintf('<img src="%snew.gif" alt="new" title="new" style="margin-left:2px;" class="addon_menu_new"/>', $path);
            }

            return $new_image;
        }

         /**
         * @brief 댓글 new 이미지 태그
         *
         * 새 댓글 표시 이미지 태그를 반환한다.
         * 새 댓글 이미지를 따로 설정하지 않았을 경우 XE의 기본 이미지를 이용한다.
         *
         * @return 새 댓글 표시 이미지 태그
         **/
        function getCommentNewImageTag($config) {

            if (!empty($config->new_image_comment)) {
                $new_image = sprintf('<img src="%s" alt="new comment" title="new comment" style="margin-left:2px;" class="addon_menu_new_comment"/>', $config->new_image_comment);
            }else{
                $path = sprintf('%s%s',getUrl(), 'modules/document/tpl/icons/');
                $new_image = sprintf('<img src="%supdate.gif" alt="new comment" title="new comment" style="margin-left:2px;" class="addon_menu_new_comment"/>', $path);
            }

            return $new_image;
        }

        /**
         * @brief url에서 mid 추출
         *
         * 메뉴 링크 url에서 mid의 값을 추출한다.
         * 먼저 url 파싱을 통해 쿼리에 mid가 있으면 당연히 그걸 리턴.
         * rewrite 룰에 의한 형식(xe/mid)의 경우 정규식을 이용하여 mid를 추출한 후 리턴.
         *
         * @return url의 mid
         **/
        function getMid($url) {
            // url 파싱
            $url_info = @parse_url($url);
            if (!$url_info) return null;

            // 내부 링크인지 확인
            if ($url_info['host'] && $url_info['host'] != $_SERVER[HTTP_HOST])   return null;

            // url 쿼리에 mid가 있으면 반환
            $url_info['query'] = str_replace('&amp;', '&', $url_info['query']);
            parse_str($url_info['query']);
            if ($mid)   return $mid;

            // rewrite 형식
            $pattern = '/^' .str_replace('/', '\/', getScriptPath()). '([a-zA-Z0-9_]+)\/?$/';
            preg_match($pattern, $url_info['path'], $matches);
            if ($matches[1])    return $matches[1];

            $pattern = '/\.\/([a-zA-Z0-9_]+)\/?$/';
            preg_match($pattern, $url_info['path'], $matches);
            if ($matches[1])    return $matches[1];

            return $url;
        }

        /**
         * @brief url에서 카테고리 번호 추출
         *
         * @return url의 카테고리 번호
         */
        function getCategorySrl($url) {
            // url 파싱
            $url_info = @parse_url($url);
            if (!$url_info) return null;

            // 내부 링크인지 확인
            if ($url_info['host'] && $url_info['host'] != $_SERVER[HTTP_HOST])   return null;

            // url 쿼리에 category_srl이 있으면 반환
            $url_info['query'] = str_replace('&amp;', '&', $url_info['query']);
            parse_str($url_info['query']);
            if ($category)   return $category;

            return null;
        }
    }
?>
