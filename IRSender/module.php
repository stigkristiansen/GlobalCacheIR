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

    public function ApplyChanges(){
        parent::ApplyChanges();
        
        $this->RegisterVariableString("LastSendt", "LastSendt");
        $this->RegisterVariableString("LastReceived", "LastReceived");
        
        IPS_SetHidden($this->GetIDForIdent('LastSendt'), true);
        IPS_SetHidden($this->GetIDForIdent('LastReceived'), true);
    }

    public function ReceiveData($JSONString) {
		$incomingData = json_decode($JSONString);
		$incomingBuffer = utf8_decode($incomingData->Buffer);
		
		$log = new Logging($this->ReadPropertyBoolean("log"), IPS_Getname($this->InstanceID));
		$log->LogMessage("Received: ".$incomingBuffer);
		
		if ($this->Lock("LastReceivedLock")) { 
             	    $Id = $this->GetIDForIdent("LastReceived");
		    SetValueString($Id, $incomingBuffer);
		    $log->LogMessage("Updated variable LastReceived");
		    $this->Unlock("LastReceivedLock"); 
		    
        } 

		return true;
    }
	
	public function SendCommand($Device, $Command) {
		if(!$this->EvaluateParent())
			return false;
		
		$cIdent = strtolower(preg_replace("/[^a-zA-Z0-9]+/", "", $Device));
		$cId = @IPS_GetObjectIDByIdent($cIdent, $this->InstanceID);
	
		$log = new Logging($this->ReadPropertyBoolean("log"), IPS_Getname($this->InstanceID));
		
		if($cId !== false) {
			$vIdent = strtolower(preg_replace("/[^a-zA-Z0-9]+/", "", $Command));
			$vId = @IPS_GetObjectIDByIdent($vIdent, $cId);
			if($vId !== false) {
				$log->LogMessage("Sending command: ".$Device.":".$Command);
				$buffer = "sendir,".$this->ReadPropertyString("port").",".IPS_GetParent($cId).",".GetValueString($vId).chr(13).chr(10);
				try{
					$this->SendDataToParent(json_encode(Array("DataID" => "{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}", "Buffer" => $buffer)));
				
					if ($this->Lock("LastSendtLock")) { 
					    $Id = $this->GetIDForIdent("LastSendt");
					    SetValueString($Id, $buffer);
					    $log->LogMessage("Updated variable LastSendt");
					    $this->Unlock("LastSendtLock"); 
					} 
				            
				} catch (Exeption $ex) {
					$log->LogMessageError("Failed to send the command ".$Device.":".$Command." . Error: ".$ex->getMessage());
					
					return false;
				}
				
				return true;
			} 
			$log->LogMessage("The command is not registered: ".$Device.":".$Command);
			return false;
		}
		
		$log->LogMessage("The device is not registered: ".$Device);
		return false;
	}
    
	public function RegisterDevice($Device) {
		$ident = strtolower(preg_replace("/[^a-zA-Z0-9]+/", "", $Device));

		$log = new Logging($this->ReadPropertyBoolean("log"), IPS_Getname($this->InstanceID));
		
		$cId = @IPS_GetObjectIDByIdent($ident, $this->InstanceID);
		if($cId === false) {
			try {
				$cId = IPS_CreateCategory();
			} catch (Exception $ex){
				$log->LogMessageError("Failed to register the device ".$Device." . Error: ".$ex->getMessage());
				return false;
			}
			
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
		$ident = strtolower(preg_replace("/[^a-zA-Z0-9]+/", "", $Device));
		
		$log = new Logging($this->ReadPropertyBoolean("log"), IPS_Getname($this->InstanceID));
		
		$cId = @IPS_GetObjectIDByIdent($ident, $this->InstanceID);
		if($cId !== false) {
			if(!IPS_HasChildren($cId)) {
				try {
					IPS_DeleteCategory($cId);
				} catch (Exeption $ex) {
					$log->LogMessageError("Failed to unregister the device ".$Device." . Error: ".$ex->getMessage());
					return false;
				}
				
				$log->LogMessage("Unregistered the device: ".$Device);
				return true;
			}
			$log->LogMessage("The device ".$Device." has registred commands. Remove all commands first");
			return false;
		}
		
		$log->LogMessage("The device does not exists: ".$Device);
		return false;
	}
	
	public function RegisterCommand($Device, $Command, $IRCode) {
		$cId = $this->RegisterDevice($Device);
				
		$log = new Logging($this->ReadPropertyBoolean("log"), IPS_Getname($this->InstanceID));		
				
		if($cId>0) {
			$ident = strtolower(preg_replace("/[^a-zA-Z0-9]+/", "", $Command));
			$vId = @IPS_GetObjectIDByIdent($ident, $cId);
			if($vId === false) {
				try{
					$vId = IPS_CreateVariable(3); // Create String variable
				} catch (Exeption $ex) {
					$log->LogMessageError("Failed to register the command ".$Device.":".$Command." Error: ".$ex->getMessage());
					return false;
				}
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
		$cIdent = strtolower(preg_replace("/[^a-zA-Z0-9]+/", "", $Device));

		$log = new Logging($this->ReadPropertyBoolean("log"), IPS_Getname($this->InstanceID));
		
		$cId = @IPS_GetObjectIDByIdent($cIdent, $this->InstanceID);
		if($cId !== false) {
			$vIdent = strtolower(preg_replace("/[^a-zA-Z0-9]+/", "", $Command));
			$vId = @IPS_GetObjectIDByIdent($vIdent, $cId);
			if($vId !== false) {
				try{
					IPS_DeleteVariable($vId);
				} catch (Exeption $ex) {
					$log->LogMessageError("Failed to unregister the command ".$Device.":".$Command." Error: ".$ex->getMessage());
					return false;
				}
				
				$log->LogMessage("The command has been unregistered: ".$Device.":".$Command);
				return true;
			}
			
			$log->LogMessage("The command does not exists: ".$Device.":".$Command);
			return false;
		}
		
		$log->LogMessage("The device does not exists: ".$Device);
		return false;
	}

	private function Lock($ident){
		$log = new Logging($this->ReadPropertyBoolean("log"), IPS_Getname($this->InstanceID));
		for ($i = 0; $i < 100; $i++){
			if (IPS_SemaphoreEnter("GCIR_" . (string) $this->InstanceID . (string) $ident, 1)){
				$log->LogMessage($ident." is locked"); 
				return true;
			} else {
				if($i==0)
					$log->LogMessage("Waiting for lock...");
				IPS_Sleep(mt_rand(1, 5));
			}
		}
        
        $log->LogMessage($ident." is already locked"); 
        return false;
    }

    private function Unlock($ident)
    {
        IPS_SemaphoreLeave("GCIR_" . (string) $this->InstanceID . (string) $ident);
		$log = new Logging($this->ReadPropertyBoolean("log"), IPS_Getname($this->InstanceID));
		$log->LogMessage($ident." is unlocked");
    }
	
/*	private function HasActiveParent(){
        $instance = IPS_GetInstance($this->InstanceID);
        if ($instance['ConnectionID'] > 0){
            $parent = IPS_GetInstance($instance['ConnectionID']);
            if ($parent['InstanceStatus'] == 102)
                return true;
        }
        return false;
    }
*/	
	private function EvaluateParent() {
    	$log = new Logging($this->ReadPropertyBoolean("log"), IPS_Getname($this->InstanceID));
		
		if($this->HasActiveParent()) {
            $instance = IPS_GetInstance($this->InstanceID);
            $parentGUID = IPS_GetInstance($instance['ConnectionID'])['ModuleInfo']['ModuleID'];
            if ($parentGUID == '{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}') {
				$log->LogMessage("The parent I/O port is active and supported");
				return true;
			} else
				$log->LogMessageError("The parent I/O port is not supported");
		} else
			$log->LogMessageError("The parent I/O port is not active.");
		
		return false;
	}
}

?>
