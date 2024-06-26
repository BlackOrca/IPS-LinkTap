<?php

declare(strict_types=1);
	class LinkTap extends IPSModule
	{
		const MqttParent = "{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}";
		const ModulToMqtt = "{043EA491-0325-4ADD-8FC2-A30C8EEB4D3F}";
		const MqttToModul = "{7F7632D9-FA40-4F38-8DEA-C83CD4325A32}";

		const Battery = "Battery";
		const GatewayId = "GatewayId";
		const IsRfLinked = "IsRfLinked";
		const IsFlowMeasurementPlugedIn = "IsFlowMeasurementPlugedIn";
		const FallAlert = "FallAlert";
		const ValveShutdownFailureAlert = "ValveShutdownFailureAlert";
		const WaterCutOffAlert = "WaterCutOffAlert";
		const HighFlowAlert = "HighFlowAlert";
		const LowFlowAlert = "LowFlowAlert";
		const SignalStrength = "SignalStrength";
		const ChildLock = "ChildLock";
		const ManualMode = "ManualMode";
		const WateringActive = "WateringActive";
		const EcoFinal = "EcoFinal";

		public function Create()
		{
			//Never delete this line!
			parent::Create();

			$this->RegisterPropertyString('UplinkTopic', '');
			//$this->RegisterPropertyString('UplinkReplyTopic', '');
			$this->RegisterPropertyString('DownlinkTopic', '');
			//$this->RegisterPropertyString('DownlinkReplyTopic', '');
			$this->RegisterPropertyString('LinkTapId', '');


			$this->RegisterVariableInteger(self::Battery, $this->Translate(self::Battery), '~Battery.100', 50);
			$this->RegisterVariableInteger(self::SignalStrength, $this->Translate(self::SignalStrength), '~Intensity.100', 60);			

			$this->RegisterVariableBoolean(self::WateringActive, $this->Translate(self::WateringActive), '~Switch', 100);			
			$this->RegisterVariableBoolean(self::IsFlowMeasurementPlugedIn, $this->Translate(self::IsFlowMeasurementPlugedIn), '~Switch', 110);
			$this->RegisterVariableBoolean(self::ChildLock, $this->Translate(self::ChildLock), '~Switch', 120);
			$this->RegisterVariableBoolean(self::ManualMode, $this->Translate(self::ManualMode), '~Switch', 130);
			$this->RegisterVariableBoolean(self::IsRfLinked, $this->Translate(self::IsRfLinked), '~Switch', 140);	
			$this->RegisterVariableBoolean(self::EcoFinal, $this->Translate(self::EcoFinal), '~Switch', 150);

			$this->RegisterVariableBoolean(self::FallAlert, $this->Translate(self::FallAlert), '~Alert', 200);
			$this->RegisterVariableBoolean(self::ValveShutdownFailureAlert, $this->Translate(self::ValveShutdownFailureAlert), '~Alert', 210);
			$this->RegisterVariableBoolean(self::WaterCutOffAlert, $this->Translate(self::WaterCutOffAlert), '~Alert', 220);
			$this->RegisterVariableBoolean(self::HighFlowAlert, $this->Translate(self::HighFlowAlert), '~Alert', 230);
			$this->RegisterVariableBoolean(self::LowFlowAlert, $this->Translate(self::LowFlowAlert), '~Alert', 240);

			$this->RegisterVariableString(self::GatewayId, $this->Translate(self::GatewayId), '', 1000);

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

			$paylod = json_decode($data['Payload'], true);

			switch($paylod['cmd'])
			{
				case 3:										
					$this->UpdateStatus($paylod);
					break;
				default:
					//$this->SendDebug('ReceiveData', 'Unknown cmd: ' . $paylod['cmd'], 0);
					break;
			}			
		}
	}

	function UpdateStatus(array $payload) : bool
	{
		$this->SendDebug('Payload', 'Process Update Status Payload');
		$battery = $paylod['dev_stat']['battery'];
		$gatewayId = $paylod['gw_id'];
		$isRfLinked = $paylod['dev_stat']['is_rf_linked'];
		$isFlowMeasurementPlugedIn = $paylod['dev_stat']['is_flm_plugin'];
		$fallAlert = $paylod['dev_stat']['is_fall'];
		$valveShutdownFailureAlert = $paylod['dev_stat']['is_broken'];
		$waterCutOffAlert = $paylod['dev_stat']['is_cutoff'];
		$highFlowAlert = $paylod['dev_stat']['is_leak'];
		$lowFlowAlert = $paylod['dev_stat']['is_clog'];
		$signalStrength = $paylod['dev_stat']['signal'];
		$childLock = $paylod['dev_stat']['child_lock'];
		$manualMode = $paylod['dev_stat']['is_manual_mode'];
		$wateringActive = $paylod['dev_stat']['is_watering'];
		$ecoFinal = $paylod['dev_stat']['is_final'];
		
		$this->SetValue(self::Battery, $battery);
		$this->SetValue(self::GatewayId, $gatewayId);
		$this->SetValue(self::IsRfLinked, $isRfLinked);
		$this->SetValue(self::IsFlowMeasurementPlugedIn, $isFlowMeasurementPlugedIn);
		$this->SetValue(self::FallAlert, $fallAlert);
		$this->SetValue(self::ValveShutdownFailureAlert, $valveShutdownFailureAlert);
		$this->SetValue(self::WaterCutOffAlert, $waterCutOffAlert);
		$this->SetValue(self::HighFlowAlert, $highFlowAlert);
		$this->SetValue(self::LowFlowAlert, $lowFlowAlert);
		$this->SetValue(self::SignalStrength, $signalStrength);
		$this->SetValue(self::ChildLock, $childLock);
		$this->SetValue(self::ManualMode, $manualMode);
		$this->SetValue(self::WateringActive, $wateringActive);
		$this->SetValue(self::EcoFinal, $ecoFinal);		
	}



	/*
 	{
		"cmd":3,
		"gw_id":"gatewayid",
		"dev_stat":{
			"dev_id":"taplinkid",
			"plan_mode":1,
			"plan_sn":0,
			"is_rf_linked":true,
			"is_flm_plugin":true,
			"is_fall":false,
			"is_broken":false,
			"is_cutoff":false,
			"is_leak":false,
			"is_clog":false,
			"signal":83,
			"battery":90,
			"child_lock":0,
			"is_manual_mode":false,
			"is_watering":false,
			"is_final":true,
			"total_duration":0,
			"remain_duration":0,
			"speed":0.00,
			"volume":30.30,
			"volume_limit":0.00,
			"failsafe_duration":0
		}
	}
	*/