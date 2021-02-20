<?php

namespace App;

class Config {

  static public function get() {
    $__config = __DIR__.'/../.htconfig';
    if( file_exists($__config) ){
      @chmod($__config, 0644);
      $config = json_decode( getData($__config) );
      $config->go_up       = (bool) $config->go_up;
      $config->show_hidden = (bool) $config->show_hidden;
      return $config;
    }
    die('.htconfig file missing');
  }

}