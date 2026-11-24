@include('errors.page', [
    'statusCode' => '403',
    'title' => 'This area is restricted',
    'message' => 'Your account does not have permission to open this page. Return to the portal or sign in with an authorized account.',
    'primaryHref' => '/',
    'primaryLabel' => 'Return to home',
    'secondaryHref' => '/admin',
    'secondaryLabel' => 'Staff sign in',
])