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

	const StopWatering = "StopWatering";
	const StartWateringImmediately = "StartWateringImmediately";
	const DismissAlert = "DismissAlert";
	const LastCommandResponse = "LastCommandResponse";
	const ActualWateringMode = "ActualWateringMode";

	public function Create()
	{
		//Never delete this line!
		parent::Create();

		$this->RegisterPropertyString('UplinkTopic', '');
		//$this->RegisterPropertyString('UplinkReplyTopic', '');
		$this->RegisterPropertyString('DownlinkTopic', '');
		//$this->RegisterPropertyString('DownlinkReplyTopic', '');
		$this->RegisterPropertyString('LinkTapId', '');

		$this->RegisterVariableBoolean(self::StopWatering, $this->Translate(self::StopWatering), '~Switch', 10);
		$this->EnableAction(self::StopWatering);

		if(!IPS_VariableProfileExists('LINKTAP.IMMEDIATELY.SECONDS'))
		{
			IPS_CreateVariableProfile('LINKTAP.IMMEDIATELY.SECONDS', VARIABLETYPE_INTEGER);
			IPS_SetVariableProfileIcon('LINKTAP.IMMEDIATELY.SECONDS', 'Drops');
			IPS_SetVariableProfileText('LINKTAP.IMMEDIATELY.SECONDS', '', '');
			IPS_SetVariableProfileValues('LINKTAP.IMMEDIATELY.SECONDS', 2, 86340, 1);			
			IPS_SetVariableProfileAssociation('LINKTAP.IMMEDIATELY.SECONDS', 2, $this->Translate('NoWatering'), '', -1);
			IPS_SetVariableProfileAssociation('LINKTAP.IMMEDIATELY.SECONDS', 1800, $this->Translate('HalfHour'), 'Drops', 0x0000FF);
			IPS_SetVariableProfileAssociation('LINKTAP.IMMEDIATELY.SECONDS', 3600, $this->Translate('OneHour'), 'Drops', 0x0000FF);
			IPS_SetVariableProfileAssociation('LINKTAP.IMMEDIATELY.SECONDS', 7200, $this->Translate('TwoHours'), 'Drops', 0x0000FF);
			IPS_SetVariableProfileAssociation('LINKTAP.IMMEDIATELY.SECONDS', 10800, $this->Translate('ThreeHours'), 'Drops', 0x0000FF);
			IPS_SetVariableProfileAssociation('LINKTAP.IMMEDIATELY.SECONDS', 14400, $this->Translate('FourHours'), 'Drops', 0x0000FF);
			IPS_SetVariableProfileAssociation('LINKTAP.IMMEDIATELY.SECONDS', 18000, $this->Translate('FiveHours'), 'Drops', 0x0000FF);
			IPS_SetVariableProfileAssociation('LINKTAP.IMMEDIATELY.SECONDS', 21600, $this->Translate('SixHours'), 'Drops', 0x0000FF);
			IPS_SetVariableProfileAssociation('LINKTAP.IMMEDIATELY.SECONDS', 25200, $this->Translate('EightHours'), 'Drops', 0x0000FF);
			IPS_SetVariableProfileAssociation('LINKTAP.IMMEDIATELY.SECONDS', 36000, $this->Translate('TenHours'), 'Drops', 0x0000FF);
			IPS_SetVariableProfileAssociation('LINKTAP.IMMEDIATELY.SECONDS', 43200, $this->Translate('TwelveHours'), 'Drops', 0x0000FF);
			IPS_SetVariableProfileAssociation('LINKTAP.IMMEDIATELY.SECONDS', 50400, $this->Translate('FourteenHours'), 'Drops', 0x0000FF);
			IPS_SetVariableProfileAssociation('LINKTAP.IMMEDIATELY.SECONDS', 64800, $this->Translate('EighteenHours'), 'Drops', 0x0000FF);
			IPS_SetVariableProfileAssociation('LINKTAP.IMMEDIATELY.SECONDS', 82800, $this->Translate('TwentyThreeHours'), 'Drops', 0x0000FF);
			IPS_SetVariableProfileAssociation('LINKTAP.IMMEDIATELY.SECONDS', 86340, $this->Translate('MaxWateringTime'), 'Drops', 0x0000FF);
		}

		if(!IPS_VariableProfileExists('LINKTAP.WATERINGMODES'))
		{
			IPS_CreateVariableProfile('LINKTAP.WATERINGMODES', VARIABLETYPE_INTEGER);
			IPS_SetVariableProfileIcon('LINKTAP.WATERINGMODES', 'Menu');
			IPS_SetVariableProfileText('LINKTAP.WATERINGMODES', '', '');
			IPS_SetVariableProfileValues('LINKTAP.WATERINGMODES', 1, 6, 1);			
			IPS_SetVariableProfileAssociation('LINKTAP.WATERINGMODES', 1, $this->Translate('InstantMode'), '', -1);
			IPS_SetVariableProfileAssociation('LINKTAP.WATERINGMODES', 2, $this->Translate('CalendarMode'), '', -1);
			IPS_SetVariableProfileAssociation('LINKTAP.WATERINGMODES', 3, $this->Translate('SevenDayMode'), '', -1);
			IPS_SetVariableProfileAssociation('LINKTAP.WATERINGMODES', 4, $this->Translate('OddEvenMode'), '', -1);
			IPS_SetVariableProfileAssociation('LINKTAP.WATERINGMODES', 5, $this->Translate('IntervalMode'), '', -1);
			IPS_SetVariableProfileAssociation('LINKTAP.WATERINGMODES', 6, $this->Translate('MonthMode'), '', -1);
		}

		if(!IPS_VariableProfileExists('LINKTAP.LOCKS'))
		{
			IPS_CreateVariableProfile('LINKTAP.LOCKS', VARIABLETYPE_INTEGER);
			IPS_SetVariableProfileIcon('LINKTAP.LOCKS', 'Menu');
			IPS_SetVariableProfileText('LINKTAP.LOCKS', '', '');
			IPS_SetVariableProfileValues('LINKTAP.LOCKS', 0, 2, 1);			
			IPS_SetVariableProfileAssociation('LINKTAP.LOCKS', 0, $this->Translate('Unlocked'), '', -1);
			IPS_SetVariableProfileAssociation('LINKTAP.LOCKS', 1, $this->Translate('PartiallyLocked'), '', -1);
			IPS_SetVariableProfileAssociation('LINKTAP.LOCKS', 2, $this->Translate('CompletelyLocked'), '', -1);
		}

		$this->RegisterVariableInteger(self::ActualWateringMode, $this->Translate(self::ActualWateringMode), 'LINKTAP.WATERINGMODES', 1);

		$this->RegisterVariableInteger(self::StartWateringImmediately, $this->Translate(self::StartWateringImmediately), 'LINKTAP.IMMEDIATELY.SECONDS', 11);
		$this->EnableAction(self::StartWateringImmediately);

		$this->RegisterVariableInteger(self::Battery, $this->Translate(self::Battery), '~Battery.100', 50);
		$this->RegisterVariableInteger(self::SignalStrength, $this->Translate(self::SignalStrength), '~Intensity.100', 60);	
		
		$this->RegisterVariableBoolean(self::DismissAlert, $this->Translate(self::DismissAlert), '~Switch', 70);
		$this->EnableAction(self::DismissAlert);

		$this->RegisterVariableBoolean(self::WateringActive, $this->Translate(self::WateringActive), '~Switch', 100);			
		$this->RegisterVariableBoolean(self::IsFlowMeasurementPlugedIn, $this->Translate(self::IsFlowMeasurementPlugedIn), '~Switch', 110);
		$this->RegisterVariableInteger(self::ChildLock, $this->Translate(self::ChildLock), 'LINKTAP.LOCKS', 120);
		$this->RegisterVariableBoolean(self::ManualMode, $this->Translate(self::ManualMode), '~Switch', 130);
		$this->RegisterVariableBoolean(self::IsRfLinked, $this->Translate(self::IsRfLinked), '~Switch', 140);	
		$this->RegisterVariableBoolean(self::EcoFinal, $this->Translate(self::EcoFinal), '~Switch', 150);

		$this->RegisterVariableBoolean(self::FallAlert, $this->Translate(self::FallAlert), '~Alert', 200);
		$this->RegisterVariableBoolean(self::ValveShutdownFailureAlert, $this->Translate(self::ValveShutdownFailureAlert), '~Alert', 210);
		$this->RegisterVariableBoolean(self::WaterCutOffAlert, $this->Translate(self::WaterCutOffAlert), '~Alert', 220);
		$this->RegisterVariableBoolean(self::HighFlowAlert, $this->Translate(self::HighFlowAlert), '~Alert', 230);
		$this->RegisterVariableBoolean(self::LowFlowAlert, $this->Translate(self::LowFlowAlert), '~Alert', 240);

		$this->RegisterVariableString(self::GatewayId, $this->Translate(self::GatewayId), '', 1000);
		$this->RegisterVariableString(self::LastCommandResponse, $this->Translate(self::LastCommandResponse), '', 1001);

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
		
		$filterResult1 = preg_quote('"Topic":"' . $this->ReadPropertyString('UplinkTopic') . '/' . $this->ReadPropertyString('LinkTapId') . '"');
		$filterResult2 = preg_quote('"Topic":"' . $this->ReadPropertyString('UplinkTopic') . '"');	
		$filter = '.*(' . $filterResult1 . '|' . $filterResult2 . ').*';
		$this->SendDebug('ReceiveDataFilter', $filter, 0);
		$this->SetReceiveDataFilter($filter);

		if ($this->HasActiveParent() && IPS_GetKernelRunlevel() == KR_READY) {
			//Initial doing
		}	
		
		$this->SetStatus(102);
	}

	public function RequestAction($Ident, $Value)
	{
		switch ($Ident) {
			case self::StopWatering:
				$this->StopWatering($Value);
				break;
			case self::StartWateringImmediately:
				$this->StartWateringImmediately($Value);
				break;
			case self::DismissAlert:
				$this->DismissAlert($Value);
			default:
				$this->SendDebug('RequestAction', 'Unknown Ident: ' . $Ident, 0);
				break;
		}
	}

	function DismissAlert(bool $Value)
	{
		$this->SendDebug('DismissAlert', 'DismissAlert', 0);

		if($this->GetValue(self::GatewayId) == '' || $this->ReadPropertyString('LinkTapId') == '')
		{
			$this->SendDebug('DismissAlert', 'GatewayId or LinkTapId not set!', 0);
			return;
		}

		$this->SetValue(self::DismissAlert, true);

		$payload = [
			'cmd' => 11,
			'dev_id' => $this->ReadPropertyString('LinkTapId'),
			'gw_id' => $this->GetValue(self::GatewayId),
			'alert' => 0
		];

		$dataJSON = $this->GetPackageForDownlink($payload);

		$this->SendDebug('DismissAlert', 'Payload to LinkTap ' . $dataJSON, 0);

		$this->SendDataToParent($dataJSON);

		$this->SetValue(self::DismissAlert, false);
	}

	function StartWateringImmediately(int $Value)
	{
		$this->SendDebug('StartWateringImmediately', 'StartWateringImmediately', 0);

		if($this->GetValue(self::GatewayId) == '' || $this->ReadPropertyString('LinkTapId') == '')
		{
			$this->SendDebug('StartWateringImmediately', 'GatewayId or LinkTapId not set!', 0);
			return;
		}

		if($Value <= 2)
		{
			$this->SendDebug('StartWateringImmediately', 'Value is less then minimum of 3 seconds. Means in our case, we stop watering if watering is active.', 0);
			$this->StopWatering(true);
			$this->SetValue(self::StartWateringImmediately, 2);
			return;
		}

		$payload = [
			'cmd' => 6,
			'dev_id' => $this->ReadPropertyString('LinkTapId'),
			'gw_id' => $this->GetValue(self::GatewayId),
			'duration' => $Value
		];

		$dataJSON = $this->GetPackageForDownlink($payload);

		$this->SendDebug('StartWateringImmediately', 'Payload to LinkTap ' . $dataJSON, 0);

		$this->SendDataToParent($dataJSON);
	}

	function StopWatering(bool $Value)
	{		
		$this->SendDebug('StopWatering', 'StopWatering', 0);

		if($this->GetValue(self::GatewayId) == '' || $this->ReadPropertyString('LinkTapId') == '')
		{
			$this->SendDebug('StopWatering', 'GatewayId or LinkTapId not set!', 0);
			return;
		}

		$this->SetValue(self::StopWatering, true);

		$payload = [
			'cmd' => 7,
			'dev_id' => $this->ReadPropertyString('LinkTapId'),
			'gw_id' => $this->GetValue(self::GatewayId)
		];

		$dataJSON = $this->GetPackageForDownlink($payload);

		$this->SendDebug('StopWatering', 'Payload to LinkTap ' . $dataJSON, 0);

		$this->SendDataToParent($dataJSON);

		$this->SetValue(self::StopWatering, false);
	}
	
	function AnswerHandshake(array $payload)
	{
		if(!array_key_exists('ver', $payload) && !array_key_exists('end_dev', $payload))
			return;

		$this->SendDebug('Payload', 'Answer Handshake start', 0);

		if($this->GetValue(self::GatewayId) == '' || $this->ReadPropertyString('LinkTapId') == '')
		{
			$this->SendDebug('StartWateringImmediately', 'GatewayId or LinkTapId not set!', 0);
			return;
		}

		$payload = [
			'cmd' => 0,
			'gw_id' => $payload['gw_id'],
			'date' => date('Ymd'),
			'time' => date('His'),
			'wday' => date('N')
		];

		$dataJSON = $this->GetPackageForDownlink($payload);

		$this->SendDebug('Answer Handshake', 'Payload to LinkTap ' . $dataJSON, 0);

		$this->SendDataToParent($dataJSON);

		$this->SendDebug('Payload', 'Answer Handshake done', 0);
	}

	function UpdateStatus(array $payload) : bool
	{
		$this->SendDebug('Payload', 'Update Status Payload start', 0);

		if(!array_key_exists('dev_stat', $payload))
		{
			$this->SendDebug('Payload', 'dev_stat not found', 0);
			return false;
		}

		$this->SendDebug('Payload', json_encode($payload), 0);

		$desiredDevId = $this->ReadPropertyString('LinkTapId');
		$devStats = $payload['dev_stat'];
		$specificDevice = null;

		// filter if dev_stat is an array or an object. when a array then search for the desired device.
		if (is_array($devStats) && array_key_exists(0, $devStats)) 
		{
			foreach ($devStats as $device) {
				if ($device['dev_id'] == $desiredDevId) { 
					$specificDevice = $device;
					break;
				}
			}
		} 
		else 
		{
			$specificDevice = $devStats;
		}

		if ($specificDevice === null) 
		{
			$this->SendDebug('Payload', 'Specific device not found', 0);
			return false;
		}
		
		$gatewayId = $payload['gw_id'];
		$battery = $specificDevice['battery'];		
		$isRfLinked = $specificDevice['is_rf_linked'];
		$isFlowMeasurementPlugedIn = $specificDevice['is_flm_plugin'];
		$fallAlert = $specificDevice['is_fall'];
		$valveShutdownFailureAlert = $specificDevice['is_broken'];
		$waterCutOffAlert = $specificDevice['is_cutoff'];
		$highFlowAlert = $specificDevice['is_leak'];
		$lowFlowAlert = $specificDevice['is_clog'];
		$signalStrength = $specificDevice['signal'];
		$childLock = $specificDevice['child_lock'];
		$manualMode = $specificDevice['is_manual_mode'];
		$wateringActive = $specificDevice['is_watering'];
		$ecoFinal = $specificDevice['is_final'];
		$wateringMode = $specificDevice['plan_mode'];
		
		$this->SetValue(self::Battery, $battery);
		$this->SetValue(self::GatewayId, $gatewayId);
		$this->SetValue(self::IsRfLinked, $isRfLinked);
		$this->SetValue(self::IsFlowMeasurementPlugedIn, $isFlowMeasurementPlugedIn);
		
		$this->SetValue(self::FallAlert, $fallAlert);
		$this->SetValue(self::ValveShutdownFailureAlert, $valveShutdownFailureAlert);
		$this->SetValue(self::WaterCutOffAlert, $waterCutOffAlert);
		if($fallAlert || $valveShutdownFailureAlert || $waterCutOffAlert)
		{
			IPS_SetDisabled($this->GetIDForIdent(self::DismissAlert), false);
		}
		else
		{
			IPS_SetDisabled($this->GetIDForIdent(self::DismissAlert), true);
		}


		$this->SetValue(self::HighFlowAlert, $highFlowAlert);
		$this->SetValue(self::LowFlowAlert, $lowFlowAlert);

		$this->SetValue(self::SignalStrength, $signalStrength);
		$this->SetValue(self::ChildLock, $childLock);

		$this->SetValue(self::ManualMode, $manualMode);
		$this->SetValue(self::WateringActive, $wateringActive);
		if($wateringActive)
		{
			IPS_SetDisabled($this->GetIDForIdent(self::StopWatering), false);
		}
		else
		{
			IPS_SetDisabled($this->GetIDForIdent(self::StopWatering), true);
			$this->SetValue(self::StartWateringImmediately, 2);
		}
		$this->SetValue(self::EcoFinal, $ecoFinal);
		$this->SetValue(self::ActualWateringMode, $wateringMode);

		$this->SendDebug('Payload', 'Update Status Payload done', 0);
		return true;
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
			return;
		}

		$this->SendDebug('ReceiveData', $JSONString, 0);

		$data = json_decode($JSONString, true);

		$payload = json_decode($data['Payload'], true);
		
		$this->ProcessPayload($payload);
	}

	function ProcessPayload(array $payload)
	{
		$this->SendDebug('Payload', 'Payload Command ' . $payload['cmd'], 0);

		switch($payload['cmd'])
		{
			case 0: //Handshake
				// if(array_key_exists('ver', $payload) || array_key_exists('end_dev', $payload))
				// 	$this->AnswerHandshake($payload);
				break;

			case 3: //Status Update
				$this->UpdateStatus($payload);
				break;

			case 6: //Start Watering Immediately
				if($this->ProcessResult($payload['ret']))
					$this->SetValue(self::WateringActive, true);				
				break;

			case 7: //Stop Watering
				if($this->ProcessResult($payload['ret']))
					$this->SetValue(self::WateringActive, false);				
				break;

			case 11: //Dismiss Alert
				$this->ProcessResult($payload['ret']);
				break;

			default:
				//$this->SendDebug('ReceiveData', 'Unknown cmd: ' . $paylod['cmd'], 0);
				break;
		}		
	}

	function GetPackageForDownlink(array $payload) : string
	{
		$data['DataID'] = self::ModulToMqtt;
		$data['PacketType'] = 3;
		$data['QualityOfService'] = 0;
		$data['Retain'] = false;
		$data['Topic'] = $this->ReadPropertyString('DownlinkTopic');
		$data['Payload'] = json_encode($payload, JSON_UNESCAPED_SLASHES);
		$dataJSON = json_encode($data, JSON_UNESCAPED_SLASHES);

		return $dataJSON;
	}

	function ProcessResult(int $value) : bool
	{
		$result = false;
		switch($value)
		{
			case 0:
				$this->SendDebug('ProcessResult', 'Success from Gateway', 0);
				$this->SetValue(self::LastCommandResponse, $this->Translate('Success'));
				$result = true;
				break;
			case 1:
				$this->SendDebug('ProcessResult', 'Error from Gateway: Message format error (1)', 0);
				$this->SetValue(self::LastCommandResponse, $this->Translate('Error from Gateway: Message format error (1)'));
				$result = false;
				break;
			case 2:
				$this->SendDebug('ProcessResult', 'Error from Gateway: CMD message not supported (2)', 0);
				$this->SetValue(self::LastCommandResponse, $this->Translate('Error from Gateway: CMD message not supported (2)'));
				$result = false;
				break;
			case 3:
				$this->SendDebug('ProcessResult', 'Error from Gateway: Gateway ID not matched (3)', 0);
				$this->SetValue(self::LastCommandResponse, $this->Translate('Error from Gateway: Gateway ID not matched (3)'));
				$result = false;
				break;
			case 4:
				$this->SendDebug('ProcessResult', 'Error from Gateway: End device ID error (4)', 0);
				$this->SetValue(self::LastCommandResponse, $this->Translate('Error from Gateway: End device ID error (4)'));
				$result = false;
				break;
			case 5:
				$this->SendDebug('ProcessResult', 'Error from Gateway: End device ID not found (5)', 0);
				$this->SetValue(self::LastCommandResponse, $this->Translate('Error from Gateway: End device ID not found (5)'));
				$result = false;
				break;
			case 6:
				$this->SendDebug('ProcessResult', 'Error from Gateway: Gateway internal error (6)', 0);
				$this->SetValue(self::LastCommandResponse, $this->Translate('Error from Gateway: Gateway internal error (6)'));
				$result = false;
				break;
			case 7:
				$this->SendDebug('ProcessResult', 'Error from Gateway: Conflict with watering plan (7)', 0);
				$this->SetValue(self::LastCommandResponse, $this->Translate('Error from Gateway: Conflict with watering plan (7)'));
				$result = false;
				break;
			case 8:
				$this->SendDebug('ProcessResult', 'Error from Gateway: Gateway busy (8)', 0);
				$this->SetValue(self::LastCommandResponse, $this->Translate('Error from Gateway: Gateway busy (8)'));
				$result = false;
				break;
			default:
				$result = false;
				break;
		}
		return $result;
	}
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