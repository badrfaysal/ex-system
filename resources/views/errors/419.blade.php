@include('errors.minimal', [
    'icon'       => 'fa-hourglass-end',
    'bg'         => '#FFFBEB',
    'color'      => '#D97706',
    'title'      => 'انتهت صلاحية الصفحة',
    'message'    => $exceptionMessage ?? '' ?: 'الجلسة انتهت أو الصفحة كانت مفتوحة لفترة طويلة. حدّث الصفحة وحاول تاني.',
    'statusCode' => 419,
    'showBack'   => true,
])
