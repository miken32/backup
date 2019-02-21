<?php
/**
 * Copyright Sangoma Technologies, Inc 2018
 */
namespace FreePBX\modules\Backup\Models;

class ModelBase {
	protected $FreePBX;
	protected $backupModVer;
	protected $data = [
		'version' => null,
		'module' => null,
		'pbx_version' => null,
		'dirs' => [],
		'files' => [],
		'configs' => [],
		'dependencies' => [],
		'garbage' => []
	];

	public function __construct($freepbx, $backupModVer, $logger, $transactionId){
		$this->FreePBX = $freepbx;
		$this->backupModVer = $backupModVer;
		$this->logger = $logger;
		$this->transactionId = $transactionId;
	}

	/**
	 * Get Directories
	 *
	 * @param array $options
	 * @return array
	 */
	public function getDirs($options = []) {
		return $this->data['dirs'];
	}

	/**
	 * Get Directories Alias
	 *
	 * @param array $options
	 * @return array
	 */
	public function getDirectories($options = []) {
		return $this->getDirs($options);
	}

	/**
	 * Get Files
	 *
	 * @param array $options
	 * @return array
	 */
	public function getFiles($options = []) {
		return $this->data['files'];
	}

	/**
	 * Get Configurations
	 *
	 * @param array $options
	 * @return array
	 */
	public function getConfigs($options = []){
		return $this->data['configs'];
	}

	/**
	 * Get Module Dependencies
	 *
	 * @param array $options
	 * @return array
	 */
	public function getDependencies($options = []){
		return $this->data['dependencies'];
	}

	/**
	 * Get Extra Data
	 *
	 * @param array $options
	 * @return array
	 */
	public function getExtraData($options = []) {
		return $this->data['extradata'];
	}

	/**
	 * Get Raw Data
	 *
	 * @return array
	 */
	public function getData(){
		return $this->data;
	}

	/**
	 * Get Module Version
	 *
	 * @return string
	 */
	public function getVersion() {
		return $this->data['version'];
	}

	/**
	 * Logging functionality
	 *
	 * @param string $message
	 * @param string $level
	 * @return void
	 */
	protected function log($message = '',$level = 'INFO'){
		if(!$this->logger) {
			$this->setupLogger();
		}
		$logger = $this->logger->withName($this->transactionId);
		switch ($level) {
			case 'DEBUG':
				return $logger->debug($message);
			case 'NOTICE':
				return $logger->notice($message);
			case 'WARNING':
				return $logger->warning($message);
			case 'ERROR':
				return $logger->error($message);
			case 'CRITICAL':
				return $logger->critical($message);
			case 'ALERT':
				return $logger->alert($message);
			case 'EMERGENCY':
				return $logger->emergency($message);
			case 'INFO':
			default:
				return $logger->info($message);
		}
	}
}