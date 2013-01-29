<?php
/**
 * ScnSocialAuth Configuration
 *
 * If you have a ./config/autoload/ directory set up for your project, you can
 * drop this config file in it and change the values as you wish.
 */
$settings = array(
    /**
     * Zend\Db\Adapter\Adapter DI Alias
     *
     * Please specify the DI alias for the configured Zend\Db\Adapter\Adapter
     * instance that ZfcUser should use.
     */
    'zend_db_adapter' => 'zfcuser_doctrine_em',

    /**
     * User Provider Entity Class
     *
     * Name of Entity class to use. Useful for using your own entity class
     * instead of the default one provided. Default is ScnSocialAuth\Entity\UserProvider.
     */
    //'user_provider_entity_class' => 'ScnSocialAuth\Entity\UserProvider',

    /**
     * Facebook Enabled
     *
     * Please specify if Facebook is enabled
     */
    //'facebook_enabled' => true,

    /**
     * Facebook Scope
     *
     * Please specify a Facebook scope
     *
     * A comma-separated list of permissions you want to request from the user.
     * See the Facebook docs for a full list of available permissions:
     * http://developers.facebook.com/docs/reference/api/permissions.
     */
    //'facebook_scope' => '',

    /**
     * Facebook Display
     *
     * Please specify a Facebook display
     *
     * The display context to show the authentication page.
     * Options are: page, popup, iframe, touch and wap.
     * Read the Facebook docs for more details:
     * http://developers.facebook.com/docs/reference/dialogs#display. Default: page
     */
    //'facebook_display' => '',

    /**
     * Foursquare Enabled
     *
     * Please specify if Foursquare is enabled
     */
    //'foursquare_enabled' => true,

    /**
     * Github Enabled
     *
     * Please specify if Github is enabled
     *
     * You can register a new application at:
     * https://github.com/settings/applications/new
     */
    //'github_enabled' => true,

    /**
     * Github Scope
     *
     * Please specify a Github scope
     *
     * A comma-separated list of permissions you want to request from the user.
     * See he Github docs for a full list of the available permissions:
     * http://developer.github.com/v3/oauth/#scopes
     */
    //'github_scope' => '',

    /**
     * Google Enabled
     *
     * Please specify if Google is enabled
     */
    //'google_enabled' => true,

    /**
     * Google Scope
     *
     * Please specify a Google scope
     *
     * A space-separated list of permissions you want to request from the user.
     * See the Google docs for a full list of available permissions:
     * https://developers.google.com/accounts/docs/OAuth2Login#scopeparameter.
     */
    //'google_scope' => 'https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email',

    /**
     * LinkedIn Enabled
     *
     * Please specify if LinkedIn is enabled
     */
    //'linkedIn_enabled' => true,

    /**
     * Twitter Enabled
     *
     * Please specify if Twitter is enabled
     */
    //'twitter_enabled' => true,

    /**
     * Yahoo! Enabled
     *
     * Please specify if Yahoo! is enabled
     */
    //'yahoo_enabled' => true,

    /**
     * Set to true if you want to display only the social login buttons without
     * the username/password etc. from ZfcUser.
     */
    //'social_login_only' => false,

    /**
     *  The home route key as used in your application
     */
    //'home_route' => 'home',

    /**
     * End of ScnSocialAuth configuration
     */
);

/**
 * You do not need to edit below this line
 */
return array(
    'scn-social-auth' => $settings,
    'service_manager' => array(
        'aliases' => array(
            'ScnSocialAuth_ZendDbAdapter' => (isset($settings['zend_db_adapter'])) ? $settings['zend_db_adapter']: 'Zend\Db\Adapter\Adapter',
        ),
    ),
);
