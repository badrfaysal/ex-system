@include('errors.minimal', [
    'icon'       => 'fa-circle-exclamation',
    'bg'         => '#FFFBEB',
    'color'      => '#D97706',
    'title'      => 'العملية دي مش متاحة',
    'message'    => $exceptionMessage ?? '' ?: 'الطريقة اللي اتنفّذ بيها الطلب ده مش مدعومة على الصفحة دي. جرّب ترجع وتعيد المحاولة من الشاشة الأصلية.',
    'statusCode' => 405,
    'showBack'   => true,
])
