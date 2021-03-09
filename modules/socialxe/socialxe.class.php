<?php
	/**
	 * @class  socialxe
     * @author CONORY (http://www.conory.com)
	 * @brief The parent class of the socialxe module
	 */
	
	class socialxe extends ModuleObject
	{
		var $config = NULL;
		var $default_services = array('twitter','facebook','google','naver','kakao');
		var $triggers = array(
			array('moduleHandler.init', 'socialxe', 'controller', 'triggerModuleHandler', 'after'),
			array('moduleObject.proc', 'socialxe', 'controller', 'triggerModuleObjectBefore', 'before'),
			array('moduleObject.proc', 'socialxe', 'controller', 'triggerModuleObjectAfter', 'after'),
			array('display', 'socialxe', 'controller', 'triggerDisplay', 'before'),
			array('document.insertDocument', 'socialxe', 'controller', 'triggerInsertDocumentAfter', 'after'),
			array('member.procMemberInsert', 'socialxe', 'controller', 'triggerInsertMember', 'before'),
			array('member.getMemberMenu', 'socialxe', 'controller', 'triggerMemberMenu', 'after'),
			array('member.deleteMember', 'socialxe', 'controller', 'triggerDeleteMember', 'after')
		);
		
		/**
		 * @brief Constructor
		 */
		function __construct()
		{
			$this->config = $this->getConfig();
			if(!Context::isExistsSSLAction('procSocialxeCallback') && Context::getSslStatus() == 'optional')
			{
				$ssl_actions = array('dispSocialxeConfirmMail','procSocialxeConfirmMail','procSocialxeCallback','dispSocialxeConnectSns');
				Context::addSSLActions($ssl_actions);
			}
		}
		
		/**
		 * @brief ��� ��ġ
		 */
		function moduleInstall()
		{
            $oModuleModel = getModel('module');
            $oModuleController = getController('module');
			
			return new BaseObject();
		}

		/**
		 * @brief ������Ʈ üũ
		 */
		function checkUpdate()
		{
            $oDB = DB::getInstance();
            $oModuleModel = getModel('module');	
			
			//Ʈ��Ŀ ��ġ
			foreach($this->triggers as $trigger)
			{
				if(!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4])) return true;
			}
			
			return false;
		}

		/**
		 * @brief ������Ʈ
		 */
		function moduleUpdate()
		{
            $oDB = DB::getInstance();
            $oModuleModel = getModel('module');
            $oModuleController = getController('module');
			
			//Ʈ��Ŀ ��ġ
			foreach($this->triggers as $trigger)
			{
				if(!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]))
				{
					$oModuleController->insertTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]);
				}
			}
			
			return new BaseObject(0, 'success_updated');
		}
		
		/**
		 * @brief ������
		 */
		function moduleUninstall()
		{
            $oModuleModel = getModel('module');
            $oModuleController = getController('module');
			
			//Ʈ��Ŀ ����
			foreach($this->triggers as $trigger)
			{
				if($oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]))
				{
					$oModuleController->deleteTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]);
				}
			}
			
			return new BaseObject();
		}
		
		/**
		 * @brief ĳ������ �����
		 */
		function recompileCache()
		{
		}
		
 		/**
		 *@brief ����
		 **/
        function getConfig() 
		{
			if(!$GLOBALS['_socialxe_config']){
				$oModuleModel = getModel('module');	
				$config = $oModuleModel->getModuleConfig('socialxe');
				
				if(!$config->delete_auto_log_record) $config->delete_auto_log_record = 0;
				if(!$config->skin) $config->skin = 'default';
				if(!$config->mskin) $config->mskin = 'default';
				if(!$config->sns_follower_count) $config->sns_follower_count = 0;
				if(!$config->mail_auth_valid_hour) $config->mail_auth_valid_hour = 0;
				
				if(!$config->sns_services) $config->sns_services = $this->default_services;
				else $config->sns_services = explode('|@|', $config->sns_services);
				
				$config->sns_input_add_info = explode('|@|', $config->sns_input_add_info);
				
				$GLOBALS['_socialxe_config'] = $config;
			}else{
				$config = $GLOBALS['_socialxe_config'];
			}
            return $config;
        }
		
 		/**
		 *@brief socialxe library
		 **/
        function getLibrary($library_name) 
		{
			require_once(_XE_PATH_ . 'modules/socialxe/socialxe.library.php');
			
			if(!$GLOBALS['_socialxe_library'][$library_name]){
				$library_file = sprintf('%smodules/socialxe/libs/%s.lib.php', _XE_PATH_,$library_name);
				if(!file_exists($library_file))
				{
					return NULL;
				}
				
				require_once($library_file);
				
				$instance_name = sprintf('library%s', ucwords($library_name));
				if(!class_exists($instance_name, false))
				{
					return NULL;
				}
				
				$oLibrary = new $instance_name($library_name);
				
				$GLOBALS['_socialxe_library'][$library_name] = $oLibrary;
			}else{
				$oLibrary = $GLOBALS['_socialxe_library'][$library_name];
			}
			
            return $oLibrary;
        }
	}