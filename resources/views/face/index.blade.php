<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Facebook Login</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

<body>

<div id="fb-root"></div>
<script async defer crossorigin="anonymous" src="https://connect.facebook.net/en_US/sdk.js"></script>
{{--
<fb:login-button
    scope="public_profile,pages_read_engagement,pages_read_user_content,pages_show_list,pages_manage_posts,pages_manage_engagement,pages_manage_metadata,publish_to_groups,read_insights,instagram_basic,instagram_manage_insights,instagram_content_publish"
    onlogin="fbCheckLoginState();">
</fb:login-button>
--}}
<!-- Кастомная кнопка авторизации -->
{{--<button onclick="customLogin();">Login custom with Facebook</button>--}}


<button onclick="checkLoginState();">Login with Facebook</button>



<script>
    window.fbAsyncInit = function () {
        FB.init({
            appId: '{{ env('FACE_ID') }}',
            cookie: true,
            xfbml: true,
            version: 'v18.0'
        });
        FB.AppEvents.logPageView();
    };

    // Функция для кнопки fb:login-button
    function fbCheckLoginState() {
        FB.getLoginStatus(function (response) {
            // Ваш код для обробки відповіді
            console.log(response);
        });
    }

    function customCheckLoginState() {
        FB.getLoginStatus(function (response) {
            if (response.status === 'connected') {
                var accessToken = response.authResponse.accessToken;

                console.log(accessToken)
                console.log(22222)
                sendToken(accessToken)
            }

        });
    }

    // Функция для кнопки Login with Facebook
    function checkLoginState() {
        FB.getLoginStatus(function (response) {
            if (response.status === 'connected') {
                // Пользователь вошел в систему и предоставил доступ к своему профилю Facebook.
                var accessToken = response.authResponse.accessToken;
                console.log(accessToken)

                sendToken(accessToken);
                alert('Status connected')

            } else {
                // Пользователь не вошел в систему или не предоставил доступ к своему профилю Facebook.
                FB.login(function (response) {
                    if (response.authResponse) {
                        var accessToken = response.authResponse.accessToken;
                        // Точно так же отправьте accessToken на сервер, если это необходимо.
                    }
                }, {
                    scope: 'pages_messaging'
                });
            }
        });
    }

    // test

    var tokeiuytn = 'EAAKL83ZB8zJUBO63H6ZCtUDkFlhU28BdhtwxjWtTkXktZC8uLUcw3S3hF6gCl56dW1ZB7lAmy65XAECWhbA6gMBMFNGAkziqrH9HVnUdz2YNPafZBN7NA8PXP3QelAYGpjAP5CgECWXZBVH8AOl0llplbwMZADSGdCLYKeDdUCdda9RVpzN4bXBDBbwCq26IeTCLMFYIuuZCr7HICJQZD';
    function sendTest() {
        sendToken(token);
    }

    function sendToken(token) {
        fetch('/face_send_token', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({token: token})
        }).then(response => response.json())
            .then(data => console.log(data))
            .catch(error => console.error('Error:', error));

    }
</script>

</body>

</html>
