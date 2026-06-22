<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Max upload sizes (kilobytes)
    |--------------------------------------------------------------------------
    | Laravel "max" rule uses kilobytes. 10240 = 10 MB.
    | Ensure PHP upload_max_filesize and post_max_size are >= these values.
    */
    'max_image_kb' => (int) env('UPLOAD_MAX_IMAGE_KB', 10240),
    'max_document_kb' => (int) env('UPLOAD_MAX_DOCUMENT_KB', 10240),

];
