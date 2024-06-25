<?php

declare(strict_types=1);
	class LinkTap extends IPSModule
	{
		const MqttParent = "{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}";
		const ModulToMqtt = "{043EA491-0325-4ADD-8FC2-A30C8EEB4D3F}";
		const MqttToModul = "{7F7632D9-FA40-4F38-8DEA-C83CD4325A32}";

		public function Create()
		{
			//Never delete this line!
			parent::Create();

			$this->RegisterPropertyString('UplinkTopic', '');
			//$this->RegisterPropertyString('UplinkReplyTopic', '');
			$this->RegisterPropertyString('DownlinkTopic', '');
			//$this->RegisterPropertyString('DownlinkReplyTopic', '');

			$this->RegisterPropertyString('LinkTapId', '');

			$this->ConnectParent(self::MqttParent);
		}

		public function Destroy()
		{
			//Never delete this line!
			parent::Destroy();
		}

		public function ApplyChanges()
		{
			//Never delete this line!
			parent::ApplyChanges();

			if($this->ReadPropertyString('LinkTapId') == '' || 
				$this->ReadPropertyString('UplinkTopic') == '' || 
				//$this->ReadPropertyString('UplinkReplyTopic') == '' || 				 
				//$this->ReadPropertyString('DownlinkReplyTopic') == '' || 
				$this->ReadPropertyString('DownlinkTopic') == '') 
			{
				$this->SendDebug("LinkTapId", "LinkTapId oder Topics nicht gesetzt!", 0);
				$this->SetStatus(104);
				return;
			}

			$this->ConnectParent(self::MqttParent);

			$this->SendDebug('LinkTapId', $this->ReadPropertyString('LinkTapId'), 0);
			$this->SendDebug('UplinkTopic', $this->ReadPropertyString('UplinkTopic'), 0);
			//$this->SendDebug('UplinkReplyTopic', $this->ReadPropertyString('UplinkReplyTopic'), 0);
			$this->SendDebug('DownlinkTopic', $this->ReadPropertyString('DownlinkTopic'), 0);
			//$this->SendDebug('DownlinkReplyTopic', $this->ReadPropertyString('DownlinkReplyTopic'), 0);
			
			$filterResult = preg_quote('"Topic":"' . $this->ReadPropertyString('UplinkTopic') . '/' . $this->ReadPropertyString('LinkTapId'));	
			$this->SendDebug('ReceiveDataFilter', '.*' . $filterResult . '.*', 0);
			$this->SetReceiveDataFilter('.*' . $filterResult . '.*');

			if ($this->HasActiveParent() && IPS_GetKernelRunlevel() == KR_READY) {
				//Initial doing
			}	
			
			$this->SetStatus(102);
		}

		public function ReceiveData($JSONString)
		{		
			if($this->ReadPropertyString('LinkTapId') == '' || 
				$this->ReadPropertyString('UplinkTopic') == '' || 
				//$this->ReadPropertyString('UplinkReplyTopic') == '' ||				 
				//$this->ReadPropertyString('DownlinkReplyTopic') == '' ||
				$this->ReadPropertyString('DownlinkTopic') == '') 
			{
				$this->SendDebug("LinkTapId", "LinkTapId oder Topics nicht gesetzt!", 0);
				$this->SetValue(self::Active, false);
				return;
			}

			$this->SendDebug('ReceiveData', $JSONString, 0);

			$data = json_decode($JSONString, true);
		}
	}