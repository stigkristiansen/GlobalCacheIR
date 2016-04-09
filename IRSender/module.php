<?

class GlobalCacheIR extends IPSModule
{
    
    
    public function Create()
    {
        parent::Create();
        $this->RequireParent("{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}");
        
        $this->RegisterPropertyBoolean ("log", false );
		
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();
    
//		$this->RegisterVariableString("Buffer", "Buffer");	
//		$this->RegisterVariableString("LastCommand", "LastCommand");

//		IPS_SetHidden($this->GetIDForIdent('Buffer'), true);
//        IPS_SetHidden($this->GetIDForIdent('LastCommand'), true);    
    }
    

    public function ReceiveData($JSONString) {
	
	    $incomingData = json_decode($JSONString);
		$incomingBuffer = utf8_decode($incomingData->Buffer);
		
		return true;
    }
	
	public function SendCode($Device, $Command) {
		
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
		}
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
			
			if(vId>0) {
				IPS_SetValueString($vId, $IRCode);
			}
		}
		
		return $vId;
	}
	
	public function UnregisterCommand($Device, $Command) {
		
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
