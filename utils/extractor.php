<?php

echo "Extracting archive ...\n\n";
$output = shell_exec('tar -zxf soctam-bin.tar.gz');
echo "<pre>$output</pre>";

echo "Remove archive";
$output = shell_exec('rm soctam-bin.tar.gz');
echo "<pre>$output</pre>";
?>