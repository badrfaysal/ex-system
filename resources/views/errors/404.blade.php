@include('errors.minimal', [
    'icon'       => 'fa-magnifying-glass',
    'bg'         => '#EFF6FF',
    'color'      => '#005B9F',
    'title'      => 'الصفحة غير موجودة',
    'message'    => $exceptionMessage ?? '' ?: 'الصفحة اللي بتدور عليها مش موجودة أو اتم نقلها لمكان تاني. اتأكد من الرابط أو ارجع للرئيسية.',
    'statusCode' => 404,
    'showBack'   => true,
])
