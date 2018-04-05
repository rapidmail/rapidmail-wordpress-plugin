<?php

    namespace Rapidmail\Api;

    /**
     * APIv2 adapter class
     */
    class Apiv2 implements AdapterInterface {

        /**
         * @var \rapidmail_apiclient
         */
        private $apiclient;

        /**
         * Constructor
         *
         * @param int $node_id
         * @param int $recipientlistId
         * @param string $api_key
         */
        public function __construct($node_id, $recipientlistId, $api_key) {

            try {

                require_once __DIR__ . '/../../vendor/rapidmail/rapidmail_apiclient.class.php';

                $this->apiclient = new \rapidmail_apiclient(
                    (int)$node_id,
                    (int)$recipientlistId,
                    (string)$api_key
                );

            } catch (\Exception $e) {
                // Ignore
            }

        }

        /**
         * @inheritdoc
         */
        public function isConfigured() {
            return $this->apiclient !== NULL;
        }

        /**
         * @inheritdoc
         */
        public function isAuthenticated() {

            if ($this->isConfigured()) {
                $recipientlist = $this->getRecipientlist(0);
                return !empty($recipientlist);
            }

            return false;

        }

        /**
         * @inheritdoc
         */
        public function getRecipientlist($recipientlistId) {

            if (!$this->isConfigured()) {
                return NULL;
            }

            try {
                return $this->apiclient->get_metadata();
            } catch (\Exception $e) {
                return null;
            }

        }

        /**
         * @inheritdoc
         */
        public function subscribeRecipient($recipientlistId, array $recipientData) {

            $email = $recipientData['email'];
            unset($recipientData['email']);

            $recipientData['status'] = 'new';
            $recipientData['activationmail'] = 'yes';

            try {
                $this->apiclient->add_recipient($email, $recipientData);
            } catch (\Exception $e) {
                return null;
            }

        }

    }