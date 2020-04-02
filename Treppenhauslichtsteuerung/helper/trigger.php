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

        // Check if actual value is a triggering value
        $lights = json_decode($this->ReadPropertyString('TriggerVariables'), true);
        $key = array_search($VariableID, array_column($lights, 'ID'));
        if (is_int($key)) {
            $actualValue = boolval(GetValue($VariableID));
            $this->SendDebug(__FUNCTION__, 'Aktueller Wert: ' . json_encode($actualValue), 0);
            $triggerValue = boolval($lights[$key]['TriggerValue']);
            $triggerAction = intval($lights[$key]['TriggerAction']);
            $this->SendDebug(__FUNCTION__, 'Auslösender Wert: ' . json_encode($triggerValue), 0);

            // We have a trigger value
            if ($actualValue == $triggerValue) {
                $this->SendDebug(__FUNCTION__, 'Die Variable ' . $VariableID . ' hat ausgelöst.', 0);
                // Check action
                switch ($triggerAction) {
                    // Timer
                    case 1:
                        $actionDescription = 'Timerfunktion';
                        break;

                    // On
                    case 2:
                        $actionDescription = 'Einbschalten';
                        break;

                    // Off
                    default:
                        $actionDescription = 'Ausschalten';
                }
                $this->SendDebug(__FUNCTION__, 'Aktion: ' . $triggerAction . ', ' . $actionDescription, 0);
                switch ($triggerAction) {
                    // Off
                    case 0:
                        $this->SwitchLightsOff();
                        break;

                    // Timer
                    case 1:
                        // Check automatic mode
                        $automaticMode = $this->GetValue('AutomaticMode');
                        if (!$automaticMode) {
                            $this->SendDebug(__FUNCTION__, 'Abbruch, Automatik ist ausgeschaltet!', 0);
                            return;
                        }

                        if ($this->GetValue('Light') == 2) {
                            $this->SendDebug(__FUNCTION__, 'Abbruch, die Lichter sind bereits dauerhaft eingeschaltet!', 0);
                            return;
                        }

                        // Check twilight
                        $checkTwilight = true;
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
                                        $checkTwilight = false;
                                    }
                                    break;

                                // Is night
                                case true:
                                    // Is day
                                    if ($twilightMode == 0) {
                                        $this->SendDebug(__FUNCTION__, 'Abbruch, aktueller Dämmerungsstatus: Es ist Nacht, Prüfung auf: Es ist Tag!', 0);
                                        $checkTwilight = false;
                                    }
                                    break;

                            }
                        }
                        if ($checkTwilight) {
                            $this->SwitchLightsOn(true);
                        }
                        break;

                    // On
                    case 2:
                        $this->SwitchLightsOn(false);
                        break;

                }
            } else {
                $this->SendDebug(__FUNCTION__, 'Die Variable ' . $VariableID . ' hat nicht ausgelöst.', 0);
            }
        }
    }
}