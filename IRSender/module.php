<?

class GlobalCacheIR extends IPSModule
{
    
    
    public function Create()
    {
        parent::Create();
        $this->RequireParent("{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}");
        
        $this->RegisterPropertyBoolean ("log", false );
		$this->RegisterPropertyString ("port", "1:1" );
    
	}

    public function ApplyChanges()
    {
        parent::ApplyChanges();
		$this->RegisterVariableString("SenderId", "SenderId");			
//		$this->RegisterVariableString("LastCommand", "LastCommand");

		IPS_SetHidden($this->GetIDForIdent('SenderId'), true);
//      IPS_SetHidden($this->GetIDForIdent('LastCommand'), true);    nd'), true);    
    }
    

    public function ReceiveData($JSONString) {
	
	    $incomingData = json_decode($JSONString);
		$incomingBuffer = utf8_decode($incomingData->Buffer);
		
		return true;
    }
	
	public function SendCode($Device, $Command) {
		$cIdent = preg_replace("/[^a-zA-Z0-9]+/", "", $Device);
		$cId = @IPS_GetObjectIDByIdent($cIdent, $this->InstanceID);
		
		if($cId !== false) {
			$vIdent = preg_replace("/[^a-zA-Z0-9]+/", "", $Command);
			$vId = @IPS_GetObjectIDByIdent($vIdent, $cId);
			if($vId !== false) {
				$buffer = "".GetValueString($vId);
				$this->SendDataToParent(json_encode(Array("DataID" => "{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}", "Buffer" => $buffer)));
				return true;
			}
			return false;
		}
		
		return false;
	}
    
	public function RegisterDevice($Device) {
		$ident = preg_replace("/[^a-zA-Z0-9]+/", "", $Device);

		$cId = 0;
		$cId = @IPS_GetObjectIDByIdent($ident, $this->InstanceID);
		if($cId === false) {
			$cId = IPS_CreateCategory();
			IPS_SetParent($cId, $this->InstanceID);
			IPS_SetName($cId, $Device);
			IPS_SetIdent($cId, $ident);
		}

		return $cId;
	}
	
	public function UnregisterDevice($Device) {
		$ident = preg_replace("/[^a-zA-Z0-9]+/", "", $Device);
		
		$cId = @IPS_GetObjectIDByIdent($ident, $this->InstanceID);
		if($cId !== false) {
			IPS_DeleteCategory($cId);
			return true;
		}
		
		return false;
	}
	
	public function RegisterCommand($Device, $Command, $IRCode) {
		$cId = $this->RegisterDevice($Device);
				
		$vId = 0;
		if($cId>0) {
			$ident = preg_replace("/[^a-zA-Z0-9]+/", "", $Command);
			$vId = @IPS_GetObjectIDByIdent($ident, $cId);
			if($vId === false) {
				$vId = IPS_CreateVariable(3); // Create String variable
				IPS_SetParent($vId, $cId);
				IPS_SetName($vId, $Command);
				IPS_SetIdent($vId, $ident);
			}
			
			if($vId>0) {
				SetValueString($vId, $IRCode);
			}
		}
		
		return $vId;
	}
	
	public function UnregisterCommand($Device, $Command) {
		$cIdent = preg_replace("/[^a-zA-Z0-9]+/", "", $Device);

		$cId = @IPS_GetObjectIDByIdent($cIdent, $this->InstanceID);
		if($cId !== false) {
			$vIdent = preg_replace("/[^a-zA-Z0-9]+/", "", $Command);
			$vId = @IPS_GetObjectIDByIdent($vIdent, $cId);
			if($vId !== false) {
				IPS_DeleteVariable($vId);
				
				return true;
			}
			return false;
		}
		
		return false;
	}

    private function Lock($ident)   {
        for ($i = 0; $i < 100; $i++)
        {
            if (IPS_SemaphoreEnter("TSG_" . (string) $this->InstanceID . (string) $ident, 1))
            {
                return true;
            }
            else
            {
                $log = new Logging($this->ReadPropertyBoolean("log"), IPS_Getname($this->InstanceID));
		$log->LogMessage("Waiting for lock");
				IPS_Sleep(mt_rand(1, 5));
            }
        }
        return false;
    }

    private function Unlock($ident)
    {
        IPS_SemaphoreLeave("TSG_" . (string) $this->InstanceID . (string) $ident);
		$log = new Logging($this->ReadPropertyBoolean("log"), IPS_Getname($this->InstanceID));
		$log->LogMessage("Buffer is unlocked");
    }
}

?>
