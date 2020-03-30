<?php

/*
 * @module      Treppenhauslichtsteuerung
 *
 * @prefix      THLS
 *
 * @file        module.php
 *
 * @author      Ulrich Bittner
 * @copyright   (c) 2019
 * @license    	CC BY-NC-SA 4.0
 *              https://creativecommons.org/licenses/by-nc-sa/4.0/
 *
 * @version     1.00-3
 * @date        2020-03-30, 18:00, 1585584000
 * @review      2020-03-30, 18:00
 *
 * @see         https://github.com/ubittner/Treppenhauslichtsteuerung/
 *
 * @guids       Library
 *              {0B7FD699-FC44-0916-EB16-4AAB8351575F}
 *
 *              Treppenhauslichtsteuerung
 *             	{9B7DC4A2-2197-EE7E-1288-6248CD3D7C70}
 */

// Declare
declare(strict_types=1);

// Include
include_once __DIR__ . '/helper/autoload.php';

class Treppenhauslichtsteuerung extends IPSModule
{
    // Helper
    use THLS_backupRestore;
    use THLS_lights;
    use THLS_trigger;

    // Constants
    private const DELAY = 1250;

    public function Create()
    {
        // Never delete this line!
        parent::Create();

        // Register properties
        $this->RegisterProperties();

        // Create profiles
        $this->CreateProfiles();

        // Register variables
        $this->RegisterVariables();

        // Register timers
        $this->RegisterTimers();
    }

    public function ApplyChanges()
    {
        // Wait until IP-Symcon is started
        $this->RegisterMessage(0, IPS_KERNELSTARTED);

        // Never delete this line!
        parent::ApplyChanges();

        // Check runlevel
        if (IPS_GetKernelRunlevel() != KR_READY) {
            return;
        }

        // Register messages
        $this->RegisterMessages();

        // Set Options
        $this->SetOptions();

        // Switch lights off
        $this->SwitchLightsOff();
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data): void
    {
        // Send debug
        // $Data[0] = actual value
        // $Data[1] = value changed
        // $Data[2] = last value
        $this->SendDebug(__FUNCTION__, 'SenderID: ' . $SenderID . ', Message: ' . $Message . ', Data: ' . print_r($Data, true), 0);
        if (!empty($Data)) {
            foreach ($Data as $key => $value) {
                $this->SendDebug(__FUNCTION__, 'Data[' . $key . '] = ' . json_encode($value), 0);
            }
        }
        switch ($Message) {
            case IPS_KERNELSTARTED:
                $this->KernelReady();
                break;

            case VM_UPDATE:
                $scriptText = 'THLS_CheckTrigger(' . $this->InstanceID . ', ' . $SenderID . ');';
                IPS_RunScriptText($scriptText);
                break;

            default:
                break;

        }
    }

    private function KernelReady()
    {
        $this->ApplyChanges();
    }

    public function Destroy()
    {
        // Never delete this line!
        parent::Destroy();

        // Delete profiles
        $this->DeleteProfiles();
    }

    public function ReloadConfiguration()
    {
        $this->ReloadForm();
    }

    public function GetConfigurationForm()
    {
        $formData = json_decode(file_get_contents(__DIR__ . '/form.json'));
        // Registered messages
        $registeredVariables = $this->GetMessageList();
        foreach ($registeredVariables as $senderID => $messageID) {
            if (!IPS_ObjectExists($senderID)) {
                foreach ($messageID as $messageType) {
                    $this->UnregisterMessage($senderID, $messageType);
                }
                continue;
            } else {
                $senderName = IPS_GetName($senderID);
                $description = $senderName;
                $parentID = IPS_GetParent($senderID);
                if (is_int($parentID) && $parentID != 0 && @IPS_ObjectExists($parentID)) {
                    $description = IPS_GetName($parentID);
                }
            }
            switch ($messageID) {
                case [10001]:
                    $messageDescription = 'IPS_KERNELSTARTED';
                    break;

                case [10603]:
                    $messageDescription = 'VM_UPDATE';
                    break;

                case [10803]:
                    $messageDescription = 'EM_UPDATE';
                    break;

                default:
                    $messageDescription = 'keine Bezeichnung';
            }
            $formData->actions[1]->items[0]->values[] = [
                'Description'        => $description,
                'SenderID'           => $senderID,
                'SenderName'         => $senderName,
                'MessageID'          => $messageID,
                'MessageDescription' => $messageDescription];
        }
        return json_encode($formData);
    }

    //#################### Request action

    public function RequestAction($Ident, $Value)
    {
        switch ($Ident) {
            case 'AutomaticMode':
                $this->ToggleAutomaticMode($Value);
                break;

        }
    }

    public function ToggleAutomaticMode(bool $State): void
    {
        $this->SetValue('AutomaticMode', $State);
    }

    //#################### Private

    private function RegisterProperties(): void
    {
        // Visibility
        $this->RegisterPropertyBoolean('EnableAutomaticMode', true);
        $this->RegisterPropertyBoolean('EnableLightStatus', true);
        $this->RegisterPropertyBoolean('EnableDutyCycle', true);

        // Trigger
        $this->RegisterPropertyString('TriggerVariables', '[]');

        // Twilight
        $this->RegisterPropertyInteger('TwilightStatus', 0);
        $this->RegisterPropertyInteger('TwilightMode', 1);

        // Duty cycle
        $this->RegisterPropertyInteger('DutyCycle', 3);

        // Lights
        $this->RegisterPropertyString('LightVariables', '[]');
    }

    private function CreateProfiles(): void
    {
        // Light status
        $profileName = 'THLS.' . $this->InstanceID . '.LightStatus';
        if (!IPS_VariableProfileExists($profileName)) {
            IPS_CreateVariableProfile($profileName, 1);
        }
        IPS_SetVariableProfileAssociation($profileName, 0, 'Aus', 'Bulb', 0x0000FF);
        IPS_SetVariableProfileAssociation($profileName, 1, 'An', 'Bulb', 0x00FF00);
        IPS_SetVariableProfileAssociation($profileName, 2, 'Lichter werden ausgeschaltet', 'Bulb', -1);
        IPS_SetVariableProfileAssociation($profileName, 3, 'Lichter werden eingeschaltet', 'Bulb', -1);
    }

    private function DeleteProfiles(): void
    {
        $profiles = ['LightStatus'];
        foreach ($profiles as $profile) {
            $profileName = 'THLS.' . $this->InstanceID . '.' . $profile;
            if (@IPS_VariableProfileExists($profileName)) {
                IPS_DeleteVariableProfile($profileName);
            }
        }
    }

    private function RegisterVariables(): void
    {
        // Automatic mode
        $this->RegisterVariableBoolean('AutomaticMode', 'Automatik', '~Switch', 0);
        $this->EnableAction('AutomaticMode');

        // Light status
        $profile = 'THLS.' . $this->InstanceID . '.LightStatus';
        $this->RegisterVariableInteger('LightStatus', 'Lichtstatus', $profile, 1);

        // Duty cycle info
        $this->RegisterVariableString('DutyCycleInfo', 'Einschaltdauer bis', '', 2);
        $id = $this->GetIDForIdent('DutyCycleInfo');
        IPS_SetIcon($id, 'Clock');
    }

    private function SetOptions(): void
    {
        // Automatic mode
        IPS_SetHidden($this->GetIDForIdent('AutomaticMode'), !$this->ReadPropertyBoolean('EnableAutomaticMode'));

        // Light status
        IPS_SetHidden($this->GetIDForIdent('LightStatus'), !$this->ReadPropertyBoolean('EnableLightStatus'));

        // Duty cycle info
        IPS_SetHidden($this->GetIDForIdent('DutyCycleInfo'), !$this->ReadPropertyBoolean('EnableDutyCycle'));
    }

    private function UnregisterMessages(): void
    {
        foreach ($this->GetMessageList() as $id => $registeredMessage) {
            foreach ($registeredMessage as $messageType) {
                if ($messageType == VM_UPDATE) {
                    $this->UnregisterMessage($id, VM_UPDATE);
                }
                if ($messageType == EM_UPDATE) {
                    $this->UnregisterMessage($id, EM_UPDATE);
                }
            }
        }
    }

    private function RegisterMessages(): void
    {
        // Unregister first
        $this->UnregisterMessages();
        // Register variables
        $triggerVariables = json_decode($this->ReadPropertyString('TriggerVariables'));
        if (!empty($triggerVariables)) {
            foreach ($triggerVariables as $variable) {
                if ($variable->Activated) {
                    if ($variable->ID != 0 && @IPS_ObjectExists($variable->ID)) {
                        $this->RegisterMessage($variable->ID, VM_UPDATE);
                    }
                }
            }
        }
    }

    private function RegisterTimers(): void
    {
        $this->RegisterTimer('SwitchLightsOff', 0, 'THLS_SwitchLightsOff(' . $this->InstanceID . ');');
    }

    private function DeactivateTimer(): void
    {
        $this->SetTimerInterval('SwitchLightsOff', 0);
        $this->SetValue('DutyCycleInfo', '-');
    }
}