<?php
	/**
	 * @class  socialxeController
     * @author CONORY (http://www.conory.com)
	 * @brief Controller class of socialxe modules
	 */
	class socialxeController extends socialxe
	{
		/**
		 * @brief Initialization
		 */
		function init()
		{
		}
		
		/**
		 * @brief �̸��� Ȯ��
		 */
		function procSocialxeConfirmMail()
		{
			if(!$_SESSION['socialxe_confirm_email']) return new BaseObject(-1, "msg_invalid_request");
			
			$email_address = Context::get('email_address');	
			if(!$email_address) return new BaseObject(-1, "msg_invalid_request");
			
			$oMemberModel = getModel('member');
			$member_srl = $oMemberModel->getMemberSrlByEmailAddress($email_address);
			if($member_srl){
				$error = 'msg_exists_email_address';
			}
			
			$saved = $_SESSION['socialxe_confirm_email'];
			$mid = $_SESSION['socialxe_auth_redirect_mid'];
			$redirect_url = getNotEncodedUrl('', 'mid', $mid, 'act', '');
			
			if(!$error){
				$oLibrary = $this->getLibrary($saved['service']);
				if(!$oLibrary) return new BaseObject(-1, "msg_invalid_request");
				
				$oLibrary->setProfile($saved['profile_info']);
				$oLibrary->setAccessToken($saved['access_token']);
				$oLibrary->setRefreshToken($saved['refresh_token']);
				$oLibrary->set('profile_email', $email_address);
				
				$output = $this->LoginSns($oLibrary);
				if(!$output->toBool()){
					$error = $output->getMessage();
					$errorCode = $output->getError();
				}
			}
			
			//����
			if($error){
				$msg = $error;
				if($errorCode == -12){
					Context::set('xe_validator_id', '');
					$redirect_url = getNotEncodedUrl('', 'mid', $mid, 'act', 'dispMemberLoginForm');
					
				}else{
					$_SESSION['tmp_socialxe_confirm_email'] = $_SESSION['socialxe_confirm_email'];
					$this->setError(-1);
					$redirect_url = getNotEncodedUrl('', 'mid', $mid, 'act', 'dispSocialxeConfirmMail');
				}
			}
			
			unset($_SESSION['socialxe_confirm_email']);
			
			$oSocialxeModel = getModel('socialxe');
			
			//�αױ��
			$info = new stdClass;
			$info->msg = $msg;
			$info->sns = $saved['service'];
			$oSocialxeModel->logRecord($this->act, $info);
			
			if($msg){
				$this->setMessage($msg);
			}
			if(!$this->getRedirectUrl()){
				$this->setRedirectUrl($redirect_url);
			}
		}
		
		/**
		 * @brief �߰����� �Է�
		 */
		function procSocialxeInputAddInfo()
		{
			if(!$_SESSION['socialxe_input_add_info']) return new BaseObject(-1, "msg_invalid_request");
			
			$saved = $_SESSION['socialxe_input_add_info'];
			$mid = $_SESSION['socialxe_auth_redirect_mid'];
			$redirect_url = getNotEncodedUrl('', 'mid', $mid, 'act', '');
			
			$oMemberModel = getModel('member');
			$signupForm = array();
			
			//�ʼ� �߰� ������
			if(in_array('require_add_info',$this->config->sns_input_add_info)){
				$member_config = $oMemberModel->getMemberConfig();
				
				foreach($member_config->signupForm as $no=>$formInfo){
					if(!$formInfo->required || $formInfo->isDefaultForm) continue;
					$signupForm[] = $formInfo->name;
				}
			}
			
			//�г��� ��
			if(in_array('nick_name',$this->config->sns_input_add_info)){
				$signupForm[] = 'nick_name';
				
				$member_srl = $oMemberModel->getMemberSrlByNickName(Context::get('nick_name'));
				if($member_srl){
					$error = 'msg_exists_nick_name';
				} 
			}
			
			//��� ����
			if(in_array('agreement',$this->config->sns_input_add_info)){
				$signupForm[] = 'accept_agreement';
			}
			
			//�߰����� ����
			$add_data = array();
			foreach($signupForm as $val)
			{
				$add_data[$val] = Context::get($val);
			}
			
			if(!$error){
				$oLibrary = $this->getLibrary($saved['service']);
				if(!$oLibrary) return new BaseObject(-1, "msg_invalid_request");
				
				$oLibrary->setProfile($saved['profile_info']);
				$oLibrary->setAccessToken($saved['access_token']);
				$oLibrary->setRefreshToken($saved['refresh_token']);
				
				$_SESSION['socialxe_input_add_info_data'] = $add_data;
				
				$output = $this->LoginSns($oLibrary);
				if(!$output->toBool()){
					$error = $output->getMessage();
				}
			}
			
			//����
			if($error){
				$msg = $error;
				$this->setError(-1);
				$redirect_url = getNotEncodedUrl('', 'mid', $mid, 'act', 'dispSocialxeInputAddInfo');
				
				$_SESSION['tmp_socialxe_input_add_info'] = $_SESSION['socialxe_input_add_info'];
			}
			
			unset($_SESSION['socialxe_input_add_info']);
			
			$oSocialxeModel = getModel('socialxe');
			
			//�αױ��
			$info = new stdClass;
			$info->msg = $msg;
			$info->sns = $saved['service'];
			$oSocialxeModel->logRecord($this->act, $info);
			
			if($msg){
				$this->setMessage($msg);
			}
			if(!$this->getRedirectUrl()){
				$this->setRedirectUrl($redirect_url);
			}
		}
		
 		/**
		 *@brief SNS ����
		 **/
        function procSocialxeSnsClear()
		{
            if(!Context::get('is_logged')) return new BaseObject(-1, "msg_not_logged");
			
			$service = Context::get('service');	
			if(!$service) return new BaseObject(-1, "msg_invalid_request");
			
			$oLibrary = $this->getLibrary($service);
			if(!$oLibrary) return new BaseObject(-1, "msg_invalid_request");
			
			$oSocialxeModel = getModel('socialxe');
			$sns_info = $oSocialxeModel->getMemberSns($service);
			if(!$sns_info) return new BaseObject(-1, "msg_invalid_request");
			
			if($this->config->sns_login == 'Y'){
				$sns_list = $oSocialxeModel->getMemberSns();
				if(!is_array($sns_list)) $sns_list = array($sns_list);
				if(count($sns_list) < 2) return new BaseObject(-1, "msg_not_clear_sns_one");
			}
			
			$oLibrary->setRefreshToken($sns_info->refresh_token);
			$oLibrary->setAccessToken($sns_info->access_token);
			
			$logged_info = Context::get('logged_info');	
			
			$args = new stdClass;
			$args->service = $service;
			$args->member_srl = $logged_info->member_srl;
			$output = executeQuery('socialxe.deleteMemberSns', $args);
			if(!$output->toBool()) return $output;			
			
			//��ū �ı�
			$oLibrary->revokeToken();
			
			//�αױ��
			$info = new stdClass;
			$info->sns = $service;
			$oSocialxeModel->logRecord($this->act, $info);
			
			$this->setMessage('msg_success_sns_register_clear');
			
			$redirect_url = getNotEncodedUrl('','mid',Context::get('mid'),'act','dispSocialxeSnsManage');
			$this->setRedirectUrl($redirect_url);
        }
		
 		/**
		 *@brief SNS ��������
		 **/
        function procSocialxeSnsLinkage()
		{
            if(!Context::get('is_logged')) return new BaseObject(-1, "msg_not_logged");
			
			$service = Context::get('service');
			if(!$service) return new BaseObject(-1, "msg_invalid_request");
			
			$oLibrary = $this->getLibrary($service);
			if(!$oLibrary) return new BaseObject(-1, "msg_invalid_request");
			
			$oSocialxeModel = getModel('socialxe');
			$sns_info = $oSocialxeModel->getMemberSns($service);
			if(!$sns_info) return new BaseObject(-1, "msg_not_linkage_sns_info");
			
			if(!method_exists($oLibrary, 'insertActivities'))
			{
				return new BaseObject(-1, sprintf(Context::getLang('msg_not_support_linkage_setting'),ucwords($service)));
			}
			
			$args = new stdClass;
			if($sns_info->linkage == 'Y'){
				$args->linkage = 'N';
			}else{
				$args->linkage = 'Y';
			}
			
			$logged_info = Context::get('logged_info');
			
			$args->service = $service;
			$args->member_srl = $logged_info->member_srl;
			$output = executeQuery('socialxe.updateMemberSns', $args);
			if(!$output->toBool()) return $output;
			
			//�αױ��
			$info = new stdClass;
			$info->sns = $service;
			$info->linkage = $args->linkage;
			$oSocialxeModel->logRecord($this->act, $info);
			
			$this->setMessage('msg_success_linkage_sns');
			
			$redirect_url = getNotEncodedUrl('','mid',Context::get('mid'),'act','dispSocialxeSnsManage');
			$this->setRedirectUrl($redirect_url);
        }
		
 		/**
		 *@brief Callback
		 **/
        function procSocialxeCallback()
		{
			$service = Context::get('service');
			if(!in_array($service,$this->config->sns_services)) return new BaseObject(-1, "msg_invalid_request");
			
			$oLibrary = $this->getLibrary($service);
			if(!$oLibrary) return new BaseObject(-1, "msg_invalid_request");
			
			$type = $_SESSION['socialxe_auth_type'];
			if(!$type) return new BaseObject(-1, "msg_invalid_request");
			
			$mid = $_SESSION['socialxe_auth_redirect_mid'];
			$redirect_url = $_SESSION['socialxe_auth_redirect'];
			
			if($redirect_url){
				$redirect_url = Context::getRequestUri().'?'.$redirect_url;
			}else{
				$redirect_url = Context::getRequestUri();
			}
			
			//��������
			$output = $oLibrary->authenticate();
			if(!$output->toBool()){
				$error = $output->getMessage();
			}
			
			//������ ��������
			if(!$error){
				$output = $oLibrary->setProfile();
				if(!$output->toBool()){
					$error = $output->getMessage();
				}
			}
			
			//���ó��
			if(!$error){
				if($type == 'register'){
					$msg = 'msg_success_sns_register';
					
					$output = $this->registerSns($oLibrary);
					if(!$output->toBool()){
						$error = $output->getMessage();
					}
					
				}elseif($type == 'login'){
					$output = $this->LoginSns($oLibrary);
					if(!$output->toBool()){
						$error = $output->getMessage();
					}
					
					//�α����� ������ �̵�(ȸ������ ����)
					$oModuleModel = getModel('module');
					$member_config = $oModuleModel->getModuleConfig('member');
					if(!$member_config->after_login_url){
						$redirect_url = getNotEncodedUrl('', 'mid', $mid, 'act', '');
					}else{
						$redirect_url = $member_config->after_login_url;
					}
				}
			}
			
			//����
			if($error){
				$msg = $error;
				$this->setError(-1);
				if($type == 'login'){
					$redirect_url = getNotEncodedUrl('', 'mid', $mid, 'act', 'dispMemberLoginForm');
				}
			}
			
			$oSocialxeModel = getModel('socialxe');
			
			//�αױ��
			$info = new stdClass;
			$info->sns = $service;
			$info->msg = $msg;
			$info->type = $type;
			$oSocialxeModel->logRecord($this->act, $info);
			
			if($msg){
				$this->setMessage($msg);
			}
			if(!$this->getRedirectUrl()){
				$this->setRedirectUrl($redirect_url);
			}
        }
		
 		/**
		 *@brief module Handler Ʈ����
		 **/
        function triggerModuleHandler(&$obj)
		{
			if($this->config->default_signup != 'Y' && $this->config->sns_login == 'Y' && (Context::get('act') != 'dispMemberLoginForm' || Context::get('mode') == 'default')){
				if(Context::get('module') == 'admin'){
					Context::addHtmlHeader('<style>.signin .login-footer, #access .login-body, #access .login-footer{display:none;}</style>');
				}else{
					Context::addHtmlHeader('<style>.signin .login-footer, #access .login-footer{display:none;}</style>');
				}
			}
			
			if(!Context::get('is_logged')) return new BaseObject();
			
			$oMemberController = getController('member');
			$oMemberController->addMemberMenu('dispSocialxeSnsManage', 'sns_manage');			
			
			$execute_act = array('dispMemberModifyInfo','dispMemberModifyEmailAddress');
			if(!in_array(Context::get('act'), $execute_act)) return new BaseObject();
			
			$oSocialxeModel = getModel('socialxe');
			$sns_user = $oSocialxeModel->memberUserSns();
			
			if($sns_user){
				if(Context::get('act') == 'dispMemberModifyInfo' || Context::get('act') == 'dispMemberModifyEmailAddress'){
					$_SESSION['rechecked_password_step'] = 'VALIDATE_PASSWORD';
				}
			}
			
            return new BaseObject();
        }
		
 		/**
		 *@brief module Object Before Ʈ����
		 **/
        function triggerModuleObjectBefore(&$obj)
		{
			if($this->config->sns_login != 'Y') return new BaseObject();
			
			$member_act = array('dispMemberSignUpForm','dispMemberFindAccount','procMemberInsert','procMemberFindAccount','procMemberFindAccountByQuestion');
			
			if($this->config->default_signup != 'Y' && in_array($obj->act, $member_act)){
				return new BaseObject(-1, "msg_use_sns_login");
			}
			
			if($this->config->default_login != 'Y' && $obj->act == 'procMemberLogin'){
				return new BaseObject(-1, "msg_use_sns_login");
			}
			
			if(!Context::get('is_logged')) return new BaseObject();
			
			$execute_act = array('dispMemberModifyPassword','procMemberModifyPassword','procMemberLeave','dispMemberLeave');
			if(!in_array($obj->act, $execute_act)) return new BaseObject();
			
			$oSocialxeModel = getModel('socialxe');
			$sns_user = $oSocialxeModel->memberUserSns();
			
			if($sns_user){
				if($obj->act == 'dispMemberModifyPassword' || $obj->act == 'procMemberModifyPassword' || ($this->config->delete_member_forbid == 'Y' && ($obj->act == 'procMemberLeave' || $obj->act == 'dispMemberLeave'))){
					if($obj->act == 'dispMemberModifyPassword'){
						$returnUrl = getNotEncodedUrl('', 'mid', Context::get('mid'), 'act', '');
						$obj->setRedirectUrl($returnUrl);
					}else{
						return new BaseObject(-1, "msg_invalid_request");
					}
					
				}elseif($obj->act == 'procMemberLeave'){
					$logged_info = Context::get('logged_info');
					$member_srl = $logged_info->member_srl;
					
					$oMemberController = getController('member');
					$output = $oMemberController->deleteMember($member_srl);
					if(!$output->toBool()) return $output;
					
					$oMemberController->destroySessionInfo();
					
					$obj->setMessage('success_delete_member_info');
					$returnUrl = getNotEncodedUrl('', 'mid', Context::get('mid'), 'act', '');
					$obj->setRedirectUrl($returnUrl);
				}
			}
			
            return new BaseObject();
        }
		
 		/**
		 *@brief module Object After Ʈ����
		 **/
        function triggerModuleObjectAfter(&$obj)
		{
			if($this->config->sns_login != 'Y') return new BaseObject();
			
			$oSocialxeModel = getModel('socialxe');
			
			if(Mobile::isFromMobilePhone()){
				$template_path = sprintf("%sm.skins/%s/",$this->module_path, $this->config->mskin);
			}else{
				$template_path = sprintf("%sskins/%s/",$this->module_path, $this->config->skin);
			}
			
			//�α��� ������
			if($obj->act == 'dispMemberLoginForm' && (Context::get('mode') != 'default' || $this->config->default_login != 'Y')){
				Context::set('config', $this->config);
				
				$obj->setTemplatePath($template_path);
				$obj->setTemplateFile('sns_login');
				
				foreach($this->config->sns_services as $key=> $val){
					$args = new stdClass;
					$args->auth_url = $oSocialxeModel->snsAuthUrl($val, 'login');
					$args->service = $val;
					$sns_services[$key] = $args;
				}
				Context::set('sns_services', $sns_services);
			
			//�������� ��߼�
			}elseif($obj->act == 'procMemberResetAuthMail'){
				$redirect_url = getNotEncodedUrl('', 'act', 'dispMemberLoginForm');
				$obj->setRedirectUrl($redirect_url);
			}
			
			if(!Context::get('is_logged')) return new BaseObject();
			
			$execute_act = array('dispMemberAdminInsert','dispMemberModifyInfo','dispMemberLeave');
			if(!in_array($obj->act, $execute_act)) return new BaseObject();
			
			$sns_user = $oSocialxeModel->memberUserSns();
			if($sns_user){
				if($obj->act == 'dispMemberLeave'){
					$obj->setTemplatePath($template_path);
					$obj->setTemplateFile('delete_member');
					
				//��й�ȣ ���� ����
				}elseif($obj->act == 'dispMemberModifyInfo'){
					$formTags = Context::get('formTags');
					$new_formTags = array();
					foreach($formTags as $no=>$formInfo){
						if($formInfo->name == 'find_account_question') continue;
						$new_formTags[] = $formInfo;
					}
					Context::set('formTags', $new_formTags);
				}
			}
			
			//������ ȸ������ ���� SNS �׸����
			if($obj->act == 'dispMemberAdminInsert' && Context::get('member_srl')){
				$member_srl = Context::get('member_srl');
				$sns_user = $oSocialxeModel->memberUserSns($member_srl);
				
				if($sns_user){
					$snsTag = array();
					foreach($this->config->sns_services as $key=> $val){
						$sns_info = $oSocialxeModel->getMemberSns($val, $member_srl);
						if(!$sns_info) continue;
						
						$snsTag[]= sprintf('[%s] <a href="%s" target="_blank">%s</a>',ucwords($val),$sns_info->profile_url, $sns_info->name);
					}
					$snsTag = implode(', ',$snsTag);
					
					$formTags = Context::get('formTags');
					$new_formTags = array();
					foreach($formTags as $no=>$formInfo){
						if($formInfo->name == 'find_account_question'){
							$formInfo->name = 'sns_info';
							$formInfo->title = 'SNS';
							$formInfo->type = '';
							$formInfo->inputTag = $snsTag;
						}
						$new_formTags[] = $formInfo;
					}
					Context::set('formTags', $new_formTags);
				}
			}
			
            return new BaseObject();
        }
		
        /**
         * @brief display Ʈ����
         **/
        function triggerDisplay(&$output)
		{
			if($this->config->sns_login != 'Y') return new BaseObject();
			if(!Context::get('is_logged')) return new BaseObject();
			
			$execute_act = array('dispMemberInfo','dispMemberModifyInfo','dispMemberAdminInsert');
			if(!in_array(Context::get('act'), $execute_act)) return new BaseObject();
			
			$oSocialxeModel = getModel('socialxe');
			$sns_user = $oSocialxeModel->memberUserSns();
			
			if($sns_user){
				if(Context::get('act') == 'dispMemberInfo'){
					$output = preg_replace('/\<a[^\>]*?dispMemberModifyPassword[^\>]*?\>[^\<]*?\<\/a\>/is', '', $output);
					
					if($this->config->delete_member_forbid == 'Y'){
						$output = preg_replace('/(\<a[^\>]*?dispMemberLeave[^\>]*?\>)[^\<]*?(\<\/a\>)/is', '', $output);
					}else{
						$output = preg_replace('/(\<a[^\>]*?dispMemberLeave[^\>]*?\>)[^\<]*?(\<\/a\>)/is', sprintf('$1%s$2', Context::getLang('delete_member_info')), $output);
					}
					
				//��й�ȣ ���� ����
				}elseif(Context::get('act') == 'dispMemberModifyInfo'){
					$acode = cut_str(md5(date('YmdHis')), 13, '');
					$output = preg_replace('/(\<input[^\>]*?value\=\"procMemberModifyInfo\"[^\>]*?\>)/is', sprintf('$1<input type="hidden" name="find_account_question" value="1" /><input type="hidden" name="find_account_answer" value="%s" />',$acode), $output);
				}
			}
			
			//������ ȸ������ ����
			if(Context::get('act') == 'dispMemberAdminInsert' && Context::get('member_srl')){
				$member_srl = Context::get('member_srl');
				$sns_user = $oSocialxeModel->memberUserSns($member_srl);
				
				if($sns_user){
					$acode = cut_str(md5(date('YmdHis')), 13, '');
					$output = preg_replace('/(\<input[^\>]*?value\=\"procMemberAdminInsert\"[^\>]*?\>)/is', sprintf('$1<input type="hidden" name="find_account_question" value="1" /><input type="hidden" name="find_account_answer" value="%s" />',$acode), $output);
				}
			}
			
			return new BaseObject();
		}
		
 		/**
		 *@brief ������� Ʈ����
		 **/
        function triggerInsertDocumentAfter($obj) 
		{
			if(!Context::get('is_logged')) return new BaseObject();
			
			//������ ��� ����
			if($this->config->linkage_module_srl){
				$module_srl_list = explode(',',$this->config->linkage_module_srl);
				if($this->config->linkage_module_target=='exclude' && in_array($obj->module_srl, $module_srl_list)) return new BaseObject();
				elseif($this->config->linkage_module_target!='exclude' && !in_array($obj->module_srl, $module_srl_list)) return new BaseObject();
			}
			
			$oSocialxeModel = getModel('socialxe');
			$sns_user = $oSocialxeModel->memberUserSns();
			if(!$sns_user) return new BaseObject();
			
			foreach($this->config->sns_services as $key=> $val){
				$sns_info = $oSocialxeModel->getMemberSns($val);
				if($sns_info->linkage != 'Y') continue;
				
				$oLibrary = $this->getLibrary($val);
				if(!$oLibrary) continue;
				
				$oLibrary->setRefreshToken($sns_info->refresh_token);
				$oLibrary->refreshToken();
				
				$args = new stdClass;
				$args->title = $obj->title;
				$args->content = $obj->content;
				$args->url = getNotEncodedFullUrl('', 'document_srl', $obj->document_srl);
				$oLibrary->insertActivities($args);
				
				//�αױ��
				$info = new stdClass;
				$info->sns = $val;
				$info->title = $obj->title;
				$oSocialxeModel->logRecord('linkage', $info);
			}
			
			return new BaseObject();
		}
		
 		/**
		 *@brief ȸ����� Ʈ����
		 **/
        function triggerInsertMember(&$config) 
		{
			//�̸��� �ּ� Ȯ��
			if(Context::get('act') == 'procSocialxeConfirmMail'){
				$config->enable_confirm = 'Y';
				
			//SNS �α��νÿ� ���������� ������
			}elseif(Context::get('act') == 'procSocialxeCallback' || Context::get('act') == 'procSocialxeInputAddInfo'){
				$config->enable_confirm = 'N';
			}
			
			return new BaseObject();
		}
		
 		/**
		 *@brief ȸ���޴� �˾� Ʈ����
		 **/
		function triggerMemberMenu()
		{
			$member_srl = Context::get('target_srl');
			$mid = Context::get('cur_mid');

			if(!$member_srl || $this->config->sns_profile != 'Y') return new BaseObject();
			
			$oSocialxeModel = getModel('socialxe');
			$sns_user = $oSocialxeModel->memberUserSns($member_srl);
			
			if(!$sns_user) return new BaseObject();
			
			$url = getUrl('', 'mid', $mid, 'act', 'dispSocialxeSnsProfile', 'member_srl', $member_srl);
			$oMemberController = getController('member');
			$oMemberController->addMemberPopupMenu($url, 'sns_profile', '');

			return new BaseObject();
		}
		
 		/**
		 *@brief ȸ������ Ʈ����
		 **/
        function triggerDeleteMember($obj) 
		{
			$args = new stdClass;
			$args->member_srl = $obj->member_srl;
            $output = executeQueryArray('socialxe.getMemberSns',$args);
			
			$sns_id = array();
			foreach($output->data as $key=> $val){
				$sns_id[] = '['.$val->service.'] '.$val->id;
				$oLibrary = $this->getLibrary($val->service);
				if(!$oLibrary) continue;
				
				$oLibrary->setRefreshToken($val->refresh_token);
				$oLibrary->setAccessToken($val->access_token);
				
				//��ū �ı�
				$oLibrary->revokeToken();
			}
			
			executeQuery('socialxe.deleteMemberSns', $args);
			
			$oSocialxeModel = getModel('socialxe');
			$logged_info = Context::get('logged_info');
			
			//�αױ��
			$info = new stdClass;
			$info->sns_id = implode(' | ', $sns_id);
			$info->nick_name = $logged_info->nick_name;
			$info->member_srl = $obj->member_srl;
			$oSocialxeModel->logRecord('delete_member', $info);
			
			return new BaseObject();
		}
		
 		/**
		 *@brief SNS ���
		 **/
        function registerSns($oLibrary, $member_srl = null)
		{
			if(!$member_srl){
				$logged_info = Context::get('logged_info');	
				$member_srl = $logged_info->member_srl;
			}
			if($this->config->sns_login != 'Y' && !$member_srl) return new BaseObject(-1, "msg_not_sns_login");
			
			if(!$oLibrary->getId()) return new BaseObject(-1, "msg_errer_api_connect");
			
			//SNS ���� �������� üũ
			if(!$oLibrary->getVerified()) return new BaseObject(-1, "msg_not_sns_verified");
			
			$id = $oLibrary->getId();
			$service = $oLibrary->getService();
			
			$oSocialxeModel = getModel('socialxe');			
			$sns_info = $oSocialxeModel->getMemberSnsById($id, $service);
			if($sns_info) return new BaseObject(-1, "msg_already_registed_sns");
			
			//�ߺ� �̸��� ������ ������ �� �������� �α���
			$oMemberModel = getModel('member');
			$email = $oLibrary->getEmail();
			if(!$member_srl && $email && !$_SESSION['socialxe_confirm_email']){
				$member_srl = $oMemberModel->getMemberSrlByEmailAddress($email);
				if($member_srl){
					//��, ������ ������ ���ȹ����� ����.
					$member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl);
					if($member_info->is_admin == 'Y'){
						unset($member_srl);
						return new BaseObject(-1, "msg_request_admin_sns_login");
					}else{
						$do_login = true;
					}
				} 
			}
			
			//ȸ������
			if(!$member_srl){
				$password = cut_str(md5(date('YmdHis')), 13, '');
				$nick_name = $oLibrary->getName();
				$profile_image = $oLibrary->getProfileImage();
				
				$replaceStr = array("\r\n", "\r", "\n", " ", "\t", "\xC2\xAD");
				$nick_name = str_replace($replaceStr, '', $nick_name);
				
				$member_name = $oMemberModel->getMemberSrlByNickName($nick_name);
				if($member_name){
					$nick_name = $nick_name.date('is');
				}
				
				//�߰���������
				if($this->config->sns_input_add_info[0] && !$_SESSION['socialxe_input_add_info_data']){
					$_SESSION['tmp_socialxe_input_add_info'] = $oLibrary->get();
					$_SESSION['tmp_socialxe_input_add_info']['nick_name'] = $nick_name;
					$redirect_url = getNotEncodedUrl('', 'act', 'dispSocialxeInputAddInfo');
					return $this->setRedirectUrl($redirect_url, new BaseObject(-1,'sns_input_add_info'));
				}
				
				//�̸��� Ȯ�ι���
				if(!$email){
					$_SESSION['tmp_socialxe_confirm_email'] = $oLibrary->get();
					$redirect_url = getNotEncodedUrl('', 'act', 'dispSocialxeConfirmMail');
					return $this->setRedirectUrl($redirect_url, new BaseObject(-1,'need_confirm_email_address'));
					
				}else{
					Context::setRequestMethod('POST');
					Context::set('password', $password, true);
					Context::set('nick_name', $nick_name, true);
					Context::set('user_name', $oLibrary->getName(), true);
					Context::set('email_address', $email, true);
					Context::set('accept_agreement', 'Y', true);
					
					$extend = $oLibrary->getProfileExtend();
					$signature = $extend->signature;
					Context::set('homepage', $extend->homepage, true);
					Context::set('blog', $extend->blog, true);
					Context::set('birthday', $extend->birthday, true);
					Context::set('gender', $extend->gender, true);
					
					//�߰�����
					$add_data = $_SESSION['socialxe_input_add_info_data'];
					foreach($add_data as $key=> $val){
						Context::set($key, $val, true);
					}
					
					unset($_SESSION['socialxe_input_add_info_data']);
				}
				
				$oMemberController = getController('member');
				$output = $oMemberController->procMemberInsert();
				if(is_object($output) && method_exists($output, 'toBool') && !$output->toBool()){
					if($output->error != -1){
						$s_output = $output;
					}else{
						return $output;
					}
				}
				
				$member_srl = $oMemberModel->getMemberSrlByEmailAddress($email);
				if(!$member_srl) return new BaseObject(-1, "msg_error_register_sns");
				
				//���� �α��� ����� ������ ���� ����Ʈ ����
				$sns_user = $oSocialxeModel->getSnsUser($id, $service);
				if($sns_user){
					$PHC_member_srl = $member_srl;
					$PHC_content = Context::getLang('PHC_member_register_sns_login');
					eval('$__PHC'.$PHC_member_srl.'__[] = array($PHC_content,$PHC_point,$PHC_type);');
					eval('Context::set(\'__PHC\'.$PHC_member_srl.\'__\',$__PHC'.$PHC_member_srl.'__);');
					
					$oPointController = getController('point');
					$oPointController->setPoint($member_srl, 0, 'update');
				}
				
				if($signature){
					$oMemberController->putSignature($member_srl, $signature);
				}
				
				if($profile_image){
					$tmp_dir = './files/cache/tmp/';
					if(!is_dir($tmp_dir)) FileHandler::makeDir($tmp_dir);
					
					$url = parse_url($profile_image);
					$path_parts = pathinfo($url['path']);
					$extension = $path_parts['extension'];
					$tmp_file = sprintf('%s%s.%s', $tmp_dir, $password,$extension);
					
					$request_config = array();
					$request_config['ssl_verify_peer'] = false;
					
					if(FileHandler::getRemoteFile($profile_image, $tmp_file, null, 3, 'GET', null, array(), array(), array(), $request_config)){
						$oMemberController->insertProfileImage($member_srl, $tmp_file);
						@unlink($tmp_file);
					}
				}
				
			//sns ���
			}else{
				$sns_info = $oSocialxeModel->getMemberSns($service, $member_srl);
				if($sns_info) return new BaseObject(-1, "msg_invalid_request");
			}
			
			$args = new stdClass;
			$args->refresh_token = $oLibrary->getRefreshToken();
			$args->access_token = $oLibrary->getAccessToken();
			$args->profile_info = $oLibrary->getProfileInfo();
			$args->profile_url = $oLibrary->getProfileUrl();
			$args->profile_image = $oLibrary->getProfileImage();
			$args->email = $oLibrary->getEmail();
			$args->name = $oLibrary->getName();
			$args->id = $oLibrary->getId();
			$args->service = $service;
			$args->member_srl = $member_srl;
			$output = executeQuery('socialxe.insertMemberSns', $args);
			if(!$output->toBool()) return $output;
			
			//SNS ID ���� ���. (SNS ������ ���� �Ǵ��� ��������)
			$sns_user = $oSocialxeModel->getSnsUser($id, $service);
			if(!$sns_user){
				$output = executeQuery('socialxe.insertSnsUser', $args);
				if(!$output->toBool()) return $output;
			}
			
			if($do_login){
				$output = $this->LoginSns($oLibrary);
				if(!$output->toBool()) return $output;
			}
			
			if($s_output) return $s_output;
			
			return new BaseObject();
        }
		
 		/**
		 *@brief SNS �α���
		 **/
        function LoginSns($oLibrary)
		{
			if($this->config->sns_login != 'Y') return new BaseObject(-1, "msg_not_sns_login");
            if(Context::get('is_logged')) return new BaseObject(-1, "already_logged");
			
			if(!$oLibrary->getId()) return new BaseObject(-1, "msg_errer_api_connect");
			
			//SNS ���� �������� üũ
			if(!$oLibrary->getVerified()) return new BaseObject(-1, "msg_not_sns_verified");
			
			$id = $oLibrary->getId();
			$service = $oLibrary->getService();
			
			$oSocialxeModel = getModel('socialxe');
			$sns_info = $oSocialxeModel->getMemberSnsById($id, $service);
			
			if($sns_info){
				$member_srl = $sns_info->member_srl;
				$oMemberModel = getModel('member');
				$member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl);
				
				//��������
				if($member_info->denied == 'Y'){
					$args = new stdClass;
					$args->member_srl = $member_srl;
					$output = executeQuery('member.chkAuthMail', $args);
					if ($output->toBool() && $output->data->count != '0'){
						$_SESSION['auth_member_srl'] = $member_info->member_srl;
						$redirectUrl = getNotEncodedUrl('', 'act', 'dispMemberResendAuthMail');
						return $this->setRedirectUrl($redirectUrl, new BaseObject(-1,'msg_user_not_confirmed'));
					}
				}
				
				$config = $oMemberModel->getMemberConfig();
				if($config->identifier == 'email_address'){
					$user_id = $member_info->email_address;
				}else{
					$user_id = $member_info->user_id;
				}
				
				$oMemberController = getController('member');
				$output = $oMemberController->doLogin($user_id, '', $this->config->sns_keep_signed=='Y'?true:false);
				if(!$output->toBool()) return $output;
				
				$args = new stdClass;
				$args->refresh_token = $oLibrary->getRefreshToken();
				$args->access_token = $oLibrary->getAccessToken();
				$args->profile_info = $oLibrary->getProfileInfo();
				$args->profile_url = $oLibrary->getProfileUrl();
				$args->profile_image = $oLibrary->getProfileImage();
				$args->email = $oLibrary->getEmail();
				$args->name = $oLibrary->getName();
				$args->service = $service;
				$args->member_srl = $member_srl;	
				$output = executeQuery('socialxe.updateMemberSns', $args);
				if(!$output->toBool()) return $output;
				
				//SNS ���
			}else{
				$output = $this->registerSns($oLibrary);
				if(!$output->toBool()) return $output;
			}
			
			return new BaseObject();
        }
	}