<?php
/**
 * Copyright Sangoma Technologies, Inc 2018
 */
namespace FreePBX\modules\Backup\Handlers;

use FreePBX\modules\Filestore\Modules\Remote as FilestoreRemote;

use Exception;
class Warmspare{
	public function __construct($freepbx = null) {
		if ($freepbx == null) {
			throw new \Exception('Not given a FreePBX Object');
		}
		$this->FreePBX = $freepbx;
		$this->backupdata = '';
	}
	public function process($id){
		$backupData = $this->getBackupString($id);
		$ssh = new FilestoreRemote();
		$host = $this->backupdata['warmspare_remoteip'];
		if(!$host){
			return;
		}
		$ssh->createSSH($host);
		$user = isset($this->backupdata['warmspare_user'])?$this->backupdata['warmspareuser']:'root';
		$homepath = '/home/'.$user;
		if($user == 'root'){
			$homepath = '/root';
		}
		$keypath = $homepath. '/.ssh/id_rsa';
		$ssh->authenticateSSH($user,$keypath);
		$transaction = 'remote-backup-'.time();
		$command = sprintf('/usr/sbin/fwconsole backup --externbackup=%s --transaction=%s',$backupData, $transaction);
		$ssh->sendCommand($command);
		$ssh->grabFile($homepath.'/'.$transaction.'.tar.gz', $homepath . '/' . $transaction . '.tar.gz');
		exec('/usr/sbin/fwconsole backup --warmspare --restore='. $homepath . '/' . $transaction . '.tar.gz',$out,$ret);
		return $ret;
	}

	public function getBackupString($id){
		$this->backupdata = $this->FreePBX->Backup->getBackup($id);
		$this->backupdata['backup_items'] = $this->freepbx->Backup->getAll('modules_' . $id);
		return base64_encode(json_encode($this->backupdata));
	}
}
