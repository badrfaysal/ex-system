@include('errors.minimal', [
    'icon'       => 'fa-circle-exclamation',
    'bg'         => '#FFFBEB',
    'color'      => '#D97706',
    'title'      => 'طلب غير صحيح',
    'message'    => $exceptionMessage ?? '' ?: 'الطلب اللي اتبعت للسيرفر فيه مشكلة. حاول تاني، ولو المشكلة استمرت أبلغ الدعم الفني.',
    'statusCode' => 400,
    'showBack'   => true,
])
