<?php
    /**
     * @class  zzz_menu_newAdminController
     * @author 난다날아 (sinsy200@gmail.com)
     * @brief  zzz_menu_new 모듈의 admin controller class
     **/

    class zzz_menu_newAdminController extends zzz_menu_new {

        /// 캐시파일 재생성시 처리한 모듈을 기억하기 위한 변수
        var $processedModule;

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 설정 저장
         **/
        function procZzz_menu_newAdminSaveConfig() {
            $config = Context::getRequestVars();

            // module Controller 객체 생성하여 입력
            $oModuleController = getController('module');
            $site_info = Context::get('site_module_info');
            $output = $oModuleController->insertModulePartConfig('zzz_menu_new', $site_info->site_srl, $config);
            if (!$output->toBool()) $output;

            $this->setMessage('success_updated');
            $this->setRedirectUrl(Context::get('success_return_url'));
        }

        /**
         * @brief 캐시파일 재생성
         **/
        function procZzz_menu_newAdminRemakeCacheAll() {
            FileHandler::removeFilesInDir($this->menu_new_cache_path);

            unset($this->processedModule);

            // 메뉴 캐시를 돌며 각 메뉴의 모듈 새글 체크
            $file_list = FileHandler::readDir($this->menu_cache_path);
            if (!count($file_list)) return new BaseObject(-1, 'error');

            foreach($file_list as $file) {
                if (strpos($file, 'xml'))   continue;

                $token = explode('.', $file);
                $menu_srl = $token[0];
                $this->procZzz_menu_newAdminRemakeCache($menu_srl);
            }
        }

        /**
         * @brief menu_srl에 해당하는 캐시 재생성
         **/
        function procZzz_menu_newAdminRemakeCache($menu_srl) {
            // menu info
            $oMenuAdminModel = getAdminModel('menu');
            $menu_info = $oMenuAdminModel->getMenu($menu_srl);

            // 메뉴 include
            @include $menu_info->php_file;

            // 각 메뉴 아이템의 캐시 재생성
            $this->_procZzz_menu_newAdminRemakeCache($menu->list, $menu_info->site_srl);

            // 해당 메뉴 캐시에 include 문 추가
            $oMenuNewController = &getController('zzz_menu_new');
            $oMenuNewController->procMenuInclude($menu_srl);
        }

        /**
         * @brief 각 메뉴 아이템의 캐시 재생성 (하위 메뉴는 재귀적으로...)
         **/
        function _procZzz_menu_newAdminRemakeCache($menu_list, $site_srl) {
            if (!count($menu_list)) return;

            $oDocumentModel = getModel('document');

            foreach($menu_list as $menu_item) {
                // 하위 메뉴가 있으면 하위 메뉴부터 처리
                if (count($menu_item['list']))    $this->_procZzz_menu_newAdminRemakeCache($menu_item['list'], $site_srl);

                // 해당 메뉴의 모듈 정보
                $oModuleModel = getModel('module');
                $oMenuNewModel = getModel('zzz_menu_new');
                $module_info = $oModuleModel->getModuleInfoByMid($oMenuNewModel->getMid($menu_item[url]), $site_srl);
                if (!$module_info->module_srl)  continue;

                // 해당 모듈의 캐시 재생성
                if (!$this->processedModule[$site_srl][$module_info->module_srl]){
                    $oMenuNewController = getController('zzz_menu_new');

                    // 모듈 전체에 대한 캐시 재생성
                    $oMenuNewController->procUpdateCache($module_info->module_srl, $site_srl);

                    // 모듈에 속한 카테고리에 대한 캐시 재생성
                    $category_list = $oDocumentModel->getCategoryList($module_info->module_srl);
                    foreach ($category_list as $category) {
                        $oMenuNewController->procUpdateCacheCategory($category->category_srl);
                    }

                    // 처리한 모듈로 설정
                    $this->processedModule[$site_srl][$module_info->module_srl] = true;
                }
            }
        }
    }
?>
