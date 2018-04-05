<?php

    namespace Rapidmail;

    /**
     * rapidmail options wrapper
     */
    class Options {

        /**
         * Key to use in wordpress options
         *
         * @var string
         */
        const OPTION_KEY = 'rm_options';

        /**
         * @var array
         */
        private $options;

        /**
         * Constructor
         */
        public function __construct() {
            $this->options = \get_option(self::OPTION_KEY);
        }

        /**
         * Get API version
         *
         * @return int
         */
        public function getApiVersion() {
            return (int)$this->get('api_version', 2);
        }

        /**
         * Get value from options by key
         * @param string $key
         * @param null|mixed $default
         * @return mixed|null
         */
        public function get($key, $default = NULL) {

            if (!isset($this->options[$key])) {
                return $default;
            }

            return $this->options[$key];

        }

        /**
         * Save options
         */
        public function save() {
            \update_option('rm_options', $this->options);
        }

        /**
         * Set option value
         *
         * @param string $key
         * @param mixed $value
         */
        public function set($key, $value) {
            $this->options[$key] = $value;
            $this->save();
        }

        /**
         * Get recipientlist ID configured for API version
         *
         * @return mixed|null
         * @throws \Exception
         */
        public function getRecipientlistId() {

            switch ($this->getApiVersion()) {

                case 2:
                    return $this->get('recipient_list_id');

                case 3:
                    return $this->get('apiv3_recipientlist_id');

                default:
                    throw new \Exception('Invalid API version');

            }

        }

    }