<div class="jumbotron mt-4 px-4 py-3 mb-0 shadow">
    <h4 class="mt-3">Confirm Result</h4>
    <p class="mb-0">Here's how your <span class="text-primary">custom operation</span> will look and work:</p>
    <div class="d-md-flex align-items-center">
        <div class="my-3 my-md-2" id="illustration"></div>
        <ol id="operation-steps"></ol>
    </div>
</div>

@push('after_scripts')
    <script>
        const TYPE_GLOBAL_OPERATION = '{{ \Backpack\DevTools\Generators\OperationGenerator::TYPE_GLOBAL }}';
        const TYPE_LINE_OPERATION = '{{ \Backpack\DevTools\Generators\OperationGenerator::TYPE_LINE }}';
        const TYPE_BULK_OPERATION = '{{ \Backpack\DevTools\Generators\OperationGenerator::TYPE_BULK }}';
        const ACTION_MAKE_GET_REQUEST_TO_BACKPACK_FORM = '{{ \Backpack\DevTools\Generators\OperationGenerator::ACTION_MAKE_GET_REQUEST_TO_BACKPACK_FORM }}';
        const ACTION_MAKE_GET_REQUEST_TO_CUSTOM_VIEW = '{{ \Backpack\DevTools\Generators\OperationGenerator::ACTION_MAKE_GET_REQUEST_TO_CUSTOM_VIEW }}';
        const ACTION_CONFIRM_AND_MAKE_AJAX_CALL = '{{ \Backpack\DevTools\Generators\OperationGenerator::ACTION_CONFIRM_AND_MAKE_AJAX_CALL }}';
        const ACTION_MAKE_AJAX_CALL = '{{ \Backpack\DevTools\Generators\OperationGenerator::ACTION_MAKE_AJAX_CALL }}';
        const ACTION_OPEN_MODAL = '{{ \Backpack\DevTools\Generators\OperationGenerator::ACTION_OPEN_MODAL }}';

        const ICON_CHECK = '<i class="la la-check text-success"></i>';
        const operationSteps = $('#operation-steps');
        const typeField = crud.field('type');
        const buttonActionField = crud.field('button_action');
        const buttonLabelField = crud.field('button_label');
        const confirmationMessageField = crud.field('confirmation_message');
        const nameField = crud.field('name');

        typeField.onChange(function (field) {
            displayTypeSteps(field.value);
        }).change();

        buttonActionField.onChange(function (field) {
            displayActionSteps(field.value);
        }).change();

        buttonLabelField.onChange(function () {
            displayTypeSteps(typeField.value);
        }).change();

        confirmationMessageField.onChange(function () {
            displayTypeSteps(typeField.value);
        }).change();

        nameField.onChange(function () {
            displayTypeSteps(typeField.value);
        }).change();

        /**
         *
         * @param {String} operationType
         */
        function displayTypeSteps(operationType) {
            operationSteps.html('');
            switch (operationType) {
                case TYPE_GLOBAL_OPERATION:
                    operationSteps
                        .append('<li>A <code>GET</code> <i>route</i> and a <i>method</i> to handle the custom view ' + ICON_CHECK + '</li>')
                        .append('<li>A <code>POST</code> <i>route</i> and a <i>method</i> to handle the form submission ' + ICON_CHECK + '</li>');
                    break;
                case TYPE_LINE_OPERATION:
                    operationSteps
                        .append('<li>A <code>GET</code> <i>route</i> and a <i>method</i> to handle the custom view ' + ICON_CHECK + '</li>')
                        .append('<li>A <code>POST</code> <i>route</i> and a <i>method</i> to handle the form submission ' + ICON_CHECK + '</li>');
                    break;
                case TYPE_BULK_OPERATION:
                    operationSteps
                        .append('<li>A <code>POST</code> <i>route</i> and a <i>method</i> to handle the form submission ' + ICON_CHECK + '</li>');
                    break;
                default:
                    break;
            }
            addOrRemoveActionMakeGetRequestOption(operationType);
            displayActionSteps(buttonActionField.value);
        }

        /**
         *
         * @param {String} buttonAction
         */
        function displayActionSteps(buttonAction) {
            const buttonLabel = buttonLabelField.value ? 'with a label <code>' + buttonLabelField.value + '</code>' : '';
            const operationName = nameField.value ? nameField.value : 'operationName';

            confirmationMessageField.show(buttonAction === ACTION_CONFIRM_AND_MAKE_AJAX_CALL);
            operationSteps.find('.action-related').remove();

            switch (buttonAction) {
                case ACTION_MAKE_GET_REQUEST_TO_BACKPACK_FORM:
                    operationSteps
                        .append('<li class="action-related">A custom view with a Backpack form in <code>/views/vendor/backpack/crud/operations/' + operationName + '.blade.php</code> ' + ICON_CHECK + '</li>')
                        .append('<li class="action-related button-description">A button ' + buttonLabel + ' in <code>/views/vendor/backpack/crud/buttons/' + operationName + '.blade.php</code> ' + ICON_CHECK + '</li>');
                    break;
                case ACTION_MAKE_GET_REQUEST_TO_CUSTOM_VIEW:
                    operationSteps
                        .append('<li class="action-related">An empty custom view in <code>/views/vendor/backpack/crud/operations/' + operationName + '.blade.php</code> ' + ICON_CHECK + '</li>')
                        .append('<li class="action-related button-description">A button ' + buttonLabel + ' in <code>/views/vendor/backpack/crud/buttons/' + operationName + '.blade.php</code> ' + ICON_CHECK + '</li>');
                    break;
                case ACTION_CONFIRM_AND_MAKE_AJAX_CALL:
                    const confirmationMessageText = confirmationMessageField.value ? '"' + confirmationMessageField.value + '"' : '';
                    operationSteps
                        .append('<li class="action-related">A button ' + buttonLabel + ' in <code>/views/vendor/backpack/crud/buttons/' + operationName + '.blade.php</code> ' + ICON_CHECK +
                            '</li>' +
                            '<ul>' +
                            '<li>This button includes a warning message <code>swal(' + confirmationMessageText + ')</code> which is shown before the operation is performed</li>' +
                            '<li>If confirmed, an ajax call is executed to the <code>POST</code> <i>route</i> previously created</li>' +
                            '</ul>' +
                            '</li>');
                    break;
                case ACTION_MAKE_AJAX_CALL:
                    operationSteps
                        .append('<li class="action-related button-description">A button ' + buttonLabel + ' in <code>/views/vendor/backpack/crud/buttons/' + operationName + '.blade.php</code> ' + ICON_CHECK +
                            '<ul>' +
                            '<li>This button triggers an ajax call to the <code>POST</code> <i>route</i> previously created (without any warning)</li>' +
                            '</ul>' +
                            '</li>')
                        .append('</ul>');
                    break;
                default:
                    operationSteps
                        .append('<li class="action-related">...</li>');
                    break;
            }

            const illustrationValue = typeField.value && buttonAction ? '<img src="https://backpackforlaravel.com/uploads/devtools/operations/'+typeField.value+'.png" alt="" class="img-fluid" style="max-width: 300px;"></li>' : '';
            $('#illustration').html(illustrationValue);
        }

        /**
         *
         * @param {String} typeValue
         */
        function addOrRemoveActionMakeGetRequestOption(typeValue) {
            const select = $(buttonActionField.input);

            if (typeValue === TYPE_BULK_OPERATION) {
                if (select.val() === ACTION_MAKE_GET_REQUEST_TO_BACKPACK_FORM || select.val() === ACTION_MAKE_GET_REQUEST_TO_CUSTOM_VIEW) {
                    $(select.val(null).trigger('change'));
                }
                select.find('option').each(function () {
                    if ($(this).val() === ACTION_MAKE_GET_REQUEST_TO_BACKPACK_FORM || $(this).val() === ACTION_MAKE_GET_REQUEST_TO_CUSTOM_VIEW) {
                        $(this).remove();
                    }
                });

                return;
            }

            if (select.find("option[value='" + ACTION_MAKE_GET_REQUEST_TO_BACKPACK_FORM + "']").length === 0) {
                const newOption = new Option('Redirect to a GET route showing a custom Backpack form', ACTION_MAKE_GET_REQUEST_TO_BACKPACK_FORM, false, false);
                select.prepend(newOption);
            }
            if (select.find("option[value='" + ACTION_MAKE_GET_REQUEST_TO_CUSTOM_VIEW + "']").length === 0) {
                const newOption = new Option('Redirect to a GET route showing a custom view', ACTION_MAKE_GET_REQUEST_TO_CUSTOM_VIEW, false, false);
                select.prepend(newOption);
            }
        }
    </script>
@endpush