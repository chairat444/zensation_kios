<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ config('app.name', 'Zensation Kiosk') }}</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <style>
    html,body {height:100%; background: linear-gradient(#e9f6ff, #ffffff);}
    .btn-lg {min-height:72px; font-size:1.35rem; font-weight:700;}
    .form-control-lg {height:3.25rem; font-size:1.2rem;}
  </style>
  @stack('styles')
</head>
<body>
  <main class="min-vh-100 d-flex flex-column">
    @yield('content')
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  @stack('scripts')
</body>
</html>
