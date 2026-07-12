@include('errors.minimal', [
    'icon'       => 'fa-gauge-high',
    'bg'         => '#FFFBEB',
    'color'      => '#D97706',
    'title'      => 'طلبات كتير في وقت قصير',
    'message'    => $exceptionMessage ?? '' ?: 'قدّمت عدد كبير من الطلبات خلال فترة قصيرة. استنى شوية وحاول تاني.',
    'statusCode' => 429,
    'showBack'   => true,
])
