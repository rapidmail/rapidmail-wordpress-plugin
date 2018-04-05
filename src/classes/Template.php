<?php

    namespace Rapidmail;

    /**
     * rapidmail simple template class
     */
    class Template {

        /**
         * @var array
         */
        private $vars = [];

        /**
         * Assign value to template
         *
         * @param string|array $varOrValuesArray
         * @param null|mixed $value
         */
        public function assign($varOrValuesArray, $value = NULL) {

            if (\is_array($varOrValuesArray)) {
                $this->vars = \array_replace($this->vars, $varOrValuesArray);
            } else {
                $this->vars[$varOrValuesArray] = $value;
            }

        }

        /**
         * Render template
         */
        public function display() {
            \extract($this->vars);
            require (\plugin_dir_path(__DIR__) . 'templates/' . \func_get_arg(0) . '.phtml');
        }

    }