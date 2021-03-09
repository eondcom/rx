<?php
    /**
     * @mainpage 메뉴에 새글 표시 모듈
     * @section intro 소개
     * 메뉴에 새글 표시 모듈은 게시판 등 모듈에 올라온 새글을 메뉴에 표시해 줍니다.
     *
     */

    /**
     * @class  zzz_menu_new
     * @author 난다날아 (sinsy200@gmail.com)
     * @brief  zzz_menu_new 모듈의 high class
     **/

    class zzz_menu_new extends ModuleObject {

        function zzz_menu_new() {
            $this->menu_cache_path = _XE_PATH_.'files/cache/menu';
            $this->menu_new_cache_path = _XE_PATH_.'files/cache/menu_new';
        }

        /**
         * @brief 설치시 추가 작업이 필요할시 구현
         **/
        function moduleInstall() {
            // 캐시 디렉토리 생성
            FileHandler::makeDir('./files/cache/menu_new');

            // 글/댓글 작성에 트리거
            $oModuleController = getController('module');

            $oModuleController->insertTrigger('document.insertDocument', 'zzz_menu_new', 'controller', 'triggerInsertDocument', 'after');
            $oModuleController->insertTrigger('comment.insertComment', 'zzz_menu_new', 'controller', 'triggerInsertComment', 'after');

            $oModuleController->insertTrigger('document.deleteDocument', 'zzz_menu_new', 'controller', 'triggerDeleteDocument', 'after');
            $oModuleController->insertTrigger('comment.deleteComment', 'zzz_menu_new', 'controller', 'triggerDeleteComment', 'after');

            $oModuleController->insertTrigger('document.moveDocumentModule', 'zzz_menu_new', 'controller', 'triggerMoveDocumentBefore', 'before');
            $oModuleController->insertTrigger('document.moveDocumentModule', 'zzz_menu_new', 'controller', 'triggerMoveDocumentAfter', 'after');

            // 메뉴 캐시 생성시, CafeXE 메뉴 설정시 추가작업을 위한 트리거 추가 (09.10.25)
            $oModuleController->insertTrigger('moduleHandler.proc', 'zzz_menu_new', 'controller', 'triggerModuleHandlerProc', 'after');
            $oModuleController->insertTrigger('display', 'zzz_menu_new', 'controller', 'triggerDisplay', 'before');

            return new BaseObject();
        }

        /**
         * @brief 설치가 이상이 없는지 체크하는 method
         **/
        function checkUpdate() {
            $oModuleModel = getModel('module');

            // 트리거 체크
            if(!$oModuleModel->getTrigger('document.insertDocument', 'zzz_menu_new', 'controller', 'triggerInsertDocument', 'after'))   return true;
            if(!$oModuleModel->getTrigger('comment.insertComment', 'zzz_menu_new', 'controller', 'triggerInsertComment', 'after'))      return true;
            if(!$oModuleModel->getTrigger('document.deleteDocument', 'zzz_menu_new', 'controller', 'triggerDeleteDocument', 'after'))   return true;
            if(!$oModuleModel->getTrigger('comment.deleteComment', 'zzz_menu_new', 'controller', 'triggerDeleteComment', 'after'))      return true;
            if(!$oModuleModel->getTrigger('moduleHandler.proc', 'zzz_menu_new', 'controller', 'triggerModuleHandlerProc', 'after'))      return true;
            if(!$oModuleModel->getTrigger('display', 'zzz_menu_new', 'controller', 'triggerDisplay', 'before'))      return true;

            // 문서 이동 트리거 추가(2010-07-23)
            if(!$oModuleModel->getTrigger('document.moveDocumentModule', 'zzz_menu_new', 'controller', 'triggerMoveDocumentBefore', 'before')) return true;
            if(!$oModuleModel->getTrigger('document.moveDocumentModule', 'zzz_menu_new', 'controller', 'triggerMoveDocumentAfter', 'after')) return true;

            return false;
        }

        /**
         * @brief 업데이트 실행
         **/
        function moduleUpdate() {
            $oModuleModel = getModel('module');
            $oModuleController = getController('module');

            if(!$oModuleModel->getTrigger('document.insertDocument', 'zzz_menu_new', 'controller', 'triggerInsertDocument', 'after'))
                $oModuleController->insertTrigger('document.insertDocument', 'zzz_menu_new', 'controller', 'triggerInsertDocument', 'after');

            if(!$oModuleModel->getTrigger('comment.insertComment', 'zzz_menu_new', 'controller', 'triggerInsertComment', 'after'))
                $oModuleController->insertTrigger('comment.insertComment', 'zzz_menu_new', 'controller', 'triggerInsertComment', 'after');

            if(!$oModuleModel->getTrigger('document.deleteDocument', 'zzz_menu_new', 'controller', 'triggerDeleteDocument', 'after'))
                $oModuleController->insertTrigger('document.deleteDocument', 'zzz_menu_new', 'controller', 'triggerDeleteDocument', 'after');

            if(!$oModuleModel->getTrigger('comment.deleteComment', 'zzz_menu_new', 'controller', 'triggerDeleteComment', 'after'))
                $oModuleController->insertTrigger('comment.deleteComment', 'zzz_menu_new', 'controller', 'triggerDeleteComment', 'after');

            if(!$oModuleModel->getTrigger('moduleHandler.proc', 'zzz_menu_new', 'controller', 'triggerModuleHandlerProc', 'after'))
                $oModuleController->insertTrigger('moduleHandler.proc', 'zzz_menu_new', 'controller', 'triggerModuleHandlerProc', 'after');

            if(!$oModuleModel->getTrigger('display', 'zzz_menu_new', 'controller', 'triggerDisplay', 'before'))
                $oModuleController->insertTrigger('display', 'zzz_menu_new', 'controller', 'triggerDisplay', 'before');

            if(!$oModuleModel->getTrigger('document.moveDocumentModule', 'zzz_menu_new', 'controller', 'triggerMoveDocumentBefore', 'before'))
                $oModuleController->insertTrigger('document.moveDocumentModule', 'zzz_menu_new', 'controller', 'triggerMoveDocumentBefore', 'before');

            if(!$oModuleModel->getTrigger('document.moveDocumentModule', 'zzz_menu_new', 'controller', 'triggerMoveDocumentAfter', 'after'))
                $oModuleController->insertTrigger('document.moveDocumentModule', 'zzz_menu_new', 'controller', 'triggerMoveDocumentAfter', 'after');

            return new BaseObject(0, 'success_updated');
        }

        /**
         * @brief 캐시 파일 재생성
         **/
        function recompileCache() {
            $oMenuNewAdminController = getAdminController('zzz_menu_new');
            $oMenuNewAdminController->procZzz_menu_newAdminRemakeCacheAll();
        }
    }
?>
