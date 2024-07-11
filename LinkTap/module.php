<?php

declare(strict_types=1);
class LinkTap extends IPSModule
{
	const MqttParent = "{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}";
	const ModulToMqtt = "{043EA491-0325-4ADD-8FC2-A30C8EEB4D3F}";
	const MqttToModul = "{7F7632D9-FA40-4F38-8DEA-C83CD4325A32}";
	
	const LinkTapId = "LinkTapId";
	const UplinkTopic = "UplinkTopic";
	const DownlinkTopic = "DownlinkTopic";
	const MeasurementUnit = "MeasurementUnit";

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
	const TotalDuration = "TotalDuration";
	const RemainDuration = "RemainDuration";
	const Speed = "Speed";
	const Volume = "Volume";
	const VolumeLimit = "VolumeLimit";

	const StopWatering = "StopWatering";
	const StartWateringImmediately = "StartWateringImmediately";
	const DismissAlert = "DismissAlert";
	const LastCommandResponse = "LastCommandResponse";
	const ActualWateringMode = "ActualWateringMode";

	public function Create()
	{
		//Never delete this line!
		parent::Create();

		$this->RegisterPropertyString(self::UplinkTopic, '');
		//$this->RegisterPropertyString('UplinkReplyTopic', '');
		$this->RegisterPropertyString(self::DownlinkTopic, '');
		//$this->RegisterPropertyString('DownlinkReplyTopic', '');
		$this->RegisterPropertyString(self::LinkTapId, '');
		$this->RegisterPropertyString(self::MeasurementUnit, 'Liter');

		$this->RegisterPropertyInteger('RequestInterval', 5);

		$this->RegisterTimer('RequestTimer', 0, 'LT_RequestData($_IPS[\'TARGET\']);');

		$this->RegisterVariables();		

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

		if($this->ReadPropertyString(self::LinkTapId) == '' || 
			$this->ReadPropertyString(self::UplinkTopic) == '' || 
			//$this->ReadPropertyString('UplinkReplyTopic') == '' || 				 
			//$this->ReadPropertyString('DownlinkReplyTopic') == '' || 
			$this->ReadPropertyString(self::DownlinkTopic) == '') 
		{
			$this->SendDebug("LinkTapId", "LinkTapId oder Topics nicht gesetzt!", 0);
			$this->SetStatus(104);
			return;
		}

		$this->ConnectParent(self::MqttParent);

		$this->SendDebug(self::LinkTapId, $this->ReadPropertyString(self::LinkTapId), 0);
		$this->SendDebug(self::UplinkTopic, $this->ReadPropertyString(self::UplinkTopic), 0);
		//$this->SendDebug('UplinkReplyTopic', $this->ReadPropertyString('UplinkReplyTopic'), 0);
		$this->SendDebug(self::DownlinkTopic, $this->ReadPropertyString(self::DownlinkTopic), 0);
		//$this->SendDebug('DownlinkReplyTopic', $this->ReadPropertyString('DownlinkReplyTopic'), 0);
		
		$filterResult1 = preg_quote('"Topic":"' . $this->ReadPropertyString(self::UplinkTopic) . '/' . $this->ReadPropertyString(self::LinkTapId) . '"');
		$filterResult2 = preg_quote('"Topic":"' . $this->ReadPropertyString(self::UplinkTopic) . '"');	
		$filter = '.*(' . $filterResult1 . '|' . $filterResult2 . ').*';
		$this->SendDebug('ReceiveDataFilter', $filter, 0);
		$this->SetReceiveDataFilter($filter);

		if ($this->HasActiveParent() && IPS_GetKernelRunlevel() == KR_READY) {
			//Initial doing
			$this->RequestData($_IPS['TARGET']);
		}

		$interval = $this->ReadPropertyInteger('RequestInterval') * 1000;
		$this->SetTimerInterval('RequestTimer', $interval);
		
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

	public function RequestData()
	{
		if(empty($this->ReadPropertyString(self::UplinkTopic)) || 
		   empty($this->ReadPropertyString(self::LinkTapId)) || 
		   empty($this->ReadPropertyString(self::DownlinkTopic)))
		{
			$this->SendDebug('RequestData', 'UplinkTopic is not set!', 0);
			return;
		}

		$this->SendDebug('RequestData', 'Send Request to LinkTap Gateway', 0);

		$payload = [
			'cmd' => 3,
			'dev_id' => $this->ReadPropertyString(self::LinkTapId),
			'gw_id' => $this->GetValue(self::GatewayId)
		];

		$dataJSON = $this->GetPackageForDownlink($payload);

		$this->SendDebug('RequestData', 'Payload to LinkTap' . $dataJSON, 0);
		$this->SendDataToParent($dataJSON);
	}

	function DismissAlert(bool $Value)
	{
		$this->SendDebug('DismissAlert', 'DismissAlert', 0);

		if($this->GetValue(self::GatewayId) == '' || $this->ReadPropertyString(self::LinkTapId) == '')
		{
			$this->SendDebug('DismissAlert', 'GatewayId or LinkTapId not set!', 0);
			return;
		}

		$this->SetValue(self::DismissAlert, true);

		$payload = [
			'cmd' => 11,
			'dev_id' => $this->ReadPropertyString(self::LinkTapId),
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

		if($this->GetValue(self::GatewayId) == '' || $this->ReadPropertyString(self::LinkTapId) == '')
		{
			$this->SendDebug('StartWateringImmediately', 'GatewayId or LinkTapId not set!', 0);
			return;
		}

		if($Value == 2)
		{
			$this->SendDebug('StartWateringImmediately', 'Value is less then minimum of 3 seconds. Means in our case, we stop watering if watering is active.', 0);
			$this->StopWatering(true);
			$this->SetValue(self::StartWateringImmediately, 2);
			return;
		}

		$payload = [
			'cmd' => 6,
			'dev_id' => $this->ReadPropertyString(self::LinkTapId),
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

		if($this->GetValue(self::GatewayId) == '' || $this->ReadPropertyString(self::LinkTapId) == '')
		{
			$this->SendDebug('StopWatering', 'GatewayId or LinkTapId not set!', 0);
			return;
		}

		$this->SetValue(self::StopWatering, true);

		$payload = [
			'cmd' => 7,
			'dev_id' => $this->ReadPropertyString(self::LinkTapId),
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

		if($this->GetValue(self::GatewayId) == '' || $this->ReadPropertyString(self::LinkTapId) == '')
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

		$desiredDevId = $this->ReadPropertyString(self::LinkTapId);
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

		$totalDuration = $specificDevice['total_duration'];
		$remainDuration = $specificDevice['remain_duration'];
		$speed = $specificDevice['speed'];
		$volume = $specificDevice['volume'];
		$volumeLimit = $specificDevice['volume_limit'];
		
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
			$this->SetValue(self::StartWateringImmediately, 0);
		}
		$this->SetValue(self::EcoFinal, $ecoFinal);
		$this->SetValue(self::ActualWateringMode, $wateringMode);

		$this->SetValue(self::TotalDuration, gmdate('H:i:s', $totalDuration));
		$this->SetValue(self::RemainDuration, gmdate('H:i:s', $remainDuration));

		$this->SetValue(self::Speed, $speed);
		$this->SetValue(self::Volume, $volume);
		$this->SetValue(self::VolumeLimit, $volumeLimit);

		$this->SendDebug('Payload', 'Update Status Payload done', 0);
		return true;
	}

	public function ReceiveData($JSONString)
	{		
		if($this->ReadPropertyString(self::LinkTapId) == '' || 
			$this->ReadPropertyString(self::UplinkTopic) == '' || 
			//$this->ReadPropertyString('UplinkReplyTopic') == '' ||				 
			//$this->ReadPropertyString('DownlinkReplyTopic') == '' ||
			$this->ReadPropertyString(self::DownlinkTopic) == '') 
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
		$data['Topic'] = $this->ReadPropertyString(self::DownlinkTopic);
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

	function RegisterVariables()
	{
		$this->RegisterVariableBoolean(self::StopWatering, $this->Translate(self::StopWatering), '~Switch', 10);
		$this->EnableAction(self::StopWatering);

		$this->RegisterWateringModes(10);
		$this->RegisterStartWateringImmediately(20);				

		$this->RegisterVariableString(self::TotalDuration, $this->Translate(self::TotalDuration), '', 100); //seconds
		$this->RegisterVariableString(self::RemainDuration, $this->Translate(self::RemainDuration), '', 110); //seconds
		
		$this->RegisterWaterSpeed(120);
		$this->RegisterVolumes(130, 140);

		$this->RegisterVariableInteger(self::Battery, $this->Translate(self::Battery), '~Battery.100', 50);
		$this->RegisterVariableInteger(self::SignalStrength, $this->Translate(self::SignalStrength), '~Intensity.100', 60);

		$this->RegisterVariableBoolean(self::WateringActive, $this->Translate(self::WateringActive), '~Switch', 200);
		$this->RegisterChildLock(210);
		$this->RegisterVariableBoolean(self::IsFlowMeasurementPlugedIn, $this->Translate(self::IsFlowMeasurementPlugedIn), '~Switch', 220);		
		$this->RegisterVariableBoolean(self::ManualMode, $this->Translate(self::ManualMode), '~Switch', 230);
		$this->RegisterVariableBoolean(self::IsRfLinked, $this->Translate(self::IsRfLinked), '~Switch', 240);	
		$this->RegisterVariableBoolean(self::EcoFinal, $this->Translate(self::EcoFinal), '~Switch', 250);

		$this->RegisterVariableBoolean(self::DismissAlert, $this->Translate(self::DismissAlert), '~Switch', 300);
		$this->EnableAction(self::DismissAlert);
		$this->RegisterVariableBoolean(self::FallAlert, $this->Translate(self::FallAlert), '~Alert', 310);
		$this->RegisterVariableBoolean(self::ValveShutdownFailureAlert, $this->Translate(self::ValveShutdownFailureAlert), '~Alert', 320);
		$this->RegisterVariableBoolean(self::WaterCutOffAlert, $this->Translate(self::WaterCutOffAlert), '~Alert', 330);
		$this->RegisterVariableBoolean(self::HighFlowAlert, $this->Translate(self::HighFlowAlert), '~Alert', 340);
		$this->RegisterVariableBoolean(self::LowFlowAlert, $this->Translate(self::LowFlowAlert), '~Alert', 350);

		$this->RegisterVariableString(self::GatewayId, $this->Translate(self::GatewayId), '', 1000);
		$this->RegisterVariableString(self::LastCommandResponse, $this->Translate(self::LastCommandResponse), '', 1001);
	}

	function RegisterStartWateringImmediately(int $Position)
	{
		if(IPS_VariableProfileExists('LINKTAP.IMMEDIATELY.SECONDS'))
			IPS_DeleteVariableProfile('LINKTAP.IMMEDIATELY.SECONDS');
		
		IPS_CreateVariableProfile('LINKTAP.IMMEDIATELY.SECONDS', VARIABLETYPE_INTEGER);
		IPS_SetVariableProfileIcon('LINKTAP.IMMEDIATELY.SECONDS', 'Drops');
		IPS_SetVariableProfileText('LINKTAP.IMMEDIATELY.SECONDS', '', '');
		IPS_SetVariableProfileValues('LINKTAP.IMMEDIATELY.SECONDS', 2, 86340, 1);
		IPS_SetVariableProfileAssociation('LINKTAP.IMMEDIATELY.SECONDS', 0, $this->Translate('ChosseAOption'), '', -1);	
		IPS_SetVariableProfileAssociation('LINKTAP.IMMEDIATELY.SECONDS', 2, $this->Translate('NoWatering'), '', -1);

		IPS_SetVariableProfileAssociation('LINKTAP.IMMEDIATELY.SECONDS', 60, $this->Translate('OneMinute'), 'Drops', 0x0000FF);
		IPS_SetVariableProfileAssociation('LINKTAP.IMMEDIATELY.SECONDS', 120, $this->Translate('TwoMinute'), 'Drops', 0x0000FF);
		IPS_SetVariableProfileAssociation('LINKTAP.IMMEDIATELY.SECONDS', 180, $this->Translate('ThreeMinute'), 'Drops', 0x0000FF);
		IPS_SetVariableProfileAssociation('LINKTAP.IMMEDIATELY.SECONDS', 240, $this->Translate('FourMinute'), 'Drops', 0x0000FF);
		IPS_SetVariableProfileAssociation('LINKTAP.IMMEDIATELY.SECONDS', 300, $this->Translate('FiveMinute'), 'Drops', 0x0000FF);
		IPS_SetVariableProfileAssociation('LINKTAP.IMMEDIATELY.SECONDS', 600, $this->Translate('TenMinute'), 'Drops', 0x0000FF);
		IPS_SetVariableProfileAssociation('LINKTAP.IMMEDIATELY.SECONDS', 900, $this->Translate('FivteenMinute'), 'Drops', 0x0000FF);
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
		
		$this->RegisterVariableInteger(self::StartWateringImmediately, $this->Translate(self::StartWateringImmediately), 'LINKTAP.IMMEDIATELY.SECONDS', $Position);
		$this->EnableAction(self::StartWateringImmediately);
	}

	function RegisterWateringModes(int $Position)
	{
		if(IPS_VariableProfileExists('LINKTAP.WATERINGMODES'))
			IPS_DeleteVariableProfile('LINKTAP.WATERINGMODES');

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
	
		$this->RegisterVariableInteger(self::ActualWateringMode, $this->Translate(self::ActualWateringMode), 'LINKTAP.WATERINGMODES', $Position);
	}

	function RegisterChildLock(int $Position)
	{
		if(IPS_VariableProfileExists('LINKTAP.LOCKS'))
			IPS_DeleteVariableProfile('LINKTAP.LOCKS');

		IPS_CreateVariableProfile('LINKTAP.LOCKS', VARIABLETYPE_INTEGER);
		IPS_SetVariableProfileIcon('LINKTAP.LOCKS', 'Menu');
		IPS_SetVariableProfileText('LINKTAP.LOCKS', '', '');
		IPS_SetVariableProfileValues('LINKTAP.LOCKS', 0, 2, 1);			
		IPS_SetVariableProfileAssociation('LINKTAP.LOCKS', 0, $this->Translate('Unlocked'), '', -1);
		IPS_SetVariableProfileAssociation('LINKTAP.LOCKS', 1, $this->Translate('PartiallyLocked'), '', -1);
		IPS_SetVariableProfileAssociation('LINKTAP.LOCKS', 2, $this->Translate('CompletelyLocked'), '', -1);
	
		$this->RegisterVariableInteger(self::ChildLock, $this->Translate(self::ChildLock), 'LINKTAP.LOCKS', $Position);

	}

	function RegisterVolumes(int $Position1, int $Postion2)
	{
		if(IPS_VariableProfileExists('LINKTAP.VOLUME'))
			IPS_DeleteVariableProfile('LINKTAP.VOLUME');

		IPS_CreateVariableProfile('LINKTAP.VOLUME', VARIABLETYPE_FLOAT);
		IPS_SetVariableProfileIcon('LINKTAP.VOLUME', 'Drops');
		IPS_SetVariableProfileText('LINKTAP.VOLUME', '', ' ' . $this->ReadPropertyString(self::MeasurementUnit));
				
		$this->RegisterVariableFloat(self::Volume, $this->Translate(self::Volume), 'LINKTAP.VOLUME', $Position1); //x
		$this->RegisterVariableFloat(self::VolumeLimit, $this->Translate(self::VolumeLimit), 'LINKTAP.VOLUME', $Postion2); //x
	}

	function RegisterWaterSpeed(int $Position)
	{
		if(IPS_VariableProfileExists('LINKTAP.SPEED'))
			IPS_DeleteVariableProfile('LINKTAP.SPEED');

		IPS_CreateVariableProfile('LINKTAP.SPEED', VARIABLETYPE_FLOAT);
		IPS_SetVariableProfileIcon('LINKTAP.SPEED', 'Drops');
		IPS_SetVariableProfileText('LINKTAP.SPEED', '', ' ' . $this->ReadPropertyString(self::MeasurementUnit) . '/min');

		$this->RegisterVariableFloat(self::Speed, $this->Translate(self::Speed), 'LINKTAP.SPEED', $Position); //x/min
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