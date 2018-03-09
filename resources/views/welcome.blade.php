<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Small Order Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="format-detection" content="telephone=no">
    <meta name="msapplication-tap-highlight" content="no">

    <link rel="icon" type="image/x-icon" href="assets/icon/favicon.ico">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#4e8ef7">
    <script src="https://maps.google.com/maps/api/js?key=AIzaSyDpb8WHrnxzwe0PQezbT9PKt0vtUaNdT64"></script>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script>var googleLoaded = false;</script>
    
    <!-- cordova.js required for cordova apps -->
    <script src="cordova.js"></script>

    <!-- un-comment this code to enable service worker
    <script>
      if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('service-worker.js')
          .then(() => console.log('service worker installed'))
          .catch(err => console.log('Error', err));
      }
    </script>-->

    <link href="{{ elixir('build/main.css', '') }}" rel="stylesheet">

</head>
<body>

<!-- Ionic's root component and where the app will load -->
<ion-app>loading...</ion-app>

<!-- The polyfills js is generated during the build process -->
<script src="{{ elixir('build/polyfills.js', '') }}"></script>

<!-- The bundle js is generated during the build process -->
<script src="{{ elixir('build/main.js', '') }}"></script>

</body>
</html>