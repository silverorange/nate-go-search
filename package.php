<?php

require_once 'PEAR/PackageFileManager2.php';

$version = '1.0.22';
$notes = <<<EOT
see ChangeLog
EOT;

$description =<<<EOT
indexing package to make searches faster
EOT;

$package = new PEAR_PackageFileManager2();
PEAR::setErrorHandling(PEAR_ERROR_DIE);

$result = $package->setOptions(
	array(
		'filelistgenerator' => 'svn',
		'simpleoutput'      => true,
		'baseinstalldir'    => '/',
		'packagedirectory'  => './',
		'dir_roles'         => array(
			'NateGoSearch' => 'php',
			'system' => 'data',
			'sql' => 'data'
		),
	)
);

$package->setPackage('NateGoSearch');
$package->setSummary('Indexer');
$package->setDescription($description);
$package->setChannel('pear.silverorange.com');
$package->setPackageType('php');
$package->setLicense('LGPL', 'http://www.gnu.org/copyleft/lesser.html');

$package->setReleaseVersion($version);
$package->setReleaseStability('beta');
$package->setAPIVersion('0.0.1');
$package->setAPIStability('beta');
$package->setNotes($notes);

$package->addIgnore('package.php');

$package->addMaintainer('lead', 'nrf', 'Nathan Fredrickson', 'nathan@silverorange.com');
$package->addMaintainer('developer', 'gauthierm', 'Mike Gauthier', 'mike@silverorange.com');

$package->addReplacement('NateGoSearch/NateGoSearchIndexer.php', 'pear-config', '@DATA-DIR@', 'data_dir');
$package->addReplacement('NateGoSearch/NateGoSearchQuery.php', 'pear-config', '@DATA-DIR@', 'data_dir');
$package->addReplacement('NateGoSearch/NateGoSearchFileSpellChecker.php', 'pear-config', '@DATA-DIR@', 'data_dir');
$package->addReplacement('NateGoSearch/NateGoSearchPSpellSpellChecker.php', 'pear-config', '@DATA-DIR@', 'data_dir');

$package->setPhpDep('5.0.5');
$package->setPearinstallerDep('1.4.0');
$package->addPackageDepWithChannel('required', 'MDB2', 'pear.php.net', '2.2.2');
$package->generateContents();

if (isset($_GET['make']) || (isset($_SERVER['argv']) && @$_SERVER['argv'][1] == 'make')) {
	$package->writePackageFile();
} else {
	$package->debugPackageFile();
}

?>
