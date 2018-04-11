<?php

    namespace Rapidmail;

    use Rapidmail\Api\AdapterInterface;
    use Rapidmail\Api\Apiv2;
    use Rapidmail\Api\Apiv3;

    /**
     * rapidmail API abstraction class
     */
    class Api implements AdapterInterface {

        /**
         * @var Options
         */
        private $options;

        /**
         * @var AdapterInterface
         */
        private $adapter;

        /**
         * Constructor
         *
         * @param Options $options
         */
        public function __construct(Options $options) {
            $this->options = $options;
        }

        /**
         * Check if we're authenticated against the API
         *
         * @return bool
         */
        public function isAuthenticated() {
            return $this->isConfigured() && $this->adapter()->isAuthenticated();
        }

        /**
         * Check if we're configured to try auth
         *
         * @return bool
         */
        public function isConfigured() {
            return $this->adapter()->isConfigured();
        }

        /**
         * Get current adapter instance
         *
         * @return AdapterInterface|Apiv2|Apiv3
         */
        public function adapter() {

            if ($this->adapter === NULL) {

                switch ($this->options->getApiVersion()) {
                    case 2:

                        $this->adapter = new Apiv2(
                            $this->options->get('node_id'),
                            $this->options->get('recipient_list_id'),
                            $this->options->get('api_key')
                        );

                        break;

                    case 3:

                        $this->adapter = new Apiv3(
                            $this->options->get('apiv3_username'),
                            $this->options->get('apiv3_password'),
                            $this->options->get('apiv3_recipientlist_id')
                        );

                        break;

                    default:
                        throw new \InvalidArgumentException('Invalid api Version');

                }

            }

            return $this->adapter;

        }

        /**
         * Get recipientlist ID
         *
         * @param $recipientlistId
         * @return array
         */
        public function getRecipientlist($recipientlistId) {
            return $this->adapter()->getRecipientlist($recipientlistId);
        }

        /**
         * Get subscribe form URL
         *
         * @param int|null $recipientlistId
         * @return string
         */
        public function getSubscribeFormUrl($recipientlistId = null) {

            if ($recipientlistId === null) {
                $recipientlistId = $this->options->getRecipientlistId();
            }

            $recipientlist = $this->getRecipientlist($recipientlistId);

            switch ($this->options->getApiVersion()) {

                case 2:
                    return $recipientlist['subscription_form_url'];

                case 3:
                    return $recipientlist['subscribe_form_url'];

            }

        }

        /**
         * Get subscribe field key
         *
         * @param int|null $recipientlistId
         * @return null|string
         */
        public function getSubscribeFieldKey($recipientlistId = null) {

            if ($this->options->getApiVersion() === 2) {
                return NULL;
            }

            if (empty($recipientlistId)) {
                $recipientlistId = $this->options->getRecipientlistId();
            }

            return $this->getRecipientlist($recipientlistId)['subscribe_form_field_key'];

        }

        /**
         * @inheritdoc
         */
        public function subscribeRecipient($recipientlistId, array $recipientData) {
            return $this->adapter()->subscribeRecipient($recipientlistId, $recipientData);
        }

        /**
         * @inheritdoc
         */
        public function reset() {
            unset($this->adapter);
            $this->adapter = NULL;
        }

    }