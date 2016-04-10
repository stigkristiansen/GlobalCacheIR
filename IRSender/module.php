<?

require_once(__DIR__ . "/../Logging.php");

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
		
    }
    

    public function ReceiveData($JSONString) {
	
		return true;
    }
	
	public function SendCommand($Device, $Command) {
		$cIdent = preg_replace("/[^a-zA-Z0-9]+/", "", $Device);
		$cId = @IPS_GetObjectIDByIdent($cIdent, $this->InstanceID);
	
		$log = new Logging($this->ReadPropertyBoolean("log"), IPS_Getname($this->InstanceID));
	
		if($cId !== false) {
			$vIdent = preg_replace("/[^a-zA-Z0-9]+/", "", $Command);
			$vId = @IPS_GetObjectIDByIdent($vIdent, $cId);
			if($vId !== false) {
				$log->LogMessage("Sending command: ".$Device.":".$Command);
				$buffer = "sendir,".$this->ReadPropertyString("port").",".IPS_GetParent($cId).",".GetValueString($vId);
				$this->SendDataToParent(json_encode(Array("DataID" => "{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}", "Buffer" => $buffer)));
				return true;
			} 
			$log->LogMessage("The command is not registered: ".$Device.":".$Command);
			return false;
		}
		
		$log->LogMessage("The device is not registered: ".$Device);
		return false;
	}
    
	public function RegisterDevice($Device) {
		$ident = preg_replace("/[^a-zA-Z0-9]+/", "", $Device);

		$log = new Logging($this->ReadPropertyBoolean("log"), IPS_Getname($this->InstanceID));
		
		$cId = @IPS_GetObjectIDByIdent($ident, $this->InstanceID);
		if($cId === false) {
			$cId = IPS_CreateCategory();
			IPS_SetParent($cId, $this->InstanceID);
			IPS_SetName($cId, $Device);
			IPS_SetIdent($cId, $ident);
			IPS_SetHidden($cId, true);
			
			$log->LogMessage("The device has been registered: ". $Device);
			return $cId;
		}
		
		$log->LogMessage("The device already exists: ". $Device);
		return $cId;
		
	}
	
	public function UnregisterDevice($Device) {
		$ident = preg_replace("/[^a-zA-Z0-9]+/", "", $Device);
		
		$log = new Logging($this->ReadPropertyBoolean("log"), IPS_Getname($this->InstanceID));
		
		$cId = @IPS_GetObjectIDByIdent($ident, $this->InstanceID);
		if($cId !== false) {
			if(!IPS_HasChildren($cId)) {
				IPS_DeleteCategory($cId);
				$log->LogMessage("Unregistered the device: ".$Device);
				return true;
			}
			$log->LogMessage("The device has registred commands: ".$Device." Remove all commands first");
			return false;
		}
		
		$log->LogMessage("The device does not exists: ".$Device);
		return false;
	}
	
	public function RegisterCommand($Device, $Command, $IRCode) {
		$cId = $this->RegisterDevice($Device);
				
		$log = new Logging($this->ReadPropertyBoolean("log"), IPS_Getname($this->InstanceID));		
				
		if($cId>0) {
			$ident = preg_replace("/[^a-zA-Z0-9]+/", "", $Command);
			$vId = @IPS_GetObjectIDByIdent($ident, $cId);
			if($vId === false) {
				$vId = IPS_CreateVariable(3); // Create String variable
				IPS_SetParent($vId, $cId);
				IPS_SetName($vId, $Command);
				IPS_SetIdent($vId, $ident);
				IPS_SetHidden($vId, true);
			}
			
			SetValueString($vId, $IRCode);
			$log->LogMessage("The command is registred: ".$Device.":".$Command);
			
			return $vId;
		}
		
		$log->LogMessage("Unable to register the command. Missing device: ".$Device.":".$Command);
		return 0;
	}
	
	public function UnregisterCommand($Device, $Command) {
		$cIdent = preg_replace("/[^a-zA-Z0-9]+/", "", $Device);

		$log = new Logging($this->ReadPropertyBoolean("log"), IPS_Getname($this->InstanceID));
		
		$cId = @IPS_GetObjectIDByIdent($cIdent, $this->InstanceID);
		if($cId !== false) {
			$vIdent = preg_replace("/[^a-zA-Z0-9]+/", "", $Command);
			$vId = @IPS_GetObjectIDByIdent($vIdent, $cId);
			if($vId !== false) {
				IPS_DeleteVariable($vId);
				
				$log->LogMessage("The command has been unregistered: ".$Command);
				return true;
			}
			
			$log->LogMessage("The command does not exists: ".$Command);
			return false;
		}
		
		$log->LogMessage("The device does not exists: ".$Device);
		return false;
	}

	    private function Lock($ident)   {
        for ($i = 0; $i < 100; $i++)
        {
            if (IPS_SemaphoreEnter("GCIR_" . (string) $this->InstanceID . (string) $ident, 1))
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
        IPS_SemaphoreLeave("GCIR_" . (string) $this->InstanceID . (string) $ident);
		$log = new Logging($this->ReadPropertyBoolean("log"), IPS_Getname($this->InstanceID));
		$log->LogMessage("Buffer is unlocked");
    }
	
}

?>
