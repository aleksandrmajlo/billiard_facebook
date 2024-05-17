//3. Facebook login with JavaScript SDK
function launchFBE() {
    FB.login(function (response) {
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
        }, {
            config_id: '123456789456', // configuration ID goes here
            response_type: 'code'   // must be set to 'code' for SUAT
            // scope: 'ads_management',
            // refer to the extras object table for details
            // extras: {
            //     "setup":{
            //       "external_business_id":"12345678912",
            //       "timezone":"Africa\/Johannesburg",
            //       "currency":"ZAR",
            //       "business_vertical":"ADS_TARGETING"
            //     },
            //     "business_config":{
            //       "business":{
            //          "name":"**"
            //       },
            //       "ig_cta": {
            //         "enabled": true,
            //         "cta_button_text": "Find Out More",
            //         "cta_button_url": "https://www.***.com"
            //       }
            //     },
            //     "repeat":false
            // }
        }

    );

}
