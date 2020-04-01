<?php

// Declare
declare(strict_types=1);

trait THLS_lights
{
    /**
     * Switches the lights on.
     *
     * @param bool $UseDutyCycle
     * false    = don't use
     * true    = use
     */
    public function SwitchLightsOn(bool $UseDutyCycle): void
    {
        $this->SendDebug(__FUNCTION__, 'Die Methode wird ausgeführt. (' . microtime(true) . ')', 0);
        $amount = $this->GetAmountOfLights();
        if ($amount == 0) {
            $this->SendDebug(__FUNCTION__, 'Abbruch, es sind keine zu schaltenden Lichter vorhanden!', 0);
            return;
        }
        $actualValue = $this->GetValue('Light');
        $newValue = 2;
        if ($UseDutyCycle) {
            $this->SetDutyCycleTimer();
            $newValue = 1;
        } else {
            $this->DeactivateDutyCycleTimer();
        }
        $this->SendDebug(__FUNCTION__, 'Alle Lichter werden eingeschaltet.', 0);
        $this->SetValue('Light', $newValue);
        if ($actualValue == 1 || $actualValue == 2) {
            $this->SendDebug(__FUNCTION__, 'Abbruch, die Lichter sind bereits eingeschaltet!', 0);
            return;
        }
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
            $this->SetValue('Light', $actualValue);
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
        $this->DeactivateDutyCycleTimer();
        $amount = $this->GetAmountOfLights();
        if ($amount == 0) {
            $this->SendDebug(__FUNCTION__, 'Abbruch, es sind keine zu schaltenden Lichter vorhanden!', 0);
            return;
        }
        $this->SendDebug(__FUNCTION__, 'Alle Lichter werden ausgeschaltet.', 0);
        $actualValue = $this->GetValue('Light');
        $this->SetValue('Light', 0);
        if ($actualValue == 0) {
            $this->SendDebug(__FUNCTION__, 'Abbruch, die Lichter sind bereits ausgeschaltet!', 0);
            return;
        }
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
            $this->SetValue('Light', $actualValue);
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