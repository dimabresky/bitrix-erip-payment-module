<?php
if( ! IsModuleInstalled("sale") ||
	! function_exists("curl_init") ||
	! function_exists("json_decode") ||
	! function_exists("mb_detect_encoding") ) return;

use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class devtm_erip extends CModule
{
	public $MODULE_ID = "devtm.erip";
	public $MODULE_VERSION;
	public $MODULE_VERSION_DATE;
	public $MODULE_NAME;
	public $MODULE_DESCRIPTION;
	public $MODULE_GROUP_RIGHTS = "N";
	
	protected $namespaceFolder = "devtm";

	protected $lang_ids = array();
	
	protected $mail_event_name = "SALE_STATUS_CHANGED_ER";
	
    function __construct()
	{
		$arModuleVersion = array();

		$path = str_replace("\\", "/", __FILE__);
		$path = substr($path, 0, strlen($path) - strlen("/index.php"));
		include($path."/version.php");

		if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion))
		{
			$this->MODULE_VERSION = $arModuleVersion["VERSION"];
			$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
		}

		$this->MODULE_NAME = Loc::getMessage("DEVTM_ERIP_MODULE_NAME");
		$this->MODULE_DESCRIPTION = Loc::getMessage("DEVTM_ERIP_MODULE_DESC");
		
		$this->setLangIds();
		
		\Bitrix\Main\Loader::includeModule("sale");
	}
	
	protected function setLangIds()
	{
		$db_lang = CLangAdmin::GetList(($b="sort"), ($o="asc"), array("ACTIVE" => "Y"));
		while ($lang = $db_lang->Fetch())
			$this->lang_ids[] = $lang["LID"];
	}
	
	protected function addPaysys()
	{
		return CSalePaySystem::Add(
									array(
										"NAME" => Loc::getMessage("DEVTM_ERIP_PS_NAME"),
										"DESCRIPTION" => Loc::getMessage("DEVTM_ERIP_PS_DESC"),
										"ACTIVE" => "Y",
										"SORT" => 100,
									)
								);
	}
	
	protected function deletePaysys()
	{
		$ps_id = (int)\Bitrix\Main\Config\Option::get( $this->MODULE_ID, "payment_system_id");
		CSalePaySystem::Delete($ps_id);
	}
	
	protected function addOStatus()
	{
		$lang_er = array();
		foreach($this->lang_ids as $lang)
		{
			$lang_er[] = array("LID" => $lang, "NAME" => Loc::getMessage("DEVTM_ERIP_STATUS_ER_NAME"), "DESCRIPTION" => Loc::getMessage("DEVTM_ERIP_STATUS_ER_DESC"));
		}

		if(empty($lang_er)) return false;
			
		return CSaleStatus::Add(
								array(
									"ID" => "ER",
									"SORT" => 100,
									"LANG" => $lang_er,
									"NOTIFY" => "N"
								)
							);
	}
	
	protected function deleteOStatus()
	{
		$code_status = \Bitrix\Main\Config\Option::get( $this->MODULE_ID, "order_status_code_erip");
		$o_s = new CSaleStatus;
		$o_s->Delete($code_status);
	}
	
	protected function addMailEvType()
	{
		foreach($this->lang_ids as $lang)
		{
			$f = array(
					"LID" => $lang,
					"EVENT_NAME" => $this->mail_event_name,
					"NAME" => Loc::getMessage("DEVTM_ERIP_MAIL_EVENT_NAME"),
					"DESCRIPTION" => Loc::getMessage("DEVTM_ERIP_MAIL_EVENT_DESC"),
				);
				
			$et = new CEventType;
			if($et->Add($f) === false)
				return false;
		}
		
		return true;
	}
	
	protected function deleteMailEvType()
	{
		$et = \Bitrix\Main\Config\Option::get( $this->MODULE_ID, "mail_event_name");
		CEventType::Delete($et);
	}
	
	protected function addMailTemplate()
	{
		$ss = array();
		$db_sites = CSite::GetList($by="sort", $order="desc", array());
		while($s = $db_sites->Fetch())
			$ss[] = $s["ID"];
		
		$f = array(
				"ACTIVE" => "Y",
				"EVENT_NAME" => $this->mail_event_name,
				"LID" => $ss,
				"EMAIL_FROM" => "#DEFAULT_EMAIL_FROM#",
				"EMAIL_TO" => "#EMAIL_TO#",
				"SUBJECT" => Loc::getMessage("DEVTM_ERIP_MAIL_TEMPLATE_THEMA"),
				"BODY_TYPE" => "text",
				"MESSAGE" => Loc::getMessage("DEVTM_ERIP_MAIL_TEMPLATE_MESS"),
			);
		
		$o_mt = new CEventMessage;
		return $o_mt->Add($f);
	}
	
	protected function deleteMailTemplate()
	{
		$mail_template_id = (int)\Bitrix\Main\Config\Option::get( $this->MODULE_ID, "mail_template_id");
		CEventMessage::Delete($mail_template_id);
	}
	
	protected function addHandlers()
	{
		return true;
	}
	
	protected function deleteHandlers()
	{
		return true;
	}
	
    public function DoInstall()
    {
		try
		{	
			//регистраниция модуля
			\Bitrix\Main\ModuleManager::registerModule($this->MODULE_ID);
			
			//создание платёжную систему
			$psid = $this->addPaysys();
			if($psid === false)
				throw new Exception(Loc::getMessage("DEVTM_ERIP_PS_ERROR_MESS"));
			
			//сохранение ID пл. системы в настройках модуля
			\Bitrix\Main\Config\Option::set( $this->MODULE_ID, "payment_system_id",  $psid);
			
			//создание статуса заказа [ЕРИП]Ожидание оплаты
			$o_status_code = $this->addOStatus();
			if($o_status_code === false)
				throw new Exception(Loc::getMessage("DEVTM_ERIP_ORDER_STATUS_ERROR_MESS"));
			
			//сохранение кода статуса заказа в настройках модуля
			\Bitrix\Main\Config\Option::set( $this->MODULE_ID, "order_status_code_erip",  $o_status_code);
			
			//Создание типа почтового события
			if($this->addMailEvType() === false)
				throw new Exception(Loc::getMessage("DEVTM_ERIP_MAIL_EVENT_ADD_ERROR"));
			
			//сохранение названия типа почтового события в настройках модуля
			\Bitrix\Main\Config\Option::set( $this->MODULE_ID, "mail_event_name",  $this->mail_event_name);
			
			//создание почтового шаблона
			$mail_temp_id = $this->addMailTemplate();
			if($mail_temp_id === false)
				throw new Exception(Loc::getMessage("DEVTM_ERIP_MAIL_TEMPLATE_ADD_ERROR"));
			
			//сохранение ID почтового шаблона в настройках модуля
			\Bitrix\Main\Config\Option::set( $this->MODULE_ID, "mail_template_id",  $mail_temp_id);
			
			//регистрация обработчика смены статуса заказа
			//if($this->addHandlers() === false)
			//	throw new Exception(Loc::getMessage("DEVTM_ERIP_HANDLERS_ADD_ERROR"));
		
			return true;
		
		}catch(Exception $e){
			$this->DoUninstall();
			return false;
		}
		return true;
    }

    public function DoUninstall()
    {
		//удаление обработчика
		//$this->deleteHandlers();
		
		//удаление почтового шаблона
		$this->deleteMailTemplate();
		
		//удаление почтового события
		$this->deleteMailEvType();
		
		//удаление статуса заказа [ЕРИП]Ожидание оплаты
		$this->deleteOStatus();
		
		//удаление платёжной системы
		$this->deletePaysys();
		
		//удаление настроек модуля
		Bitrix\Main\Config\Option::delete( $this->MODULE_ID );
        
		//удаление модуля из системы
		Bitrix\Main\ModuleManager::unRegisterModule($this->MODULE_ID);
		return true;
    }
}
