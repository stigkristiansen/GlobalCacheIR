<?



class GlobalCacheIR extends IPSModule
{
    
    
    public function Create()
    {
        parent::Create();
        $this->RequireParent("{6DC3D946-0D31-450F-A8C6-C42DB8D7D4F1}");
        
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
		return true;
	}
    
	public function RegisterDevice($Device) {
		
	}
	
	public function UnregisterDevice($Device) {
		
	}
	
	public function RegisterCommand($Device, $Command, $IRCode) {
		
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
