<?php

namespace App\Adaptors;

use League\Flysystem\Local\LocalFilesystemAdapter;

class LocalAdaptor {

  static public function get() {
    // The internal adapter
    return new LocalFilesystemAdapter(
      // Determine root directory
      __DIR__.'/../../storage/'.$_ENV['STORAGE_FOLDER_NAME']
    );
  }
  
}