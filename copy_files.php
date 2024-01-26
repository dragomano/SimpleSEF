<?php

$targets = [
	[
		'src' => __DIR__ . '/vendor/behat/transliterator/src/Behat/Transliterator/data',
		'dest' =>  __DIR__ . '/src/Sources/SimpleSEF-Db/data',
	],
	[
		'src' => __DIR__ . '/vendor/behat/transliterator/src/Behat/Transliterator/Transliterator.php',
		'dest' => __DIR__ . '/src/Sources/SimpleSEF-Db/Transliterator.php',
	],
];

function copyFiles($targets) {
	foreach ($targets as $target) {
		$src = $target['src'];
		$dest = $target['dest'];

		if (is_dir($src)) {
			$files = scandir($src);

			foreach ($files as $file) {
				if ($file !== '.' && $file !== '..') {
					if (is_dir($src . '/' . $file)) {
						copyFiles([['src' => $src . '/' . $file, 'dest' => $dest . '/' . $file]]);
					} else {
						copy($src . '/' . $file, $dest . '/' . $file);
					}
				}
			}
		} else {
			copy($src, $dest);
		}
	}
}

copyFiles($targets);
