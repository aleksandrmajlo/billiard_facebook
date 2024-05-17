<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Title</title>
</head>
<body>

<div id="fb-root"></div>
<script async defer crossorigin="anonymous" src="https://connect.facebook.net/en_US/sdk.js"></script>


<script>
    window.fbAsyncInit = function () {
        FB.init({
            appId: '{{ env('FACE_ID') }}',
            cookie: false,
            xfbml: true,
            version: 'v18.0'
        });
        FB.AppEvents.logPageView();
    };
    //3. Facebook login with JavaScript SDK
    function launchFBE() {
        FB.getLoginStatus(function (response) {
            console.group('FB.getLoginStatus');
            console.log(response)
            console.groupEnd();
            if (response.status === 'connected') {
                var accessToken = response.authResponse.accessToken;
                console.group('connected');
                console.log(accessToken)
                console.groupEnd();
                alert('токен получен... окно с авторизацией не нужно')
            }
            else{
                FB.login(function (response) {
                    console.group('FB.login');
                    console.log(response)
                    console.groupEnd();
                    if (response.authResponse) {
                        var accessToken = response.authResponse.accessToken;
                        console.group('accessToken');
                        console.log(accessToken)
                        console.groupEnd();
                    }else {
                        console.log('User cancelled login or did not fully authorize.');
                    }
                }, {
                    scope: 'pages_messaging',
                    // scope: 'ads_read'
                    // scope: 'whatsapp_business_messaging,whatsapp_business_management',
                    // config_id:'1048205039733278' ,
                });

            }
        },true);


        FB.login(function (response) {
            console.log(response);
            if (response.authResponse) {
                // returns a User Access Token with scopes requested
                const accessToken = response.authResponse.accessToken;
                const message = {
                    'success':true,
                    'access_token':accessToken,
                };
                // store access token for later
            } else {
                console.log('User cancelled login or did not fully authorize.');
            }
        },
            {
            // scope: 'ads_read',
            // config_id:'906627644458110' ,
            scope: 'catalog_management,manage_business_extension',
            // refer to the extras object table for details
            extras: {
                "setup":{
                    "external_business_id":"<external_business_id>",
                    "timezone":"America\/Los_Angeles",
                    "currency":"USD",
                    "business_vertical":"ECOMMERCE"
                },
                "business_config":{
                    "business":{
                        "name":"<business_name>"
                    },
                    "ig_cta": {
                        "enabled": true,
                        "cta_button_text": "Book Now",
                        "cta_button_url": "https://partner-site.com/foo-business"
                    }
                },
                "repeat":false
            }
        });

    }
</script>
<button onclick="launchFBE()"> Launch FBE Workflow </button>

</body>
</html>
