/**
 * @file tpl/js/zzz_menu_new.admin.js
 * @author 난다날아 (sinsy200@gmail.com)
 * @brief 관리자 모드용 자바스크립트
 */

/** @brief 캐시파일 재생성 */
function doRemakeCache() {
    exec_xml('zzz_menu_new', 'procZzz_menu_newAdminRemakeCacheAll', new Array(), null, new Array());
}

