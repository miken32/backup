<?php
/**
 * Copyright Sangoma Technologies, Inc 2018
 */
namespace FreePBX\modules\Backup\Handlers\Backup;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use function FreePBX\modules\Backup\Json\json_decode;
use function FreePBX\modules\Backup\Json\json_encode;
use splitbrain\PHPArchive\Tar;
use FreePBX\modules\Backup\Handlers\FreePBXModule;
use modgettext;
abstract class Common extends \FreePBX\modules\Backup\Handlers\CommonFile {
	protected $tar;
	protected $filename;
	protected $defaultFallback = false;

	public function __construct($freepbx, $filePath, $transactionId, $pid){
		parent::__construct($freepbx, $filePath, $transactionId, $pid);
		$this->filePath = $filePath;
	}

	/**
	 * Set default Fallback flag
	 *
	 * @param boolean $value
	 * @return void
	 */
	public function setDefaultFallback($value) {
		$this->defaultFallback = !empty($value);
	}

	public function setFilename($filename) {
		$this->filename = $filename;
		$this->file = $this->filePath.'/'.$this->filename;
	}

	public function getFile() {
		return $this->file;
	}

	public function process() {
		throw new \Exception("Nothing to process!");
	}

	protected function openFile() {
		$this->fs->mkdir(dirname($this->file));
		//setup and clean out the singlebackup folder
		$this->fs->remove($this->tmp);
		$this->fs->mkdir($this->tmp);

		//open the tarball for writing
		$this->tar = new Tar();
		$this->tar->setCompression(9, Tar::COMPRESS_GZIP);
		$this->tar->create($this->tmp .'/'. $this->filename);

		$this->fs->mkdir($this->tmp . '/modulejson');
		$this->tar->addFile($this->tmp . '/modulejson', 'modulejson');

		$this->fs->mkdir($this->tmp . '/files');
		$this->tar->addFile($this->tmp . '/files', 'files');

		return $this->tar;
	}

	protected function processModule($module) {
		$this->log(sprintf(_("Working with %s module"), $module['rawname']));
		//check to make sure the module supports backup
		if($module['rawname'] === 'framework') {
			$class = 'FreePBX\Builtin\Backup';
		} else {
			$class = sprintf('\\FreePBX\\modules\\%s\\Backup', $module['ucfirst']);
		}

		if(!class_exists($class)){
			$msg = sprintf(_("The module %s doesn't seem to support Backup"),$module['rawname']);
			$this->log($msg,'WARNING');
			$this->addWarning($msg);
			if(!$this->defaultFallback) {
				return [];
			}
			$this->log(_("Using default backup strategy"),'WARNING');
			$class = 'FreePBX\modules\Backup\BackupBase';
		}


		$modData = [
			"module" => $module['rawname'],
			"version" => null
		];

		//Ask the module for data
		$class = new $class($this->freepbx, $this->backupModVer, $this->getLogger(), $this->transactionId, $modData, $this->defaultFallback);

		$class->runBackup($this->transactionId, 'tarnamebase');
		if ($class->getModified() === false) {
			$msg = sprintf(_("The module %s returned no data, No backup created"),$module['rawname']);
			$this->log("\t".$msg,'WARNING');
			$this->addWarning($msg);
			return [];
		}

		foreach ($class->getDirs() as $dir) {
			if (empty($dir)) {
				continue;
			}
			$fdir = $this->Backup->getPath('/' . ltrim($dir, '/'));
			$this->log("\t".sprintf(_('Adding directory to tar: %s'),$fdir),'DEBUG');
			$this->fs->mkdir($this->tmp . '/' . $fdir);
			$this->tar->addFile($this->tmp . '/' . $fdir, $fdir);
		}

		foreach ($class->getFiles() as $file) {
			$srcpath = isset($file['pathto']) ? $file['pathto'] : '';
			if (empty($srcpath)) {
				continue;
			}
			$srcfile = $srcpath . '/' . $file['filename'];
			$destpath = $this->Backup->getPath('files/' . ltrim($file['pathto'], '/'));
			$destfile = $destpath .'/'. $file['filename'];
			$files[$srcfile] = $destfile;
			$this->log("\t".sprintf(_('Adding file to tar: %s'),$destfile),'DEBUG');
			$this->tar->addFile($srcfile, $destfile);
		}

		$modjson = $this->tmp . '/modulejson/' . $module['ucfirst'] . '.json';
		file_put_contents($modjson, json_encode($class->getData(), JSON_PRETTY_PRINT));
		$this->log("\t".sprintf(_('Adding module manifest for %s'),$module['rawname']),'DEBUG');
		$this->tar->addFile($modjson, 'modulejson/' . $module['ucfirst'] . '.json');

		return $class->getData();
	}

	public function closeFile() {
		$this->tar->close();
		unset($this->tar);
		$this->fs->rename($this->tmp .'/'. $this->filename, $this->file);
		$this->fs->remove($this->tmp);
	}
}