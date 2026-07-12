@include('errors.minimal', [
    'icon'       => 'fa-server',
    'bg'         => '#FEF2F2',
    'color'      => '#DC2626',
    'title'      => 'حصل خطأ في السيرفر',
    'message'    => 'حصلت مشكلة غير متوقعة من عندنا وإحنا شغالين عليها. جرّب تاني بعد شوية، ولو المشكلة استمرت أبلغ الدعم الفني.',
    'statusCode' => 500,
    'showBack'   => false,
])
