<?php

    namespace Rapidmail;

    /**
     * Rapidmail widget class
     */
    class Widget extends \WP_Widget {

        /**
         * Constructor
         */
        public function __construct() {

            parent::__construct(
                'rapidmail_subscription_form',
                \__('rapidmail Anmeldeformular', Rapidmail::TEXT_DOMAIN),
                [
                    'customize_selective_refresh' => true,
                ]
            );

        }

        /**
         * Get checkbox configs
         *
         * @return array
         */
        private function getCheckboxConfigs() {

            return [
                'firstname' => \__('Vorname anzeigen', Rapidmail::TEXT_DOMAIN),
                'lastname' => \__('Nachname anzeigen', Rapidmail::TEXT_DOMAIN),
                'gender' => \__('Anrede anzeigen', Rapidmail::TEXT_DOMAIN),
                'mailtype' => \__('Format anzeigen', Rapidmail::TEXT_DOMAIN)
            ];

        }

        /**
         * @inheritdoc
         */
        public function form($instance) {

            $defaults = [
                'title' => ''
            ];

            $args = \wp_parse_args((array)$instance, $defaults);

            ?>
            <p>
                <label for="<?php echo \esc_attr($this->get_field_id('title')); ?>"><?php \_e('Überschrift', Rapidmail::TEXT_DOMAIN); ?></label>
                <input class="widefat" id="<?php echo \esc_attr($this->get_field_id('title')); ?>" name="<?php echo \esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo \esc_attr($args['title']); ?>" />
            </p>
            <?php



            foreach ($this->getCheckboxConfigs() AS $id => $title) {
            ?>
                <p>
                    <input class="checkbox" type="checkbox" id="<?php echo \esc_attr($this->get_field_id('show_' . $id)); ?>" name="<?php echo \esc_attr($this->get_field_name('show_' . $id)); ?>" <?php if ($args['show_' . $id]) { ?> checked="checked" <?php } ?>/>
                    <label for="<?php echo \esc_attr($this->get_field_id('show_' . $id)); ?>"><?php echo $title; ?></label>
                </p>
            <?php
            }

        }

        /**
         * @inheritdoc
         */
        public function update($new_instance, $instance) {

            $instance['title'] = isset($new_instance['title']) ? \wp_strip_all_tags($new_instance['title']) : '';

            foreach ($this->getCheckboxConfigs() AS $checkbox => $title) {
                $instance['show_' . $checkbox] = isset( $new_instance['show_' . $checkbox] );
            }

            return $instance;

        }

        /**
         * @inheritdoc
         */
        public function widget($args, $instance) {

            $rapidmail = Rapidmail::instance();

            $args['rm_is_api_configured'] = $rapidmail->getApi()->isConfigured();

            $template = new Template();
            $template->assign([
                'instance' => (array)$instance,
                'settings' => $args,
                'widget' => $this,
                'options' => $rapidmail->getOptions()
            ]);

            $template->display('widget');

        }

        /**
         * Add widget styles and scripts
         */
        public static function addResources() {

            \wp_enqueue_style('rapidmail-widget-css', \plugins_url('css/widget.css', __DIR__));

            \wp_register_script('rapidmail-widget-js', \plugins_url('js/widget.js', __DIR__), ['jquery-core']);
            \wp_localize_script('rapidmail-widget-js', 'rmwidget', [
                'msg_an_error_occurred' => \__('Es ist ein Fehler aufgetreten', Rapidmail::TEXT_DOMAIN),
                'msg_subscribe_success' => \__('Vielen Dank für Ihre Anmeldung!', Rapidmail::TEXT_DOMAIN),
                'spinner_uri' => \get_site_url(null, '/wp-includes/images/wpspin_light.gif')
            ]);
            \wp_enqueue_script('rapidmail-widget-js');

        }

        /**
         * Register widget
         *
         * @param Api $api
         */
        public static function register(Api $api) {

            if (!\is_admin() || $api->isAuthenticated()) {

                \register_widget('Rapidmail\\Widget');
                \add_action('wp_enqueue_scripts', [__CLASS__, 'addResources']);

                \add_shortcode( 'rm_form', function($args) {

                    $args = array_merge([
                        'show_firstname' => false,
                        'show_lastname' => false,
                        'show_gender' => false,
                        'show_mailtype' => false
                    ], (empty($args) || !is_array($args) ? [] : $args));

                    $template = new Template();
                    $template->assign([
                        'instance' => $args,
                        'settings' => [],
                        'widget' => (object)[
                            'number' => uniqid()
                        ],
                        'options' => Rapidmail::instance()->getOptions()
                    ]);

                    $template->display('shortcode');

                });
            }

        }

    }
