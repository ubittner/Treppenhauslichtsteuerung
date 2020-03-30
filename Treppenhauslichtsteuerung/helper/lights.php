<?php

// Declare
declare(strict_types=1);

trait THLS_lights
{
    /**
     * Switches the lights on.
     */
    public function SwitchLightsOn(): void
    {
        $this->SendDebug(__FUNCTION__, 'Die Methode wird ausgeführt. (' . microtime(true) . ')', 0);
        $amount = $this->GetAmountOfLights();
        if ($amount == 0) {
            $this->SendDebug(__FUNCTION__, 'Abbruch, es sind keine zu schaltenden Lichter vorhanden!', 0);
            return;
        }
        $this->SendDebug(__FUNCTION__, 'Lichter werden eingeschaltet.', 0);
        $this->SetValue('LightStatus', 3);
        $lights = json_decode($this->ReadPropertyString('LightVariables'));
        $i = 0;
        $switchStatus = false;
        foreach ($lights as $light) {
            if ($light->Activated) {
                $id = $light->ID;
                if ($id != 0 && @IPS_ObjectExists($id)) {
                    $i++;
                    $switchOnValue = boolval($light->SwitchOnValue);
                    $toggle = @RequestAction($id, $switchOnValue);
                    if (!$toggle) {
                        IPS_Sleep(self::DELAY);
                        $toggleAgain = @RequestAction($id, $switchOnValue);
                        if (!$toggleAgain) {
                            $this->SendDebug(__FUNCTION__, 'Fehler, Licht mit der ID ' . $id . ' konnte nicht eingeschaltet werden!', 0);
                            IPS_LogMessage(__FUNCTION__, 'Fehler, Licht mit der ID ' . $id . ' konnte nicht eingeschaltet werden!');
                        } else {
                            $switchStatus = true;
                        }
                    } else {
                        $switchStatus = true;
                    }
                    if ($i < $amount) {
                        $this->SendDebug(__FUNCTION__, 'Verzögerung wird ausgeführt.', 0);
                        IPS_Sleep(self::DELAY);
                    }
                }
            }
        }
        if ($switchStatus) {
            $this->SendDebug(__FUNCTION__, 'Lichter wurden eingeschaltet.', 0);
            $this->SetValue('LightStatus', 1);
        }
    }

    /**
     * Switches the lights off.
     */
    public function SwitchLightsOff(): void
    {
        $this->SendDebug(__FUNCTION__, 'Die Methode wird ausgeführt. (' . microtime(true) . ')', 0);
        $this->DeactivateTimer();
        $amount = $this->GetAmountOfLights();
        if ($amount == 0) {
            $this->SendDebug(__FUNCTION__, 'Abbruch, es sind keine zu schaltenden Lichter vorhanden!', 0);
            return;
        }
        $this->SendDebug(__FUNCTION__, 'Lichter werden ausgeschaltet.', 0);
        $this->SetValue('LightStatus', 2);
        $lights = json_decode($this->ReadPropertyString('LightVariables'));
        $i = 0;
        $switchStatus = false;
        foreach ($lights as $light) {
            if ($light->Activated) {
                $id = $light->ID;
                if ($id != 0 && @IPS_ObjectExists($id)) {
                    $i++;
                    $switchOffValue = boolval($light->SwitchOffValue);
                    $toggle = @RequestAction($id, $switchOffValue);
                    if (!$toggle) {
                        IPS_Sleep(self::DELAY);
                        $toggleAgain = @RequestAction($id, $switchOffValue);
                        if (!$toggleAgain) {
                            $this->SendDebug(__FUNCTION__, 'Fehler, Licht mit der ID ' . $id . ' konnte nicht ausgeschaltet werden!', 0);
                            IPS_LogMessage(__FUNCTION__, 'Fehler, Licht mit der ID ' . $id . ' konnte nicht ausgeschaltet werden!');
                        } else {
                            $switchStatus = true;
                        }
                    } else {
                        $switchStatus = true;
                    }
                    if ($i < $amount) {
                        $this->SendDebug(__FUNCTION__, 'Verzögerung wird ausgeführt.', 0);
                        IPS_Sleep(self::DELAY);
                    }
                }
            }
        }
        if ($switchStatus) {
            $this->SendDebug(__FUNCTION__, 'Lichter wurden ausgeschaltet.', 0);
            $this->SetValue('LightStatus', 0);
        }
    }

    //##################### Private

    /**
     * Gets the amount of lights and returns the value.
     *
     * @return int
     */
    private function GetAmountOfLights(): int
    {
        $amount = 0;
        $lights = json_decode($this->ReadPropertyString('LightVariables'));
        if (!empty($lights)) {
            foreach ($lights as $light) {
                if ($light->Activated) {
                    $id = $light->ID;
                    if ($id != 0 && @IPS_ObjectExists($id)) {
                        $amount++;
                    }
                }
            }
        }
        return $amount;
    }
}