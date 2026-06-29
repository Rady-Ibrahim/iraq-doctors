<meta name="csrf-token" content="{{ csrf_token() }}">
@include('partials.web-csrf-keepalive', ['csrfRefreshUrl' => $csrfRefreshUrl])
