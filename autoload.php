<?php

namespace Silverorange\Autoloader;

$package = new Package('silverorange/nate_go_search');

$package->addRule(new Rule('exceptions', 'NateGoSearch', 'Exception'));
$package->addRule(new Rule('', 'NateGoSearch'));

Autoloader::addPackage($package);

?>
