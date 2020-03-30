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
        $this->SetDutyCycleTimer();
        $this->SendDebug(__FUNCTION__, 'Alle Lichter werden eingeschaltet.', 0);
        $this->SetValue('LightStatus', 1);
        $lights = json_decode($this->ReadPropertyString('LightVariables'));
        $toggleStatus = [];
        $i = 0;
        foreach ($lights as $light) {
            if ($light->Activated) {
                $id = $light->ID;
                if ($id != 0 && @IPS_ObjectExists($id)) {
                    $toggleStatus[$id] = true;
                    $i++;
                    $switchOnValue = boolval($light->SwitchOnValue);
                    $toggle = @RequestAction($id, $switchOnValue);
                    if (!$toggle) {
                        IPS_Sleep(self::DELAY);
                        $toggleAgain = @RequestAction($id, $switchOnValue);
                        if (!$toggleAgain) {
                            $toggleStatus[$id] = false;
                            $this->SendDebug(__FUNCTION__, 'Fehler, das Licht mit der ID ' . $id . ' konnte nicht eingeschaltet werden!', 0);
                            IPS_LogMessage(__FUNCTION__, 'Fehler, das Licht mit der ID ' . $id . ' konnte nicht eingeschaltet werden!');
                        }
                    }
                    if ($i < $amount) {
                        $this->SendDebug(__FUNCTION__, 'Die Verzögerung wird ausgeführt.', 0);
                        IPS_Sleep(self::DELAY);
                    }
                }
            }
        }
        if (!in_array(true, $toggleStatus)) {
            // Revert switch
            $this->SetValue('LightStatus', 0);
        }
        if (in_array(true, $toggleStatus)) {
            $this->SendDebug(__FUNCTION__, 'Die Lichter wurden eingeschaltet.', 0);
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
        $this->SendDebug(__FUNCTION__, 'Alle Lichter werden ausgeschaltet.', 0);
        $this->SetValue('LightStatus', 0);
        $lights = json_decode($this->ReadPropertyString('LightVariables'));
        $toggleStatus = [];
        $i = 0;
        foreach ($lights as $light) {
            if ($light->Activated) {
                $id = $light->ID;
                if ($id != 0 && @IPS_ObjectExists($id)) {
                    $toggleStatus[$id] = true;
                    $i++;
                    $switchOffValue = boolval($light->SwitchOffValue);
                    $toggle = @RequestAction($id, $switchOffValue);
                    if (!$toggle) {
                        IPS_Sleep(self::DELAY);
                        $toggleAgain = @RequestAction($id, $switchOffValue);
                        if (!$toggleAgain) {
                            $toggleStatus[$id] = false;
                            $this->SendDebug(__FUNCTION__, 'Fehler, das Licht mit der ID ' . $id . ' konnte nicht ausgeschaltet werden!', 0);
                            IPS_LogMessage(__FUNCTION__, 'Fehler, das Licht mit der ID ' . $id . ' konnte nicht ausgeschaltet werden!');
                        }
                    }
                    if ($i < $amount) {
                        $this->SendDebug(__FUNCTION__, 'Die Verzögerung wird ausgeführt.', 0);
                        IPS_Sleep(self::DELAY);
                    }
                }
            }
        }
        if (!in_array(true, $toggleStatus)) {
            // Revert switch
            $this->SetValue('LightStatus', 1);
        }
        if (in_array(true, $toggleStatus)) {
            $this->SendDebug(__FUNCTION__, 'Die Lichter wurden ausgeschaltet.', 0);
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