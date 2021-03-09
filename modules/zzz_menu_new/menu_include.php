<?php
    /**
     * 단순히 메뉴 캐시 파일의 제일 뒤에 추가되는 include를 통해 실행된다.
     * zzz_menu_new.controller의 procNew()함수를 실행한다.
     *
     * @file menu_include.php
     * @author 난다날아 (sinsy200@gmail.com)
     * @brief  메뉴 캐시에 include되어 작동하는 코드
     **/

     $oMenuNewController = getController('zzz_menu_new');
     $oMenuNewController->procNew($menu->list);
?>