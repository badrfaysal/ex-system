@include('errors.minimal', [
    'icon'       => 'fa-lock',
    'bg'         => '#FFFBEB',
    'color'      => '#D97706',
    'title'      => 'لازم تسجّل دخول',
    'message'    => $exceptionMessage ?? '' ?: 'الجلسة غير متاحة أو انتهت. سجّل دخول تاني عشان تكمل.',
    'statusCode' => 401,
    'showBack'   => false,
])
