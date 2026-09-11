<?php

return [
    'table_name'      => 'sepomex',
    'source_file'     => storage_path('CPdescarga.txt'),
    'encoding_input'  => 'iso-8859-1',
    'encoding_output' => 'utf-8',
    'min_import_rows' => (int) env('SEPOMEX_MIN_IMPORT_ROWS', 100000),
    'chunk'           => 50,
];
