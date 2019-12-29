<?php
require_once __DIR__ . '/../vendor/autoload.php';

exit(main($argv));

/**
 * @param string[] $argv
 * @return int
 */
function main(array $argv) {
      if (!isset($argv[1])) {
            fputs(STDERR, 'Usage: php atena.php data.csv' . PHP_EOL);
            return -1;
      }
      
      $options = [
            // 英数字を全角にして縦に並べる
            'to_zenkaku' => true,
            // テンプレートを使用する
            'template' => true,
      ];

      $atena = new \GSSHagaki\HagakiAtena($argv[1], $options);
      return 0;
}