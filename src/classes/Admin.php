<?php

    namespace Rapidmail;

    use Rapidmail\Api\AdapterInterface;

    /**
     * rapidmail admin options
     */
    class Admin {

        /**
         * @var Options
         */
        private $options;

        /**
         * @var Api
         */
        private $api;

        /**
         * Constructor
         *
         * @param Options $options
         * @param Api $api
         */
        public function __construct(Options $options, Api $api) {
            $this->options = $options;
            $this->api = $api;
        }

        /**
         * Initialize admin menu item
         */
        public function initMenu() {

            \add_options_page(
                \__('rapidmail Options', Rapidmail::TEXT_DOMAIN),
                'rapidmail',
                'edit_plugins',
                'rapidmail',
                [
                    $this,
                    'showOptionsPage'
                ]
            );

        }

        /**
         * Show options page
         */
        public function showOptionsPage() {

            $link = '<a href="https://www.rapidmail.de/anmelden?pid=125&utm_source=wp-plugin&utm_medium=Plugin&utm_campaign=Wordpress" target="_blank">' . \__('Jetzt kostenlos bei rapidmail anmelden!', Rapidmail::TEXT_DOMAIN) . '</a>';

            ?>
            <div class="wrap">
                <h1><?php \_e('Einstellungen', Rapidmail::TEXT_DOMAIN); ?> &rsaquo; rapidmail</h1>
                <p><?php \printf(\__('Bitte hinterlegen Sie hier Ihre rapidmail API Zugangsdaten. Wenn Sie noch kein Kunde bei rapidmail sind, können Sie sich hier kostenlos anmelden: %s', Rapidmail::TEXT_DOMAIN), $link); ?></p>
                <form method="post" action="options.php">
                    <?php
                        \settings_fields( 'rapidmail' );
                        \do_settings_sections( 'rapidmail' );
                        \submit_button(null, 'primary', 'save');
                    ?>
                </form>
            </div>
            <?php

        }

        /**
         * Sanitize options before saving
         *
         * @param array $values
         * @return array
         */
        public function sanitizeOptions(array $values) {

            $sane_data = [];
            $sane_data['api_version'] = \intval($values['api_version']);

            if (!\in_array($sane_data['api_version'], [2, 3], true)) {

                \add_settings_error(
                    Options::OPTION_KEY,
                    \esc_attr('api_version'),
                    \__('Ungültige API-Version', Rapidmail::TEXT_DOMAIN),
                    'error'
                );

                return $sane_data;

            }

            if ($sane_data['api_version'] === AdapterInterface::API_V1) {

                $sane_data['api_key'] = \sanitize_text_field($values['api_key']);
                $sane_data['recipient_list_id'] = empty($values['recipient_list_id']) ? '' : \intval($values['recipient_list_id']);
                $sane_data['node_id'] = empty($values['node_id']) ? '' : \intval($values['node_id']);

            } elseif ($sane_data['api_version'] === AdapterInterface::API_V3) {

                $sane_data['apiv3_username'] = \sanitize_text_field($values['apiv3_username']);
                $sane_data['apiv3_password'] = \sanitize_text_field($values['apiv3_password']);

                $this->options->setAll($sane_data);
                $this->api->reset();

                if ($this->api->isAuthenticated()) {

                    if (!\preg_match('/^[1-9][0-9]*$/', $values['apiv3_recipientlist_id']) || !\array_key_exists($values['apiv3_recipientlist_id'], $this->api->adapter()->getRecipientlists())) {

                        \add_settings_error(
                            Options::OPTION_KEY,
                            \esc_attr('apiv3_recipientlist_id'),
                            \__('Bitte eine gültige Empfängerliste auswählen', Rapidmail::TEXT_DOMAIN),
                            'error'
                        );

                    } else {
                        $sane_data['apiv3_recipientlist_id'] = \intval($values['apiv3_recipientlist_id']);
                    }

                }

            }

            $sane_data['comment_subscription_active'] = \intval($values['comment_subscription_active']);
            $sane_data['comment_subscription_label'] = empty($values['comment_subscription_label']) ? NULL : \sanitize_text_field($values['comment_subscription_label']);

            $this->options->setAll($sane_data);
            $this->api->reset();

            if ($this->api->isConfigured()) {

                $sane_data['subscribe_form_url'] = $this->api->getSubscribeFormUrl();

                if ($this->options->getApiVersion() === AdapterInterface::API_V3) {
                    $sane_data['apiv3_subscribe_field_key'] = $this->api->getSubscribeFieldKey();
                }

            }

            return $sane_data;

        }

        /**
         * Event handler for adminInit event
         */
        public function onAdminInit() {

            \register_setting(
                'rapidmail',
                Options::OPTION_KEY,
                array(
                    'description' => \__('Verwendete API Version', Rapidmail::TEXT_DOMAIN),
                    'sanitize_callback' => [$this, 'sanitizeOptions']
                )
            );

            \add_settings_section(
                'connection',
                \__('Verbindungseinstellungen', Rapidmail::TEXT_DOMAIN),
                NULL,
                'rapidmail'
            );

            \add_settings_field(
                'api_version',
                \__('API-Version', Rapidmail::TEXT_DOMAIN),
                function() {

                    $api_version = $this->options->getApiVersion();

                    echo '<select name="rm_options[api_version]" id="rm-api-version">
                            <option value="1"' . ($api_version === AdapterInterface::API_V1 ? ' selected="selected"' : '') . '>' . \__('V1 (veraltet)', Rapidmail::TEXT_DOMAIN) . '</option>
                            <option value="3"' . ($api_version === AdapterInterface::API_V3 ? ' selected="selected"' : '') . '>' . \__('V3', Rapidmail::TEXT_DOMAIN) . '</option>
                          </select>';
                },
                'rapidmail',
                'connection'
            );

            if ($this->options->getApiVersion() === AdapterInterface::API_V1) {

                \add_settings_field(
                    'api_key',
                    \__('API-Schlüssel', Rapidmail::TEXT_DOMAIN),
                    function() {

                        echo '<input type="text" class="regular-text" value="' . \esc_html($this->options->get('api_key')) . '" id="api_key" name="rm_options[api_key]">';

                        if ($this->api->isAuthenticated()) {
                            echo '&nbsp;<img src="' . \esc_url(\admin_url('images/yes.png' )) . '" alt="' . \__('Verbindung hergestellt', Rapidmail::TEXT_DOMAIN) . '" />';
                        } elseif ($this->api->isConfigured()) {
                            echo '&nbsp;<img src="' . \esc_url(\admin_url('images/no.png' )) . '" alt="' . \__('Verbindungsaufbau fehlgeschlagen', Rapidmail::TEXT_DOMAIN) . '" />';
                        }

                        echo '<br><small>' . \__('Den API Key, die ID der Empfängerliste und Node-ID finden Sie im rapidmail Kundenbereich unter Einstellungen &rsaquo; API', Rapidmail::TEXT_DOMAIN) . '</small>';

                    },
                    'rapidmail',
                    'connection'
                );

                \add_settings_field(
                    'recipient_list_id',
                    \__('ID der Empfängerliste', Rapidmail::TEXT_DOMAIN),
                    function() {
                        echo '<input type="text" class="regular-text" value="' . \esc_html($this->options->get('recipient_list_id')) . '" id="recipient_list_id" name="rm_options[recipient_list_id]">';
                    },
                    'rapidmail',
                    'connection'
                );

                \add_settings_field(
                    'node_id',
                    \__('Node ID', Rapidmail::TEXT_DOMAIN),
                    function() {
                        echo '<input type="text" class="regular-text" value="' . \esc_html($this->options->get('node_id')) . '" id="node_id" name="rm_options[node_id]">';
                    },
                    'rapidmail',
                    'connection'
                );

            }

            if ($this->options->getApiVersion() === AdapterInterface::API_V1) {

                \add_settings_field(
                    'apiv3_username',
                    \__('API-Benutzername', Rapidmail::TEXT_DOMAIN),
                    function() {

                        echo '<input type="text" class="regular-text" value="' . \esc_html($this->options->get('apiv3_username')) . '" id="apiv3_username" name="rm_options[apiv3_username]">';

                        if ($this->api->isAuthenticated()) {
                            echo '&nbsp;<img src="' . \esc_url(\admin_url('images/yes.png' )) . '" alt="' . \__('Verbindung hergestellt', Rapidmail::TEXT_DOMAIN) . '" />';
                        } elseif ($this->api->isConfigured()) {
                            echo '&nbsp;<img src="' . \esc_url(\admin_url('images/no.png' )) . '" alt="' . \__('Verbindungsaufbau fehlgeschlagen', Rapidmail::TEXT_DOMAIN) . '" />';
                        }

                        echo '<br><small>' . \esc_html__('Zugangsdaten für die API finden Sie im rapidmail Kundenbereich unter Einstellungen › API', Rapidmail::TEXT_DOMAIN) . '</small>';

                    },
                    'rapidmail',
                    'connection'
                );

                \add_settings_field(
                    'apiv3_password',
                    \__('API-Passwort', Rapidmail::TEXT_DOMAIN),
                    function() {
                        echo '<input type="password" class="regular-text" value="' . \esc_html($this->options->get('apiv3_password')) . '" id="apiv3_password" name="rm_options[apiv3_password]">';
                    },
                    'rapidmail',
                    'connection'
                );

                \add_settings_field(
                    'apiv3_recipientlist_id',
                    \__('Empfängerliste', Rapidmail::TEXT_DOMAIN),
                    function() {

                        echo '<select name="rm_options[apiv3_recipientlist_id]">';

                        if ($this->api->isAuthenticated()) {

                            echo '<option value="0">' . \__('Bitte wählen', Rapidmail::TEXT_DOMAIN) . '</option>';

                            $recipientlists = $this->api->adapter()->getRecipientlists();
                            $recipientlistId = $this->options->get('apiv3_recipientlist_id');

                            foreach ($recipientlists AS $id => $name) {
                                echo '<option value="' . $id . '"' . ($recipientlistId == $id ? ' selected="selected"' : '') . '>' . \esc_html($name) . ' (ID ' . $id . ')</option>';
                            }

                        } else {
                            echo '<option value="0">' . \__('Bitte gültige Zugangsdaten hinterlegen', Rapidmail::TEXT_DOMAIN) . '</option>';
                        }

                        echo '</select>';

                    },
                    'rapidmail',
                    'connection'
                );

            }

            \add_settings_section(
                'comments',
                \__('Abonnentengewinnung über Kommentare', Rapidmail::TEXT_DOMAIN),
                function() {
                    echo \esc_html__('Durch Aktivierung dieser Funktion wird das Kommentarformular in Ihrem Blog mit einer Newsletter-Bestellmöglichkeit erweitert. 
                            Setzt der Benutzer beim Kommentieren einen Haken erhält er eine Bestätigungs-E-Mail (Double-Opt-In).
                            Nach einem durch Klick auf den Bestätigungslink ist er als aktiver Empfänger in der Empfängerliste eingetragen.', Rapidmail::TEXT_DOMAIN);
                },
                'rapidmail'
            );

            \add_settings_field(
                'comment_subscription_active',
                \__('Aktiv', Rapidmail::TEXT_DOMAIN),
                function() {
                    echo '<input type="checkbox" name="rm_options[comment_subscription_active]" id="comment_subscription_active" value="1" ' . (\intval($this->options->get('comment_subscription_active')) ? ' checked="checked"' : '') . ' />';
                },
                'rapidmail',
                'comments'
            );

            \add_settings_field(
                'comment_subscription_label',
                \__('Feldbeschreibung', Rapidmail::TEXT_DOMAIN),
                function() {
                    echo '<input type="text" class="regular-text" value="' . \esc_html($this->options->get('comment_subscription_label')) . '" id="comment_subscription_label" name="rm_options[comment_subscription_label]" placeholder="' . esc_html__('Newsletter abonnieren (jederzeit wieder abbestellbar)') . '">';
                },
                'rapidmail',
                'comments'
            );

        }

        /**
         * Add admin scripts
         *
         * @param string $hook
         */
        public function addScripts($hook) {

            if($hook != 'settings_page_rapidmail') {
                return;
            }

            \wp_register_script('rapidmail-admin', \plugins_url('js/admin.js', __DIR__), ['jquery-core']);
            \wp_enqueue_script('rapidmail-admin');

        }

        /**
         * Add action links for plugins
         */
        private function addPluginActionLinks() {

            \add_filter('plugin_action_links', function($links, $file) {

                if ($file != RAPIDMAIL_PLUGIN_BASENAME) {
                    return $links;
                }

                $settings_link = \sprintf(
                    '<a href="%1$s">%2$s</a>',
                    \menu_page_url('rapidmail', false),
                    \esc_html__('Settings', Rapidmail::TEXT_DOMAIN)
                );

                \array_unshift( $links, $settings_link );

                return $links;

            }, 10, 2);

        }

        /**
         * Init admin handling
         */
        public function init() {

            $this->addPluginActionLinks();

            \add_action('admin_init', [$this, 'onAdminInit']);
            \add_action('admin_enqueue_scripts', [$this, 'addScripts']);
            \add_action('admin_menu', [$this, 'initMenu']);

        }

    }