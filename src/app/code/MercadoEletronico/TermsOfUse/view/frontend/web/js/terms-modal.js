/**
 * Copyright © Mercado Eletrônico. All rights reserved.
 *
 * Exibe o modal dos Termos de Uso com base na seção "terms-of-use" do
 * customer-data e registra o aceite via REST (POST .../mine/accept).
 */
define([
    'jquery',
    'Magento_Customer/js/customer-data',
    'Magento_Ui/js/modal/alert',
    'mage/translate',
    'Magento_Ui/js/modal/modal'
], function ($, customerData, alert, $t) {
    'use strict';

    return function (config) {
        var section = customerData.get(config.sectionName),
            $content = $('<div class="terms-of-use-modal__content"></div>'),
            initialized = false,
            isOpen = false,
            submitting = false;

        function close() {
            if (isOpen) {
                $content.modal('closeModal');
                isOpen = false;
            }
        }

        function accept(event) {
            var $button = $(event.currentTarget);

            if (submitting) {
                return;
            }

            submitting = true;
            $button.prop('disabled', true);

            $.ajax({
                url: config.acceptUrl,
                type: 'POST',
                data: JSON.stringify({}),
                contentType: 'application/json',
                dataType: 'json',
                global: false
            }).done(function () {
                close();
                customerData.reload([config.sectionName], true);
            }).fail(function () {
                alert({
                    content: $t('We could not register your acceptance. Please try again.')
                });
            }).always(function () {
                submitting = false;
                $button.prop('disabled', false);
            });
        }

        function init(data) {
            $content.modal({
                type: 'popup',
                title: data.title,
                modalClass: 'terms-of-use-modal',
                responsive: true,
                clickableOverlay: false,
                // Aceite obrigatório: não fecha com ESC nem pelo "X" (oculto via CSS).
                keyEventHandlers: {
                    escapeKey: function () {}
                },
                buttons: [{
                    text: data.button_label,
                    class: 'action primary terms-of-use-modal__accept',
                    click: accept
                }]
            });
            initialized = true;
        }

        function render(data) {
            if (!data || !data.show) {
                close();

                return;
            }

            $content.html(data.content);

            if (!initialized) {
                init(data);
            } else {
                $content.modal('setTitle', data.title);
            }

            if (!isOpen) {
                $content.modal('openModal');
                isOpen = true;
            }
        }

        section.subscribe(render);
        render(section());
    };
});
