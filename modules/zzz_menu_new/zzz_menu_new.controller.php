<?php
    /**
     * @class  zzz_menu_newController
     * @author 난다날아 (sinsy200@gmail.com)
     * @brief  zzz_menu_new 모듈의 controller class
     **/

    define('MENU_NEW_DOCUMENT', 1);
    define('MENU_NEW_COMMENT', 2);

    class zzz_menu_newController extends zzz_menu_new {

        /**
         * @brief 글 작성 후 트리거. 글이 작성된 모듈의 캐시를 재생성한다.
         * @param[in] $obj 작성된 글의 정보
         **/
        function triggerInsertDocument(&$obj) {
            $this->procUpdateCache($obj->module_srl);
            $this->procUpdateCacheCategory($obj->category_srl);

            return new BaseObject();
        }

        /**
         * @brief 댓글 작성 후 트리거. 댓글이 작성된 모듈의 캐시를 재생성한다.
         * @param[in] $obj 작성된 댓글의 정보
         **/
        function triggerInsertComment(&$obj) {
            $this->procUpdateCache($obj->module_srl);

            return new BaseObject();
        }

        /**
         * @brief 글 삭제 후 트리거. 글이 삭제된 모듈의 캐시를 재생성한다.
         * @param[in] obj 삭제된 글의 정보
         **/
        function triggerDeleteDocument(&$obj) {
            $this->procUpdateCache($obj->module_srl);
            $this->procUpdateCacheCategory($obj->category_srl);

            return new BaseObject();
        }

        /**
         * @brief 댓글 삭제 후 트리거. 댓글이 삭제된 모듈의 캐시를 재생성한다.
         * @param[in] $obj 삭제된 댓글의 정보
         **/
        function triggerDeleteComment(&$obj) {
            $this->procUpdateCache($obj->module_srl);

            return new BaseObject();
        }

        /**
         * @brief 게시물 이동 전 트리거.
         * @param[in] $obj 이동되는 게시물의 정보
         *
         * 게시물 이동을 하게 되면 이동 전에 있던 모듈과 이동 후의 모듈 모두 캐시 업데이트가 필요하다.
         * 게시물이 있던 모듈은 트리거를 통해 직접 알 수 없고 트리거를 통해 전달되는 Obj.의 document_srls를 이용하여
         * 본래 모듈의 정보를 알아내는 방법을 사용하였다.
         * 캐시의 생성은 게시물의 이동이 완료된 후 이뤄져야 하는데 게시물의 document_srl을 이용하여
         * 이동 전 모듈의 알아 내는 것은 게시물 이동 전에 가능하다.
         * 그래서 우선 게시물 이동 전에 원래 있던 모듈을 알아낸 후 세션에 담아
         * 게시물 이동 후 세션의 내용을 이용하도록 하였다.
         * 이동 게시물은 여러개일 수 있다. 각 원본은 여러개이지만 이동하는 곳은 하나다.
         */
        function triggerMoveDocumentBefore(&$obj) {
            $oDocumentModel = getModel('document');

            // 원래 게시물이 있던 모듈 정보 구하기
            $module_srls = array();
            $category_srls = array();
            $documents = $oDocumentModel->getDocuments($obj->document_srls);

            foreach ($documents as $document) {
                $module_srls[] = $document->get('module_srl');
                $category_srls[] = $document->get('category_srl');
            }
            array_unique($module_srls);
            array_unique($category_srls);

            // 세션에 저장
            $_SESSION['menu_new_move_module_srls'] = $module_srls;
            $_SESSION['menu_new_move_category_srls'] = $category_srls;

            return new BaseObject();
        }

        /**
         * @brief 게시물 이동 후 트리거
         * @param[in] $obj 이동한 게시물 정보
         */
        function triggerMoveDocumentAfter(&$obj) {
            // 우선 이동하는 모듈의 캐시를 업데이트 한다.
            $this->procUpdateCache($obj->module_srl);
            $this->procUpdateCacheCategory($obj->category_srl);

            // 원래 게시물이 있던 모듈
            $module_srls = $_SESSION['menu_new_move_module_srls'];

            // 원래 게시물이 있던 모듈의 캐시를 업데이트 한다.
            if (is_array($module_srls)) {
                foreach ($module_srls as $module_srl) {
                    $this->procUpdateCache($module_srl);
                }
            }

            // 원래 게시물이 있던 카테고리
            $category_srls = $_SESSION['menu_new_move_category_srls'];

            // 원래 게시물이 있던 카테고리의 캐시를 업데이트 한다.
            if (is_array($category_srls)) {
            foreach ($category_srls as $category_srl) {
                    $this->procUpdateCacheCategory($category_srl);
                }
            }

            return new BaseObject();
        }

        /**
         * @brief 메뉴 캐시에 include 코드 추가
         * @param[in] $menu_srl include 코드를 추가할 메뉴의 menu_srl
         *
         * XE의 메뉴 캐시 파일 제일 마지막에 새글 표시를 위한 include 코드를 추가한다.
         * 메뉴에 새글 표시 모듈은 바로 이 include 문에 의해 proNew() 함수를 실행하며 작동된다.
         **/
        function procMenuInclude($menu_srl) {
            $cache = sprintf("%s/%d.php", $this->menu_cache_path, $menu_srl);
            $content = FileHandler::readFile($cache);
            if (!$content)  return;

            // 이미 include가 추가 되었는지 확인
            $res = strpos($content, 'menu_include.php');
            if ($res)   return;

            // include문 추가
            $content .= '<? @include _XE_PATH_."modules/zzz_menu_new/menu_include.php"; ?>';
            FileHandler::writeFile($cache, $content);
        }

        /**
         * @brief new 적용. 모듈 실행 여부만 확인한 후 _procNew() 함수를 실행한다.
         * @param[out] $menu_list 새글 표시를 적용할 메뉴 정보
         **/
        function procNew(&$menu_list) {
        	if (Context::getResponseMethod() != 'HTML' || Context::get('module') == 'admin') return;

            $oMenuNewModel = getModel('zzz_menu_new');
            $config = $oMenuNewModel->getConfig();
            $site_info = Context::get('site_module_info');
            if ($config->use_menu_new != 'Y')   return;

            $this->_procNew($menu_list, $config, $site_info->site_srl);
        }

        /**
         * @brief new 적용 (재귀)
         * @param[out] $menu_list 새글 표시를 적용할 메뉴 정보
         * @param[in] $config 환경설정 정보
         * @param[in] $site_srl 현재 메뉴가 속한 가상 사이트 번호
         *
         * 메뉴 정보를 재귀적으로 돌면서 새글 표시를 적용한다.
         * 새 글/댓글 여부는 캐시 파일을 이용하여 확인하는데 메뉴 링크에서 mid를 추출하여
         * 해당하는 캐시 파일을 include하여 마지막 글/댓글의 시간을 알아낸다.
         **/
        function _procNew(&$menu_list, &$config, &$site_srl) {
            if (!count($menu_list)) return;

            $is_new = false;
            foreach($menu_list as $menu_srl => $menu_item) {
                $regdate = 0;
                $regdate_com = 0;

                // 하위 메뉴가 있으면 먼저 처리
                $is_sub_new = false;
                if (count($menu_item['list']))
                    $is_sub_new = $this->_procNew($menu_list[$menu_srl]['list'], $config, $site_srl);

                // mid, category_srl 구하기
                $oMenuNewModel = getModel('zzz_menu_new');
                $mid = $oMenuNewModel->getMid($menu_item['url']);
                $category_srl = $oMenuNewModel->getCategorySrl($menu_item['url']);

                // 해당 mid에 새글 표시 사용인지 확인
                $is_use = in_array($mid, $config->mid_list2);
                if ($config->select_module_mode == 'out')   $is_use = !$is_use;
                if (!count($config->mid_list2))    $is_use = true;

                if (!empty($mid) && $is_use) {
                    // 현재 메뉴의 마지막 글 시간
                    // 카테고리 메뉴이면 카테고리를 이용한다.
                    if ($category_srl) {
                        $cache = sprintf("%s/category_doc.%d.php", $this->menu_new_cache_path, $category_srl);
                        @include $cache;
                    }else{
                        $cache = sprintf("%s/%d.%s_doc.php", $this->menu_new_cache_path, $site_srl, $mid);
                        @include $cache;

                        // 현재 메뉴의 마지막 댓글 시간
                        if ($config->use_comment == 'Y') {
                            $cache = sprintf("%s/%d.%s_com.php", $this->menu_new_cache_path, $site_srl, $mid);
                            @include $cache;
                        }
                    }
                }
                // 설정된 시간 이내 새글이 있으면 new 이미지 추가
                if (($config->up_new == 'Y' && $is_sub_new == MENU_NEW_DOCUMENT) || intVal($config->time_check) < intVal($regdate)) {
                    if (!empty($menu_item['link']))   $menu_list[$menu_srl]['link'] .= $config->new_image_tag;
                    if ($config->text_new == 'Y' && !empty($menu_item['text']))   $menu_list[$menu_srl]['text'] .= $config->new_image_tag;
                    $is_new = MENU_NEW_DOCUMENT;
                }

                // 설정된 시간 이내 새 댓글이 있으면 new 이미지 추가
                else if (($config->up_new == 'Y' && $is_sub_new == MENU_NEW_COMMENT) || intVal($config->time_check) < intVal($regdate_com)) {
                    if (!empty($menu_item['link']))   $menu_list[$menu_srl]['link'] .= $config->new_comment_image_tag;
                    if ($config->text_new == 'Y' && !empty($menu_item['text']))   $menu_list[$menu_srl]['text'] .= $config->new_comment_image_tag;

                    // 메뉴 중 하나라도 새글이 없을 때만 새댓글 표시한다.
                    if ($is_new != MENU_NEW_DOCUMENT){
                        $is_new = MENU_NEW_COMMENT;
                    }
                }
            }

            return $is_new;
        }

        /**
         * @brief 캐시 업데이트
         * @param[in] $module_srl 캐시 업데이트할 모듈의 번호
         * @param[in] $site_srl 캐시 업데이트할 모듈이 속한 가상 사이트 번호
         *
         * 캐시파일은 단순히 php코드로 마지막 글/댓글의 작성시간이 '$regdate=시간;'과 같이 작성된다.
         * 파일명은 site_srl.mid_doc.php, site_srl.mid_com.php 형식으로 생성된다.
         * 이렇게 한 이유는 XE의 메뉴 정보의 링크를 통해 mid를 추출할 수 있기 때문이다.
         * site_srl을 붙이는 이유는 mid는 가상 사이트마다 scope를 가지기 때문이다.
         * 그리고 플래닛 모듈의 경우 특별히 따로 처리하였는데 메뉴에는 회원 개인 플래닛을
         * 링크하는 것보다는 전체 플래닛을 링크하기 때문에 플래닛 전체에 대한 처리를 추가하였다.
         **/
        function procUpdateCache($module_srl, $site_srl = -1) {
            // 메뉴에 새글 표시 사용중인지 확인
            $oMenuNewModel = getModel('zzz_menu_new');
            $config = $oMenuNewModel->getConfig();
            if ($config->use_menu_new != 'Y')   return new BaseObject();

            // site_srl
            if ($site_srl == -1) {
                $site_info = Context::get('site_module_info');
                $site_srl = $site_info->site_srl;
            }

            // module_info
            $oModuleModel = getModel('module');
            $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
            $mid = $module_info->mid;
            if (empty($mid))  return;

            // 마지막 글의 시간을 구한다.
            $args->list_count = 1;
            $args->order_type = 'asc';
            $args->module_srl = $module_info->module_srl;
            $output = executeQuery('document.getDocumentList', $args);
			if (!$output->toBool()) return;

            if (count($output->data)){ foreach($output->data as $doc){
                $regdate = ztime($doc->regdate);
            }}

			// document에 대한 캐시 생성
            $cache = sprintf("%s/%d.%s_doc.php", $this->menu_new_cache_path, $site_srl, $mid);
            $buff = sprintf('<? $regdate=%d; ?>', $regdate);
            FileHandler::writeFile($cache, $buff);

            // 플래닛
            if ($module_info->module == 'planet') {
                // 플래닛 mid
                $oPlanetModel = getModel('planet');
                $planet_config = $oPlanetModel->getPlanetConfig();
                $planet_mid = $planet_config->mid;

                // 플래닛의 마지막 글을 구한다.
                $args->order = 'asc';
                $output = executeQuery('planet.getPlanetNewestContentList', $args);
                if (!$output->toBool()) return;

                if (count($output->data)){ foreach($output->data as $doc){
                    $regdate = ztime($doc->regdate);
                }}

                // planet에 대한 캐시 생성
                if (!empty($planet_mid)) {
                    $cache = sprintf("%s/%d.%s_doc.php", $this->menu_new_cache_path, $site_srl, $planet_mid);
                    $buff = sprintf('<? $regdate=%d; ?>', $regdate);
                    FileHandler::writeFile($cache, $buff);
                }
            }

            // 마지막 댓글의 시간을 구한다.
            $output = executeQuery('comment.getNewestCommentList', $args);
            if (!$output->toBool()) return;

            $regdate = ztime($output->data->regdate);

            // comment에 대한 캐시 생성
            $cache = sprintf("%s/%d.%s_com.php", $this->menu_new_cache_path, $site_srl, $mid);
            $buff = sprintf('<? $regdate_com=%d; ?>', $regdate);
            FileHandler::writeFile($cache, $buff);
        }

        /**
         * @brief 카테고리 캐시 업데이트
         * @param[in] $category_srl 캐시 업데이트할 카테고리의 번호
         *
         * 캐시파일은 단순히 php코드로 마지막 글/댓글의 작성시간이 '$regdate=시간;'과 같이 작성된다.
         * 파일명은 category_doc.category_srl.php 형식으로 생성된다.
         * 댓글은 지원하지 않는다.
         **/
        function procUpdateCacheCategory($category_srl) {
            // 메뉴에 새글 표시 사용중인지 확인
            $oMenuNewModel = getModel('zzz_menu_new');
            $config = $oMenuNewModel->getConfig();
            if ($config->use_menu_new != 'Y')   return new BaseObject();

            // 마지막 글의 시간을 구한다.
            $args->list_count = 1;
            $args->order_type = 'asc';
            $args->category_srl = $category_srl;
            $output = executeQuery('document.getDocumentList', $args);
            if (!$output->toBool()) return;

            if (count($output->data)){ foreach($output->data as $doc){
                $regdate = ztime($doc->regdate);
            }}

            // document에 대한 캐시 생성
            $cache = sprintf("%s/category_doc.%d.php", $this->menu_new_cache_path, $category_srl);
            $buff = sprintf('<? $regdate=%d; ?>', $regdate);
            FileHandler::writeFile($cache, $buff);
        }

        /**
         * @brief 메뉴 캐시 생성시추가 작업.
         *
         * 트리거로 지원되지 않는 XE 메뉴 캐시 재생성 시 메뉴에 새글 표시의 캐시도 재생성하기 위해 사용.
         **/
        function triggerModuleHandlerProc(&$oModule) {
            $target_act = array(
                                'procHomepageInsertMenuItem',
                                'procHomepageDeleteMenuItem',
                                'procHomepageMenuItemMove',

                                'procMenuAdminInsertItem',
            					'procMenuAdminUpdateItem',
                                'procMenuAdminDeleteItem',
                                'procMenuAdminMoveItem',
                                'procMenuAdminMakeXmlFile',
            					'procMenuAdminButtonUpload',
            					'procMenuAdminUpdateAuth'
                                );

            if (in_array($oModule->act, $target_act)) {
	            $file_list = FileHandler::readDir($this->menu_cache_path);
	            if (!count($file_list)) return new BaseObject(-1, 'error');

	            foreach($file_list as $file) {
	                if (strpos($file, 'xml'))   continue;

	                $token = explode('.', $file);
	                $menu_srl = $token[0];
	                $this->procMenuInclude($menu_srl);
	            }
            }

            return new BaseObject();

        }

        /**
         * @brief CafeXE 메뉴 설정 화면 추가 작업. CafeXE의 메뉴 설정에서도 메뉴에 새글 표시를 설정할 수 있도록 한다.
         **/
        function triggerDisplay(&$output) {
            if (Context::getResponseMethod() == 'HTML' && Context::get('act') == 'dispHomepageAdminSiteTopMenu') {
                // 설정 가져오기
                $oMenuNewModel = getModel('zzz_menu_new');
                $config = $oMenuNewModel->getConfig();
                Context::set('config', $config);

                // 현재 사이트의 mid 목록
                $oModuleModel = getModel('module');
                $site_info = Context::get('site_module_info');
                $args->site_srl = $site_info->site_srl;
                $mid_list = $oModuleModel->getMidList($args);
                Context::set('mid_list',$mid_list);

                // 설정 화면 컴파일
                $oTemplate = TemplateHandler::getInstance();
                $menu_new = $oTemplate->compile('./modules/zzz_menu_new/tpl', 'menu_new_config.html');

                // HTML 추가
                $output = str_replace('</iframe>', "</iframe>$menu_new", $output);
            }

            return new BaseObject();
        }
    }

?>
