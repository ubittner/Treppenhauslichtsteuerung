<?php

// Declare
declare(strict_types=1);

trait THLS_trigger
{
    /**
     * Checks the trigger.
     *
     * @param int $VariableID
     */
    public function CheckTrigger(int $VariableID): void
    {
        $this->SendDebug(__FUNCTION__, 'Die Methode wird ausgeführt. (' . microtime(true) . ')', 0);

        // Check automatic mode
        $automaticMode = $this->GetValue('AutomaticMode');
        if (!$automaticMode) {
            $this->SendDebug(__FUNCTION__, 'Abbruch, Automatik ist ausgeschaltet!', 0);
            return;
        }

        // Check if actual value is a triggering value
        $trigger = false;
        $lights = json_decode($this->ReadPropertyString('TriggerVariables'), true);
        $key = array_search($VariableID, array_column($lights, 'ID'));
        if (is_int($key)) {
            $actualValue = boolval(GetValue($VariableID));
            $this->SendDebug(__FUNCTION__, 'Aktueller Wert: ' . json_encode($actualValue), 0);
            $triggerValue = boolval($lights[$key]['TriggerValue']);
            $this->SendDebug(__FUNCTION__, 'Auslösender Wert: ' . json_encode($triggerValue), 0);
            if ($actualValue == $triggerValue) {
                $trigger = true;
            } else {
                $this->SendDebug(__FUNCTION__, 'Variable hat nicht ausgelöst.', 0);
            }
        }

        // Check twilight
        $twilight = $this->ReadPropertyInteger('TwilightStatus');
        if ($twilight != 0 && @IPS_ObjectExists($twilight)) {
            $twilightStatus = boolval(GetValue($twilight));
            $twilightMode = $this->ReadPropertyInteger('TwilightMode');
            switch ($twilightStatus) {
                // Is day
                case false:
                    // Is night
                    if ($twilightMode == 1) {
                        $this->SendDebug(__FUNCTION__, 'Abbruch, aktueller Dämmerungsstatus: Es ist Tag, Prüfung auf: Es ist Nacht!', 0);
                        $trigger = false;
                    }
                    break;

                // Is night
                case true:
                    // Is day
                    if ($twilightMode == 0) {
                        $this->SendDebug(__FUNCTION__, 'Abbruch, aktueller Dämmerungsstatus: Es ist Nacht, Prüfung auf: Es ist Tag!', 0);
                        $trigger = false;
                    }
                    break;

            }
        }

        // We have a trigger value
        if ($trigger) {
            $this->SendDebug(__FUNCTION__, 'Variable hat ausgelöst.', 0);
            $duration = $this->ReadPropertyInteger('DutyCycle') * 60;
            $this->SetTimerInterval('SwitchLightsOff', $duration * 1000);
            $timestamp = time() + $duration;
            $this->SetValue('DutyCycleInfo', date('d.m.Y, H:i:s', ($timestamp)));
            $lightStatus = intval($this->GetValue('LightStatus'));
            if ($lightStatus == 0) {
                $this->SwitchLightsOn();
            }
        }
    }
}